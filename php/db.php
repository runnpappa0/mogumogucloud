<?php

function getDbConnection() {
    $host = '127.0.0.1';  // ホスト名
    $dbname = 'mogumogucloud';  // データベース名
    $username = 'root';  // ユーザー名
    $password = '';  // パスワード（必要に応じて変更）

    try {
        $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // エラーモード
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // デフォルトのフェッチモード
            PDO::ATTR_EMULATE_PREPARES => false, // プリペアドステートメントのエミュレーションを無効化
        ];
        return new PDO($dsn, $username, $password, $options);
    } catch (PDOException $e) {
        error_log("Database connection error: " . $e->getMessage());
        throw new Exception("データベース接続に失敗しました。");
    }
}
