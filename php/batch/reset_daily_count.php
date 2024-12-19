<?php
require_once __DIR__ . '/../db.php';

try {
    $db = getDbConnection();
    
    $sql = "UPDATE users 
            SET daily_change_count = 0, 
                last_change_date = NULL 
            WHERE last_change_date < CURRENT_DATE";
            
    $stmt = $db->prepare($sql);
    $stmt->execute();
    
} catch (PDOException $e) {
    error_log('Daily count reset error: ' . $e->getMessage());
}