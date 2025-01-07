<?php
namespace MoguMogu\Common;

use MoguMogu\Config\TimeConstants;

class DateTimeUtils {
    public static function getTargetDate(): string {
        $now = new \DateTime();
        $weekday = (int)$now->format('w');
        $boundary = new \DateTime(date('Y-m-d ' . 
            TimeConstants::DATE_BOUNDARY_HOUR . ':' . 
            TimeConstants::DATE_BOUNDARY_MINUTE . ':00'
        ));

        // 金曜日15:00以降から月曜日15:00までは翌週月曜日を返す場合の条件を見直し
        if (($weekday === 5 && $now >= $boundary) ||  // 金曜日15:00以降
            $weekday === 6 ||                         // 土曜日
            $weekday === 0) {                         // 日曜日のみ
            return date('Y-m-d', strtotime('next monday'));
        }

        // 月曜日15:00より前は当日を返す
        if ($weekday === 1 && $now < $boundary) {
            return date('Y-m-d');  // 当日
        }

        // その他の日の15:00以降は翌日
        if ($now >= $boundary) {
            return date('Y-m-d', strtotime('+1 day'));
        }

        // それ以外は当日
        return date('Y-m-d');
    }

    public static function formatTargetDate(string $date): string {
        $dateTime = new \DateTime($date);
        $weekdays = ['日', '月', '火', '水', '木', '金', '土'];
        
        return $dateTime->format('n月j日') . '（' . 
               $weekdays[(int)$dateTime->format('w')] . '）の注文';
    }
}