<?php
session_start();
require_once '../php/db.php'; // DB接続ファイルを読み込み

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => '不正なリクエストです。']);
    exit;
}

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

// 入力値チェック
if (empty($username) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'ユーザー名またはパスワードを入力してください。']);
    exit;
}

try {
    $db = getDbConnection(); // データベース接続

    // ユーザーをデータベースから取得
    $stmt = $db->prepare('SELECT id, username, password, role FROM users WHERE username = :username');
    $stmt->bindParam(':username', $username, PDO::PARAM_STR);
    $stmt->execute();

    $user = $stmt->fetch();

    // パスワード検証
    if (!$user || $password !== $user['password']) { // 平文で比較
        echo json_encode(['success' => false, 'message' => 'ユーザー名またはパスワードが正しくありません。']);
        exit;
    }

    // セッション情報を設定
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['user_role'] = $user['role']; // 統一されたセッションキー

    // ログイン成功時のリダイレクト先
    if ($user['role'] === '管理者') {
        $redirect = '/templates/admin-dashboard.php'; // 管理者ダッシュボード
    } elseif ($user['role'] === '利用者' || $user['role'] === 'スタッフ') {
        $redirect = '/templates/today-order.php'; // 利用者・スタッフ画面
    } else {
        // 万が一、想定外の役割が設定されている場合の処理
        echo json_encode(['success' => false, 'message' => '無効な役割が割り当てられています。']);
        exit;
    }

    echo json_encode(['success' => true, 'redirect' => $redirect]);
} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'message' => 'サーバーエラーが発生しました。']);
    exit;
}
