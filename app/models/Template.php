<?php
require_once __DIR__ . '/../core/Model.php';

class Template extends Model {
    protected $table = 'contract_templates';

    public function __construct() {
        parent::__construct();
        $this->ensureTableExists();
    }

    private function ensureTableExists() {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table} (
            id INT AUTO_INCREMENT PRIMARY KEY,
            tenant_id INT NOT NULL,
            name VARCHAR(255) NOT NULL,
            content LONGTEXT,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX (tenant_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        $this->db->exec($sql);
        
        // Mevcut tablo varsa ve tenant_id kolonu yoksa ekle
        try {
            $this->db->query("SELECT tenant_id FROM {$this->table} LIMIT 1");
        } catch (Exception $e) {
            // Tabloyu güncelle ve varsayılan tenant_id ata
            $this->db->exec("ALTER TABLE {$this->table} ADD COLUMN tenant_id INT NOT NULL DEFAULT 1 AFTER id");
            $this->db->exec("CREATE INDEX idx_tenant_id ON {$this->table}(tenant_id)");
        }

        // has_border kolonu kontrolü ve eklenmesi
        try {
            $this->db->query("SELECT has_border FROM {$this->table} LIMIT 1");
        } catch (Exception $e) {
            $this->db->exec("ALTER TABLE {$this->table} ADD COLUMN has_border TINYINT(1) DEFAULT 0 AFTER content");
        }

        // is_default kolonu kontrolü ve eklenmesi
        try {
            $this->db->query("SELECT is_default FROM {$this->table} LIMIT 1");
        } catch (Exception $e) {
            $this->db->exec("ALTER TABLE {$this->table} ADD COLUMN is_default TINYINT(1) DEFAULT 0 AFTER has_border");
        }
    }
    
    public function getLatest($tenant_id = null) {
        if (!$tenant_id) $tenant_id = $_SESSION['tenant_id'] ?? 0;
        
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE tenant_id = ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$tenant_id]);
        return $stmt->fetch();
    }

    public function getDefaultTemplate() {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE is_default = 1 ORDER BY id DESC LIMIT 1");
        $stmt->execute();
        return $stmt->fetch();
    }
    
    public function saveTemplate($name, $content, $has_border = 0, $tenant_id = null) {
        if (!$tenant_id) $tenant_id = $_SESSION['tenant_id'] ?? 0;
        
        $is_default = 0;
        if (isset($_SESSION['role']) && $_SESSION['role'] === 'superadmin') {
            $is_default = 1;
            // Diğer tüm şablonların is_default değerini 0 yap
            $this->db->exec("UPDATE {$this->table} SET is_default = 0");
        }

        $existing = $this->getLatest($tenant_id);
        if ($existing) {
            return $this->update($existing['id'], [
                'name' => $name,
                'content' => $content,
                'has_border' => $has_border,
                'is_default' => $is_default,
                'tenant_id' => $tenant_id
            ]);
        } else {
            return $this->create([
                'name' => $name,
                'content' => $content,
                'has_border' => $has_border,
                'is_default' => $is_default,
                'tenant_id' => $tenant_id
            ]);
        }
    }
}
