// 利用者一覧の取得
const userList = document.getElementById('userList');

const fetchUsers = async (search = '') => {
    try {
        const response = await fetch(`/php/api/admin-user-list.php?search=${encodeURIComponent(search)}`);
        const data = await response.json();

        if (!data.success || !data.users || data.users.length === 0) {
            userList.innerHTML = '<tr><td colspan="9">利用者がいません。</td></tr>';
            return;
        }

        userList.innerHTML = '';
        data.users.forEach((user, index) => {
            const weekdays = user.weekdays ? user.weekdays.split(',').join('・') : '';
            userList.innerHTML += `
                <tr>
                    <td>${index + 1}</td>
                    <td>${user.username}</td>
                    <td>${user.name}</td>
                    <td>${weekdays}</td>
                    <td>${user.bento_type || ''}</td>
                    <td>${user.rice_amount || ''}</td>
                    <td>${user.notes || ''}</td>
                    <td>${user.role}</td>
                    <td>
                        <button class="btn btn-sm btn-primary" data-id="${user.id}" onclick="editUser(${user.id})">編集</button>
                        <button class="btn btn-sm btn-danger" data-id="${user.id}" onclick="deleteUser(${user.id})">削除</button>
                    </td>
                </tr>`;
        });
    } catch (error) {
        console.error('利用者の取得中にエラーが発生しました:', error);
        userList.innerHTML = '<tr><td colspan="9">利用者の取得中にエラーが発生しました。</td></tr>';
    }
};

// ユーザー編集
const editUser = (id) => {
    alert(`編集機能: ユーザーID ${id}`);
};

// ユーザー削除
const deleteUser = (id) => {
    if (confirm('この利用者を削除しますか？')) {
        alert(`削除機能: ユーザーID ${id}`);
    }
};

// 検索フォームイベントリスナー
document.getElementById('searchInput').addEventListener('input', (e) => {
    fetchUsers(e.target.value);
});

// 初期ロード時に利用者一覧を取得
document.addEventListener('DOMContentLoaded', () => {
    fetchUsers();
});
