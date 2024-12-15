<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mogumogu Cloud - ロス分析</title>
    <link rel="stylesheet" href="/public/css/admin.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
    <!-- ヘッダー -->
    <div id="header"></div>

    <!-- メインコンテンツ -->
    <div class="container mt-4">
        <h1 class="mb-4">ロス分析</h1>

        <!-- ロス金額情報 -->
        <div class="row g-3 mb-4" id="lossSummary">
            <div class="col-md-6">
                <div class="p-3 border rounded bg-light">
                    <h5>今月のロス金額</h5>
                    <p id="currentLoss" class="fs-3 text-danger">¥0</p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="p-3 border rounded bg-light">
                    <h5>これまでのロス総額</h5>
                    <p id="totalLoss" class="fs-3 text-danger">¥0</p>
                </div>
            </div>
        </div>

        <!-- 複合グラフ -->
        <div class="mb-4" id="lossChartContainer">
            <h5 id="chartTitle">月別ロス比較</h5>
            <canvas id="lossChart"></canvas>
        </div>

        <!-- ロスユーザーリスト -->
        <div class="mb-4">
            <h5>ロスのある利用者リスト</h5>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>利用者名</th>
                        <th>ロス個数</th>
                        <th>詳細</th>
                    </tr>
                </thead>
                <tbody id="lossUserList">
                    <tr><td colspan="3">データを読み込んでいます...</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ロス詳細モーダル -->
    <div class="modal fade" id="lossDetailModal" tabindex="-1" aria-labelledby="lossDetailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="lossDetailModalLabel">ロス詳細</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="閉じる"></button>
                </div>
                <div class="modal-body">
                    <!-- サマリー情報 -->
                    <div id="summaryInfo" class="mb-4" style="display: none;">
                        <h3>ロス個数と自己負担額</h3>
                        <ul class="list-group">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                合計ロス数
                                <span id="totalLossCount" class="badge bg-danger rounded-pill">0</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                合計ロス額（発注金額）
                                <span id="totalLossAmount" class="badge bg-danger rounded-pill">¥0</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                自己負担額
                                <span id="userLossAmount" class="badge bg-danger rounded-pill">¥0</span>
                            </li>
                        </ul>
                    </div>

                    <!-- ロス詳細リスト -->
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
                                    <td colspan="3">データを読み込んでいます...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">閉じる</button>
                </div>
            </div>
        </div>
    </div>

    <!-- フッター -->
    <div id="footer"></div>

    <script>
        async function loadLayout() {
            document.getElementById("header").innerHTML = await fetch("/templates/layouts/admin-header.php").then(res => res.text());
            document.getElementById("footer").innerHTML = await fetch("/templates/layouts/admin-footer.php").then(res => res.text());
        }

        async function fetchLossData() {
            try {
                const response = await fetch('/php/api/admin-loss-analysis.php');
                const data = await response.json();

                if (!data.success) {
                    throw new Error(data.message || 'ロスデータの取得に失敗しました。');
                }

                // ロス金額表示
                document.getElementById('currentLoss').textContent = `¥${data.current_month_loss.toLocaleString()}`;
                document.getElementById('totalLoss').textContent = `¥${data.total_loss.toLocaleString()}`;

                // グラフの描画
                if (data.loss_trends.length >= 2) {
                    renderLossChart(data.loss_trends);
                } else {
                    document.getElementById('lossChartContainer').style.display = 'none';
                }

                // ロス利用者リスト
                const userList = document.getElementById('lossUserList');
                userList.innerHTML = '';
                if (data.loss_users.length > 0) {
                    data.loss_users.forEach(user => {
                        userList.innerHTML += `
                            <tr>
                                <td>${user.name}</td>
                                <td>${user.loss_count}</td>
                                <td><button class="btn btn-sm btn-primary loss-detail-btn" data-user-id="${user.id}" data-user-name="${user.name}">詳細</button></td>
                            </tr>
                        `;
                    });
                } else {
                    userList.innerHTML = '<tr><td colspan="3">ロスのある利用者はいません。</td></tr>';
                }
            } catch (error) {
                console.error('ロス分析データ取得中にエラーが発生しました:', error);
            }
        }

        function renderLossChart(trends) {
            // 古い月→最新の月の順にデータを並べ替え
            const sortedTrends = trends.sort((a, b) => new Date(a.month) - new Date(b.month));

            const ctx = document.getElementById('lossChart').getContext('2d');
            const labels = sortedTrends.map(t => t.month);
            const lossCounts = sortedTrends.map(t => t.loss_count);
            const lossAmounts = sortedTrends.map(t => t.loss_amount);

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels,
                    datasets: [
                        {
                            type: 'line',
                            label: 'ロス数',
                            data: lossCounts,
                            borderColor: 'blue',
                            fill: false,
                            yAxisID: 'y',
                        },
                        {
                            type: 'bar',
                            label: 'ロス金額',
                            data: lossAmounts,
                            backgroundColor: 'rgba(255, 99, 132, 0.5)',
                            yAxisID: 'y1',
                        },
                    ],
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            type: 'linear',
                            position: 'left',
                            title: {
                                display: true,
                                text: 'ロス数',
                            },
                        },
                        y1: {
                            type: 'linear',
                            position: 'right',
                            title: {
                                display: true,
                                text: 'ロス金額 (¥)',
                            },
                            grid: {
                                drawOnChartArea: false,
                            },
                        },
                    },
                },
            });
        }

        document.addEventListener('click', (event) => {
            if (event.target.classList.contains('loss-detail-btn')) {
                const userId = event.target.getAttribute('data-user-id');
                const userName = event.target.getAttribute('data-user-name'); // ボタンのデータ属性からユーザー名を取得
                loadLossDetails(userId, userName);
            }
        });

        // ロス詳細を取得してモーダルに表示
        async function loadLossDetails(userId, userName) {
            try {
                const response = await fetch(`/php/api/admin-loss-analysis.php?user_id=${userId}`);
                const data = await response.json();

                if (!data.success || !data.loss_details) {
                    throw new Error(data.message || 'ロス詳細データの取得に失敗しました。');
                }

                // モーダルのタイトルにユーザー名をセット
                document.getElementById('lossDetailModalLabel').textContent = `ロス詳細 - ${userName}`;

                // サマリー情報の更新
                const summaryInfo = document.getElementById('summaryInfo');
                document.getElementById('totalLossCount').textContent = data.loss_summary.total_loss_count;
                document.getElementById('totalLossAmount').textContent = `¥${data.loss_summary.total_loss_amount.toLocaleString()}`;
                document.getElementById('userLossAmount').textContent = `¥${data.loss_summary.user_burden_amount.toLocaleString()}`;
                summaryInfo.style.display = 'block';

                // ロス詳細の更新
                const lossDetails = document.getElementById('lossDetails');
                lossDetails.innerHTML = '';
                if (data.loss_details.length > 0) {
                    data.loss_details.forEach(detail => {
                        lossDetails.innerHTML += `
                            <tr>
                                <td>${detail.order_date}</td>
                                <td>${detail.loss_reason}</td>
                                <td>${detail.bento_detail}</td>
                            </tr>
                        `;
                    });
                } else {
                    lossDetails.innerHTML = '<tr><td colspan="3">ロスの詳細がありません。</td></tr>';
                }

                // モーダルを表示
                const lossModal = new bootstrap.Modal(document.getElementById('lossDetailModal'));
                lossModal.show();
            } catch (error) {
                console.error('ロス詳細取得中にエラーが発生しました:', error);
                alert('ロス詳細の取得に失敗しました。');
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            loadLayout();
            fetchLossData();
        });
    </script>
</body>
</html>