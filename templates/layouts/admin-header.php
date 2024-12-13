<nav class="navbar navbar-expand-lg navbar-light bg-light">
    <div class="container-fluid">
        <a class="navbar-brand" href="admin-dashboard.php">
            <img src="/public/assets/logo_long.png" alt="Obento System Logo" class="logo">
            管理画面
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMenu" aria-controls="navbarMenu" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarMenu">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link" href="admin-dashboard.php">ダッシュボード</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="admin-user-list.php">利用者管理</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="admin-order-summary.php">注文履歴</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="admin-loss-analysis.php">ロス分析</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="admin-request-list.php">申請一覧</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="admin-request-list.php">メニュー登録</a>
                </li>
            </ul>
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link text-danger" href="/php/auth/logout.php">ログアウト</a>
                </li>
            </ul>
        </div>
    </div>
</nav>
