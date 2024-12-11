<?php
session_start();
require_once '../db.php';

header('Content-Type: application/json');

// セッション確認
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => '認証されていません。']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    $db = getDbConnection();

    if ($method === 'GET') {
        // 申請一覧を取得 (usersテーブルと結合して申請者名を取得)
        $query = "
        SELECT 
            ccr.id,
            ccr.user_id,
            ccr.weekdays,
            ccr.rice_amount,
            ccr.notes,
            ccr.status,
            ccr.created_at,
            u.name
        FROM 
            contract_change_requests AS ccr
        JOIN 
            users AS u
        ON 
            ccr.user_id = u.id
        ORDER BY 
            ccr.created_at DESC
        ";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'requests' => $requests]);
    } elseif ($method === 'POST') {
        // リクエストデータを取得
        $input = json_decode(file_get_contents('php://input'), true);

        if (!isset($input['id'])) {
            echo json_encode(['success' => false, 'message' => '申請IDが必要です。']);
            exit;
        }

        $requestId = $input['id'];

        // 該当申請を取得
        $query = "SELECT user_id, weekdays, rice_amount FROM contract_change_requests WHERE id = :id AND status = '承認待ち'";
        $stmt = $db->prepare($query);
        $stmt->execute([':id' => $requestId]);
        $request = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$request) {
            echo json_encode(['success' => false, 'message' => '該当する承認待ちの申請が見つかりません。']);
            exit;
        }

        // `contract_details` に既存データがあるか確認
        $query = "SELECT id FROM contract_details WHERE user_id = :user_id";
        $stmt = $db->prepare($query);
        $stmt->execute([':user_id' => $request['user_id']]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            // 既存データを更新
            $query = "UPDATE contract_details 
                      SET weekdays = :weekdays, rice_amount = :rice_amount, updated_at = NOW()
                      WHERE user_id = :user_id";
            $stmt = $db->prepare($query);
            $stmt->execute([
                ':weekdays' => $request['weekdays'],
                ':rice_amount' => $request['rice_amount'],
                ':user_id' => $request['user_id']
            ]);
        } else {
            // 新しいレコードを挿入
            $query = "INSERT INTO contract_details (user_id, weekdays, rice_amount, updated_at)
                      VALUES (:user_id, :weekdays, :rice_amount, NOW())";
            $stmt = $db->prepare($query);
            $stmt->execute([
                ':user_id' => $request['user_id'],
                ':weekdays' => $request['weekdays'],
                ':rice_amount' => $request['rice_amount']
            ]);
        }

        // 申請ステータスを更新
        $query = "UPDATE contract_change_requests SET status = '承認済み', updated_at = NOW() WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->execute([':id' => $requestId]);

        echo json_encode(['success' => true, 'message' => '申請が承認されました。']);
    } else {
        echo json_encode(['success' => false, 'message' => '不正なリクエストです。']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'エラーが発生しました: ' . $e->getMessage()]);
}
