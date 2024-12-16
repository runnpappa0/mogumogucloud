<?php
require_once 'config.php';

class HolidayChecker {
    private static $holidays = null;
    
    public static function isHoliday($date) {
        if (self::$holidays === null) {
            self::$holidays = self::getJapaneseHolidays(date('Y'));
        }
        return in_array($date, self::$holidays);
    }
    
    private static function getJapaneseHolidays($year) {
        $calendar_id = 'japanese__ja@holiday.calendar.google.com';
        $time_min = $year . '-01-01T00:00:00Z';
        $time_max = $year . '-12-31T23:59:59Z';
        
        $url = "https://www.googleapis.com/calendar/v3/calendars/{$calendar_id}/events?"
             . "key=" . GOOGLE_API_KEY 
             . "&timeMin={$time_min}&timeMax={$time_max}";

        $response = file_get_contents($url);
        $data = json_decode($response, true);
        
        $holidays = [];
        if (isset($data['items'])) {
            foreach ($data['items'] as $item) {
                $holidays[] = substr($item['start']['date'], 0, 10);
            }
        }
        
        return $holidays;
    }
}