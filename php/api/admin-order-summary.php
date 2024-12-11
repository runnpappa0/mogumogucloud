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
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 15; // 1ページあたりの件数
    $offset = ($page - 1) * $limit;

    $dateFilter = isset($_GET['date']) ? $_GET['date'] : null;
    $nameFilter = isset($_GET['name']) ? $_GET['name'] : null;

    // 注文履歴の取得クエリ
    $query = "
    SELECT 
        bo.id,
        u.name,
        bo.bento_type,
        bo.rice_amount,
        bo.delivery_place,
        bo.order_date,
        bo.status,
        lr.loss_reason,
        lr.additional_notes
    FROM 
        bento_orders AS bo
    JOIN 
        users AS u ON bo.user_id = u.id
    LEFT JOIN 
        loss_records AS lr ON bo.id = lr.order_id
    WHERE 1=1
";

    if ($dateFilter) {
      $query .= " AND bo.order_date = :order_date";
    }
    if ($nameFilter) {
      $query .= " AND u.name LIKE :name";
    }

    $query .= " ORDER BY bo.order_date DESC LIMIT :limit OFFSET :offset";
    $stmt = $db->prepare($query);

    if ($dateFilter) {
      $stmt->bindValue(':order_date', $dateFilter, PDO::PARAM_STR);
    }
    if ($nameFilter) {
      $stmt->bindValue(':name', '%' . $nameFilter . '%', PDO::PARAM_STR);
    }

    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 全件数を取得
    $countQuery = "
            SELECT COUNT(*) AS total_count 
            FROM bento_orders AS bo
            JOIN users AS u ON bo.user_id = u.id
            WHERE 1=1
        ";

    if ($dateFilter) {
      $countQuery .= " AND bo.order_date = :order_date";
    }
    if ($nameFilter) {
      $countQuery .= " AND u.name LIKE :name";
    }

    $countStmt = $db->prepare($countQuery);

    if ($dateFilter) {
      $countStmt->bindValue(':order_date', $dateFilter, PDO::PARAM_STR);
    }
    if ($nameFilter) {
      $countStmt->bindValue(':name', '%' . $nameFilter . '%', PDO::PARAM_STR);
    }

    $countStmt->execute();
    $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total_count'];
    $totalPages = ceil($totalCount / $limit);

    // 注文金額の集計
    $previousMonthStart = date('Y-m-01', strtotime('-1 month'));
    $previousMonthEnd = date('Y-m-t', strtotime('-1 month'));

    $amountQuery = "
      SELECT 
          bo.bento_type,
          bo.rice_amount,
          COUNT(*) AS count,
          p.price,
          (COUNT(*) * p.price) AS subtotal
      FROM 
          bento_orders AS bo
      JOIN 
          price_list AS p
      ON 
          bo.bento_type = p.bento_type AND bo.rice_amount = p.rice_amount
      WHERE 
          bo.order_date BETWEEN :start_date AND :end_date
          AND bo.bento_type != '冷凍' -- 冷凍を除外
      GROUP BY 
          bo.bento_type, bo.rice_amount, p.price
    ";

    $amountStmt = $db->prepare($amountQuery);
    $amountStmt->execute([
      ':start_date' => $previousMonthStart,
      ':end_date' => $previousMonthEnd
    ]);

    $amounts = $amountStmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
      'success' => true,
      'orders' => $orders,
      'amounts' => $amounts,
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
