<?php
require_once __DIR__ . '/../database.php';

class FAQ {
    public static function getAll($onlyActive = false) {
        try {
            $sql = "SELECT * FROM faqs";
            if ($onlyActive) $sql .= " WHERE is_active = 1";
            $sql .= " ORDER BY display_order ASC, id ASC";
            return db()->query($sql)->fetchAll() ?: [];
        } catch (Exception $e) {
            error_log("FAQ::getAll error: " . $e->getMessage());
            return [];
        }
    }

    public static function getById($id) {
        try {
            $stmt = db()->prepare("SELECT * FROM faqs WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (Exception $e) { return null; }
    }

    public static function create($data) {
        try {
            $stmt = db()->prepare("INSERT INTO faqs (question, answer, display_order, is_active) VALUES (?, ?, ?, ?)");
            return $stmt->execute([
                $data['question'],
                $data['answer'] ?? '',
                (int)($data['display_order'] ?? 0),
                isset($data['is_active']) ? (int)$data['is_active'] : 1
            ]);
        } catch (Exception $e) {
            error_log("FAQ::create error: " . $e->getMessage());
            return false;
        }
    }

    public static function update($id, $data) {
        try {
            $stmt = db()->prepare("UPDATE faqs SET question = ?, answer = ?, display_order = ?, is_active = ? WHERE id = ?");
            return $stmt->execute([
                $data['question'],
                $data['answer'] ?? '',
                (int)($data['display_order'] ?? 0),
                isset($data['is_active']) ? (int)$data['is_active'] : 1,
                $id
            ]);
        } catch (Exception $e) {
            error_log("FAQ::update error: " . $e->getMessage());
            return false;
        }
    }

    public static function delete($id) {
        try {
            $stmt = db()->prepare("DELETE FROM faqs WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (Exception $e) { return false; }
    }
}
