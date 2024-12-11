<?php
session_start();
require_once '../db.php';

header('Content-Type: application/json');

// 認証チェック
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] === '未認証') {
    echo json_encode(['success' => false, 'message' => '認証されていません。']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    $db = getDbConnection();

    if ($method === 'POST' && $action === 'process_changes') {
        $input = json_decode(file_get_contents('php://input'), true);

        if (empty($input['changes']) || !is_array($input['changes'])) {
            echo json_encode(['success' => false, 'message' => '変更データが不足しています。']);
            exit;
        }

        foreach ($input['changes'] as $change) {
            $action = $change['action'] ?? '';
            $orderId = $change['order_id'] ?? null;
            $userId = $change['user_id'] ?? null;
            $bentoType = $change['bento_type'] ?? null;
            $riceAmount = $change['rice_amount'] ?? null;
            $deliveryPlace = $change['delivery_place'] ?? null;

            // 管理者は制限なし、利用者/スタッフは変更制限を確認
            if ($_SESSION['user_role'] !== '管理者') {
                $stmt = $db->prepare("SELECT daily_change_count, last_change_date FROM users WHERE id = :id");
                $stmt->execute([':id' => $_SESSION['user_id']]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user) {
                    if ($user['last_change_date'] !== date('Y-m-d')) {
                        // 日付が変わった場合、回数をリセット
                        $stmt = $db->prepare("UPDATE users SET daily_change_count = 0, last_change_date = CURDATE() WHERE id = :id");
                        $stmt->execute([':id' => $_SESSION['user_id']]);
                    } elseif ($user['daily_change_count'] >= 2) {
                        echo json_encode(['success' => false, 'message' => '1日の変更回数制限を超えています。']);
                        exit;
                    }
                }
            }

            // アクション処理
            switch ($action) {
                case 'add':
                    if (!$userId || !$bentoType || !$deliveryPlace) {
                        echo json_encode(['success' => false, 'message' => '必須データが不足しています。']);
                        exit;
                    }
                    $stmt = $db->prepare("
                        INSERT INTO bento_orders (user_id, bento_type, rice_amount, delivery_place, order_date)
                        VALUES (:user_id, :bento_type, :rice_amount, :delivery_place, CURDATE())
                    ");
                    $stmt->execute([
                        ':user_id' => $userId,
                        ':bento_type' => $bentoType,
                        ':rice_amount' => $riceAmount,
                        ':delivery_place' => $deliveryPlace
                    ]);
                    $changeDetail = "新たに注文を追加";
                    break;

                case 'update':
                    if (!$orderId) {
                        echo json_encode(['success' => false, 'message' => '注文IDが不足しています。']);
                        exit;
                    }
                    $stmt = $db->prepare("
                        UPDATE bento_orders
                        SET bento_type = :bento_type,
                            rice_amount = :rice_amount,
                            delivery_place = :delivery_place,
                            updated_at = NOW()
                        WHERE id = :order_id
                    ");
                    $stmt->execute([
                        ':bento_type' => $bentoType,
                        ':rice_amount' => $riceAmount,
                        ':delivery_place' => $deliveryPlace,
                        ':order_id' => $orderId
                    ]);
                    $changeDetail = "注文を更新";
                    break;

                case 'delete':
                    if (!$orderId) {
                        echo json_encode(['success' => false, 'message' => '注文IDが不足しています。']);
                        exit;
                    }
                    $stmt = $db->prepare("DELETE FROM bento_orders WHERE id = :order_id");
                    $stmt->execute([':order_id' => $orderId]);
                    $changeDetail = "注文をキャンセル";
                    break;

                default:
                    echo json_encode(['success' => false, 'message' => '無効なアクションです。']);
                    exit;
            }

            // 変更履歴を保存
            $stmt = $db->prepare("
                INSERT INTO order_change_logs (changer_id, changer_role, order_id, action, change_detail)
                VALUES (:changer_id, :changer_role, :order_id, :action, :change_detail)
            ");
            $stmt->execute([
                ':changer_id' => $_SESSION['user_id'],
                ':changer_role' => $_SESSION['user_role'],
                ':order_id' => $orderId,
                ':action' => $action,
                ':change_detail' => $changeDetail
            ]);

            // 利用者/スタッフの場合、変更回数を増加
            if ($_SESSION['user_role'] !== '管理者') {
                $stmt = $db->prepare("UPDATE users SET daily_change_count = daily_change_count + 1 WHERE id = :id");
                $stmt->execute([':id' => $_SESSION['user_id']]);
            }
        }

        echo json_encode(['success' => true, 'message' => '変更を処理しました。']);
    } elseif ($method === 'GET' && $action === 'fetch_order_changes') {
        $stmt = $db->prepare("
            SELECT 
                ocl.changer_id,
                ocl.changer_role,
                ocl.action,
                ocl.change_detail,
                ocl.change_time
            FROM order_change_logs AS ocl
            WHERE DATE(ocl.change_time) = CURDATE()
            ORDER BY ocl.change_time ASC
        ");
        $stmt->execute();
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'changes' => $logs]);
    } else {
        echo json_encode(['success' => false, 'message' => '不正なリクエストです。']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'エラーが発生しました: ' . $e->getMessage()]);
}
