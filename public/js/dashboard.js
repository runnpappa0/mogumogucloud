// 配達先別発注数を取得して表示
async function fetchOrderCounts() {
    try {
        const response = await fetch('/php/api/admin-dashboard.php?action=fetch_order_counts');
        const text = await response.text(); // 生のレスポンスを取得

        // 空レスポンスチェック
        if (!text.trim()) {
            throw new Error('サーバーからのレスポンスが空です。');
        }

        // JSON パース処理
        const data = JSON.parse(text);

        if (!data.success) {
            throw new Error(data.message || '配達先別発注数の取得に失敗しました。');
        }

        const { facility_inside, facility_outside, frozen } = data.data;

        // 施設内テーブルの更新
        updateOrderTable('facilityInsideTable', facility_inside);

        // 施設外テーブルの更新
        updateOrderTable('facilityOutsideTable', facility_outside);

        // 冷凍テーブルの更新
        updateFrozenTable('frozenTable', frozen);

    } catch (error) {
        console.error('配達先別発注数の取得中にエラーが発生しました:', error);
    }
}

// テーブルを更新する関数
function updateOrderTable(tableId, data) {
    const table = document.getElementById(tableId);
    Array.from(table.querySelectorAll('tbody tr')).forEach(row => {
        const bentoType = row.getAttribute('data-bento-type');
        const cells = row.querySelectorAll('td');
        const riceAmounts = ['大盛', '普通盛', '半ライス', 'おかずのみ'];

        riceAmounts.forEach((amount, index) => {
            cells[index + 1].textContent = (data && data[bentoType] && data[bentoType][amount]) || ''; // 空欄の場合は空白
        });
    });
}

// 冷凍テーブルを更新する関数
function updateFrozenTable(tableId, frozenData) {
    const table = document.getElementById(tableId);
    const cells = table.querySelectorAll('tbody tr td');

    // 冷凍データを表示
    cells[0].textContent = frozenData.facility_inside || ''; // 施設内
    cells[1].textContent = frozenData.facility_outside || ''; // 施設外
}

// ユーザー取得
async function fetchUserList() {
    try {
        const response = await fetch('/php/api/admin-get-users.php');
        const data = await response.json();

        if (!data.success) {
            throw new Error(data.message || 'ユーザーリストの取得に失敗しました。');
        }

        const userSelect = document.getElementById('userSelect');
        userSelect.innerHTML = ''; // 初期化

        data.users.forEach(user => {
            const option = document.createElement('option');
            option.value = user.id;
            option.textContent = user.name;
            userSelect.appendChild(option);
        });
    } catch (error) {
        console.error('ユーザーリスト取得中にエラーが発生しました:', error);
        alert('ユーザーリストの取得に失敗しました。');
    }
}

// ライスの量フィールドを切り替える
function toggleRiceAmountField(bentoType, fieldId) {
    const riceAmountField = document.getElementById(fieldId);
    if (!riceAmountField) {
        console.error('ライスの量フィールドが見つかりません:', fieldId);
        return;
    }

    if (bentoType === '冷凍') {
        riceAmountField.value = '未選択'; // 明示的に「未選択」に設定
        riceAmountField.disabled = true; // フィールドを無効化
    } else {
        riceAmountField.disabled = false; // フィールドを有効化
    }
}

