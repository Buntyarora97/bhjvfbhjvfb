<?php
require_once __DIR__ . '/../database.php';

class GoogleReview {
    public static function getAll($onlyActive = false) {
        try {
            $sql = "SELECT * FROM google_reviews";
            if ($onlyActive) $sql .= " WHERE is_active = 1";
            $sql .= " ORDER BY display_order ASC, id ASC";
            return db()->query($sql)->fetchAll() ?: [];
        } catch (Exception $e) {
            error_log("GoogleReview::getAll error: " . $e->getMessage());
            return [];
        }
    }

    public static function getById($id) {
        try {
            $stmt = db()->prepare("SELECT * FROM google_reviews WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (Exception $e) { return null; }
    }

    public static function create($data) {
        try {
            $stmt = db()->prepare("INSERT INTO google_reviews (reviewer_name, reviewer_img, review_date, rating, review_text, display_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)");
            return $stmt->execute([
                $data['reviewer_name'],
                $data['reviewer_img'] ?? '',
                $data['review_date'] ?? date('M d, Y'),
                (int)($data['rating'] ?? 5),
                $data['review_text'] ?? '',
                (int)($data['display_order'] ?? 0),
                isset($data['is_active']) ? (int)$data['is_active'] : 1
            ]);
        } catch (Exception $e) {
            error_log("GoogleReview::create error: " . $e->getMessage());
            return false;
        }
    }

    public static function update($id, $data) {
        try {
            $sets = ["reviewer_name = ?", "review_date = ?", "rating = ?", "review_text = ?", "display_order = ?", "is_active = ?"];
            $params = [$data['reviewer_name'], $data['review_date'] ?? '', (int)($data['rating'] ?? 5), $data['review_text'] ?? '', (int)($data['display_order'] ?? 0), isset($data['is_active']) ? (int)$data['is_active'] : 1];
            if (!empty($data['reviewer_img'])) { $sets[] = "reviewer_img = ?"; $params[] = $data['reviewer_img']; }
            $params[] = $id;
            $stmt = db()->prepare("UPDATE google_reviews SET " . implode(', ', $sets) . " WHERE id = ?");
            return $stmt->execute($params);
        } catch (Exception $e) {
            error_log("GoogleReview::update error: " . $e->getMessage());
            return false;
        }
    }

    public static function delete($id) {
        try {
            $stmt = db()->prepare("DELETE FROM google_reviews WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (Exception $e) { return false; }
    }
}
