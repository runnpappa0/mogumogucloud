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

  if ($method === 'POST') {
    // JSONリクエストの読み込み
    $input = json_decode(file_get_contents('php://input'), true);

    // バリデーション: 必須フィールドが存在するか確認
    if (empty($input['orders']) || !is_array($input['orders'])) {
      echo json_encode(['success' => false, 'message' => '注文データが不足しています。']);
      exit;
    }

    foreach ($input['orders'] as $order) {
      if (empty($order['order_id']) || empty($order['status'])) {
        echo json_encode(['success' => false, 'message' => '注文IDまたは状態が不足しています。']);
        exit;
      }

      $orderId = (int)$order['order_id'];
      $status = $order['status'];

      if ($status === 'ロス') {
        // ロスの場合のみ理由と備考をチェック
        if (empty($order['reason'])) {
          echo json_encode(['success' => false, 'message' => 'ロス理由が不足しています。']);
          exit;
        }

        $reason = $order['reason'];
        $additionalNotes = $order['additional_notes'] ?? null;

        if ($reason === 'その他' && empty($additionalNotes)) {
          echo json_encode(['success' => false, 'message' => '「その他」の場合、追加メモが必要です。']);
          exit;
        }

        // ロス理由を保存
        $lossReasonQuery = "
                INSERT INTO loss_records (order_id, loss_reason, additional_notes)
                VALUES (:order_id, :loss_reason, :additional_notes)
                ON DUPLICATE KEY UPDATE
                    loss_reason = VALUES(loss_reason),
                    additional_notes = VALUES(additional_notes)
            ";
        $stmt = $db->prepare($lossReasonQuery);
        $stmt->execute([
          ':order_id' => $orderId,
          ':loss_reason' => $reason,
          ':additional_notes' => $additionalNotes
        ]);
      } elseif ($status === '消費済み') {
        // 状態が「消費済み」に変更された場合、loss_records を削除
        $deleteLossRecordQuery = "DELETE FROM loss_records WHERE order_id = :order_id";
        $stmt = $db->prepare($deleteLossRecordQuery);
        $stmt->execute([':order_id' => $orderId]);
      }

      // 注文状態を更新（全ステータス対象）
      $updateStatusQuery = "UPDATE bento_orders SET status = :status WHERE id = :order_id";
      $stmt = $db->prepare($updateStatusQuery);
      $stmt->execute([
        ':status' => $status,
        ':order_id' => $orderId
      ]);
    }

    echo json_encode(['success' => true, 'message' => '注文状態を更新しました。']);
  } else {
    echo json_encode(['success' => false, 'message' => '不正なリクエストです。']);
  }
} catch (PDOException $e) {
  echo json_encode(['success' => false, 'message' => 'エラーが発生しました: ' . $e->getMessage()]);
}
