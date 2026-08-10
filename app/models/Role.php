<?php

require_once __DIR__ . '/../core/Model.php';

class Role extends Model {
    protected $table = 'roles';

    /**
     * Kurum ve sistem geneli rolleri getirir
     */
    public function getAllForTenant($tenantId = null) {
        if ($tenantId === null) {
            $stmt = $this->db->prepare("
                SELECT r.*, COUNT(u.id) as user_count 
                FROM roles r 
                LEFT JOIN users u ON u.role_id = r.id 
                GROUP BY r.id 
                ORDER BY r.is_system DESC, r.id ASC
            ");
            $stmt->execute();
        } else {
            $stmt = $this->db->prepare("
                SELECT r.*, COUNT(u.id) as user_count 
                FROM roles r 
                LEFT JOIN users u ON u.role_id = r.id 
                WHERE r.is_system = 1 OR r.tenant_id = ? 
                GROUP BY r.id 
                ORDER BY r.is_system DESC, r.id ASC
            ");
            $stmt->execute([$tenantId]);
        }
        return $stmt->fetchAll();
    }

    /**
     * Yeni yetki grubu ekler
     */
    public function createRole($name, $description = '', $tenantId = null) {
        return $this->create([
            'name' => trim($name),
            'description' => trim($description),
            'tenant_id' => $tenantId,
            'is_system' => 0
        ]);
    }

    /**
     * Yetki grubunu günceller (Sistem rolleri adı ve açıklaması değiştirilemez)
     */
    /**
     * Yetki grubunu günceller (Superadmin ID 1 adı korunur, diğerlerinin adı ve açıklaması güncellenebilir)
     */
    public function updateRole($id, $name, $description = '') {
        $role = $this->find($id);
        if (!$role) {
            return false;
        }
        if ($id == 1) {
            // Superadmin rolünün adı korunur, açıklaması güncellenebilir
            return $this->update($id, [
                'description' => trim($description)
            ]);
        }
        return $this->update($id, [
            'name' => trim($name),
            'description' => trim($description)
        ]);
    }

    /**
     * Yetki grubuna ait kullanıcı sayısını döner
     */
    public function getUserCount($roleId) {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM users WHERE role_id = ?");
        $stmt->execute([$roleId]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Yetki grubunu siler (Superadmin ID 1 silinemez, kullanıcı bağlı olan silinemez)
     */
    public function deleteRole($id) {
        $role = $this->find($id);
        if (!$role || $id == 1) {
            return false;
        }
        
        // Eğer role tanımlı kullanıcılar varsa silme engellenir
        if ($this->getUserCount($id) > 0) {
            return false;
        }

        // İzin kayıtlarını temizle
        $stmt = $this->db->prepare("DELETE FROM role_permissions WHERE role_id = ?");
        $stmt->execute([$id]);

        return $this->delete($id);
    }
}
