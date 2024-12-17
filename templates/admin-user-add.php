<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>もぐもぐクラウド - 新規ユーザー追加</title>
    <link rel="stylesheet" href="/public/css/admin.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="shortcut icon" href="../public/assets/favicon.png" type="image/x-icon">
</head>

<body>
    <!-- ヘッダー -->
    <div id="header"></div>

    <!-- メインコンテンツ -->
    <div class="container mt-5 pt-5">
        <h1 class="mb-4">新規ユーザー追加</h1>
        <form id="addUserForm">
            <div class="mb-3">
                <label for="addUsername" class="form-label">ユーザー名</label>
                <input type="text" class="form-control" id="addUsername" placeholder="ユーザー名を入力" required>
            </div>
            <div class="mb-3">
                <label for="addPassword" class="form-label">パスワード</label>
                <div class="input-group">
                    <input type="text" class="form-control" id="addPassword" readonly>
                    <button type="button" class="btn btn-outline-secondary" id="generatePassword">生成</button>
                    <button type="button" class="btn btn-outline-secondary" id="togglePassword">非表示</button>
                </div>
            </div>
            <div class="mb-3">
                <label for="addName" class="form-label">名前</label>
                <input type="text" class="form-control" id="addName" placeholder="名前を入力" required>
            </div>
            <div class="mb-3">
                <label for="addDays" class="form-label">お弁当が必要な曜日</label>
                <div id="addDays" class="form-check">
                    <input class="form-check-input" type="checkbox" id="monday" value="月">
                    <label class="form-check-label" for="monday">月</label><br>
                    <input class="form-check-input" type="checkbox" id="tuesday" value="火">
                    <label class="form-check-label" for="tuesday">火</label><br>
                    <input class="form-check-input" type="checkbox" id="wednesday" value="水">
                    <label class="form-check-label" for="wednesday">水</label><br>
                    <input class="form-check-input" type="checkbox" id="thursday" value="木">
                    <label class="form-check-label" for="thursday">木</label><br>
                    <input class="form-check-input" type="checkbox" id="friday" value="金">
                    <label class="form-check-label" for="friday">金</label>
                </div>
            </div>
            <div class="mb-3">
                <label for="addBentoType" class="form-label">お弁当タイプ</label>
                <select id="addBentoType" class="form-select">
                    <option value="">選択しない</option>
                    <option value="Aランチ">Aランチ</option>
                    <option value="Bランチ">Bランチ</option>
                    <option value="冷凍">冷凍</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="addRiceAmount" class="form-label">ライスの量</label>
                <select id="addRiceAmount" class="form-select">
                    <option value="">選択しない</option>
                    <option value="大盛">大盛</option>
                    <option value="普通盛">普通盛</option>
                    <option value="半ライス">半ライス</option>
                    <option value="おかずのみ">おかずのみ</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="addDeliveryPlace" class="form-label">配置</label>
                <select id="addDeliveryPlace" class="form-select" required>
                    <option value="施設内">施設内</option>
                    <option value="施設外">施設外</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="addNotes" class="form-label">特記事項</label>
                <textarea id="addNotes" class="form-control" rows="3" placeholder="アレルギー情報や特別な注意事項を入力"></textarea>
            </div>
            <div class="mb-3">
                <label for="addRole" class="form-label">区分</label>
                <select id="addRole" class="form-select">
                    <option value="利用者">利用者</option>
                    <option value="スタッフ">スタッフ</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="addCanChangeDelivery" class="form-label">配達先変更権限</label>
                <select id="addCanChangeDelivery" class="form-select" required>
                    <option value="1">あり</option>
                    <option value="0" selected>なし</option>
                </select>
            </div>
            <div class="text-end">
                <button type="submit" class="btn btn-primary">追加</button>
                <a href="admin-user-list.php" class="btn btn-secondary">キャンセル</a>
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

        // ランダムパスワード生成
        function generateRandomPassword() {
            const passwordInput = document.getElementById('addPassword');
            passwordInput.value = Math.floor(100000 + Math.random() * 900000).toString(); // 6桁のランダム数字
        }

        // パスワード表示/非表示切替
        function togglePasswordVisibility() {
            const passwordInput = document.getElementById('addPassword');
            const toggleButton = document.getElementById('togglePassword');

            if (passwordInput.type === 'text') {
                passwordInput.type = 'password';
                toggleButton.textContent = '表示';
            } else {
                passwordInput.type = 'text';
                toggleButton.textContent = '非表示';
            }
        }

        // フォームのバリデーション
        function validateForm() {
            const bentoType = document.getElementById('addBentoType').value;
            const riceAmount = document.getElementById('addRiceAmount').value;
            const weekdays = Array.from(document.querySelectorAll('#addDays input:checked')).length;

            // 曜日が選択されている場合のみお弁当関連のバリデーションを実行
            if (weekdays > 0) {
                // お弁当タイプが選択されているか確認
                if (!bentoType) {
                    alert('お弁当が必要な曜日を選択した場合、お弁当タイプを選択してください。');
                    return false;
                }

                // AランチまたはBランチの場合、ライスの量は必須
                if ((bentoType === 'Aランチ' || bentoType === 'Bランチ') && !riceAmount) {
                    alert('AランチまたはBランチの場合、ライスの量を選択してください。');
                    return false;
                }
            }
            return true;
        }

        // お弁当タイプ変更時のライスの量フィールド制御
        document.getElementById('addBentoType').addEventListener('change', function(e) {
            const riceAmountSelect = document.getElementById('addRiceAmount');
            if (e.target.value === '冷凍' || e.target.value === '') {
                riceAmountSelect.value = '';
                riceAmountSelect.disabled = true;
            } else {
                riceAmountSelect.disabled = false;
                if (riceAmountSelect.value === '') {
                    riceAmountSelect.value = '普通盛';
                }
            }
        });

        // フォーム送信処理
        async function handleSubmit(event) {
            event.preventDefault();

            // フォームのデータを取得
            const username = document.getElementById('addUsername').value.trim();
            const password = document.getElementById('addPassword').value.trim();
            const name = document.getElementById('addName').value.trim();
            const weekdays = Array.from(document.querySelectorAll('#addDays input:checked')).map(cb => cb.value);
            const bentoType = document.getElementById('addBentoType').value;
            const riceAmount = document.getElementById('addRiceAmount').value;
            const notes = document.getElementById('addNotes').value.trim();
            const role = document.getElementById('addRole').value;

            // 必須項目チェック
            if (!username || !password || !name) {
                alert('ユーザー名、パスワード、名前は必須です。');
                return;
            }

            // 曜日とお弁当タイプのバリデーション
            if (weekdays.length > 0 && !bentoType) {
                alert('曜日を選択した場合、お弁当タイプを選択してください。');
                return;
            }

            // お弁当タイプとライスの量のバリデーション
            if (bentoType && (bentoType === 'Aランチ' || bentoType === 'Bランチ') && !riceAmount) {
                alert('AランチまたはBランチを選択した場合、ライスの量を選択してください。');
                return;
            }

            // 送信データの構造
            const data = {
                username,
                password,
                name,
                role,
                weekdays: weekdays.length > 0 ? weekdays : null,
                bento_type: bentoType || null,
                rice_amount: bentoType ? riceAmount : null,
                notes: notes || null
            };

            try {
                const response = await fetch('/php/api/admin-user-add.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (result.success) {
                    alert('新規利用者が追加されました。');
                    window.location.href = 'admin-user-list.php';
                } else {
                    alert(result.message || '利用者の追加に失敗しました。');
                }
            } catch (error) {
                console.error('利用者追加中にエラーが発生しました:', error);
                alert('利用者追加中にエラーが発生しました。');
            }
        }

        // 初期処理
        document.addEventListener('DOMContentLoaded', () => {
            loadLayout();
            document.getElementById('generatePassword').addEventListener('click', generateRandomPassword);
            document.getElementById('togglePassword').addEventListener('click', togglePasswordVisibility);
            document.getElementById('addUserForm').addEventListener('submit', handleSubmit);
            document.getElementById('addBentoType').addEventListener('change', handleBentoTypeChange);

            // 初回パスワード生成
            generateRandomPassword();
            // 初期状態のお弁当タイプに応じたライスの量フィールドの制御
            handleBentoTypeChange();
        });
    </script>
</body>

</html>