<!DOCTYPE html>
<html lang="ja">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Obento System - 今日の注文</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="/public/css/style.css">
</head>

<body>
  <!-- ヘッダーを挿入 -->
  <div id="header"></div>

  <!-- メインコンテンツ -->
  <div class="container mt-4">
    <h1 class="mb-4">今日の注文</h1>
    <div id="orderContent">
      <!-- 注文情報をここに動的に挿入 -->
    </div>

    <div id="noOrder" class="text-center mt-4" style="display: none;">
      <p>本日の注文がありません。</p>
      <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addOrderModal">注文を追加</button>
    </div>
  </div>

  <!-- キャンセル確認モーダル -->
  <div class="modal fade" id="cancelModal" tabindex="-1" aria-labelledby="cancelModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="cancelModalLabel">注文キャンセル確認</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="閉じる"></button>
        </div>
        <div class="modal-body">
          今日の注文をキャンセルします。よろしいでしょうか？
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">戻る</button>
          <button type="button" id="confirmCancel" class="btn btn-danger">確定</button>
        </div>
      </div>
    </div>
  </div>

  <!-- 新規注文モーダル -->
  <div class="modal fade" id="addOrderModal" tabindex="-1" aria-labelledby="addOrderModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="addOrderModalLabel">新規注文を追加</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="閉じる"></button>
        </div>
        <div class="modal-body">
          <form id="addOrderForm">
            <div class="mb-3">
              <label for="newBentoType" class="form-label">お弁当タイプ</label>
              <select id="newBentoType" class="form-select">
                <option value="Aランチ" selected>Aランチ</option>
                <option value="Bランチ">Bランチ</option>
              </select>
            </div>
            <div class="mb-3">
              <label for="newRiceAmount" class="form-label">ライスの量</label>
              <select id="newRiceAmount" class="form-select">
                <option value="大盛">大盛</option>
                <option value="普通盛" selected>普通盛</option>
                <option value="半ライス">半ライス</option>
                <option value="おかずのみ">おかずのみ</option>
              </select>
            </div>
            <div class="mb-3">
              <label for="newDeliveryPlace" class="form-label">配達先</label>
              <select id="newDeliveryPlace" class="form-select">
                <option value="施設内">施設内</option>
                <option value="施設外">施設外</option>
              </select>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">閉じる</button>
          <button type="button" id="saveNewOrder" class="btn btn-primary">保存</button>
        </div>
      </div>
    </div>
  </div>

  <!-- フッターを挿入 -->
  <div id="footer"></div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // レイアウトの読み込み
    async function loadLayout() {
      document.getElementById("header").innerHTML = await fetch("/templates/layouts/header.php").then(res => res.text());
      document.getElementById("footer").innerHTML = await fetch("/templates/layouts/footer.php").then(res => res.text());
    }

    // 今日の注文を取得してフォームを描画
    async function fetchTodayOrder() {
      try {
        const response = await fetch("/php/api/today-order.php", {
          method: "GET"
        });
        const data = await response.json();

        const orderContent = document.getElementById("orderContent");
        const noOrder = document.getElementById("noOrder");

        if (data.success) {
          const order = data.order;
          orderContent.innerHTML = `
        <form id="orderForm">
          <div class="mb-3">
            <label for="bentoType" class="form-label">お弁当タイプ</label>
            <select id="bentoType" name="bento_type" class="form-select">
              <option value="Aランチ" ${order.bento_type === "Aランチ" ? "selected" : ""}>Aランチ</option>
              <option value="Bランチ" ${order.bento_type === "Bランチ" ? "selected" : ""}>Bランチ</option>
            </select>
          </div>
          <div class="mb-3">
            <label for="riceAmount" class="form-label">ライスの量</label>
            <select id="riceAmount" name="rice_amount" class="form-select">
              <option value="大盛" ${order.rice_amount === "大盛" ? "selected" : ""}>大盛</option>
              <option value="普通盛" ${order.rice_amount === "普通盛" ? "selected" : ""}>普通盛</option>
              <option value="半ライス" ${order.rice_amount === "半ライス" ? "selected" : ""}>半ライス</option>
              <option value="おかずのみ" ${order.rice_amount === "おかずのみ" ? "selected" : ""}>おかずのみ</option>
            </select>
          </div>
          <fieldset class="mb-3">
            <legend class="form-label">配達先</legend>
            <div class="d-flex gap-3">
              <div>
                <input type="radio" id="deliveryInside" name="delivery_place" value="施設内" ${order.delivery_place === "施設内" ? "checked" : ""}>
                <label for="deliveryInside">施設内</label>
              </div>
              <div>
                <input type="radio" id="deliveryOutside" name="delivery_place" value="施設外" ${order.delivery_place === "施設外" ? "checked" : ""}>
                <label for="deliveryOutside">施設外</label>
              </div>
            </div>
          </fieldset>
          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">変更</button>
            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#cancelModal">注文をキャンセル</button>
          </div>
        </form>
      `;
          noOrder.style.display = "none";
        } else {
          orderContent.innerHTML = "";
          noOrder.style.display = "block";
        }
      } catch (error) {
        alert("エラーが発生しました。もう一度お試しください。");
      }
    }

    // 注文を変更する関数
    async function handleSubmitOrder(event) {
      event.preventDefault();
      try {
        const bentoType = document.getElementById("bentoType").value;
        const riceAmount = document.getElementById("riceAmount").value;
        const deliveryPlace = document.querySelector('input[name="delivery_place"]:checked').value;

        const orderData = {
          bento_type: bentoType,
          rice_amount: riceAmount,
          delivery_place: deliveryPlace,
        };

        const response = await fetch("/php/api/today-order.php", {
          method: "POST",
          headers: {
            "Content-Type": "application/json"
          },
          body: JSON.stringify(orderData),
        });

        const result = await response.json();
        alert(result.message);
        fetchTodayOrder();
      } catch (error) {
        alert("注文の変更に失敗しました。");
      }
    }

    // 新規注文を保存する関数
    async function handleAddOrder() {
      try {
        const bentoType = document.getElementById("newBentoType").value;
        const riceAmount = document.getElementById("newRiceAmount").value;
        const deliveryPlace = document.getElementById("newDeliveryPlace").value;

        const orderData = {
          bento_type: bentoType,
          rice_amount: riceAmount,
          delivery_place: deliveryPlace
        };

        const response = await fetch("/php/api/today-order.php", {
          method: "POST",
          headers: {
            "Content-Type": "application/json"
          },
          body: JSON.stringify(orderData),
        });

        const result = await response.json();

        if (result.success) {
          alert("新規注文が保存されました！");
          document.getElementById("addOrderForm").reset();
          fetchTodayOrder();

          const addOrderModal = bootstrap.Modal.getInstance(document.getElementById("addOrderModal"));
          addOrderModal.hide();
        } else {
          alert(result.message || "新規注文の保存に失敗しました。");
        }
      } catch (error) {
        alert("エラーが発生しました。もう一度お試しください。");
      }
    }

    // イベントリスナーの設定
    document.addEventListener("DOMContentLoaded", () => {
      loadLayout();
      fetchTodayOrder();

      document.getElementById("confirmCancel").addEventListener("click", async () => {
        const response = await fetch("/php/api/today-order.php", {
          method: "DELETE"
        });
        const result = await response.json();
        alert(result.message);
        const cancelModal = bootstrap.Modal.getInstance(document.getElementById("cancelModal"));
        cancelModal.hide();
        fetchTodayOrder();
      });

      document.getElementById("saveNewOrder").addEventListener("click", handleAddOrder);
      document.getElementById("orderContent").addEventListener("submit", handleSubmitOrder);
    });
  </script>
</body>

</html>