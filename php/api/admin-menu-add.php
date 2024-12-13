<?php
session_start();
require_once '../db.php';

// エラー表示を無効化（JSON出力の前にHTMLが出力されるのを防ぐ）
ini_set('display_errors', 0);
error_reporting(0);

header('Content-Type: application/json');

// admin-menu-add.php の認証チェック部分を修正
if (!isset($_SESSION['user_id'])) {
  echo json_encode(['success' => false, 'message' => '認証されていません。']);
  exit;
}

// POSTメソッドの場合のみ管理者チェック
if ($method === 'POST' && $_SESSION['user_role'] !== '管理者') {
  echo json_encode(['success' => false, 'message' => '権限がありません。']);
  exit;
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    $db = getDbConnection();

    if ($method === 'GET') {
        // 今月と来月のメニューリンク取得
        $today = new DateTime('first day of this month');
        $nextMonth = (new DateTime('first day of this month'))->modify('+1 month');
        
        $query = "
            SELECT target_month, bento_type, link_url
            FROM menu_links
            WHERE target_month IN (?, ?)
            ORDER BY target_month ASC, bento_type ASC
        ";
        
        $stmt = $db->prepare($query);
        $stmt->execute([
            $today->format('Y-m-d'),
            $nextMonth->format('Y-m-d')
        ]);
        
        $links = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'links' => $links]);

    } elseif ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['links']) || !is_array($input['links'])) {
            echo json_encode(['success' => false, 'message' => '不正なデータ形式です。']);
            exit;
        }

        try {
            $db->beginTransaction();

            // 既存のリンクを更新または挿入
            $stmt = $db->prepare("
                INSERT INTO menu_links (target_month, bento_type, link_url)
                VALUES (:target_month, :bento_type, :link_url)
                ON DUPLICATE KEY UPDATE
                link_url = VALUES(link_url)
            ");

            foreach ($input['links'] as $link) {
                // link_urlが空文字列の場合はNULLとして扱う
                $linkUrl = !empty($link['link_url']) ? $link['link_url'] : null;
                
                if ($linkUrl === null) {
                    // リンクが空の場合は該当レコードを削除
                    $deleteStmt = $db->prepare("
                        DELETE FROM menu_links 
                        WHERE target_month = :target_month 
                        AND bento_type = :bento_type
                    ");
                    $deleteStmt->execute([
                        ':target_month' => $link['target_month'],
                        ':bento_type' => $link['bento_type']
                    ]);
                } else {
                    // リンクがある場合は更新または挿入
                    $stmt->execute([
                        ':target_month' => $link['target_month'],
                        ':bento_type' => $link['bento_type'],
                        ':link_url' => $linkUrl
                    ]);
                }
            }

            $db->commit();
            echo json_encode(['success' => true, 'message' => 'メニューリンクを保存しました。']);

        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

} catch (Exception $e) {
    error_log($e->getMessage()); // エラーをログに記録
    echo json_encode([
        'success' => false,
        'message' => 'エラーが発生しました: ' . $e->getMessage()
    ]);
}