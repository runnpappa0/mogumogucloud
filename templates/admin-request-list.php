<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Obento System - 申請一覧</title>
    <link rel="stylesheet" href="/public/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</head>

<body>
    <!-- ヘッダー -->
    <div id="header"></div>

    <!-- メインコンテンツ -->
    <div class="container mt-4">
        <h1 class="mb-4">申請一覧</h1>

        <!-- 申請一覧テーブル -->
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>#</th>
                    <th>申請者名</th>
                    <th>申請日</th>
                    <th>申請内容</th>
                    <th>ステータス</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody id="requestTable">
                <tr>
                    <td colspan="6">申請がありません。</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- フッター -->
    <div id="footer"></div>

    <script>
    async function loadLayout() {
        const header = document.getElementById("header");
        const footer = document.getElementById("footer");
        header.innerHTML = await fetch("/templates/layouts/admin-header.php").then(res => res.text());
        footer.innerHTML = await fetch("/templates/layouts/admin-footer.php").then(res => res.text());
    }

    async function fetchRequests() {
        try {
            const response = await fetch('/php/api/contract-approve.php');
            const data = await response.json();

            const tbody = document.getElementById('requestTable');
            tbody.innerHTML = '';

            if (data.requests && data.requests.length > 0) {
                data.requests.forEach((req, index) => {
                    const weekdays = req.weekdays ? req.weekdays.split(',').join(', ') : 'お弁当不要';
                    const statusBadge = req.status === '承認待ち' ? 'bg-warning' : 'bg-success';

                    tbody.innerHTML += `
                        <tr>
                            <td>${index + 1}</td>
                            <td>${req.name}</td>
                            <td>${new Date(req.created_at).toLocaleDateString()}</td>
                            <td>
                                <div>
                                    <strong>曜日:</strong> ${weekdays}<br>
                                    <strong>ライスの量:</strong> ${req.rice_amount || 'なし'}<br>
                                    <strong>備考:</strong> ${req.notes || 'なし'}
                                </div>
                            </td>
                            <td><span class="badge ${statusBadge}">${req.status}</span></td>
                            <td>
                                ${req.status === '承認待ち' ? `<button class="btn btn-sm btn-success" onclick="approveRequest(${req.id})">承認</button>` : '—'}
                            </td>
                        </tr>
                    `;
                });
            } else {
                tbody.innerHTML = '<tr><td colspan="6">申請がありません。</td></tr>';
            }
        } catch (error) {
            console.error('申請一覧の取得中にエラーが発生しました:', error);
        }
    }

    async function approveRequest(id) {
        if (!confirm('この申請を承認しますか？')) return;

        try {
            const response = await fetch('/php/api/contract-approve.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ id })
            });

            const result = await response.json();

            if (result.success) {
                alert(result.message);
                fetchRequests();
            } else {
                alert(result.message || '承認に失敗しました。');
            }
        } catch (error) {
            console.error('承認処理中にエラーが発生しました:', error);
            alert('承認処理中にエラーが発生しました。');
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        loadLayout();
        fetchRequests();
    });
</script>
</body>

</html>