// DOMContentLoaded イベント内で処理を設定
document.addEventListener('DOMContentLoaded', () => {
    const addOrderModalElement = document.getElementById('addOrderModal');
    const addOrderButton = document.querySelector('#addOrderModal .btn-primary');

    if (!addOrderModalElement || !addOrderButton) {
        console.error('モーダル要素または注文追加ボタンが見つかりません。');
        return;
    }

    // モーダルを開いたときにユーザーリストを取得
    addOrderModalElement.addEventListener('show.bs.modal', async () => {
        try {
            const response = await fetch('/php/api/admin-dashboard.php?action=fetch_users');
            const text = await response.text();

            if (!text.trim()) {
                throw new Error('サーバーからのレスポンスが空です。');
            }

            const data = JSON.parse(text);

            if (!data.success || !data.users) {
                throw new Error(data.message || 'ユーザーリストの取得に失敗しました。');
            }

            const userSelect = document.getElementById('userSelect');
            userSelect.innerHTML = ''; // 既存のオプションをクリア

            // ユーザーリストをあいうえお順で追加
            data.users.forEach(user => {
                const option = document.createElement('option');
                option.value = user.id;
                option.textContent = user.name;
                userSelect.appendChild(option);
            });
        } catch (error) {
            console.error('ユーザーリスト取得中にエラーが発生しました:', error);
            alert('ユーザーリストの取得に失敗しました。');
        }
    });

    // 注文追加ボタンのクリックイベントを設定
    document.querySelector('#addOrderModal .btn-primary').addEventListener('click', async () => {
        const userId = document.getElementById('userSelect').value;
        const bentoType = document.getElementById('addBentoType').value;
        const riceAmount = document.getElementById('addRiceAmount').value;
        const deliveryPlace = document.getElementById('addDeliveryPlace').value;

        // バリデーション
        if (!userId || !bentoType || !deliveryPlace) {
            alert('全ての必須項目を入力してください。');
            return;
        }
        if ((bentoType === 'Aランチ' || bentoType === 'Bランチ') && riceAmount === '未選択') {
            alert('AランチまたはBランチの場合、ライスの量を選択してください。');
            return;
        }

        const data = {
            user_id: userId,
            bento_type: bentoType,
            rice_amount: riceAmount === '未選択' ? null : riceAmount,
            delivery_place: deliveryPlace,
        };

        try {
            const response = await fetch('/php/api/admin-dashboard.php?action=add_order', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data),
            });

            const text = await response.text();

            // 空レスポンスチェック
            if (!text.trim()) {
                throw new Error('サーバーからのレスポンスが空です。');
            }

            const result = JSON.parse(text);

            if (result.success) {
                alert('注文が追加されました。');
                fetchOrderCounts(); // 配達先別発注数を再取得
                fetchOrderDetails(); // 注文内訳を再取得
                const modalInstance = bootstrap.Modal.getInstance(document.getElementById('addOrderModal'));
                modalInstance.hide();
            } else {
                throw new Error(result.message || '注文の追加に失敗しました。');
            }
        } catch (error) {
            console.error('注文追加中にエラーが発生しました:', error);
            alert('注文追加中にエラーが発生しました。');
        }
    });

    // 注文追加モーダルの表示時にライスの量フィールドを切り替え
    document.getElementById('addBentoType').addEventListener('change', (event) => {
        toggleRiceAmountField(event.target.value, 'addRiceAmount');
    });

    // モーダルが表示されたときの初期化処理
    document.getElementById('addOrderModal').addEventListener('show.bs.modal', () => {
        const bentoType = document.getElementById('addBentoType').value;
        toggleRiceAmountField(bentoType, 'addRiceAmount');
    });
});

// 注文内訳を取得して表示
async function fetchOrderDetails() {
    try {
        const response = await fetch('/php/api/admin-dashboard.php?action=fetch_orders');
        const data = await response.json();

        if (!data.success) {
            throw new Error(data.message || '注文内訳の取得に失敗しました。');
        }

        const orderTableBody = document.querySelector('#orderTableBody');
        orderTableBody.innerHTML = ''; // 初期化

        if (data.orders.length === 0) {
            orderTableBody.innerHTML = '<tr><td colspan="7">本日注文がありません。</td></tr>';
            return;
        }

        // 各注文をテーブルに追加
        data.orders.forEach(order => {
            let statusBadge = '';
            if (order.status === '消費済み') {
                statusBadge = '<span class="badge bg-success">消費済み</span>';
            } else if (order.status === 'ロス') {
                statusBadge = '<span class="badge bg-warning">ロス</span>';
            }

            orderTableBody.innerHTML += `
            <tr>
                <td><input type="checkbox" class="orderCheckbox" data-order-id="${order.order_id}"></td>
                <td>${order.user_name}</td>
                <td>${order.bento_type}</td>
                <td>${order.rice_amount || 'なし'}</td>
                <td>${order.delivery_place}</td>
                <td>${statusBadge}</td>
                <td>
                    <button class="btn btn-sm btn-primary edit-order-btn" data-order-id="${order.order_id}">編集</button>
                    <button class="btn btn-sm btn-danger cancel-order-btn" data-order-id="${order.order_id}">キャンセル</button>
                </td>
            </tr>
        `;
        });

    } catch (error) {
        console.error('注文内訳取得中にエラーが発生しました:', error);
        const orderTableBody = document.querySelector('#orderTableBody');
        orderTableBody.innerHTML = '<tr><td colspan="7">エラーが発生しました。</td></tr>';
    }
}

