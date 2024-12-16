<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/holiday_checker.php';

// テスト用の日付設定機能
function setTestDate($date) {
    return date('Y-m-d', strtotime($date));
}

try {
    // 翌日の日付を取得（テスト用環境変数対応）
    $tomorrow = isset($_ENV['TEST_DATE']) 
        ? setTestDate($_ENV['TEST_DATE']) 
        : date('Y-m-d', strtotime('+1 day'));
    $tomorrow_weekday = date('w', strtotime($tomorrow));

    // 実行ログ
    error_log("処理開始: 対象日 {$tomorrow}");

    // 土日判定
    if ($tomorrow_weekday == 0 || $tomorrow_weekday == 6) {
        error_log("土日のため処理をスキップします: {$tomorrow}");
        exit;
    }

    // 祝日判定
    $is_holiday = HolidayChecker::isHoliday($tomorrow);
    error_log("祝日判定結果: " . ($is_holiday ? "祝日" : "平日"));

    $db = getDbConnection();
    
    // 既存の注文をクリア
    $clear_query = "DELETE FROM bento_orders WHERE order_date = :tomorrow";
    $clear_stmt = $db->prepare($clear_query);
    $clear_stmt->execute([':tomorrow' => $tomorrow]);
    $deleted_count = $clear_stmt->rowCount();
    error_log("既存の注文をクリア: {$deleted_count}件");

    // 注文作成
    $query = "INSERT INTO bento_orders (user_id, order_date, bento_type, rice_amount, delivery_place)
              SELECT 
                  u.id,
                  :tomorrow,
                  CASE WHEN :is_holiday THEN '冷凍' ELSE cd.bento_type END,
                  CASE WHEN :is_holiday THEN NULL ELSE cd.rice_amount END,
                  u.default_delivery_place
              FROM users u
              JOIN contract_details cd ON u.id = cd.user_id
              WHERE 
                  FIND_IN_SET(:weekday, cd.weekdays) > 0
                  AND u.role != '管理者'";
    
    $stmt = $db->prepare($query);
    $stmt->execute([
        ':tomorrow' => $tomorrow,
        ':is_holiday' => $is_holiday ? 1 : 0,
        ':weekday' => ['日','月','火','水','木','金','土'][$tomorrow_weekday]
    ]);
    
    $created_count = $stmt->rowCount();
    error_log("注文作成完了: {$created_count}件");

} catch (Exception $e) {
    error_log("注文作成エラー: " . $e->getMessage());
    exit(1);
}