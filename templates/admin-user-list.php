<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>もぐもぐクラウド - 利用者管理</title>
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
        <h1 class="mb-4">利用者管理</h1>

        <!-- 検索フォーム -->
        <div class="mb-4">
            <label for="searchInput" class="form-label">検索</label>
            <input type="text" class="form-control" id="searchInput" placeholder="名前で検索">
        </div>

        <!-- 利用者一覧テーブル -->
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>#</th>
                    <th>名前</th>
                    <th>お弁当が必要な曜日</th>
                    <th>お弁当タイプ</th>
                    <th>ライスの量</th>
                    <th>配置</th>
                    <th>特記事項</th>
                    <th>区分</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody id="userList">
                <tr>
                    <td colspan="9">データを読み込んでいます...</td>
                </tr>
            </tbody>
        </table>

        <!-- 利用者追加ボタン -->
        <div class="text-end">
            <a href="admin-user-add.php" class="btn btn-primary">ユーザーを追加</a>
        </div>
    </div>

    <!-- 編集モーダル -->
    <div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editUserModalLabel">利用者の編集</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="閉じる"></button>
                </div>
                <div class="modal-body">
                    <form>
                        <!-- 隠しフィールド -->
                        <input type="hidden" id="editUserId">

                        <div class="mb-3">
                            <label for="editUsername" class="form-label">ユーザーネーム</label>
                            <input type="text" class="form-control" id="editUsername" disabled>
                        </div>
                        <div class="mb-3">
                            <label for="editPassword" class="form-label">パスワード</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="editPassword">
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility()">表示</button>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="editName" class="form-label">名前</label>
                            <input type="text" class="form-control" id="editName" required>
                        </div>
                        <div class="mb-3">
                            <label for="editDays" class="form-label">お弁当が必要な曜日</label>
                            <div id="editDays">
                                <input class="form-check-input" type="checkbox" id="monday" value="月">
                                <label class="form-check-label" for="monday">月</label>
                                <br>
                                <input class="form-check-input" type="checkbox" id="tuesday" value="火">
                                <label class="form-check-label" for="tuesday">火</label>
                                <br>
                                <input class="form-check-input" type="checkbox" id="wednesday" value="水">
                                <label class="form-check-label" for="wednesday">水</label>
                                <br>
                                <input class="form-check-input" type="checkbox" id="thursday" value="木">
                                <label class="form-check-label" for="thursday">木</label>
                                <br>
                                <input class="form-check-input" type="checkbox" id="friday" value="金">
                                <label class="form-check-label" for="friday">金</label>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="editBentoType" class="form-label">お弁当タイプ</label>
                            <select id="editBentoType" class="form-select">
                                <option value="">未選択</option>
                                <option value="Aランチ">Aランチ</option>
                                <option value="Bランチ">Bランチ</option>
                                <option value="冷凍">冷凍</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="editRiceAmount" class="form-label">ライスの量</label>
                            <select id="editRiceAmount" class="form-select">
                                <option value="">未選択</option>
                                <option value="大盛">大盛</option>
                                <option value="普通盛">普通盛</option>
                                <option value="半ライス">半ライス</option>
                                <option value="おかずのみ">おかずのみ</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="editDeliveryPlace" class="form-label">配置</label>
                            <select id="editDeliveryPlace" class="form-select" required>
                                <option value="施設内">施設内</option>
                                <option value="施設外">施設外</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="editNotes" class="form-label">特記事項</label>
                            <textarea id="editNotes" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="editRole" class="form-label">区分</label>
                            <select id="editRole" class="form-select">
                                <option value="利用者">利用者</option>
                                <option value="スタッフ">スタッフ</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="editCanChangeDelivery" class="form-label">配達先変更権限</label>
                            <select id="editCanChangeDelivery" class="form-select" required>
                                <option value="1">あり</option>
                                <option value="0">なし</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">閉じる</button>
                    <button type="button" class="btn btn-primary" onclick="saveUserData()">保存</button>
                </div>
            </div>
        </div>
    </div>

    <!-- フッター -->
    <div id="footer"></div>

    <script>
        // ヘッダーとフッターを読み込む
        async function loadLayout() {
            document.getElementById("header").innerHTML = await fetch(
                "/templates/layouts/admin-header.php").then(res => res.text());
            document.getElementById("footer").innerHTML = await fetch(
                "/templates/layouts/admin-footer.php").then(res => res.text());
        }

        // ユーザー一覧を取得
        async function fetchUserList() {
            const tbody = document.getElementById('userList');
            tbody.innerHTML = '<tr><td colspan="9">データを読み込んでいます...</td></tr>';

            try {
                const response = await fetch('/php/api/admin-user-list.php');
                const data = await response.json();

                if (!data.success || !data.users) {
                    throw new Error(data.message || '利用者データが取得できませんでした。');
                }

                tbody.innerHTML = '';
                data.users.forEach((user, index) => {
                    const weekdays = user.weekdays ? user.weekdays.split(',').join('・') :
                        '';
                    const bentoType = user.bento_type || '';
                    const riceAmount = user.rice_amount || '';
                    const notes = user.contract_notes || '';

                    const row =
                        `
                    <tr>
                        <td>${index + 1}</td>
                        <td>${user.name}</td>
                        <td>${weekdays}</td>
                        <td>${bentoType}</td>
                        <td>${riceAmount}</td>
                        <td>${user.default_delivery_place || ''}</td>
                        <td>${notes}</td>
                        <td>${user.role}</td>
                        <td>
                            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editUserModal" onclick="loadUserData(${user.id})">編集</button>
                            <button class="btn btn-sm btn-danger" onclick="confirmDelete(${user.id}, '${user.username}')">削除</button>
                        </td>
                    </tr>
                `;
                    tbody.innerHTML += row;
                });
            } catch (error) {
                console.error('利用者一覧取得中にエラーが発生しました:', error);
                tbody.innerHTML = `<tr><td colspan="9">エラー: ${error.message}</td></tr>`;
            }
        }

        // 名前検索
        document.getElementById('searchInput').addEventListener('input', (event) => {
            const query = event.target.value.toLowerCase();
            const rows = document.querySelectorAll('#userList tr');
            rows.forEach(row => {
                const nameCell = row.querySelector('td:nth-child(3)');
                if (nameCell && nameCell.textContent.toLowerCase().includes(query)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        function togglePasswordVisibility() {
            const passwordInput = document.getElementById('editPassword');
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
            } else {
                passwordInput.type = 'password';
            }
        }

        // ユーザー情報を編集モーダルに反映
        async function loadUserData(userId) {
            try {
                const response = await fetch(`/php/api/admin-user-list.php?id=${userId}`);
                const data = await response.json();

                if (!data.success || !data.user) {
                    throw new Error(data.message || '利用者データが取得できませんでした。');
                }

                const user = data.user;
                document.getElementById('editUserId').value = user.id;
                document.getElementById('editUsername').value = user.username;
                document.getElementById('editName').value = user.name;
                document.getElementById('editRole').value = user.role;

                // 弁当タイプとライスの量の設定
                const bentoTypeSelect = document.getElementById('editBentoType');
                const riceAmountSelect = document.getElementById('editRiceAmount');

                bentoTypeSelect.value = user.bento_type || 'Aランチ';
                riceAmountSelect.value = user.rice_amount || '';

                // 冷凍の場合、ライスの量を無効化
                if (user.bento_type === '冷凍') {
                    riceAmountSelect.value = '';
                    riceAmountSelect.disabled = true;
                } else {
                    riceAmountSelect.disabled = false;
                }

                // その他の設定
                const weekdays = user.weekdays ? user.weekdays.split(',') : [];
                document.querySelectorAll('#editDays input[type="checkbox"]').forEach(checkbox => {
                    checkbox.checked = weekdays.includes(checkbox.value);
                });

                document.getElementById('editDeliveryPlace').value = user.default_delivery_place;
                document.getElementById('editCanChangeDelivery').value = user.can_change_delivery ? "1" : "0";
                document.getElementById('editNotes').value = user.contract_notes || '';
            } catch (error) {
                console.error('編集データ取得中にエラーが発生しました:', error);
            }
        }

        function validateEditForm() {
            const bentoType = document.getElementById('editBentoType').value;
            const riceAmount = document.getElementById('editRiceAmount').value;
            const weekdays = Array.from(document.querySelectorAll('#editDays input:checked')).length;

            // 曜日が選択されている場合のみお弁当関連のバリデーションを実行
            if (weekdays > 0) {
                // お弁当タイプが未選択の場合
                if (!bentoType) {
                    alert('曜日を選択した場合、お弁当タイプを選択してください。');
                    return false;
                }
                // AランチまたはBランチでライスの量が未選択の場合
                if ((bentoType === 'Aランチ' || bentoType === 'Bランチ') && !riceAmount) {
                    alert('AランチまたはBランチを選択した場合、ライスの量を選択してください。');
                    return false;
                }
            }
            return true;
        }

        function handleEditBentoTypeChange(e) {
            const riceAmountSelect = document.getElementById('editRiceAmount');
            if (e.target.value === '冷凍' || e.target.value === '') {
                riceAmountSelect.value = '';
                riceAmountSelect.disabled = true;
            } else {
                riceAmountSelect.disabled = false;
                // 以前の選択が冷凍または未選択だった場合、デフォルト値を設定
                if (riceAmountSelect.value === '') {
                    riceAmountSelect.value = '普通盛';
                }
            }
        }

        // ユーザー情報を保存
        async function saveUserData() {
            // バリデーションチェック
            if (!validateEditForm()) {
                return;
            }

            const userId = document.getElementById('editUserId').value;
            const bentoType = document.getElementById('editBentoType').value;
            const riceAmount = document.getElementById('editRiceAmount').value;
            const weekdays = Array.from(document.querySelectorAll('#editDays input:checked'))
                .map(cb => cb.value);

            const data = {
                id: userId,
                password: document.getElementById('editPassword').value,
                name: document.getElementById('editName').value,
                weekdays: weekdays,
                bento_type: bentoType,
                rice_amount: bentoType === '冷凍' ? '' : riceAmount,
                default_delivery_place: document.getElementById('editDeliveryPlace').value,
                notes: document.getElementById('editNotes').value,
                role: document.getElementById('editRole').value,
                can_change_delivery: Number(document.getElementById('editCanChangeDelivery').value),
            };

            try {
                const response = await fetch('/php/api/admin-user-list.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                });
                const result = await response.json();

                if (result.success) {
                    alert('ユーザー情報が更新されました。');
                    fetchUserList();
                    document.querySelector('#editUserModal .btn-close').click();
                } else {
                    alert(result.message || 'ユーザー情報の更新に失敗しました。');
                }
            } catch (error) {
                console.error('ユーザー情報更新中にエラーが発生しました:', error);
                alert('ユーザー情報の更新中にエラーが発生しました。');
            }
        }

        // ユーザー削除
        async function confirmDelete(userId, username) {
            if (!confirm(`ユーザー ${username} を削除します。よろしいですか？`)) return;

            try {
                const response = await fetch(`/php/api/admin-user-list.php?id=${userId}`, {
                    method: 'DELETE',
                });
                const data = await response.json();

                if (data.success) {
                    alert(data.message);
                    fetchUserList(); // 一覧を再取得
                } else {
                    alert(data.message || '削除に失敗しました。');
                }
            } catch (error) {
                console.error('削除中にエラーが発生しました:', error);
                alert('削除中にエラーが発生しました。');
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            // 既存のイベントリスナー
            loadLayout();
            fetchUserList();

            // お弁当タイプ変更時のイベントリスナー
            const editBentoTypeSelect = document.getElementById('editBentoType');
            if (editBentoTypeSelect) {
                editBentoTypeSelect.removeEventListener('change', handleEditBentoTypeChange);
                editBentoTypeSelect.addEventListener('change', handleEditBentoTypeChange);
            }

            // 編集モーダルが開かれるたびに初期状態を設定
            const editModal = document.getElementById('editUserModal');
            if (editModal) {
                editModal.addEventListener('shown.bs.modal', () => {
                    const bentoType = document.getElementById('editBentoType').value;
                    const riceAmountSelect = document.getElementById('editRiceAmount');

                    if (bentoType === '冷凍') {
                        riceAmountSelect.value = '';
                        riceAmountSelect.disabled = true;
                    } else {
                        riceAmountSelect.disabled = false;
                    }
                });
            }
        });
    </script>
</body>

</html>