document.addEventListener('DOMContentLoaded', () => {
    // 編集モーダルを開いたときの処理
    document.addEventListener('click', async (event) => {
        if (event.target.classList.contains('edit-order-btn')) {
            const orderId = event.target.getAttribute('data-order-id');
            openEditModal(orderId);
        }
    });

    // 保存ボタンのクリックイベント
    document.querySelector('#editModal .btn-primary').addEventListener('click', saveOrderChanges);
});

// 編集モーダルの開閉と値の設定
async function openEditModal(orderId) {
    try {
        const response = await fetch(`/php/api/admin-dashboard.php?action=fetch_order_details&order_id=${orderId}`);
        const data = await response.json();

        if (!data.success || !data.order) {
            throw new Error(data.message || '注文データの取得に失敗しました。');
        }

        const order = data.order;

        // モーダル内のフィールドをセット
        const bentoTypeField = document.getElementById('bentoType');
        const riceAmountField = document.getElementById('riceAmount');
        const deliveryPlaceField = document.getElementById('deliveryPlace');

        // 値をセット（order.bento_typeが必ず正しい値を持つことを前提）
        if (bentoTypeField.querySelector(`option[value="${order.bento_type}"]`)) {
            bentoTypeField.value = order.bento_type;
        } else {
            console.error(`無効な弁当タイプ: ${order.bento_type}`);
        }

        riceAmountField.value = order.rice_amount || '未選択';
        deliveryPlaceField.value = order.delivery_place;

        // ライスの量の状態を更新
        toggleRiceAmountField(order.bento_type, 'riceAmount');

        // イベントリスナーをリセット
        bentoTypeField.removeEventListener('change', handleBentoTypeChange);
        bentoTypeField.addEventListener('change', handleBentoTypeChange);

        // モーダルを表示
        const editModal = new bootstrap.Modal(document.getElementById('editModal'));
        editModal.show();

        // 保存ボタンに orderId をセット
        document.querySelector('#editModal .btn-primary').setAttribute('data-order-id', orderId);

        // イベントハンドラー
        function handleBentoTypeChange(event) {
            toggleRiceAmountField(event.target.value, 'riceAmount');
        }
    } catch (error) {
        console.error('編集モーダルの初期化中にエラーが発生しました:', error);
        alert('編集モーダルの初期化に失敗しました。');
    }
}

// 保存ボタンの処理
async function saveOrderChanges() {
    const orderId = document.querySelector('#editModal .btn-primary').getAttribute('data-order-id');
    const bentoType = document.getElementById('bentoType').value;
    const riceAmount = document.getElementById('riceAmount').value;
    const deliveryPlace = document.getElementById('deliveryPlace').value;

    // バリデーション
    if ((bentoType === 'Aランチ' || bentoType === 'Bランチ') && riceAmount === '未選択') {
        alert('AランチまたはBランチの場合、ライスの量を選択してください。');
        return;
    }

    const data = {
        order_id: orderId,
        bento_type: bentoType,
        rice_amount: riceAmount === '未選択' ? null : riceAmount,
        delivery_place: deliveryPlace,
    };

    try {
        const response = await fetch('/php/api/admin-dashboard.php?action=update_order', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data),
        });

        const result = await response.json();

        if (result.success) {
            alert('注文が更新されました。');
            fetchOrderCounts(); // 発注数を更新
            fetchOrderDetails(); // 注文内訳を更新

            const editModal = bootstrap.Modal.getInstance(document.getElementById('editModal'));
            editModal.hide();
        } else {
            throw new Error(result.message || '注文の更新に失敗しました。');
        }
    } catch (error) {
        console.error('注文更新中にエラーが発生しました:', error);
        alert('注文更新中にエラーが発生しました。');
    }
}

