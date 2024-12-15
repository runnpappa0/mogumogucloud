<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>もぐもぐクラウド - 注文履歴</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="/public/css/style.css">
</head>

<body>
    <!-- ヘッダー -->
    <div id="header"></div>

    <!-- メインコンテンツ -->
    <div class="container mt-5 pt-5">
        <h1 class="mb-4">注文履歴</h1>

        <!-- 集計情報 -->
        <div class="alert alert-info" id="summaryInfo" style="display: none;">
            <h4>直近1か月の集計</h4>
            <p>注文数: <span id="orderCount">0</span></p>
            <p>自己負担額: ¥<span id="totalLossCost">0</span></p>
        </div>

        <!-- 日付検索フォーム -->
        <div class="mb-3">
            <label for="searchDate" class="form-label">日付で検索</label>
            <input type="date" class="form-control" id="searchDate">
        </div>

        <!-- 注文履歴テーブル -->
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>日付</th>
                    <th>お弁当タイプ</th>
                    <th>ライスの量</th>
                    <th>配達先</th>
                    <th>ステータス</th>
                </tr>
            </thead>
            <tbody id="orderHistory">
                <tr>
                    <td colspan="5">注文履歴がありません。</td>
                </tr>
            </tbody>
        </table>

        <!-- ページネーション -->
        <nav>
            <ul class="pagination justify-content-center" id="pagination">
                <!-- ページ番号を動的に挿入 -->
            </ul>
        </nav>
    </div>

    <div class="mb-5"></div>

    <!-- フッター -->
    <div id="footer"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        async function loadLayout() {
            document.getElementById("header").innerHTML = await fetch("/templates/layouts/header.php").then(res => res.text());
            document.getElementById("footer").innerHTML = await fetch("/templates/layouts/footer.php").then(res => res.text());
        }

        // 注文履歴を取得
        async function fetchOrderHistory(page = 1, date = null) {
            try {
                const url = new URL('/php/api/order-history.php', window.location.origin);
                url.searchParams.append('page', page);
                if (date) {
                    url.searchParams.append('date', date);
                }

                const response = await fetch(url);
                const data = await response.json();

                if (!data.success || !data.orders.length) {
                    document.getElementById('orderHistory').innerHTML = '<tr><td colspan="5">注文履歴がありません。</td></tr>';
                    document.getElementById('summaryInfo').style.display = 'none';
                    document.getElementById('pagination').innerHTML = '';
                    return;
                }

                // 注文履歴の更新
                const tbody = document.getElementById('orderHistory');
                tbody.innerHTML = '';
                data.orders.forEach(order => {
                    const badgeClass = order.status === '消費済み' ? 'bg-success' : (order.status === 'ロス' ? 'bg-warning' : '');
                    const statusDisplay = order.status ? `<span class="badge ${badgeClass}">${order.status}</span>` : '';

                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${order.order_date}</td>
                        <td>${order.bento_type}</td>
                        <td>${order.rice_amount || 'なし'}</td>
                        <td>${order.delivery_place}</td>
                        <td>${statusDisplay}</td>
                    `;
                    tbody.appendChild(row);
                });

                // 集計情報の更新
                if (data.summary) {
                    document.getElementById('orderCount').textContent = data.summary.order_count;
                    document.getElementById('totalLossCost').textContent = data.summary.total_cost;
                    document.getElementById('summaryInfo').style.display = 'block';
                }

                // ページネーションの更新
                updatePagination(data.pagination.total_pages, data.pagination.current_page);
            } catch (error) {
                console.error('エラーが発生しました:', error);
            }
        }

        // ページネーションを更新
        function updatePagination(totalPages, currentPage) {
            const pagination = document.getElementById('pagination');
            pagination.innerHTML = '';

            for (let i = 1; i <= totalPages; i++) {
                const pageItem = document.createElement('li');
                pageItem.className = `page-item ${i === currentPage ? 'active' : ''}`;
                pageItem.innerHTML = `<a class="page-link" href="#">${i}</a>`;
                pageItem.addEventListener('click', (event) => {
                    event.preventDefault();
                    fetchOrderHistory(i, document.getElementById('searchDate').value);
                });
                pagination.appendChild(pageItem);
            }
        }

        // 日付検索
        document.getElementById('searchDate').addEventListener('input', (event) => {
            fetchOrderHistory(1, event.target.value);
        });

        // 初期ロード
        document.addEventListener('DOMContentLoaded', () => {
            loadLayout();
            fetchOrderHistory();
        });
    </script>
</body>

</html>