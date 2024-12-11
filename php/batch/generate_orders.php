<?php
require_once 'db.php';

try {
    $db = getDbConnection();
    $db->beginTransaction();

    // 当日の注文をクリア済みに更新
    $clearQuery = "
        UPDATE bento_orders
        SET is_cleared = 1
        WHERE order_date = CURDATE() AND is_cleared = 0
    ";
    $db->exec($clearQuery);

    // 翌日の情報を準備
    $nextDay = date('Y-m-d', strtotime('+1 day'));
    $nextWeekday = ['日', '月', '火', '水', '木', '金', '土'][date('w', strtotime($nextDay))];

    // 契約内容から翌日の注文を生成
    $insertQuery = "
        INSERT INTO bento_orders (user_id, bento_type, rice_amount, delivery_place, order_date, is_cleared)
        SELECT 
            cd.user_id,
            'Aランチ', -- デフォルト値
            cd.rice_amount,
            cd.delivery_place,
            :next_day,
            0
        FROM contract_details cd
        WHERE FIND_IN_SET(:next_weekday, cd.weekdays)
    ";
    $stmt = $db->prepare($insertQuery);
    $stmt->execute([
        ':next_day' => $nextDay,
        ':next_weekday' => $nextWeekday,
    ]);

    $db->commit();
    echo "バッチ処理が正常に完了しました。\n";

} catch (PDOException $e) {
    $db->rollBack();
    echo "バッチ処理中にエラーが発生しました: " . $e->getMessage() . "\n";
}
