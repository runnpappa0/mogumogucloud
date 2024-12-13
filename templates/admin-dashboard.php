<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Obento System - 管理者ダッシュボード</title>
    <link rel="stylesheet" href="/public/css/style.css">
    <script src="/public/js/dashboard.js" defer></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</head>

<body>
    <!-- ヘッダー -->
    <div id="header"></div>

    <!-- メインコンテンツ -->
    <div class="container mt-4">
        <h1 class="mb-4">管理者ダッシュボード</h1>

        <!-- 配達先別発注数 -->
        <div class="mb-4">

            <!-- 施設内テーブル -->
            <h3>施設内</h3>
            <table id="facilityInsideTable" class="table table-bordered">
                <thead>
                    <tr>
                        <th></th>
                        <th>大盛ライスセット</th>
                        <th>普通盛ライスセット</th>
                        <th>半ライスセット</th>
                        <th>おかずのみ</th>
                    </tr>
                </thead>
                <tbody>
                    <tr data-bento-type="Aランチ">
                        <td>Aランチ</td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                    <tr data-bento-type="Bランチ">
                        <td>Bランチ</td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                </tbody>
            </table>

            <!-- 施設外テーブル -->
            <h3>施設外</h3>
            <table id="facilityOutsideTable" class="table table-bordered">
                <thead>
                    <tr>
                        <th></th>
                        <th>大盛ライスセット</th>
                        <th>普通盛ライスセット</th>
                        <th>半ライスセット</th>
                        <th>おかずのみ</th>
                    </tr>
                </thead>
                <tbody>
                    <tr data-bento-type="Aランチ">
                        <td>Aランチ</td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                    <tr data-bento-type="Bランチ">
                        <td>Bランチ</td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                </tbody>
            </table>

            <!-- 冷凍テーブル -->
            <h3>冷凍</h3>
            <table id="frozenTable" class="table table-bordered">
                <thead>
                    <tr>
                        <th>施設内</th>
                        <th>施設外</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td></td>
                        <td></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- 注文追加ボタン -->
        <div class="text-end mt-4">
            <button class="btn btn-primary open-add-order-modal" data-bs-toggle="modal"
                data-bs-target="#addOrderModal">注文を追加</button>
        </div>

        <!-- 注文追加モーダル -->
        <div class="modal fade" id="addOrderModal" tabindex="-1" aria-labelledby="addOrderModalLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addOrderModalLabel">注文を追加</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="閉じる"></button>
                    </div>
                    <div class="modal-body">
                        <form>
                            <!-- ユーザーリスト -->
                            <div class="mb-3">
                                <label for="userSelect" class="form-label">ユーザー</label>
                                <select id="userSelect" class="form-select">
                                    <!-- 動的にユーザーリストを追加 -->
                                </select>
                            </div>
                            <!-- お弁当の種類 -->
                            <div class="mb-3">
                                <label for="addBentoType" class="form-label">お弁当の種類</label>
                                <select id="addBentoType" class="form-select">
                                    <option value="Aランチ">Aランチ</option>
                                    <option value="Bランチ">Bランチ</option>
                                    <option value="冷凍">冷凍</option>
                                </select>
                            </div>
                            <!-- ライスの量 -->
                            <div class="mb-3">
                                <label for="addRiceAmount" class="form-label">ライスの量</label>
                                <select id="addRiceAmount" class="form-select">
                                    <option value="未選択">未選択</option>
                                    <option value="大盛">大盛</option>
                                    <option value="普通盛">普通盛</option>
                                    <option value="半ライス">半ライス</option>
                                    <option value="おかずのみ">おかずのみ</option>
                                </select>
                            </div>
                            <!-- 配達先 -->
                            <div class="mb-3">
                                <label for="addDeliveryPlace" class="form-label">配達先</label>
                                <select id="addDeliveryPlace" class="form-select">
                                    <option value="施設内">施設内</option>
                                    <option value="施設外">施設外</option>
                                </select>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <!-- 閉じるボタン -->
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">閉じる</button>
                        <!-- 追加ボタン -->
                        <button type="button" class="btn btn-primary add-order-btn">追加</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- 注文内訳 -->
        <div class="mb-4">
            <h3>注文内訳</h3>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="selectAll"></th>
                        <th>利用者</th>
                        <th>お弁当の種類</th>
                        <th>ライスの量</th>
                        <th>配達先</th>
                        <th>状態</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody id="orderTableBody">
                    <tr>
                        <td colspan="7">データを読み込んでいます...</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- 編集モーダル -->
        <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editModalLabel">注文の編集</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="閉じる"></button>
                    </div>
                    <div class="modal-body">
                        <form>
                            <input type="hidden" id="editOrderId"> <!-- 注文IDを保持 -->
                            <div class="mb-3">
                                <label for="bentoType" class="form-label">お弁当の種類</label>
                                <select id="bentoType" class="form-select">
                                    <option value="Aランチ">Aランチ</option>
                                    <option value="Bランチ">Bランチ</option>
                                    <option value="冷凍">冷凍</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="riceAmount" class="form-label">ライスの量</label>
                                <select id="riceAmount" class="form-select">
                                    <option value="未選択">未選択</option>
                                    <option value="大盛">大盛</option>
                                    <option value="普通盛">普通盛</option>
                                    <option value="半ライス">半ライス</option>
                                    <option value="おかずのみ">おかずのみ</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="deliveryPlace" class="form-label">配達先</label>
                                <select id="deliveryPlace" class="form-select">
                                    <option value="施設内">施設内</option>
                                    <option value="施設外">施設外</option>
                                </select>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">閉じる</button>
                        <button type="button" id="saveEditOrderButton" class="btn btn-primary"
                            data-action="edit">保存</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- 状態変更ボタン -->
        <div class="text-end">
            <button class="btn btn-success update-status-btn" data-status="消費済み">選択した注文を消費済みに変更</button>
            <button class="btn btn-warning update-status-btn" data-status="ロス" data-bs-toggle="modal"
                data-bs-target="#lossReasonModal">選択した注文をロスに変更</button>
        </div>

        <!-- ロス理由選択モーダル -->
        <div class="modal fade" id="lossReasonModal" tabindex="-1" aria-labelledby="lossReasonModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="lossReasonModalLabel">ロス理由の選択</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="閉じる"></button>
                    </div>
                    <div class="modal-body">
                        <form id="lossReasonForm">
                            <div id="lossReasonList">
                                <!-- 選択された注文に対するロス理由をここに動的に追加 -->
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">閉じる</button>
                        <button type="button" class="btn btn-primary" onclick="saveLossReasons()">保存</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- 注文変更履歴 -->
        <div class="mb-4">
            <h3>当日の注文変更履歴</h3>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>注文者</th>
                        <th>変更者</th>
                        <th>アクション</th>
                        <th>変更内容</th>
                        <th>変更日時</th>
                    </tr>
                </thead>
                <tbody id="orderChangeHistoryBody">
                    <tr>
                        <td colspan="6">データを読み込んでいます...</td>
                    </tr>
                </tbody>
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

        document.addEventListener('DOMContentLoaded', () => {
            loadLayout();
        });
    </script>

</body>

</html>