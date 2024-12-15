<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mogumogu Cloud - メニュー登録</title>
    <link rel="stylesheet" href="/public/css/admin.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</head>

<body>
    <!-- ヘッダー -->
    <div id="header"></div>

    <!-- メインコンテンツ -->
    <div class="container mt-4">
        <h2>メニュー登録</h2>

        <form id="menuLinkForm" class="mt-4">
            <!-- 今月 -->
            <div class="mb-4">
                <h4 class="h6 border-bottom pb-2" id="currentMonth"></h4>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Aランチ</label>
                        <input type="url" class="form-control" name="current_a" id="current_a"
                            placeholder="https://example.com/menu.pdf">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Bランチ</label>
                        <input type="url" class="form-control" name="current_b" id="current_b"
                            placeholder="https://example.com/menu.pdf">
                    </div>
                </div>
            </div>

            <!-- 来月 -->
            <div class="mb-4">
                <h4 class="h6 border-bottom pb-2" id="nextMonth"></h4>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Aランチ</label>
                        <input type="url" class="form-control" name="next_a" id="next_a"
                            placeholder="https://example.com/menu.pdf">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Bランチ</label>
                        <input type="url" class="form-control" name="next_b" id="next_b"
                            placeholder="https://example.com/menu.pdf">
                    </div>
                </div>
            </div>

            <div class="mt-3">
                <button type="submit" class="btn btn-primary">登録</button>
            </div>
        </form>
    </div>

    <!-- フッター -->
    <div id="footer"></div>

    <script>
        // ヘッダーとフッターを読み込む
        async function loadLayout() {
            document.getElementById("header").innerHTML = await fetch("./layouts/admin-header.php").then(res => res.text());
            document.getElementById("footer").innerHTML = await fetch("./layouts/admin-footer.php").then(res => res.text());
        }

        // 月表示を設定
        function setupMonthLabels() {
            const today = new Date();
            const nextMonth = new Date(today.getFullYear(), today.getMonth() + 1, 1);

            document.getElementById('currentMonth').textContent =
                `${today.getFullYear()}年${today.getMonth() + 1}月`;
            document.getElementById('nextMonth').textContent =
                `${nextMonth.getFullYear()}年${nextMonth.getMonth() + 1}月`;

            // hidden inputsに日付を設定
            const formData = {
                current_month: `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-01`,
                next_month: `${nextMonth.getFullYear()}-${String(nextMonth.getMonth() + 1).padStart(2, '0')}-01`
            };
            return formData;
        }

        // 現在のリンクを取得して表示
        async function fetchCurrentLinks() {
            try {
                const response = await fetch('/php/api/admin-menu-add.php');
                const data = await response.json();

                if (!data.success) {
                    throw new Error(data.message);
                }

                // フォームに値を設定
                data.links.forEach(link => {
                    const date = new Date(link.target_month);
                    const today = new Date();
                    const isCurrentMonth = date.getMonth() === today.getMonth();
                    const prefix = isCurrentMonth ? 'current' : 'next';
                    const suffix = link.bento_type === 'Aランチ' ? 'a' : 'b';
                    const inputId = `${prefix}_${suffix}`;

                    document.getElementById(inputId).value = link.link_url || '';
                });

            } catch (error) {
                console.error('メニューリンクの取得に失敗:', error);
                alert('メニューリンクの取得に失敗しました。');
            }
        }

        // フォーム送信処理
        document.getElementById('menuLinkForm').addEventListener('submit', async (e) => {
            e.preventDefault();

            const dates = setupMonthLabels();
            const formData = {
                links: [{
                        target_month: dates.current_month,
                        bento_type: 'Aランチ',
                        link_url: document.getElementById('current_a').value
                    },
                    {
                        target_month: dates.current_month,
                        bento_type: 'Bランチ',
                        link_url: document.getElementById('current_b').value
                    },
                    {
                        target_month: dates.next_month,
                        bento_type: 'Aランチ',
                        link_url: document.getElementById('next_a').value
                    },
                    {
                        target_month: dates.next_month,
                        bento_type: 'Bランチ',
                        link_url: document.getElementById('next_b').value
                    }
                ]
            };

            try {
                const response = await fetch('/php/api/admin-menu-add.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(formData)
                });

                const result = await response.json();

                if (result.success) {
                    alert('メニューリンクを保存しました。');
                    await fetchCurrentLinks(); // 表示を更新
                } else {
                    throw new Error(result.message);
                }
            } catch (error) {
                console.error('メニューリンクの保存に失敗:', error);
                alert('メニューリンクの保存に失敗しました。');
            }
        });

        // 初期化処理
        document.addEventListener('DOMContentLoaded', () => {
            loadLayout();
            setupMonthLabels();
            fetchCurrentLinks();
        });
    </script>
</body>

</html>