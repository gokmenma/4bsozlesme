<?php
require_once __DIR__ . '/../core/Model.php';

class Feedback extends Model {
    protected $table = 'feedbacks';

    /** Geçerli geri bildirim türleri */
    public const TYPES = ['bug', 'suggestion', 'other'];
    /** Geçerli durumlar */
    public const STATUSES = ['new', 'in_progress', 'resolved'];

    public function __construct() {
        parent::__construct();
        $this->ensureTableExists();
    }

    /**
     * Otomatik veritabanı tablosu kontrolü ve oluşturulması
     * (Kanban modülündeki yaklaşımla aynı: ilk erişimde tabloyu garanti eder.)
     */
    private function ensureTableExists() {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table} (
            id INT AUTO_INCREMENT PRIMARY KEY,
            tenant_id INT DEFAULT NULL,
            user_id INT NOT NULL,
            type ENUM('bug', 'suggestion', 'other') NOT NULL DEFAULT 'suggestion',
            subject VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            page_url VARCHAR(512) DEFAULT NULL,
            status ENUM('new', 'in_progress', 'resolved') NOT NULL DEFAULT 'new',
            admin_note TEXT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX (tenant_id),
            INDEX (user_id),
            INDEX (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $this->db->exec($sql);
    }

    /**
     * Tüm geri bildirimleri (gönderen kullanıcı ve kurum bilgisiyle) getirir.
     * Yalnızca superadmin paneli içindir.
     *
     * @param string $statusFilter 'all' veya geçerli bir durum
     */
    public function getAllWithRelations(string $statusFilter = 'all'): array {
        $sql = "SELECT f.*, u.name AS user_name, u.email AS user_email, t.name AS tenant_name
                FROM {$this->table} f
                LEFT JOIN users u ON f.user_id = u.id
                LEFT JOIN tenants t ON f.tenant_id = t.id";

        $params = [];
        if ($statusFilter !== 'all' && in_array($statusFilter, self::STATUSES, true)) {
            $sql .= " WHERE f.status = ?";
            $params[] = $statusFilter;
        }

        $sql .= " ORDER BY FIELD(f.status, 'new', 'in_progress', 'resolved'), f.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Durumlara göre adet döner: ['all' => x, 'new' => x, 'in_progress' => x, 'resolved' => x]
     */
    public function countsByStatus(): array {
        $counts = ['all' => 0, 'new' => 0, 'in_progress' => 0, 'resolved' => 0];
        $stmt = $this->db->query("SELECT status, COUNT(*) AS cnt FROM {$this->table} GROUP BY status");
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $counts[$row['status']] = (int)$row['cnt'];
            $counts['all'] += (int)$row['cnt'];
        }
        return $counts;
    }

    /**
     * Yalnızca durum (status) alanını günceller.
     */
    public function updateStatus(int $id, string $status): bool {
        if (!in_array($status, self::STATUSES, true)) {
            return false;
        }
        $stmt = $this->db->prepare("UPDATE {$this->table} SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $id]);
    }

    /**
     * Yöneticinin yanıtını (admin_note) günceller.
     */
    public function updateNote(int $id, string $note): bool {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET admin_note = ? WHERE id = ?");
        return $stmt->execute([$note, $id]);
    }
}
