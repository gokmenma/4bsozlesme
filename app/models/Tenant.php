<?php

require_once __DIR__ . '/../core/Model.php';

class Tenant extends Model {
    protected $table = 'tenants';

    /**
     * Kurumu bir kullanıcıya atar
     */
    public function associateWithUser($tenantId, $userId, $role = 'admin') {
        $stmt = $this->db->prepare("INSERT INTO user_tenants (tenant_id, user_id, role, created_at) VALUES (?, ?, ?, NOW())");
        return $stmt->execute([$tenantId, $userId, $role]);
    }
}
