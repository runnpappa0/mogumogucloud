<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/holiday_checker.php';

// テスト用の日付設定機能
function setTestDate($date)
{
  return date('Y-m-d', strtotime($date));
}

try {
  // 翌日の日付と曜日を取得
  $tomorrow = isset($argv[1])
    ? setTestDate($argv[1])
    : date('Y-m-d', strtotime('+1 day'));
  $tomorrow_weekday = ['日', '月', '火', '水', '木', '金', '土'][date('w', strtotime($tomorrow))];

  // 土日判定（日本語の曜日で判定）
  if ($tomorrow_weekday === '日' || $tomorrow_weekday === '土') {
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

  // 注文作成（クエリを修正）
  $query = "
      INSERT INTO bento_orders 
          (user_id, order_date, bento_type, rice_amount, delivery_place, status, updated_by) 
      SELECT 
          u.id,
          :tomorrow,
          CASE WHEN :is_holiday_bento = 1 THEN '冷凍' ELSE cd.bento_type END,
          CASE WHEN :is_holiday_rice = 1 THEN NULL ELSE cd.rice_amount END,
          u.default_delivery_place,
          NULL as status,
          NULL as updated_by
      FROM users u
      JOIN contract_details cd ON u.id = cd.user_id
      WHERE 
          cd.weekdays LIKE :weekday_pattern
          AND u.role != '管理者'
  ";

  $stmt = $db->prepare($query);
  $stmt->execute([
    ':tomorrow' => $tomorrow,
    ':is_holiday_bento' => $is_holiday ? 1 : 0,
    ':is_holiday_rice' => $is_holiday ? 1 : 0,
    ':weekday_pattern' => '%' . $tomorrow_weekday . '%'
  ]);

  $created_count = $stmt->rowCount();
  error_log("注文作成完了: {$created_count}件");
} catch (Exception $e) {
  error_log("注文作成エラー: " . $e->getMessage());
  exit(1);
}