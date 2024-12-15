<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>もぐもぐクラウド - 管理者注文履歴</title>
    <link rel="stylesheet" href="/public/css/admin.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</head>

<body>
    <!-- ヘッダー -->
    <div id="header"></div>

    <!-- メインコンテンツ -->
    <div class="container mt-5 pt-5">
        <h1 class="mb-4">注文履歴</h1>

        <!-- 検索フォーム -->
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <label for="dateSearch" class="form-label">日付で検索</label>
                <input type="date" id="dateSearch" class="form-control" placeholder="YYYY-MM-DD">
            </div>
            <div class="col-md-6">
                <label for="nameSearch" class="form-label">名前で検索</label>
                <input type="text" id="nameSearch" class="form-control" placeholder="名前を入力">
            </div>
        </div>

        <!-- 注文履歴テーブル -->
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>利用者名</th>
                        <th>お弁当の種類</th>
                        <th>ライスの量</th>
                        <th>配達先</th>
                        <th>日付</th>
                        <th>状態</th>
                        <th>ロス理由</th>
                    </tr>
                </thead>
                <tbody id="orderTableBody">
                    <tr>
                        <td colspan="8">注文履歴がありません。</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- ページネーション -->
        <nav class="mb-4">
            <ul class="pagination justify-content-center" id="pagination">
                <!-- ページ番号を動的に挿入 -->
            </ul>
        </nav>

        <!-- 注文金額 -->
        <div class="mt-4">
            <h5>注文金額</h5>
            <table class="table table-bordered w-75">
                <thead>
                    <tr>
                        <th>お弁当の種類</th>
                        <th>ライスの量</th>
                        <th>注文数</th>
                        <th>単価</th>
                        <th>小計</th>
                    </tr>
                </thead>
                <tbody id="orderAmountBody">
                    <tr>
                        <td colspan="5">計算中...</td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="4">合計</th>
                        <th id="totalAmount">0円</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- フッター -->
    <div id="footer"></div>

    <script>
        async function loadLayout() {
            document.getElementById("header").innerHTML = await fetch("/templates/layouts/admin-header.php").then(res => res.text());
            document.getElementById("footer").innerHTML = await fetch("/templates/layouts/admin-footer.php").then(res => res.text());
        }

        async function fetchOrderHistory(page = 1, date = null, name = null) {
            try {
                const url = new URL('/php/api/admin-order-summary.php', window.location.origin);
                url.searchParams.append('page', page);
                if (date) url.searchParams.append('date', date);
                if (name) url.searchParams.append('name', name);

                const response = await fetch(url);
                const data = await response.json();

                if (!data.success) {
                    document.getElementById('orderTableBody').innerHTML = '<tr><td colspan="7">注文履歴がありません。</td></tr>';
                    document.getElementById('orderAmountBody').innerHTML = '<tr><td colspan="5">計算中...</td></tr>';
                    document.getElementById('totalAmount').textContent = '0円';
                    document.getElementById('pagination').innerHTML = '';
                    return;
                }

                // 履歴の表示
                const tbody = document.getElementById('orderTableBody');
                tbody.innerHTML = '';
                data.orders.forEach((order, index) => {
                    const lossReason = order.status === 'ロス' ?
                        (order.loss_reason === 'その他' ? order.additional_notes : order.loss_reason) :
                        '';
                    const row = `
                        <tr>
                            <td>${index + 1}</td>
                            <td>${order.name}</td>
                            <td>${order.bento_type}</td>
                            <td>${order.rice_amount || 'なし'}</td>
                            <td>${order.delivery_place}</td>
                            <td>${order.order_date}</td>
                            <td><span class="badge ${order.status === '消費済み' ? 'bg-success' : 'bg-warning'}">${order.status}</span></td>
                            <td>${lossReason || ''}</td>
                        </tr>`;
                    tbody.innerHTML += row;
                });

                // 金額の表示
                const amountBody = document.getElementById('orderAmountBody');
                amountBody.innerHTML = '';
                let total = 0;
                data.amounts.forEach(amount => {
                    const subtotal = amount.count * amount.price;
                    total += subtotal;
                    amountBody.innerHTML += `
                    <tr>
                        <td>${amount.bento_type}</td>
                        <td>${amount.rice_amount || 'なし'}</td>
                        <td>${amount.count}</td>
                        <td>${amount.price.toLocaleString('ja-JP')}円</td>
                        <td>${subtotal.toLocaleString('ja-JP')}円</td>
                    </tr>`;
                });
                document.getElementById('totalAmount').textContent = `${total.toLocaleString('ja-JP')}円`;

                // ページネーションの更新
                updatePagination(data.pagination.total_pages, page);
            } catch (error) {
                console.error('注文履歴取得中にエラーが発生しました:', error);
            }
        }

        function updatePagination(totalPages, currentPage) {
            const pagination = document.getElementById('pagination');
            pagination.innerHTML = '';

            for (let i = 1; i <= totalPages; i++) {
                const pageItem = `
                <li class="page-item ${i === currentPage ? 'active' : ''}">
                    <a class="page-link" href="#">${i}</a>
                </li>`;
                const element = document.createElement('li');
                element.innerHTML = pageItem;
                element.addEventListener('click', (e) => {
                    e.preventDefault();
                    const date = document.getElementById('dateSearch').value;
                    const name = document.getElementById('nameSearch').value;
                    fetchOrderHistory(i, date, name);
                });
                pagination.appendChild(element);
            }
        }

        document.getElementById('dateSearch').addEventListener('input', () => {
            const date = document.getElementById('dateSearch').value;
            const name = document.getElementById('nameSearch').value;
            fetchOrderHistory(1, date, name);
        });

        document.getElementById('nameSearch').addEventListener('input', () => {
            const date = document.getElementById('dateSearch').value;
            const name = document.getElementById('nameSearch').value;
            fetchOrderHistory(1, date, name);
        });

        document.addEventListener('DOMContentLoaded', () => {
            loadLayout();
            fetchOrderHistory();
        });
    </script>
</body>

</html>