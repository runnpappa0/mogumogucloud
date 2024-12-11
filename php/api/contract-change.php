<?php
session_start();
require_once '../db.php';

header('Content-Type: application/json');

// セッション確認
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => '認証されていません。']);
    exit;
}

$user_id = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

try {
    $db = getDbConnection();

    if ($method === 'GET') {
        // 履歴を取得
        $query = "SELECT * FROM contract_change_requests WHERE user_id = :user_id ORDER BY created_at DESC";
        $stmt = $db->prepare($query);
        $stmt->execute([':user_id' => $user_id]);
        $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'requests' => $requests]);
    } elseif ($method === 'POST') {
        // リクエストデータを取得
        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input) {
            echo json_encode(['success' => false, 'message' => '入力データが不正です。']);
            exit;
        }

        $weekdays = isset($input['weekdays']) ? implode(',', $input['weekdays']) : null;
        $rice_amount = $input['rice_amount'] ?? null;
        $remarks = $input['remarks'] ?? null;

        // 既存の承認待ち申請があるか確認
        $query = "SELECT * FROM contract_change_requests WHERE user_id = :user_id AND status = '承認待ち'";
        $stmt = $db->prepare($query);
        $stmt->execute([':user_id' => $user_id]);
        $existingRequest = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existingRequest) {
            // 承認待ち申請がある場合、上書き保存
            $query = "UPDATE contract_change_requests 
                      SET weekdays = :weekdays, rice_amount = :rice_amount, notes = :notes, updated_at = NOW()
                      WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->execute([
                ':weekdays' => $weekdays,
                ':rice_amount' => $rice_amount,
                ':notes' => $remarks,
                ':id' => $existingRequest['id']
            ]);
        } else {
            // 新規申請を追加
            $query = "INSERT INTO contract_change_requests (user_id, weekdays, rice_amount, notes, status, created_at, updated_at)
                      VALUES (:user_id, :weekdays, :rice_amount, :notes, '承認待ち', NOW(), NOW())";
            $stmt = $db->prepare($query);
            $stmt->execute([
                ':user_id' => $user_id,
                ':weekdays' => $weekdays,
                ':rice_amount' => $rice_amount,
                ':notes' => $remarks
            ]);
        }

        echo json_encode(['success' => true, 'message' => '申請が送信されました。']);
    } else {
        echo json_encode(['success' => false, 'message' => '不正なリクエストです。']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'エラーが発生しました: ' . $e->getMessage()]);
}
