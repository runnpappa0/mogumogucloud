<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/holiday_checker.php';

// テスト用の日付設定機能
function setTestDate($date)
{
    return date('Y-m-d', strtotime($date));
}

try {
    // 現在時刻の取得
    $now = new DateTime();
    $today_weekday = $now->format('w');
    $today_date = $now->format('Y-m-d');

    error_log("注文作成処理開始: " . $now->format('Y-m-d H:i:s'));

    // 金曜日の場合は翌週月曜日を設定
    if ($today_weekday === '5') { // 金曜日
        $target_date = isset($argv[1])
            ? setTestDate($argv[1])
            : date('Y-m-d', strtotime('next monday'));
        error_log("金曜日処理: 翌週月曜日の注文を作成します - {$target_date}");
    } else {
        $target_date = isset($argv[1])
            ? setTestDate($argv[1])
            : date('Y-m-d', strtotime('+1 day'));
        error_log("通常処理: 翌日の注文を作成します - {$target_date}");
    }

    $target_weekday = ['日', '月', '火', '水', '木', '金', '土'][date('w', strtotime($target_date))];
    error_log("対象日の曜日: {$target_weekday}");

    // 土日判定（日本語の曜日で判定）
    if ($target_weekday === '日' || $target_weekday === '土') {
        error_log("土日のため処理をスキップします: {$target_date}");
        exit;
    }

    // 祝日判定
    $is_holiday = HolidayChecker::isHoliday($target_date);
    error_log("祝日判定結果: " . ($is_holiday ? "祝日" : "平日"));

    $db = getDbConnection();

    try {
        // トランザクション開始
        $db->beginTransaction();
        error_log("トランザクション開始");

        // 注文変更履歴をクリア
        $clear_logs_query = "DELETE FROM order_change_logs";
        $clear_logs_stmt = $db->prepare($clear_logs_query);
        $clear_logs_stmt->execute();
        $cleared_logs_count = $clear_logs_stmt->rowCount();
        error_log("注文変更履歴をクリア: {$cleared_logs_count}件");

        // 既存の注文をクリア
        $clear_orders_query = "DELETE FROM bento_orders WHERE order_date = :target_date";
        $clear_orders_stmt = $db->prepare($clear_orders_query);
        $clear_orders_stmt->execute([':target_date' => $target_date]);
        $deleted_count = $clear_orders_stmt->rowCount();
        error_log("既存の注文をクリア: {$deleted_count}件");

        // 注文作成
        $query = "
            INSERT INTO bento_orders 
                (user_id, order_date, bento_type, rice_amount, delivery_place, status) 
            SELECT 
                u.id,
                :target_date,
                CASE WHEN :is_holiday_bento = 1 THEN '冷凍' ELSE cd.bento_type END,
                CASE WHEN :is_holiday_rice = 1 THEN NULL ELSE cd.rice_amount END,
                u.default_delivery_place,
                NULL as status
            FROM users u
            JOIN contract_details cd ON u.id = cd.user_id
            WHERE 
                cd.weekdays LIKE :weekday_pattern
                AND u.role != '管理者'
        ";

        $params = [
            ':target_date' => $target_date,
            ':is_holiday_bento' => $is_holiday ? 1 : 0,
            ':is_holiday_rice' => $is_holiday ? 1 : 0,
            ':weekday_pattern' => '%' . $target_weekday . '%'
        ];

        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $created_count = $stmt->rowCount();
        error_log("注文作成完了: {$created_count}件");

        // トランザクションのコミット
        $db->commit();
        error_log("トランザクションコミット完了");

    } catch (Exception $e) {
        // エラーが発生した場合はロールバック
        $db->rollBack();
        error_log("トランザクションロールバック実行: " . $e->getMessage());
        throw $e;
    }
} catch (Exception $e) {
    error_log("注文作成エラー: " . $e->getMessage());
    exit(1);
}

error_log("注文作成処理完了");