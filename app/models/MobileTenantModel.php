<?php

require_once __DIR__ . '/../core/Model.php';

class MobileTenantModel extends Model {
    protected $table = 'tenants';

    public function list(): array {
        $stmt = $this->db->query(
            "SELECT t.id, t.name, t.slug, t.is_active, t.created_at,
                    (SELECT COUNT(*) FROM users u WHERE u.tenant_id = t.id) AS user_count
             FROM tenants t ORDER BY t.name ASC"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function exists(int $id): bool {
        $stmt = $this->db->prepare("SELECT 1 FROM tenants WHERE id = ?");
        $stmt->execute([$id]);
        return (bool)$stmt->fetchColumn();
    }

    public function userCount(int $id): int {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM users WHERE tenant_id = ?");
        $stmt->execute([$id]);
        return (int)$stmt->fetchColumn();
    }

    public function updateTenant(int $id, string $name, bool $active): void {
        $stmt = $this->db->prepare("UPDATE tenants SET name = ?, is_active = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$name, $active ? 1 : 0, $id]);
    }

    public function deleteTenant(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM tenants WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }
}
