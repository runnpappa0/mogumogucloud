<?php
session_start();
require_once __DIR__ . '/../config/constants.php';  // 先に定数ファイルを読み込む
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../common/DateTimeUtils.php';
require_once __DIR__ . '/../common/OrderDeadlineUtils.php';

use MoguMogu\Common\DateTimeUtils;
use MoguMogu\Common\OrderDeadlineUtils;
use MoguMogu\Config\TimeConstants;

header('Content-Type: application/json');

// セッションにユーザーIDがない場合は認証エラー
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => '認証されていません。']);
    exit;
}

$user_id = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

// 変更回数チェック用の関数
function checkChangeLimit($db, $userId)
{
    // 管理者は制限なし
    if ($_SESSION['user_role'] === '管理者') {
        return true;
    }

    try {
        $db->beginTransaction();

        // 現在の変更回数情報を取得（行ロック）
        $stmt = $db->prepare("SELECT daily_change_count, last_change_date FROM users WHERE id = ? FOR UPDATE");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        $today = date('Y-m-d');

        // 日付が変わっていれば回数をリセット
        if ($user['last_change_date'] !== $today) {
            $stmt = $db->prepare("UPDATE users SET daily_change_count = 0, last_change_date = ? WHERE id = ?");
            $stmt->execute([$today, $userId]);
            $user['daily_change_count'] = 0;
        }

        // 変更可能かチェック
        if ($user['daily_change_count'] >= 2) {
            $db->rollBack();
            return false;
        }

        $db->commit();
        return true;
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
}

// 変更回数をインクリメントする関数
function incrementChangeCount($db, $userId)
{
    if ($_SESSION['user_role'] === '管理者') {
        return true;
    }

    $stmt = $db->prepare("
        UPDATE users 
        SET daily_change_count = daily_change_count + 1,
            last_change_date = CURRENT_DATE
        WHERE id = ?
    ");
    return $stmt->execute([$userId]);
}

// 履歴記録用関数
function logOrderChange($db, $orderId, $changerId, $userId, $action, $details)
{
    $stmt = $db->prepare("
        INSERT INTO order_change_logs (order_id, changer_id, user_id, action, change_detail, change_time)
        VALUES (:order_id, :changer_id, :user_id, :action, :change_detail, NOW())
    ");
    $stmt->execute([
        ':order_id' => $orderId,
        ':changer_id' => $changerId,
        ':user_id' => $userId,
        ':action' => $action,
        ':change_detail' => json_encode($details, JSON_UNESCAPED_UNICODE),
    ]);
}

try {
    $db = getDbConnection();

    // today-order.php の GET メソッド処理部分
    if ($method === 'GET') {
        // 対象日付を取得
        $targetDate = DateTimeUtils::getTargetDate();

        // 注文の編集可否をチェック
        $isEditable = OrderDeadlineUtils::isOrderEditable($targetDate);
        $remainingTime = OrderDeadlineUtils::getRemainingTime($targetDate);

        // ユーザー情報を取得
        $userQuery = 'SELECT can_change_delivery, default_delivery_place FROM users WHERE id = :user_id';
        $stmt = $db->prepare($userQuery);
        $stmt->execute([':user_id' => $user_id]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);

        // 注文情報を取得
        $query = 'SELECT * FROM bento_orders WHERE user_id = :user_id AND order_date = :order_date';
        $stmt = $db->prepare($query);
        $stmt->execute([':user_id' => $user_id, ':order_date' => $targetDate]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        // 変更可能回数の情報を取得
        $canChange = checkChangeLimit($db, $user_id);
        $query = 'SELECT daily_change_count, last_change_date FROM users WHERE id = :user_id';
        $stmt = $db->prepare($query);
        $stmt->execute([':user_id' => $user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // 残り変更回数を計算
        $remainingChanges = 2;
        if ($user['last_change_date'] === date('Y-m-d')) {
            $remainingChanges = 2 - $user['daily_change_count'];
        }

        // レスポンスを返す
        if ($order) {
            echo json_encode([
                'success' => true,
                'order' => $order,
                'remainingChanges' => $remainingChanges,
                'isEditable' => $isEditable && $canChange,
                'remainingTime' => $remainingTime,
                'user_can_change_delivery' => (bool)$userData['can_change_delivery'],
                'default_delivery_place' => $userData['default_delivery_place'],
                'targetDate' => DateTimeUtils::formatTargetDate($targetDate)
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => '注文がありません。',
                'remainingChanges' => $remainingChanges,
                'isEditable' => $isEditable && $canChange,
                'remainingTime' => $remainingTime,
                'user_can_change_delivery' => (bool)$userData['can_change_delivery'],
                'default_delivery_place' => $userData['default_delivery_place'],
                'targetDate' => DateTimeUtils::formatTargetDate($targetDate)
            ]);
        }
    } elseif ($method === 'POST') {
        $targetDate = DateTimeUtils::getTargetDate();
        
        // 編集可能かチェック
        if (!OrderDeadlineUtils::isOrderEditable($targetDate)) {
            http_response_code(403);
            echo json_encode([
                'success' => false, 
                'message' => '注文の変更締め切り時間を過ぎています。'
            ]);
            exit;
        }

        // 変更回数チェック
        if (!checkChangeLimit($db, $user_id)) {
            echo json_encode(['success' => false, 'message' => '本日の変更可能回数（2回）を超えています。']);
            exit;
        }

        // JSONデータの受け取りと検証
        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input || empty($input['bento_type']) || empty($input['rice_amount']) || empty($input['delivery_place'])) {
            echo json_encode(['success' => false, 'message' => '入力データが不正です。']);
            exit;
        }

        $bento_type = $input['bento_type'];
        $rice_amount = $input['rice_amount'];
        $delivery_place = $input['delivery_place'];

        try {
            $db->beginTransaction();

            // 本日の注文が存在するか確認
            $query = 'SELECT * FROM bento_orders WHERE user_id = :user_id AND order_date = :order_date FOR UPDATE';
            $stmt = $db->prepare($query);
            $stmt->execute([':user_id' => $user_id, ':order_date' => $targetDate]);
            $existingOrder = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existingOrder) {
                // 変更有無をチェック
                if (
                    $bento_type === $existingOrder['bento_type'] &&
                    $rice_amount === $existingOrder['rice_amount'] &&
                    $delivery_place === $existingOrder['delivery_place']
                ) {
                    $db->rollBack();
                    echo json_encode(['success' => true, 'message' => '注文を更新しました。']);
                    exit;
                }

                // 更新
                $query = 'UPDATE bento_orders 
                          SET bento_type = :bento_type, rice_amount = :rice_amount, delivery_place = :delivery_place, updated_at = NOW()
                          WHERE id = :id';
                $stmt = $db->prepare($query);
                $stmt->execute([
                    ':bento_type' => $bento_type,
                    ':rice_amount' => $rice_amount,
                    ':delivery_place' => $delivery_place,
                    ':id' => $existingOrder['id'],
                ]);

                logOrderChange($db, $existingOrder['id'], $user_id, $user_id, '更新', [
                    'bento_type' => ['before' => $existingOrder['bento_type'], 'after' => $bento_type],
                    'rice_amount' => ['before' => $existingOrder['rice_amount'], 'after' => $rice_amount],
                    'delivery_place' => ['before' => $existingOrder['delivery_place'], 'after' => $delivery_place],
                ]);

                // 変更回数をインクリメント
                incrementChangeCount($db, $user_id);

                $db->commit();
                echo json_encode(['success' => true, 'message' => '注文を更新しました。']);
            } else {
                // 新規追加
                $query = 'INSERT INTO bento_orders (user_id, order_date, bento_type, rice_amount, delivery_place, created_at, updated_at)
                          VALUES (:user_id, :order_date, :bento_type, :rice_amount, :delivery_place, NOW(), NOW())';
                $stmt = $db->prepare($query);
                $stmt->execute([
                    ':user_id' => $user_id,
                    ':order_date' => $targetDate,
                    ':bento_type' => $bento_type,
                    ':rice_amount' => $rice_amount,
                    ':delivery_place' => $delivery_place,
                ]);

                $newOrderId = $db->lastInsertId();
                logOrderChange($db, $newOrderId, $user_id, $user_id, '新規追加', [
                    'bento_type' => $bento_type,
                    'rice_amount' => $rice_amount,
                    'delivery_place' => $delivery_place,
                ]);

                // 変更回数をインクリメント
                incrementChangeCount($db, $user_id);

                $db->commit();
                echo json_encode(['success' => true, 'message' => '注文を追加しました。']);
            }
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    } elseif ($method === 'DELETE') {
        $targetDate = DateTimeUtils::getTargetDate();
        
        // 編集可能かチェック
        if (!OrderDeadlineUtils::isOrderEditable($targetDate)) {
            http_response_code(403);
            echo json_encode([
                'success' => false, 
                'message' => '注文の変更締め切り時間を過ぎています。'
            ]);
            exit;
        }
        
        // 変更回数チェック
        if (!checkChangeLimit($db, $user_id)) {
            echo json_encode(['success' => false, 'message' => '本日の変更可能回数（2回）を超えています。']);
            exit;
        }

        try {
            $db->beginTransaction();

            // 注文をキャンセル
            $query = 'SELECT * FROM bento_orders WHERE user_id = :user_id AND order_date = :order_date FOR UPDATE';
            $stmt = $db->prepare($query);
            $stmt->execute([':user_id' => $user_id, ':order_date' => $targetDate]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($order) {
                logOrderChange($db, $order['id'], $user_id, $user_id, 'キャンセル', [
                    'bento_type' => $order['bento_type'],
                    'rice_amount' => $order['rice_amount'],
                    'delivery_place' => $order['delivery_place'],
                ]);

                $query = 'DELETE FROM bento_orders WHERE id = :id';
                $stmt = $db->prepare($query);
                $stmt->execute([':id' => $order['id']]);

                // 変更回数をインクリメント
                incrementChangeCount($db, $user_id);

                $db->commit();
                echo json_encode(['success' => true, 'message' => '注文をキャンセルしました。']);
            } else {
                $db->rollBack();
                echo json_encode(['success' => false, 'message' => 'キャンセルする注文が見つかりませんでした。']);
            }
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    } else {
        echo json_encode(['success' => false, 'message' => '不正なリクエストです。']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'エラーが発生しました: ' . $e->getMessage()]);
}
