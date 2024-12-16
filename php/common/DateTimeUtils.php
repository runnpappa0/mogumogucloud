<?php
// php/common/DateTimeUtils.php
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
            return date('Y-m-d', strtotime('+1 day'));
        }
        return date('Y-m-d');
    }

    public static function formatTargetDate(string $date): string {
        $dateTime = new \DateTime($date);
        $weekdays = ['日', '月', '火', '水', '木', '金', '土'];
        
        return $dateTime->format('n月j日') . '（' . 
               $weekdays[(int)$dateTime->format('w')] . '）の注文';
    }
}