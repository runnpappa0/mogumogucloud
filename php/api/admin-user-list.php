<?php
session_start();
require_once '../db.php';

header('Content-Type: application/json');

// 管理者チェック
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== '管理者') {
    echo json_encode(['success' => false, 'message' => '認証されていません。']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    $db = getDbConnection();

    if ($method === 'GET') {
        if (isset($_GET['id'])) {
            $userId = (int) $_GET['id'];
            $query = "
                SELECT 
                    u.id, u.username, u.password, u.name, u.role,
                    u.default_delivery_place, u.can_change_delivery,
                    cd.weekdays, cd.bento_type, cd.rice_amount, cd.notes AS contract_notes
                FROM users AS u
                LEFT JOIN contract_details AS cd ON u.id = cd.user_id
                WHERE u.id = :id
            ";
            $stmt = $db->prepare($query);
            $stmt->execute([':id' => $userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                echo json_encode(['success' => true, 'user' => $user]);
            } else {
                echo json_encode(['success' => false, 'message' => 'ユーザーが見つかりません。']);
            }
        } else {
            $query = "
                SELECT 
                    u.id, u.username, u.name, u.role,
                    u.default_delivery_place, u.can_change_delivery,
                    cd.weekdays, cd.bento_type, cd.rice_amount, cd.notes AS contract_notes
                FROM users AS u
                LEFT JOIN contract_details AS cd ON u.id = cd.user_id
                WHERE u.role != '管理者'
                ORDER BY 
                    CASE u.role 
                        WHEN '利用者' THEN 1
                        WHEN 'スタッフ' THEN 2
                        ELSE 3
                    END,
                    CASE u.default_delivery_place
                        WHEN '施設内' THEN 1
                        WHEN '施設外' THEN 2
                        ELSE 3
                    END,
                    u.name
            ";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['success' => true, 'users' => $users]);
        }
    } elseif ($method === 'POST') {
        // ユーザー情報更新
        $input = json_decode(file_get_contents('php://input'), true);

        if (!isset($input['id'])) {
            echo json_encode(['success' => false, 'message' => 'ユーザーIDが必要です。']);
            exit;
        }

        // パスワードのハッシュ化対応
        $password = $input['password'] ?? null;
        if ($password === null || $password === '') {
            // パスワード未入力時は既存のハッシュ値を維持
            $passwordQuery = "SELECT password FROM users WHERE id = :id";
            $passwordStmt = $db->prepare($passwordQuery);
            $passwordStmt->execute([':id' => $input['id']]);
            $password = $passwordStmt->fetchColumn();
        } else {
            // 新しいパスワードが入力された場合はハッシュ化
            $password = password_hash($password, PASSWORD_DEFAULT);
        }

        // usersテーブルの更新
        $userQuery = "
        UPDATE users 
        SET name = :name,
            password = :password,
            role = :role,
            default_delivery_place = :default_delivery_place,
            can_change_delivery = :can_change_delivery
        WHERE id = :id
    ";
        $userStmt = $db->prepare($userQuery);
        $userStmt->execute([
            ':name' => $input['name'],
            ':password' => $password,
            ':role' => $input['role'],
            ':default_delivery_place' => $input['default_delivery_place'],
            ':can_change_delivery' => $input['can_change_delivery'],
            ':id' => $input['id']
        ]);

        // contract_detailsテーブルの更新または挿入
        $detailsQuery = "
            INSERT INTO contract_details (user_id, weekdays, bento_type, rice_amount, notes)
            VALUES (:user_id, :weekdays, :bento_type, :rice_amount, :notes)
            ON DUPLICATE KEY UPDATE
                weekdays = VALUES(weekdays),
                bento_type = VALUES(bento_type),
                rice_amount = VALUES(rice_amount),
                notes = VALUES(notes)
        ";
        $detailsStmt = $db->prepare($detailsQuery);
        $detailsStmt->execute([
            ':user_id' => $input['id'],
            ':weekdays' => isset($input['weekdays']) ? implode(',', $input['weekdays']) : null,
            ':bento_type' => $input['bento_type'] ?? 'Aランチ',
            ':rice_amount' => ($input['bento_type'] === '冷凍') ? null : ($input['rice_amount'] ?? null),
            ':notes' => $input['notes'] ?? null
        ]);

        echo json_encode(['success' => true, 'message' => 'ユーザー情報を更新しました。']);
    } elseif ($method === 'DELETE') {
        // ユーザー削除
        if (!isset($_GET['id'])) {
            echo json_encode(['success' => false, 'message' => 'ユーザーIDが必要です。']);
            exit;
        }

        $userId = (int) $_GET['id'];

        // usersテーブルとcontract_detailsテーブルの関連データを削除
        $deleteUserQuery = "DELETE FROM users WHERE id = :id";
        $deleteUserStmt = $db->prepare($deleteUserQuery);
        $deleteUserStmt->execute([':id' => $userId]);

        $deleteDetailsQuery = "DELETE FROM contract_details WHERE user_id = :user_id";
        $deleteDetailsStmt = $db->prepare($deleteDetailsQuery);
        $deleteDetailsStmt->execute([':user_id' => $userId]);

        echo json_encode(['success' => true, 'message' => 'ユーザーを削除しました。']);
    } else {
        echo json_encode(['success' => false, 'message' => '不正なリクエストです。']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'エラーが発生しました: ' . $e->getMessage()]);
}
