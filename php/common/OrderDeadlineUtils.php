<?php
// php/common/OrderDeadlineUtils.php
namespace MoguMogu\Common;

use MoguMogu\Config\TimeConstants;

class OrderDeadlineUtils
{
    public static function isOrderEditable(string $targetDate): bool
    {
        $now = new \DateTime();
        $today = new \DateTime(date('Y-m-d'));
        $orderDate = new \DateTime($targetDate);

        // 金曜日15:00以降の月曜日注文の場合
        if ($now->format('w') === '5' && $orderDate->format('w') === '1') {
            $deadline = new \DateTime(date('Y-m-d ' .
                TimeConstants::ORDER_DEADLINE_HOUR . ':' .
                TimeConstants::ORDER_DEADLINE_MINUTE . ':00'));
            $deadline->modify('+3 days'); // 月曜日の締め切り時刻

            return $now < $deadline;
        }

        // 通常の注文の場合（当日9:25まで）
        $deadline = new \DateTime($targetDate . ' ' .
            TimeConstants::ORDER_DEADLINE_HOUR . ':' .
            TimeConstants::ORDER_DEADLINE_MINUTE . ':00');

        return $now < $deadline;
    }

    public static function getRemainingTime(string $targetDate): ?array
    {
        $now = new \DateTime();
        $today = new \DateTime(date('Y-m-d'));
        $orderDate = new \DateTime($targetDate);

        // 金曜日15:00以降の月曜日注文の場合
        if ($now->format('w') === '5' && $orderDate->format('w') === '1') {
            $deadline = new \DateTime(date('Y-m-d ' .
                TimeConstants::ORDER_DEADLINE_HOUR . ':' .
                TimeConstants::ORDER_DEADLINE_MINUTE . ':00'));
            $deadline->modify('+3 days'); // 月曜日の締め切り時刻

            if (!self::isOrderEditable($targetDate)) {
                return null;
            }

            $interval = $now->diff($deadline);
            return [
                'hours' => $interval->h + ($interval->days * 24),
                'minutes' => $interval->i
            ];
        }

        // 通常の注文の場合（当日9:25まで）
        if (!self::isOrderEditable($targetDate)) {
            return null;
        }

        $deadline = new \DateTime($targetDate . ' ' .
            TimeConstants::ORDER_DEADLINE_HOUR . ':' .
            TimeConstants::ORDER_DEADLINE_MINUTE . ':00');

        $interval = $now->diff($deadline);
        return [
            'hours' => $interval->h + ($interval->days * 24),
            'minutes' => $interval->i
        ];
    }
}
