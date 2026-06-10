<?php
require_once __DIR__ . '/../database.php';

class HomeBanner {
    public static function getAll($onlyActive = false) {
        try {
            $sql = "SELECT * FROM home_banners";
            if ($onlyActive) $sql .= " WHERE is_active = 1";
            $sql .= " ORDER BY display_order ASC, id ASC";
            return db()->query($sql)->fetchAll() ?: [];
        } catch (Exception $e) {
            error_log("HomeBanner::getAll error: " . $e->getMessage());
            return [];
        }
    }

    public static function getById($id) {
        try {
            $stmt = db()->prepare("SELECT * FROM home_banners WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (Exception $e) { return null; }
    }

    public static function create($data) {
        try {
            $stmt = db()->prepare("INSERT INTO home_banners (desktop_image, mobile_image, link_url, display_order, is_active) VALUES (?, ?, ?, ?, ?)");
            return $stmt->execute([
                $data['desktop_image'] ?? '',
                $data['mobile_image'] ?? '',
                $data['link_url'] ?? '',
                (int)($data['display_order'] ?? 0),
                isset($data['is_active']) ? (int)$data['is_active'] : 1
            ]);
        } catch (Exception $e) {
            error_log("HomeBanner::create error: " . $e->getMessage());
            return false;
        }
    }

    public static function update($id, $data) {
        try {
            $sets = ["link_url = ?", "display_order = ?", "is_active = ?"];
            $params = [$data['link_url'] ?? '', (int)($data['display_order'] ?? 0), isset($data['is_active']) ? (int)$data['is_active'] : 1];
            if (!empty($data['desktop_image'])) { $sets[] = "desktop_image = ?"; $params[] = $data['desktop_image']; }
            if (!empty($data['mobile_image'])) { $sets[] = "mobile_image = ?"; $params[] = $data['mobile_image']; }
            $params[] = $id;
            $stmt = db()->prepare("UPDATE home_banners SET " . implode(', ', $sets) . " WHERE id = ?");
            return $stmt->execute($params);
        } catch (Exception $e) {
            error_log("HomeBanner::update error: " . $e->getMessage());
            return false;
        }
    }

    public static function delete($id) {
        try {
            $stmt = db()->prepare("DELETE FROM home_banners WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (Exception $e) { return false; }
    }
}
