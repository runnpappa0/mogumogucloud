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
        $limit = 10; // 1ページあたりの件数
        $offset = ($page - 1) * $limit;

        $dateFilter = isset($_GET['date']) ? $_GET['date'] : null;

        // 現在の月と先月を計算
        $currentMonth = date('Y-m');
        $previousMonthStart = date('Y-m-01', strtotime('-1 month'));
        $previousMonthEnd = date('Y-m-t', strtotime('-1 month'));

        // 注文履歴の取得クエリ
        $query = "SELECT order_date, bento_type, rice_amount, delivery_place, status 
                  FROM bento_orders 
                  WHERE user_id = :user_id";

        if ($dateFilter) {
            $query .= " AND order_date = :order_date";
        }

        $query .= " ORDER BY order_date DESC LIMIT :limit OFFSET :offset";
        $stmt = $db->prepare($query);

        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        if ($dateFilter) {
            $stmt->bindValue(':order_date', $dateFilter, PDO::PARAM_STR);
        }

        $stmt->execute();
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 全件数を取得
        $countQuery = "SELECT COUNT(*) AS total_count 
                       FROM bento_orders 
                       WHERE user_id = :user_id";
        if ($dateFilter) {
            $countQuery .= " AND order_date = :order_date";
        }

        $countStmt = $db->prepare($countQuery);
        $countStmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);

        if ($dateFilter) {
            $countStmt->bindValue(':order_date', $dateFilter, PDO::PARAM_STR);
        }

        $countStmt->execute();
        $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total_count'];
        $totalPages = ceil($totalCount / $limit);

        // 先月の集計情報
        $summaryQuery = "SELECT COUNT(*) AS order_count, COUNT(*) * 200 AS total_cost 
                         FROM bento_orders 
                         WHERE user_id = :user_id 
                         AND order_date BETWEEN :start_date AND :end_date";
        $summaryStmt = $db->prepare($summaryQuery);
        $summaryStmt->execute([
            ':user_id' => $user_id,
            ':start_date' => $previousMonthStart,
            ':end_date' => $previousMonthEnd
        ]);

        $summary = $summaryStmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'orders' => $orders,
            'summary' => $summary,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => '不正なリクエストです。']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'エラーが発生しました: ' . $e->getMessage()]);
}
