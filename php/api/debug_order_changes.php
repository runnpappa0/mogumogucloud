<?php
// debug_order_changes.php

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../common/DateTimeUtils.php';
require_once '../db.php';

use MoguMogu\Common\DateTimeUtils;

function debug_log($message, $data = null) {
    $log = date('Y-m-d H:i:s') . " - " . $message;
    if ($data !== null) {
        $log .= ": " . print_r($data, true);
    }
    error_log($log);
    echo $log . "\n";
}

try {
  $db = getDbConnection();
  $targetDate = DateTimeUtils::getTargetDate();

  debug_log("デバッグ開始");
  debug_log("対象日付", $targetDate);

  // 日付判定のロジックをクエリに組み込む
  $query = "
      SELECT 
          ocl.changer_id,
          c.name AS changer_name,
          ocl.user_id,
          u.name AS user_name,
          ocl.action,
          ocl.change_detail,
          DATE_FORMAT(ocl.change_time, '%Y-%m-%d %H:%i') AS change_time,
          bo.id as order_id,
          bo.order_date
      FROM order_change_logs ocl
      LEFT JOIN bento_orders bo ON ocl.order_id = bo.id
      JOIN users c ON ocl.changer_id = c.id
      JOIN users u ON ocl.user_id = u.id
      WHERE 
          bo.order_date = :target_date 
          OR 
          (
              ocl.order_id IS NULL 
              AND DATE(
                  CASE 
                      WHEN (DAYOFWEEK(ocl.change_time) = 6 AND TIME(ocl.change_time) >= '15:00') 
                          THEN DATE_ADD(ocl.change_time, INTERVAL 3 DAY)
                      WHEN DAYOFWEEK(ocl.change_time) = 7 
                          THEN DATE_ADD(ocl.change_time, INTERVAL 2 DAY)
                      WHEN DAYOFWEEK(ocl.change_time) = 1 
                          THEN DATE_ADD(ocl.change_time, INTERVAL 1 DAY)
                      WHEN DAYOFWEEK(ocl.change_time) = 2 AND TIME(ocl.change_time) < '15:00' 
                          THEN ocl.change_time
                      WHEN TIME(ocl.change_time) >= '15:00' 
                          THEN DATE_ADD(ocl.change_time, INTERVAL 1 DAY)
                      ELSE ocl.change_time
                  END
              ) = :target_date_2
          )
      ORDER BY ocl.change_time DESC
  ";

  debug_log("実行クエリ", $query);
  debug_log("バインドパラメータ", [
      ':target_date' => $targetDate,
      ':target_date_2' => $targetDate
  ]);

  $stmt = $db->prepare($query);
  $stmt->execute([
      ':target_date' => $targetDate,
      ':target_date_2' => $targetDate
  ]);
  $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

  debug_log("取得結果数", count($results));
  foreach ($results as $result) {
      debug_log("履歴:", [
          'user' => $result['user_name'],
          'action' => $result['action'],
          'time' => $result['change_time'],
          'order_id' => $result['order_id']
      ]);
  }

} catch (Exception $e) {
  debug_log("エラー発生", $e->getMessage());
}