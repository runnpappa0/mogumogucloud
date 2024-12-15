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
    <title>もぐもぐクラウド - ロスサマリー</title>
    <link rel="stylesheet" href="/public/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</head>

<body>
    <!-- ヘッダー -->
    <div id="header"></div>

    <!-- メインコンテンツ -->
    <div class="container mt-5 pt-5">
        <h1 class="mb-4">ロスサマリー</h1>

        <!-- 意識向上のためのメッセージ -->
        <div class="impact-banner loss-none" style="display: none;"></div>

        <!-- ロス個数と自己負担額、発注金額 -->
        <div id="summaryInfo" class="mb-4" style="display: none;">
            <h3>ロス個数と自己負担額</h3>
            <ul class="list-group">
                <!-- サマリー情報を動的に挿入 -->
            </ul>
        </div>

        <!-- ロスの詳細一覧 -->
        <div class="mb-4">
            <h3>ロスの詳細</h3>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>日付</th>
                        <th>理由</th>
                        <th>お弁当の種類</th>
                    </tr>
                </thead>
                <tbody id="lossDetails">
                    <tr>
                        <td colspan="3">ロス履歴がありません。</td>
                    </tr>
                </tbody>
            </table>
        </div>

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

    <script>
        // ヘッダーとフッターを読み込む
        async function loadLayout() {
            document.getElementById("header").innerHTML = await fetch("/templates/layouts/header.php").then(res => res.text());
            document.getElementById("footer").innerHTML = await fetch("/templates/layouts/footer.php").then(res => res.text());
        }

        // ロスサマリーを取得
        async function loadLossSummary(page = 1) {
            try {
                const response = await fetch(`/php/api/loss-summary.php?page=${page}`);
                const data = await response.json();

                const impactBanner = document.querySelector('.impact-banner');

                if (!data.success || data.details.length === 0) {
                    // ロスなしの場合のメッセージとスタイル
                    impactBanner.textContent = 'ロス履歴がありません。引き続きご協力をお願いいたします。';
                    impactBanner.classList.add('loss-none');
                    impactBanner.classList.remove('loss-exist');
                    impactBanner.style.display = 'block';

                    document.querySelector('#summaryInfo').style.display = 'none';
                    document.querySelector('#lossDetails').innerHTML = '<tr><td colspan="3">ロス履歴がありません。</td></tr>';
                    return;
                }

                // ロスありの場合のメッセージとスタイル
                impactBanner.textContent = 'ロスを減らしましょう！無断欠席や連絡なしのキャンセルは避け、環境への負担を減らしましょう。';
                impactBanner.classList.add('loss-exist');
                impactBanner.classList.remove('loss-none');
                impactBanner.style.display = 'block';

                // サマリー情報を表示
                const summary = data.summary;
                document.querySelector('#summaryInfo').style.display = 'block';
                document.querySelector('#summaryInfo .list-group').innerHTML = `
            <li class="list-group-item d-flex justify-content-between align-items-center">
                合計ロス数
                <span class="badge bg-danger rounded-pill">${summary.total_loss_count}</span>
            </li>
            <li class="list-group-item d-flex justify-content-between align-items-center">
                合計ロス額（発注金額）
                <span class="badge bg-danger rounded-pill">¥${summary.total_loss_cost}</span>
            </li>
            <li class="list-group-item d-flex justify-content-between align-items-center">
                自己負担額
                <span class="badge bg-danger rounded-pill">¥${summary.self_cost}</span>
            </li>
        `;

                // ロス詳細を表示
                const lossDetails = data.details;
                const tbody = document.querySelector('#lossDetails');
                tbody.innerHTML = '';
                lossDetails.forEach(detail => {
                    const lossReason = detail.loss_reason === 'その他' ?
                        detail.additional_notes :
                        detail.loss_reason;

                    const row = `
                <tr>
                    <td>${detail.order_date}</td>
                    <td>${lossReason}</td>
                    <td>${detail.bento_detail}</td>
                </tr>
            `;
                    tbody.insertAdjacentHTML('beforeend', row);
                });

                // ページネーション更新
                updatePagination(data.totalPages, page);
            } catch (error) {
                console.error('エラー:', error);
            }
        }


        // ページネーションを更新
        function updatePagination(totalPages, currentPage) {
            const pagination = document.querySelector('#pagination');
            pagination.innerHTML = '';

            for (let i = 1; i <= totalPages; i++) {
                const pageItem = document.createElement('li');
                pageItem.className = `page-item ${i === currentPage ? 'active' : ''}`;
                pageItem.innerHTML = `<a class="page-link" href="#">${i}</a>`;
                pageItem.addEventListener('click', () => loadLossSummary(i));
                pagination.appendChild(pageItem);
            }
        }

        // 初期ロード
        document.addEventListener('DOMContentLoaded', () => {
            loadLayout();
            loadLossSummary();
        });
    </script>
</body>

</html>