// 注文キャンセル機能
async function cancelOrder(orderId) {
    if (!confirm('この注文をキャンセルします。よろしいですか？')) return;

    try {
        const response = await fetch(`/php/api/admin-dashboard.php?action=delete_order&order_id=${orderId}`, {
            method: 'DELETE',
        });

        // 成功時の204対応
        if (response.status === 204) {
            alert('キャンセルが完了しました。');
            fetchOrderCounts(); // 配達先別発注数を再取得
            fetchOrderDetails(); // 注文内訳を再取得
            return;
        }

        const text = await response.text();
        if (!text) {
            throw new Error('サーバーからの応答が空です。');
        }

        const data = JSON.parse(text);

        if (data.success) {
            alert('キャンセルが完了しました。');
            fetchOrderCounts(); // 配達先別発注数を再取得
            fetchOrderDetails(); // 注文内訳を再取得
        } else {
            throw new Error(data.message || '注文のキャンセルに失敗しました。');
        }
    } catch (error) {
        console.error('注文キャンセル中にエラーが発生しました:', error);
        alert('注文キャンセル中にエラーが発生しました。');
    }
}

// イベントリスナー: キャンセルボタン
document.addEventListener('click', event => {
    if (event.target.classList.contains('cancel-order-btn')) {
        const orderId = event.target.getAttribute('data-order-id');
        cancelOrder(orderId);
    }
});

// "消費済み"への状態変更
document.querySelector(".update-status-btn[data-status='消費済み']").addEventListener("click", async () => {
    const selectedOrders = document.querySelectorAll(".orderCheckbox:checked");
    if (selectedOrders.length === 0) {
        alert("注文を選択してください。");
        return;
    }

    const orders = Array.from(selectedOrders).map(order => ({
        order_id: order.dataset.orderId,
        status: "消費済み" // "消費済み"の場合、理由や備考は不要
    }));

    try {
        const response = await fetch("/php/api/update_order_status.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ orders })
        });

        const result = await response.json();
        if (result.success) {
            // 更新成功: UIを更新
            selectedOrders.forEach(order => {
                const row = order.closest("tr");
                const statusCell = row.querySelector("td:nth-child(6) span");

                if (statusCell) {
                    statusCell.className = "badge bg-success";
                    statusCell.innerText = "消費済み";
                }
            });
        } else {
            console.error(result.message || "状態変更に失敗しました。");
        }
    } catch (error) {
        console.error("状態変更中にエラーが発生しました:", error);
    }
});

// ロス理由モーダルを動的に生成
document.getElementById("lossReasonModal").addEventListener("show.bs.modal", function (event) {
    const selectedOrders = document.querySelectorAll(".orderCheckbox:checked");

    // 選択された注文がない場合
    if (selectedOrders.length === 0) {
        event.preventDefault(); // モーダルの表示をキャンセル
        alert("注文を選択してください。");
        return;
    }

    const lossReasonList = document.getElementById("lossReasonList");
    lossReasonList.innerHTML = "";

    selectedOrders.forEach((order, index) => {
        const row = order.closest("tr");
        const userName = row.cells[1].innerText;
        const bentoType = row.cells[2].innerText;

        const item = document.createElement("div");
        item.className = "mb-3";
        item.innerHTML = `
            <label for="lossReason${index}" class="form-label">${userName} - ${bentoType}</label>
            <select id="lossReason${index}" class="form-select loss-reason-select" data-index="${index}">
                <option value="無断欠席">無断欠席</option>
                <option value="時間までに連絡なし">時間までに連絡なし</option>
                <option value="早退">早退</option>
                <option value="その他">その他</option>
            </select>
            <input type="text" id="lossReasonOther${index}" class="form-control mt-2 d-none" placeholder="理由を入力してください">
        `;
        lossReasonList.appendChild(item);
    });

    // "その他" 選択時の動作
    document.querySelectorAll(".loss-reason-select").forEach(select => {
        select.addEventListener("change", function () {
            const index = this.dataset.index;
            const otherInput = document.getElementById(`lossReasonOther${index}`);
            if (this.value === "その他") {
                otherInput.classList.remove("d-none");
            } else {
                otherInput.classList.add("d-none");
                otherInput.value = ""; // テキストボックスをクリア
            }
        });
    });
});

