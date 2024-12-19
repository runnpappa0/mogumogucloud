<?php
namespace MoguMogu\Common;

use MoguMogu\Config\TimeConstants;

class DateTimeUtils {
    public static function getTargetDate(): string {
        $now = new \DateTime();
        $boundary = new \DateTime(date('Y-m-d ' . 
            TimeConstants::DATE_BOUNDARY_HOUR . ':' . 
            TimeConstants::DATE_BOUNDARY_MINUTE . ':00'
        ));
        
        if ($now >= $boundary) {
            // 金曜日の場合
            if ($now->format('w') === '5') {
                return date('Y-m-d', strtotime('+3 day')); // 翌週月曜日
            }
            return date('Y-m-d', strtotime('+1 day')); // 翌日
        }
        return date('Y-m-d'); // 当日
    }

    public static function formatTargetDate(string $date): string {
        $dateTime = new \DateTime($date);
        $weekdays = ['日', '月', '火', '水', '木', '金', '土'];
        
        return $dateTime->format('n月j日') . '（' . 
               $weekdays[(int)$dateTime->format('w')] . '）の注文';
    }
}