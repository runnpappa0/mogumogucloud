<?php
session_start();
require_once '../db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => '認証されていません。']);
    exit;
}

$user_id = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

try {
    $db = getDbConnection();

    if ($method === 'GET') {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = 10;
        $offset = ($page - 1) * $limit;

        // ロス詳細を取得
        $query = "
            SELECT
                bo.order_date,
                lr.loss_reason,
                lr.additional_notes,
                CONCAT(bo.bento_type, '（', IFNULL(bo.rice_amount, 'なし'), '）') AS bento_detail
            FROM loss_records lr
            INNER JOIN bento_orders bo ON lr.order_id = bo.id
            WHERE bo.user_id = :user_id
            ORDER BY bo.order_date DESC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $db->prepare($query);
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $lossDetails = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // ロスサマリーを取得
        $summaryQuery = "
            SELECT 
                COUNT(*) AS total_loss_count,
                SUM(pl.price) AS total_loss_cost,
                COUNT(*) * 200 AS self_cost
            FROM loss_records lr
            INNER JOIN bento_orders bo ON lr.order_id = bo.id
            INNER JOIN price_list pl ON pl.bento_type = bo.bento_type AND (pl.rice_amount = bo.rice_amount OR pl.rice_amount IS NULL)
            WHERE bo.user_id = :user_id
        ";

        $summaryStmt = $db->prepare($summaryQuery);
        $summaryStmt->execute([':user_id' => $user_id]);
        $summary = $summaryStmt->fetch(PDO::FETCH_ASSOC);

        // 総ページ数を計算
        $countQuery = "
            SELECT COUNT(*) AS total_count
            FROM loss_records lr
            INNER JOIN bento_orders bo ON lr.order_id = bo.id
            WHERE bo.user_id = :user_id
        ";
        $countStmt = $db->prepare($countQuery);
        $countStmt->execute([':user_id' => $user_id]);
        $totalCount = $countStmt->fetchColumn();
        $totalPages = ceil($totalCount / $limit);

        echo json_encode([
            'success' => true,
            'details' => $lossDetails,
            'summary' => $summary,
            'totalPages' => $totalPages
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => '不正なリクエストです。']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'エラーが発生しました: ' . $e->getMessage()]);
}
