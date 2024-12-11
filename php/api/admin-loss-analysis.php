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
    $db = getDbConnection();

    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        if (isset($_GET['user_id'])) {
            // 特定の利用者のロス詳細を取得
        $userId = (int) $_GET['user_id'];
            $lossDetailQuery = "
            SELECT 
                bo.order_date,
                CASE 
                    WHEN lr.loss_reason = 'その他' THEN lr.additional_notes
                    ELSE lr.loss_reason
                END AS loss_reason,
                CONCAT(bo.bento_type, '（', bo.rice_amount, '）') AS bento_detail
            FROM bento_orders AS bo
            JOIN loss_records AS lr ON bo.id = lr.order_id
            WHERE bo.status = 'ロス' AND bo.user_id = :user_id
            ORDER BY bo.order_date DESC
        ";
            $lossDetailStmt = $db->prepare($lossDetailQuery);
            $lossDetailStmt->execute([':user_id' => $userId]);
            $lossDetails = $lossDetailStmt->fetchAll(PDO::FETCH_ASSOC);

        // 合計ロス数、自己負担額、ロス額を計算
        $summaryQuery = "
            SELECT 
                COUNT(bo.id) AS total_loss_count,
                COUNT(bo.id) * 200 AS user_burden_amount, -- 修正点
                SUM(p.price) AS total_loss_amount
            FROM bento_orders AS bo
            JOIN loss_records AS lr ON bo.id = lr.order_id
            JOIN price_list AS p ON bo.bento_type = p.bento_type AND bo.rice_amount = p.rice_amount
            WHERE bo.status = 'ロス' AND bo.user_id = :user_id
        ";
            $summaryStmt = $db->prepare($summaryQuery);
            $summaryStmt->execute([':user_id' => $userId]);
            $summary = $summaryStmt->fetch(PDO::FETCH_ASSOC);

            echo json_encode([
            'success' => true,
            'loss_summary' => [
                'total_loss_count' => $summary['total_loss_count'] ?? 0,
                'user_burden_amount' => $summary['user_burden_amount'] ?? 0,
                'total_loss_amount' => $summary['total_loss_amount'] ?? 0,
            ],
            'loss_details' => $lossDetails,
        ]);
        } else {
            // **1. 今月とこれまでのロス金額**
            $currentMonthStart = date('Y-m-01');
            $currentMonthEnd = date('Y-m-t');

            $lossQuery = "
                SELECT 
                    SUM(CASE WHEN bo.order_date BETWEEN :current_month_start AND :current_month_end THEN p.price ELSE 0 END) AS current_month_loss,
                    SUM(p.price) AS total_loss
                FROM bento_orders AS bo
                JOIN price_list AS p
                ON bo.bento_type = p.bento_type AND bo.rice_amount = p.rice_amount
                WHERE bo.status = 'ロス'
            ";
            $lossStmt = $db->prepare($lossQuery);
            $lossStmt->execute([
                ':current_month_start' => $currentMonthStart,
                ':current_month_end' => $currentMonthEnd,
            ]);
            $lossSummary = $lossStmt->fetch(PDO::FETCH_ASSOC);

            $currentMonthLoss = $lossSummary['current_month_loss'] ?? 0;
            $totalLoss = $lossSummary['total_loss'] ?? 0;

            // **2. 月別ロスの推移（最大12か月分、最低今月と先月分）**
            $trendQuery = "
                SELECT 
                    DATE_FORMAT(bo.order_date, '%Y-%m') AS month,
                    COUNT(bo.id) AS loss_count,
                    SUM(p.price) AS loss_amount
                FROM bento_orders AS bo
                JOIN price_list AS p
                ON bo.bento_type = p.bento_type AND bo.rice_amount = p.rice_amount
                WHERE bo.status = 'ロス'
                GROUP BY month
                ORDER BY month DESC
                LIMIT 12
            ";
            $trendStmt = $db->prepare($trendQuery);
            $trendStmt->execute();
            $lossTrends = $trendStmt->fetchAll(PDO::FETCH_ASSOC);

            // **最低条件: 今月と先月をチェック**
            $minimumMonths = [
                date('Y-m'), // 今月
                date('Y-m', strtotime('-1 month')), // 先月
            ];
            $trendMonths = array_column($lossTrends, 'month');
            foreach ($minimumMonths as $month) {
                if (!in_array($month, $trendMonths)) {
                    array_push($lossTrends, [
                        'month' => $month,
                        'loss_count' => 0,
                        'loss_amount' => 0,
                    ]);
                }
            }

            // ソートして最新から並べ直す
            usort($lossTrends, function ($a, $b) {
                return strcmp($b['month'], $a['month']);
            });

            // 最大12か月分を取得
            $lossTrends = array_slice($lossTrends, 0, 12);

            // **3. ロスが発生した利用者リスト**
            $userQuery = "
                SELECT 
                    u.id,
                    u.name,
                    COUNT(bo.id) AS loss_count
                FROM bento_orders AS bo
                JOIN users AS u
                ON bo.user_id = u.id
                WHERE bo.status = 'ロス'
                GROUP BY u.id, u.name
                ORDER BY loss_count DESC
            ";
            $userStmt = $db->prepare($userQuery);
            $userStmt->execute();
            $lossUsers = $userStmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'current_month_loss' => (float) $currentMonthLoss,
                'total_loss' => (float) $totalLoss,
                'loss_trends' => $lossTrends,
                'loss_users' => $lossUsers,
            ]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => '不正なリクエストです。']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'エラーが発生しました: '.$e->getMessage()]);
}