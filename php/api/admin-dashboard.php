<?php
session_start();
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../common/DateTimeUtils.php';
require_once __DIR__ . '/../db.php';

use MoguMogu\Common\DateTimeUtils;

header('Content-Type: application/json');

// 管理者チェック
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== '管理者') {
    echo json_encode(['success' => false, 'message' => '認証されていません。']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// ヘルパー関数: 注文変更履歴を記録
function logOrderChange($db, ?int $order_id, int $user_id, string $action, array $change_detail)
{
    $query = "
        INSERT INTO order_change_logs 
        (order_id, changer_id, user_id, action, change_detail)
        VALUES (:order_id, :changer_id, :user_id, :action, :change_detail)
    ";

    $stmt = $db->prepare($query);
    return $stmt->execute([
        ':order_id' => $order_id,
        ':changer_id' => $_SESSION['user_id'],  // 現在ログイン中の管理者ID
        ':user_id' => $user_id,
        ':action' => $action,
        ':change_detail' => json_encode($change_detail, JSON_UNESCAPED_UNICODE)
    ]);
}

try {
    $db = getDbConnection();

    if ($method === 'GET') {
        // 対象日付を取得（15:00以降は翌日の注文を表示）
        $targetDate = DateTimeUtils::getTargetDate();

        if ($action === 'fetch_order_counts') {
            // 対象日付を取得（金曜日15:00以降は翌週月曜日）
            $targetDate = DateTimeUtils::getTargetDate();
            $formattedDate = DateTimeUtils::formatTargetDate($targetDate);

            // 配達先別の必要発注数を取得
            $query = "
                SELECT 
                    delivery_place,
                    bento_type,
                    rice_amount,
                    COUNT(*) AS count
                FROM bento_orders
                WHERE order_date = :target_date
                GROUP BY delivery_place, bento_type, rice_amount
            ";

            try {
                $stmt = $db->prepare($query);
                $stmt->execute([':target_date' => $targetDate]);
                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // レスポンスデータの構造化
                $data = [
                    'target_date' => $targetDate,
                    'formatted_date' => $formattedDate,
                    'facility_inside' => [],
                    'facility_outside' => [],
                    'frozen' => ['facility_inside' => 0, 'facility_outside' => 0],
                ];

                foreach ($result as $row) {
                    $isFrozen = $row['bento_type'] === '冷凍';
                    $placeKey = $row['delivery_place'] === '施設内' ? 'facility_inside' : 'facility_outside';

                    if ($isFrozen) {
                        $data['frozen'][$placeKey] += $row['count'];
                    } else {
                        if (!isset($data[$placeKey][$row['bento_type']])) {
                            $data[$placeKey][$row['bento_type']] = [];
                        }
                        $data[$placeKey][$row['bento_type']][$row['rice_amount']] = $row['count'];
                    }
                }

                echo json_encode([
                    'success' => true,
                    'data' => $data,
                    'target_date' => $targetDate,
                    'formatted_date' => $formattedDate
                ]);
            } catch (PDOException $e) {
                echo json_encode([
                    'success' => false,
                    'message' => 'データベースエラーが発生しました。',
                    'error' => $e->getMessage()
                ]);
            }
        } elseif ($action === 'fetch_orders') {
            // 注文内訳を取得（日付を動的に設定）
            $query = "
                SELECT 
                    bo.id AS order_id,
                    u.name AS user_name,
                    bo.bento_type,
                    bo.rice_amount,
                    bo.delivery_place,
                    bo.status
                FROM bento_orders AS bo
                JOIN users AS u ON bo.user_id = u.id
                WHERE bo.order_date = :target_date
                ORDER BY 
                    CASE u.role 
                        WHEN '利用者' THEN 1
                        WHEN 'スタッフ' THEN 2 
                        ELSE 3 
                    END,
                    CASE bo.delivery_place 
                        WHEN '施設内' THEN 1 
                        WHEN '施設外' THEN 2 
                        ELSE 3 
                    END,
                    u.name ASC
            ";
            $stmt = $db->prepare($query);
            $stmt->execute([':target_date' => $targetDate]);
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'orders' => $orders,
                'target_date' => $targetDate,
                'formatted_date' => DateTimeUtils::formatTargetDate($targetDate)
            ]);
        } elseif ($action === 'fetch_users') {
            // ユーザーリストを取得
            $query = "
                SELECT id, name
                FROM users
                WHERE role != '管理者'
                ORDER BY 
                    FIELD(role, '利用者', 'スタッフ'),
                    name ASC
            ";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['success' => true, 'users' => $users]);
        } elseif ($action === 'fetch_order_details') {
            // 特定注文の詳細を取得
            $orderId = $_GET['order_id'] ?? null;
            if (!$orderId) {
                echo json_encode(['success' => false, 'message' => '注文IDが指定されていません。']);
                exit;
            }

            $query = "
                SELECT 
                    bento_type,
                    rice_amount,
                    delivery_place
                FROM bento_orders
                WHERE id = :order_id
            ";
            $stmt = $db->prepare($query);
            $stmt->execute([':order_id' => $orderId]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$order) {
                echo json_encode(['success' => false, 'message' => '指定された注文が見つかりません。']);
                exit;
            }

            echo json_encode(['success' => true, 'order' => $order]);
        }
    } elseif ($method === 'POST') {
        $targetDate = DateTimeUtils::getTargetDate();

        if ($action === 'add_order') {
            // 注文を追加
            $input = json_decode(file_get_contents('php://input'), true);

            if (empty($input['user_id']) || empty($input['bento_type']) || empty($input['delivery_place'])) {
                echo json_encode(['success' => false, 'message' => '必要なデータが不足しています。']);
                exit;
            }

            try {
                $db->beginTransaction();

                // 注文を追加
                $query = "
                    INSERT INTO bento_orders 
                    (user_id, bento_type, rice_amount, delivery_place, order_date)
                    VALUES 
                    (:user_id, :bento_type, :rice_amount, :delivery_place, :target_date)
                ";
                $stmt = $db->prepare($query);
                $stmt->execute([
                    ':user_id' => $input['user_id'],
                    ':bento_type' => $input['bento_type'],
                    ':rice_amount' => $input['rice_amount'] ?? null,
                    ':delivery_place' => $input['delivery_place'],
                    ':target_date' => $targetDate
                ]);

                $orderId = $db->lastInsertId();

                // 変更履歴を記録
                $changeDetail = [
                    'bento_type' => $input['bento_type'],
                    'rice_amount' => $input['rice_amount'] ?? null,
                    'delivery_place' => $input['delivery_place']
                ];
                logOrderChange($db, $orderId, $input['user_id'], '新規追加', $changeDetail);

                $db->commit();
                echo json_encode(['success' => true, 'message' => '注文を新規作成しました。']);
            } catch (Exception $e) {
                $db->rollBack();
                echo json_encode(['success' => false, 'message' => 'エラーが発生しました：' . $e->getMessage()]);
            }
        } elseif ($action === 'update_order') {
            // 注文を更新
            $input = json_decode(file_get_contents('php://input'), true);

            if (empty($input['order_id']) || empty($input['bento_type']) || empty($input['delivery_place'])) {
                echo json_encode(['success' => false, 'message' => '必要なデータが不足しています。']);
                exit;
            }

            try {
                $db->beginTransaction();

                // 更新前の注文情報を取得
                $stmt = $db->prepare("SELECT * FROM bento_orders WHERE id = ?");
                $stmt->execute([$input['order_id']]);
                $oldOrder = $stmt->fetch(PDO::FETCH_ASSOC);

                // 注文を更新
                $query = "
                    UPDATE bento_orders
                    SET bento_type = :bento_type, rice_amount = :rice_amount, delivery_place = :delivery_place
                    WHERE id = :order_id
                ";
                $stmt = $db->prepare($query);
                $stmt->execute([
                    ':bento_type' => $input['bento_type'],
                    ':rice_amount' => $input['rice_amount'] ?? null,
                    ':delivery_place' => $input['delivery_place'],
                    ':order_id' => $input['order_id']
                ]);

                // 変更履歴を記録
                $changeDetail = [
                    'bento_type' => [
                        'before' => $oldOrder['bento_type'],
                        'after' => $input['bento_type']
                    ],
                    'rice_amount' => [
                        'before' => $oldOrder['rice_amount'],
                        'after' => $input['rice_amount'] ?? null
                    ],
                    'delivery_place' => [
                        'before' => $oldOrder['delivery_place'],
                        'after' => $input['delivery_place']
                    ]
                ];
                logOrderChange($db, $input['order_id'], $oldOrder['user_id'], '更新', $changeDetail);

                $db->commit();
                echo json_encode(['success' => true, 'message' => '注文を更新しました。']);
            } catch (Exception $e) {
                $db->rollBack();
                echo json_encode(['success' => false, 'message' => 'エラーが発生しました：' . $e->getMessage()]);
            }
        }
    } elseif ($method === 'DELETE') {
        if ($action === 'delete_order') {
            // 注文を削除
            $orderId = $_GET['order_id'] ?? null;
            if (!$orderId) {
                echo json_encode(['success' => false, 'message' => '注文IDが必要です。']);
                exit;
            }

            try {
                $db->beginTransaction();

                // 削除前の注文情報を取得
                $stmt = $db->prepare("SELECT * FROM bento_orders WHERE id = ?");
                $stmt->execute([$orderId]);
                $oldOrder = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$oldOrder) {
                    throw new Exception('注文が見つかりません。');
                }

                // 変更履歴を記録（注文削除前に実行）
                $changeDetail = [
                    'bento_type' => $oldOrder['bento_type'],
                    'rice_amount' => $oldOrder['rice_amount'],
                    'delivery_place' => $oldOrder['delivery_place']
                ];

                // 変更履歴の記録
                $logQuery = "
                    INSERT INTO order_change_logs 
                    (order_id, changer_id, user_id, action, change_detail)
                    VALUES (:order_id, :changer_id, :user_id, :action, :change_detail)
                ";
                $logStmt = $db->prepare($logQuery);
                $logStmt->execute([
                    ':order_id' => $orderId,
                    ':changer_id' => $_SESSION['user_id'], // セッションから管理者のIDを取得
                    ':user_id' => $oldOrder['user_id'],
                    ':action' => 'キャンセル',
                    ':change_detail' => json_encode($changeDetail, JSON_UNESCAPED_UNICODE)
                ]);

                // 注文を削除
                $deleteQuery = "DELETE FROM bento_orders WHERE id = :order_id";
                $deleteStmt = $db->prepare($deleteQuery);
                $deleteStmt->execute([':order_id' => $orderId]);

                $db->commit();
                echo json_encode(['success' => true, 'message' => '注文を削除しました。']);
            } catch (Exception $e) {
                $db->rollBack();
                echo json_encode(['success' => false, 'message' => 'エラーが発生しました：' . $e->getMessage()]);
            }
        }
    } else {
        echo json_encode(['success' => false, 'message' => '不正なリクエストです。']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'エラーが発生しました: ' . $e->getMessage()]);
}
