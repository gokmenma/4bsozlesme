<?php
require_once __DIR__ . '/../core/Model.php';

class UcretsizIzin extends Model {
    protected $table = 'ucretsiz_izin';

    public function __construct() {
        parent::__construct();
        $this->ensureTableExists();
    }

    private function ensureTableExists() {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table} (
            id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            tenant_id BIGINT(20) UNSIGNED NOT NULL,
            personel_id BIGINT(20) UNSIGNED NOT NULL,
            baslangic_tarihi DATE NOT NULL,
            bitis_tarihi DATE NOT NULL,
            gun_sayisi INT NOT NULL,
            aciklama VARCHAR(255) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            deleted_at TIMESTAMP NULL DEFAULT NULL,
            INDEX (tenant_id),
            INDEX (personel_id),
            INDEX (deleted_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        $this->db->exec($sql);
    }

    public function getByPersonnel($personel_id, $tenant_id = null) {
        if ($tenant_id === null) {
            $tenant_id = $_SESSION['tenant_id'] ?? 0;
        }
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE personel_id = ? AND tenant_id = ? AND deleted_at IS NULL ORDER BY baslangic_tarihi DESC");
        $stmt->execute([$personel_id, $tenant_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
