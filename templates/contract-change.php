<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>もぐもぐクラウド - 契約変更申請</title>
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
        <h1 class="mb-4">契約変更申請</h1>

        <!-- 契約変更申請フォーム -->
        <form id="contractForm">
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> お弁当が必要な曜日を選択し、ライスの量を指定してください。
            </div>

            <!-- お弁当不要選択 -->
            <div class="mb-4">
                <h3>契約オプション</h3>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="noBento" value="不要">
                    <label class="form-check-label" for="noBento">お弁当不要</label>
                </div>
            </div>

            <!-- 曜日選択 -->
            <div id="weekdaySelection" class="mb-4">
                <h3>曜日選択</h3>
                <div class="form-check">
                    <input class="form-check-input weekday-checkbox" type="checkbox" id="monday" value="月">
                    <label class="form-check-label" for="monday">月曜日</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input weekday-checkbox" type="checkbox" id="tuesday" value="火">
                    <label class="form-check-label" for="tuesday">火曜日</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input weekday-checkbox" type="checkbox" id="wednesday" value="水">
                    <label class="form-check-label" for="wednesday">水曜日</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input weekday-checkbox" type="checkbox" id="thursday" value="木">
                    <label class="form-check-label" for="thursday">木曜日</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input weekday-checkbox" type="checkbox" id="friday" value="金">
                    <label class="form-check-label" for="friday">金曜日</label>
                </div>
            </div>

            <!-- ライスの量 -->
            <div id="riceAmountContainer" class="mb-4">
                <label for="riceAmount" class="form-label">ライスの量</label>
                <select id="riceAmount" class="form-select">
                    <option value="大盛" selected>大盛</option>
                    <option value="普通盛">普通盛</option>
                    <option value="半ライス">半ライス</option>
                    <option value="おかずのみ">おかずのみ</option>
                </select>
            </div>

            <!-- 備考欄 -->
            <div class="mb-4">
                <label for="remarks" class="form-label">備考</label>
                <textarea id="remarks" class="form-control" rows="3" placeholder="特記事項や要望を記載してください"></textarea>
            </div>

            <!-- 送信ボタン -->
            <button type="submit" class="btn btn-primary">申請する</button>
        </form>

        <!-- 申請履歴 -->
        <div class="mt-5">
            <h3>申請履歴</h3>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>申請日</th>
                        <th>内容</th>
                        <th>ステータス</th>
                    </tr>
                </thead>
                <tbody id="requestHistory">
                    <tr>
                        <td colspan="3">申請履歴がありません。</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="mb-5"></div>

    <!-- フッター -->
    <div id="footer"></div>

    <script>
        async function loadLayout() {
            document.getElementById("header").innerHTML = await fetch("/templates/layouts/header.php").then(res => res.text());
            document.getElementById("footer").innerHTML = await fetch("/templates/layouts/footer.php").then(res => res.text());
        }

        document.getElementById('noBento').addEventListener('change', function() {
            const isChecked = this.checked;
            const weekdayCheckboxes = document.querySelectorAll('.weekday-checkbox');
            const riceAmountContainer = document.getElementById('riceAmountContainer');
            const remarks = document.getElementById('remarks');

            weekdayCheckboxes.forEach(checkbox => {
                checkbox.disabled = isChecked;
                if (isChecked) checkbox.checked = false;
            });
            riceAmountContainer.style.display = isChecked ? 'none' : 'block';
            remarks.placeholder = isChecked ?
                'お弁当が不要になった理由があればご記入ください（例: 体調不良、一時的な通所停止など）' :
                '特記事項や要望を記載してください';
        });

        async function submitContractChange(event) {
            event.preventDefault();

            // バリデーション: お弁当不要 または 少なくとも1つの曜日が選択されている必要がある
            const noBentoChecked = document.getElementById('noBento').checked;
            const weekdayCheckboxes = document.querySelectorAll('.weekday-checkbox:checked');

            if (!noBentoChecked && weekdayCheckboxes.length === 0) {
                alert('お弁当不要を選択するか、少なくとも1つの曜日を選択してください。');
                return; // バリデーションに失敗した場合、送信を中断
            }

            // 送信データの準備
            const weekdays = noBentoChecked ?
                null :
                Array.from(weekdayCheckboxes).map(cb => cb.value);
            const riceAmount = noBentoChecked ? null : document.getElementById('riceAmount').value;
            const remarks = document.getElementById('remarks').value;

            const requestData = {
                weekdays,
                rice_amount: riceAmount,
                remarks
            };

            try {
                const response = await fetch('/php/api/contract-change.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(requestData)
                });
                const result = await response.json();
                alert(result.message);
                if (result.success) {
                    fetchContractChangeHistory();
                }
            } catch (error) {
                console.error('申請送信中にエラーが発生しました:', error);
                alert('申請送信中にエラーが発生しました。');
            }
        }

        async function fetchContractChangeHistory() {
            try {
                const response = await fetch('/php/api/contract-change.php');
                const data = await response.json();

                const tbody = document.getElementById('requestHistory');
                tbody.innerHTML = '';

                if (data.requests && data.requests.length > 0) {
                    data.requests.forEach(req => {
                        const weekdays = req.weekdays ? req.weekdays.split(',').join('・') : 'お弁当不要';
                        const statusBadge = req.status === '承認待ち' ? 'bg-warning' : 'bg-success';

                        tbody.innerHTML += `
                            <tr>
                                <td>${new Date(req.created_at).toLocaleDateString()}</td>
                                <td>${weekdays}${req.rice_amount ? `：${req.rice_amount}` : ''}</td>
                                <td><span class="badge ${statusBadge}">${req.status}</span></td>
                            </tr>
                        `;
                    });
                } else {
                    tbody.innerHTML = '<tr><td colspan="3">申請履歴がありません。</td></tr>';
                }
            } catch (error) {
                console.error('履歴取得中にエラーが発生しました:', error);
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            loadLayout();
            fetchContractChangeHistory();
            document.getElementById('contractForm').addEventListener('submit', submitContractChange);
        });
    </script>
</body>

</html>