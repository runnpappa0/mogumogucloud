<?php
session_start();
require_once '../db.php';

header('Content-Type: application/json');

// 管理者チェック
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== '管理者') {
    echo json_encode(['success' => false, 'message' => '認証されていません。']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);

    // 必須項目チェック
    if (empty($input['username']) || empty($input['password']) || empty($input['name'])) {
        echo json_encode(['success' => false, 'message' => '必須項目が不足しています。']);
        exit;
    }

    $db = getDbConnection();

    // usersテーブルに追加
    $userQuery = "INSERT INTO users (username, password, name, role) VALUES (:username, :password, :name, :role)";
    $userStmt = $db->prepare($userQuery);
    $userStmt->execute([
        ':username' => $input['username'],
        ':password' => $input['password'], // 平文保存（ハッシュ化は次のタスク）
        ':name' => $input['name'],
        ':role' => $input['role']
    ]);

    $userId = $db->lastInsertId();

    // contract_detailsテーブルに契約情報を追加（条件付き）
    if (!empty($input['weekdays']) && !empty($input['bento_type'])) {
        $detailsQuery = "
            INSERT INTO contract_details (user_id, weekdays, bento_type, rice_amount, notes)
            VALUES (:user_id, :weekdays, :bento_type, :rice_amount, :notes)
        ";
        $detailsStmt = $db->prepare($detailsQuery);
        $detailsStmt->execute([
            ':user_id' => $userId,
            ':weekdays' => implode(',', $input['weekdays']),
            ':bento_type' => $input['bento_type'],
            ':rice_amount' => $input['rice_amount'] ?? null,
            ':notes' => $input['notes'] ?? null
        ]);
    }

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'エラーが発生しました: ' . $e->getMessage()]);
}
