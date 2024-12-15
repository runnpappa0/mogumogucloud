<?php
session_start();

// ログイン済みの場合、遷移先を決定
if (isset($_SESSION['user_id'])) {
    $redirectUrl = ($_SESSION['role'] === '管理者') ? '/templates/admin-dashboard.php' : '/templates/today-order.php';
    header("Location: $redirectUrl");
    exit;
}
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>もぐもぐクラウド - ログイン</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="/public/css/style.css">
</head>

<body>
    <div class="container d-flex flex-column justify-content-center align-items-center vh-100">
        <!-- ロゴ部分 -->
        <img src="/public/assets/logo.png" alt="もぐもぐクラウド Logo" class="mb-4" style="max-width: 150px;">

        <!-- ログイン枠 -->
        <div class="card p-4 shadow-sm" style="width: 100%; max-width: 400px;">
            <h2 class="text-center mb-4">ログイン</h2>
            <form id="loginForm">
                <!-- ユーザー名 -->
                <div class="mb-3">
                    <label for="username" class="form-label">ユーザー名</label>
                    <input type="text" class="form-control" id="username" name="username" placeholder="ユーザー名を入力" required>
                </div>
                <!-- パスワード -->
                <div class="mb-3">
                    <label for="password" class="form-label">パスワード</label>
                    <input type="password" class="form-control" id="password" name="password" placeholder="パスワードを入力" required>
                </div>
                <!-- 記憶するチェックボックス -->
                <div class="form-check mb-3">
                    <input type="checkbox" class="form-check-input" id="rememberMe">
                    <label class="form-check-label" for="rememberMe">ログイン情報を記憶する</label>
                </div>
                <!-- ログインボタン -->
                <button type="submit" class="btn btn-primary w-100">ログイン</button>
            </form>
        </div>
    </div>

    <script>
        // ログインフォームの処理
        document.getElementById("loginForm").addEventListener("submit", async function(event) {
            event.preventDefault();

            const username = document.getElementById("username").value.trim();
            const password = document.getElementById("password").value.trim();

            try {
                const response = await fetch("/php/auth.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded"
                    },
                    body: new URLSearchParams({
                        username,
                        password
                    })
                });

                const result = await response.json();

                if (result.success) {
                    window.location.href = result.redirect; // PHP から遷移先を受け取る
                } else {
                    alert(result.message);
                }
            } catch (error) {
                console.error(error);
                alert("サーバーエラーが発生しました。もう一度お試しください。");
            }
        });


        // ページ読み込み時に記憶された情報を入力フィールドにセット
        window.addEventListener("load", function() {
            const rememberedUsername = localStorage.getItem("rememberedUsername");
            const rememberedPassword = localStorage.getItem("rememberedPassword");
            if (rememberedUsername) {
                document.getElementById("username").value = rememberedUsername;
            }
            if (rememberedPassword) {
                document.getElementById("password").value = rememberedPassword;
                document.getElementById("rememberMe").checked = true;
            }
        });
    </script>
</body>

</html>