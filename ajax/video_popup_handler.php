<?php
require_once '../includes/config.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

if ($action === 'get_active') {
    try {
        $db = db();
        $popup = $db->query("SELECT * FROM video_popups WHERE is_active = 1 ORDER BY created_at DESC LIMIT 1")->fetch();
        echo json_encode(['success' => true, 'popup' => $popup ?: null]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'popup' => null]);
    }
} elseif ($action === 'increment_view') {
    try {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $db = db();
            $stmt = $db->prepare("UPDATE video_popups SET view_count = view_count + 1 WHERE id = ?");
            $stmt->execute([$id]);
        }
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false]);
    }
} else {
    echo json_encode(['success' => false, 'popup' => null]);
}