// ロス理由の保存
async function saveLossReasons() {
    const selectedOrders = document.querySelectorAll(".orderCheckbox:checked");
    const reasons = Array.from(selectedOrders).map((order, index) => {
        const reasonSelect = document.getElementById(`lossReason${index}`);
        const reason = reasonSelect.value;
        const otherReason = document.getElementById(`lossReasonOther${index}`).value;

        return {
            order_id: order.dataset.orderId,
            status: "ロス",
            reason: reason,
            additional_notes: reason === "その他" ? otherReason : null
        };
    });

    const requestData = { orders: reasons };

    try {
        const response = await fetch('/php/api/update_order_status.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(requestData)
        });

        const result = await response.json();
        if (result.success) {
            selectedOrders.forEach((order, index) => {
                const row = order.closest("tr");
                const statusCell = row.querySelector("td:nth-child(6) span");

                if (statusCell) {
                    statusCell.className = "badge bg-warning";
                    statusCell.innerText = "ロス";
                } else {
                    console.error("状態セルが見つかりませんでした:", row);
                }
            });

            const lossReasonModal = bootstrap.Modal.getInstance(document.getElementById("lossReasonModal"));
            lossReasonModal.hide();
        } else {
            console.error(result.message || "ロス理由の保存に失敗しました。");
        }
    } catch (error) {
        console.error("ロス理由保存中にエラーが発生しました:", error);
    }
}

// 当日の注文変更履歴を取得して表示
async function fetchOrderChangeHistory() {
    try {
        const response = await fetch('/php/api/process_order_changes.php?action=fetch_order_changes', {
            method: 'GET',
        });

        if (!response.ok) {
            throw new Error('サーバーエラーが発生しました。');
        }

        const data = await response.json();

        if (!data.success) {
            throw new Error(data.message || '注文変更履歴の取得に失敗しました。');
        }

        const historyTableBody = document.querySelector('#orderChangeHistoryBody');
        historyTableBody.innerHTML = '';

        if (data.changes.length === 0) {
            historyTableBody.innerHTML = '<tr><td colspan="6">本日の変更履歴はありません。</td></tr>';
            return;
        }

        data.changes.forEach((change, index) => {
            const row = `
                <tr>
                    <td>${index + 1}</td>
                    <td>${change.changer_id} (${change.changer_role})</td>
                    <td>${change.action}</td>
                    <td>${change.change_detail}</td>
                    <td>${change.change_time}</td>
                </tr>
            `;
            historyTableBody.innerHTML += row;
        });
    } catch (error) {
        console.error('注文変更履歴取得中にエラーが発生しました:', error);
        alert(error.message || '注文変更履歴の取得に失敗しました。');
    }
}

// 変更を処理する関数（注文の追加・更新・キャンセル）
async function processOrderChanges(changes) {
    try {
        const response = await fetch('/php/api/process_order_changes.php?action=process_changes', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ changes }),
        });

        if (!response.ok) {
            throw new Error('サーバーエラーが発生しました。');
        }

        const data = await response.json();

        if (!data.success) {
            throw new Error(data.message || '注文の変更処理に失敗しました。');
        }

        alert('注文の変更が成功しました！');
        fetchOrderChangeHistory(); // 履歴を更新
        fetchOrderDetails(); // 注文内訳を更新
    } catch (error) {
        console.error('注文変更中にエラーが発生しました:', error);
        alert(error.message || '注文変更に失敗しました。');
    }
}

// 初期化処理
document.addEventListener('DOMContentLoaded', () => {
    fetchOrderCounts(); // 配達先別発注数を取得
    fetchOrderDetails(); // 注文内訳を取得
    fetchUserList(); // ユーザーリストを取得
    fetchOrderChangeHistory();

    const selectAllCheckbox = document.getElementById("selectAll");

    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener("change", function () {
            const checkboxes = document.querySelectorAll(".orderCheckbox");
            checkboxes.forEach(checkbox => checkbox.checked = this.checked);
        });
    } else {
        console.error("selectAll チェックボックスが見つかりません。");
    }
});