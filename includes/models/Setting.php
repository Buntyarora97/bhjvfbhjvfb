<?php
require_once __DIR__ . '/../database.php';

class Setting {
    /**
     * Returns key/value column names based on DB driver.
     * PostgreSQL: key, value
     * MySQL (Hostinger): setting_key, setting_value
     */
    private static function cols() {
        $driver = '';
        try { $driver = db()->getAttribute(PDO::ATTR_DRIVER_NAME); } catch (Exception $e) {}
        if ($driver === 'pgsql') {
            return ['"key"', 'value'];
        } else {
            return ['`setting_key`', 'setting_value'];
        }
    }

    public static function get($key, $default = null) {
        try {
            [$kCol, $vCol] = self::cols();
            $stmt = db()->prepare("SELECT $vCol FROM settings WHERE $kCol = ?");
            $stmt->execute([$key]);
            $result = $stmt->fetch();
            return $result ? $result[$vCol] : $default;
        } catch (Exception $e) {
            return $default;
        }
    }

    public static function set($key, $value) {
        try {
            [$kCol, $vCol] = self::cols();
            $stmt = db()->prepare("SELECT id FROM settings WHERE $kCol = ?");
            $stmt->execute([$key]);
            if ($stmt->fetch()) {
                $stmt = db()->prepare("UPDATE settings SET $vCol = ? WHERE $kCol = ?");
                return $stmt->execute([$value, $key]);
            } else {
                $stmt = db()->prepare("INSERT INTO settings ($kCol, $vCol) VALUES (?, ?)");
                return $stmt->execute([$key, $value]);
            }
        } catch (Exception $e) {
            error_log("Setting::set error for [$key]: " . $e->getMessage());
            return false;
        }
    }

    public static function getAll() {
        try {
            [$kCol, $vCol] = self::cols();
            $stmt = db()->query("SELECT $kCol AS k, $vCol AS v FROM settings");
            $out = [];
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $out[$row['k']] = $row['v'];
            }
            return $out;
        } catch (Exception $e) {
            error_log("Setting::getAll error: " . $e->getMessage());
            return [];
        }
    }
}
