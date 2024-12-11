<?php
session_start();
require_once '../db.php';

header('Content-Type: application/json');

// セッションにユーザーIDがない場合は認証エラー
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => '認証されていません。']);
    exit;
}

$user_id = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

try {
    $db = getDbConnection();

    // 履歴記録用関数
    function logOrderChange($db, $orderId, $changerId, $changerRole, $userId, $action, $details) {
        $stmt = $db->prepare("
            INSERT INTO order_change_logs (order_id, changer_id, changer_role, user_id, action, change_detail, change_time)
            VALUES (:order_id, :changer_id, :changer_role, :user_id, :action, :change_detail, NOW())
        ");
        $stmt->execute([
            ':order_id' => $orderId,
            ':changer_id' => $changerId,
            ':changer_role' => $changerRole,
            ':user_id' => $userId,
            ':action' => $action,
            ':change_detail' => json_encode($details, JSON_UNESCAPED_UNICODE),
        ]);
    }

    if ($method === 'GET') {
        // 今日の注文を取得
        $today = date('Y-m-d');
        $query = 'SELECT * FROM bento_orders WHERE user_id = :user_id AND order_date = :order_date';
        $stmt = $db->prepare($query);
        $stmt->execute([':user_id' => $user_id, ':order_date' => $today]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($order) {
            echo json_encode(['success' => true, 'order' => $order]);
        } else {
            echo json_encode(['success' => false, 'message' => '本日注文がありません。']);
        }
    } elseif ($method === 'POST') {
        // JSONデータの受け取りと検証
        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input || empty($input['bento_type']) || empty($input['rice_amount']) || empty($input['delivery_place'])) {
            echo json_encode(['success' => false, 'message' => '入力データが不正です。']);
            exit;
        }

        $bento_type = $input['bento_type'];
        $rice_amount = $input['rice_amount'];
        $delivery_place = $input['delivery_place'];
        $today = date('Y-m-d');

        // 本日の注文が存在するか確認
        $query = 'SELECT * FROM bento_orders WHERE user_id = :user_id AND order_date = :order_date';
        $stmt = $db->prepare($query);
        $stmt->execute([':user_id' => $user_id, ':order_date' => $today]);
        $existingOrder = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existingOrder) {
            // 更新
            $query = 'UPDATE bento_orders 
                          SET bento_type = :bento_type, rice_amount = :rice_amount, delivery_place = :delivery_place, updated_at = NOW()
                          WHERE id = :id';
            $stmt = $db->prepare($query);
            $stmt->execute([
                ':bento_type' => $bento_type,
                ':rice_amount' => $rice_amount,
                ':delivery_place' => $delivery_place,
                ':id' => $existingOrder['id'],
            ]);

            logOrderChange($db, $existingOrder['id'], $user_id, '利用者', $user_id, '更新', [
                'bento_type' => ['before' => $existingOrder['bento_type'], 'after' => $bento_type],
                'rice_amount' => ['before' => $existingOrder['rice_amount'], 'after' => $rice_amount],
                'delivery_place' => ['before' => $existingOrder['delivery_place'], 'after' => $delivery_place],
            ]);
            echo json_encode(['success' => true, 'message' => '注文を更新しました。']);
        } else {
            // 新規追加
            $query = 'INSERT INTO bento_orders (user_id, order_date, bento_type, rice_amount, delivery_place, created_at, updated_at)
                          VALUES (:user_id, :order_date, :bento_type, :rice_amount, :delivery_place, NOW(), NOW())';
            $stmt = $db->prepare($query);
            $stmt->execute([
                ':user_id' => $user_id,
                ':order_date' => $today,
                ':bento_type' => $bento_type,
                ':rice_amount' => $rice_amount,
                ':delivery_place' => $delivery_place,
            ]);

            $newOrderId = $db->lastInsertId();
            logOrderChange($db, $newOrderId, $user_id, '利用者', $user_id, '新規追加', [
                'bento_type' => $bento_type,
                'rice_amount' => $rice_amount,
                'delivery_place' => $delivery_place,
            ]);
            echo json_encode(['success' => true, 'message' => '注文を追加しました。']);
        }
    } elseif ($method === 'DELETE') {
        // 注文をキャンセル
        $today = date('Y-m-d');
        $query = 'SELECT * FROM bento_orders WHERE user_id = :user_id AND order_date = :order_date';
        $stmt = $db->prepare($query);
        $stmt->execute([':user_id' => $user_id, ':order_date' => $today]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($order) {
            logOrderChange($db, $order['id'], $user_id, '利用者', $user_id, 'キャンセル', [
                'bento_type' => $order['bento_type'],
                'rice_amount' => $order['rice_amount'],
                'delivery_place' => $order['delivery_place'],
            ]);

            $query = 'DELETE FROM bento_orders WHERE id = :id';
            $stmt = $db->prepare($query);
            $stmt->execute([':id' => $order['id']]);
            echo json_encode(['success' => true, 'message' => '注文をキャンセルしました。']);
        } else {
            echo json_encode(['success' => false, 'message' => 'キャンセルする注文が見つかりませんでした。']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => '不正なリクエストです。']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'エラーが発生しました: ' . $e->getMessage()]);
}
