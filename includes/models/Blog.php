<?php
require_once __DIR__ . '/../database.php';

class Blog {

    private static function isPg() {
        return getenv('DATABASE_URL') ? true : false;
    }

    public static function generateSlug($title, $existingId = null) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
        $slug = trim($slug, '-');
        if ($slug === '') $slug = 'blog-' . time();

        $base = $slug;
        $i = 1;
        while (true) {
            $sql = "SELECT id FROM blogs WHERE slug = ?";
            $params = [$slug];
            if ($existingId) {
                $sql .= " AND id <> ?";
                $params[] = $existingId;
            }
            $stmt = db()->prepare($sql . " LIMIT 1");
            $stmt->execute($params);
            if (!$stmt->fetch()) break;
            $i++;
            $slug = $base . '-' . $i;
        }
        return $slug;
    }

    public static function fields() {
        return [
            'title','slug','excerpt','content',
            'featured_image','featured_image_alt',
            'meta_title','meta_description','primary_keyword','canonical_url','robots',
            'faq_json','featured_snippet',
            'content_intent','target_audience','region','language','entity_tags',
            'author_name','author_bio','publish_date','update_date',
            'internal_links','external_links',
            'cta_text','cta_link',
            'status','schedule_date'
        ];
    }

    public static function create($data) {
        $fields = self::fields();
        $cols = implode(',', $fields);
        $place = implode(',', array_fill(0, count($fields), '?'));
        $vals = [];
        foreach ($fields as $f) {
            $v = $data[$f] ?? null;
            if ($v === '') $v = null;
            $vals[] = $v;
        }
        $sql = "INSERT INTO blogs ($cols) VALUES ($place)";
        if (self::isPg()) $sql .= " RETURNING id";

        $stmt = db()->prepare($sql);
        $stmt->execute($vals);

        if (self::isPg()) {
            $row = $stmt->fetch();
            return $row['id'] ?? null;
        }
        return db()->lastInsertId();
    }

    public static function update($id, $data) {
        $fields = self::fields();
        $sets = [];
        $vals = [];
        foreach ($fields as $f) {
            $sets[] = "$f = ?";
            $v = $data[$f] ?? null;
            if ($v === '') $v = null;
            $vals[] = $v;
        }
        $vals[] = $id;
        $sql = "UPDATE blogs SET " . implode(',', $sets) . ", updated_at = CURRENT_TIMESTAMP WHERE id = ?";
        $stmt = db()->prepare($sql);
        return $stmt->execute($vals);
    }

    public static function delete($id) {
        $stmt = db()->prepare("DELETE FROM blogs WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public static function getById($id) {
        $stmt = db()->prepare("SELECT * FROM blogs WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function getBySlug($slug) {
        $stmt = db()->prepare("SELECT * FROM blogs WHERE slug = ? LIMIT 1");
        $stmt->execute([$slug]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function getAll($status = null) {
        $sql = "SELECT * FROM blogs";
        $params = [];
        if ($status) {
            $sql .= " WHERE status = ?";
            $params[] = $status;
        }
        $sql .= " ORDER BY COALESCE(publish_date, created_at::date) DESC, id DESC";
        if (!self::isPg()) {
            $sql = str_replace('created_at::date', 'DATE(created_at)', $sql);
        }
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getPublished($limit = null) {
        $sql = "SELECT * FROM blogs WHERE status = 'published'
                AND (schedule_date IS NULL OR schedule_date <= CURRENT_TIMESTAMP)
                ORDER BY COALESCE(publish_date, ";
        $sql .= self::isPg() ? "created_at::date" : "DATE(created_at)";
        $sql .= ") DESC, id DESC";
        if ($limit) $sql .= " LIMIT " . (int)$limit;
        $stmt = db()->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function incrementViews($id) {
        try {
            $stmt = db()->prepare("UPDATE blogs SET views = COALESCE(views,0) + 1 WHERE id = ?");
            $stmt->execute([$id]);
        } catch (Exception $e) {}
    }

    public static function decodeFaqs($faqJson) {
        if (!$faqJson) return [];
        $d = json_decode($faqJson, true);
        return is_array($d) ? $d : [];
    }

    public static function decodeLinks($txt) {
        if (!$txt) return [];
        $out = [];
        foreach (preg_split("/\r\n|\n|\r/", $txt) as $line) {
            $line = trim($line);
            if ($line === '') continue;
            if (strpos($line, '|') !== false) {
                [$label, $url] = array_map('trim', explode('|', $line, 2));
            } else {
                $label = $line; $url = $line;
            }
            $out[] = ['label' => $label, 'url' => $url];
        }
        return $out;
    }
}
