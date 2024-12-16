<?php
// php/common/OrderDeadlineUtils.php
namespace MoguMogu\Common;

use MoguMogu\Config\TimeConstants;

class OrderDeadlineUtils {
    public static function isOrderEditable(string $targetDate): bool {
        $now = new \DateTime();
        $today = new \DateTime(date('Y-m-d'));
        $deadline = new \DateTime(date('Y-m-d ' . 
            TimeConstants::ORDER_DEADLINE_HOUR . ':' . 
            TimeConstants::ORDER_DEADLINE_MINUTE . ':00'
        ));
        $orderDate = new \DateTime($targetDate);
        
        if ($orderDate > $today) {
            return true;
        }
        
        if ($orderDate == $today && $now < $deadline) {
            return true;
        }
        
        return false;
    }

    public static function getRemainingTime(string $targetDate): ?array {
        if (!self::isOrderEditable($targetDate)) {
            return null;
        }

        $now = new \DateTime();
        $orderDate = new \DateTime($targetDate);
        $deadline = new \DateTime($targetDate . ' ' . 
            TimeConstants::ORDER_DEADLINE_HOUR . ':' . 
            TimeConstants::ORDER_DEADLINE_MINUTE . ':00'
        );
        
        $interval = $now->diff($deadline);
        return [
            'hours' => $interval->h + ($interval->days * 24),
            'minutes' => $interval->i
        ];
    }
}