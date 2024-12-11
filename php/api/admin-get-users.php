<?php
session_start();
require_once '../db.php';

header('Content-Type: application/json');

// 管理者チェック
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== '管理者') {
    echo json_encode(['success' => false, 'message' => '認証されていません。']);
    exit;
}

try {
    $db = getDbConnection();

    // usersテーブルから名前をあいうえお順で取得
    $query = "
        SELECT id, name 
        FROM users 
        WHERE role = '利用者' OR role = 'スタッフ'
        ORDER BY name COLLATE utf8mb4_unicode_ci
    ";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'users' => $users]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'エラーが発生しました: ' . $e->getMessage()]);
}