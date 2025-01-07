<?php
class HolidayChecker {
    private static $holidays = null;
    
    public static function isHoliday($date) {
        if (self::$holidays === null) {
            self::$holidays = self::getJapaneseHolidays();
        }
        return in_array($date, self::$holidays);
    }
    
    private static function getJapaneseHolidays() {
        try {
            $url = 'https://www8.cao.go.jp/chosei/shukujitsu/syukujitsu.csv';
            $csv = file_get_contents($url);
            if ($csv === false) {
                error_log("祝日データの取得に失敗しました");
                return [];
            }

            // Shift-JISからUTF-8に変換
            $csv = mb_convert_encoding($csv, 'UTF-8', 'SJIS');
            
            $holidays = [];
            $lines = explode("\n", $csv);
            array_shift($lines); // ヘッダー行（国民の祝日・休日月日,国民の祝日・休日名称）を削除
            
            foreach ($lines as $line) {
                if (empty($line)) continue;
                $data = str_getcsv($line);
                if (isset($data[0])) {
                    // yyyy/m/d形式をyyyy-mm-dd形式に変換
                    $holiday = date('Y-m-d', strtotime($data[0]));
                    $holidays[] = $holiday;
                }
            }
            
            return $holidays;

        } catch (Exception $e) {
            error_log("祝日チェックエラー: " . $e->getMessage());
            return [];
        }
    }
}