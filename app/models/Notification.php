<?php
require_once __DIR__ . '/../core/Model.php';

/**
 * Genel Bildirim Modeli
 * Kullanıcıya yönelik bildirimleri tutar (geri bildirim güncellemeleri, sistem
 * mesajları vb.). Kanban/Feedback modülüyle aynı yaklaşımla tabloyu ilk
 * erişimde otomatik oluşturur.
 */
class Notification extends Model {
    protected $table = 'notifications';

    public function __construct() {
        parent::__construct();
        $this->ensureTableExists();
    }

    private function ensureTableExists() {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table} (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            tenant_id INT DEFAULT NULL,
            type VARCHAR(40) NOT NULL DEFAULT 'system',
            title VARCHAR(255) NOT NULL,
            body TEXT DEFAULT NULL,
            link VARCHAR(512) DEFAULT NULL,
            is_read TINYINT(1) NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX (user_id),
            INDEX (is_read)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $this->db->exec($sql);
    }

    /**
     * Yeni bildirim oluşturur.
     */
    public function notify(int $user_id, string $title, string $body = '', string $type = 'system', ?string $link = null, ?int $tenant_id = null): int {
        return (int)$this->create([
            'user_id'   => $user_id,
            'tenant_id' => $tenant_id,
            'type'      => $type,
            'title'     => $title,
            'body'      => $body !== '' ? $body : null,
            'link'      => $link,
            'is_read'   => 0,
        ]);
    }

    /**
     * Kullanıcının bildirimlerini (en yeni önce) getirir.
     */
    public function getForUser(int $user_id, int $limit = 20): array {
        $limit = max(1, min(200, $limit));
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->table} WHERE user_id = ? ORDER BY id DESC LIMIT {$limit}"
        );
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Okunmamış bildirim sayısı.
     */
    public function countUnread(int $user_id): int {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM {$this->table} WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$user_id]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Tek bir bildirimi okundu işaretler (sahibi doğrulanarak).
     */
    public function markRead(int $id, int $user_id): bool {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET is_read = 1 WHERE id = ? AND user_id = ?");
        return $stmt->execute([$id, $user_id]);
    }

    /**
     * Kullanıcının tüm bildirimlerini okundu işaretler.
     */
    public function markAllRead(int $user_id): bool {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET is_read = 1 WHERE user_id = ? AND is_read = 0");
        return $stmt->execute([$user_id]);
    }
}
