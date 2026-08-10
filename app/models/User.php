<?php

require_once __DIR__ . '/../core/Model.php';

class User extends Model {
    protected $table = 'users';

    /**
     * Kullanıcı adına veya e-posta adresine göre kullanıcıyı bulur
     */
    public function findByUsername($usernameOrEmail) {
        $input = trim($usernameOrEmail);
        $stmt = $this->db->prepare("
            SELECT u.* FROM {$this->table} u
            LEFT JOIN tenants t ON u.tenant_id = t.id
            WHERE (u.email = ? 
               OR (u.username IS NOT NULL AND u.username != '' AND u.username = ?)
               OR ((u.username IS NULL OR u.username = '') AND SUBSTRING_INDEX(u.email, '@', 1) = ?))
              AND (u.tenant_id IS NULL OR t.id IS NOT NULL)
            ORDER BY (CASE WHEN t.id IS NOT NULL THEN 1 ELSE 0 END) DESC, u.id DESC
            LIMIT 1
        ");
        $stmt->execute([$input, $input, $input]);
        return $stmt->fetch();
    }
    /**
     * Kullanıcının bağlı olduğu tüm kurumları (tenants) getirir
     */
    public function getTenants($userId) {
        $sql = "SELECT t.* FROM tenants t 
                JOIN user_tenants ut ON t.id = ut.tenant_id 
                WHERE ut.user_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /**
     * Tüm kullanıcıları ekleyen kişinin adıyla ve rol grubuyla getirir
     */
    public function allWithCreator() {
        $stmt = $this->db->query("
            SELECT u.*, cu.name as creator_name, r.name as role_group_name 
            FROM users u 
            LEFT JOIN users cu ON u.created_by = cu.id
            LEFT JOIN roles r ON u.role_id = r.id
            ORDER BY u.id DESC
        ");
        return $stmt->fetchAll();
    }

    /**
     * Belirli bir sütuna göre filtreler, ekleyen kişinin adıyla ve rol grubuyla getirir
     */
    public function whereWithCreator($column, $value) {
        $stmt = $this->db->prepare("
            SELECT u.*, cu.name as creator_name, r.name as role_group_name 
            FROM users u 
            LEFT JOIN users cu ON u.created_by = cu.id 
            LEFT JOIN roles r ON u.role_id = r.id
            WHERE u.`{$column}` = ?
            ORDER BY u.name ASC
        ");
        $stmt->execute([$value]);
        return $stmt->fetchAll();
    }

    /**
     * Kullanıcının detaylı rol ve yetki grubu bilgilerini getirir
     */
    public function getUserRoleInfo($userId) {
        $stmt = $this->db->prepare("
            SELECT u.id, u.role, u.role_id, r.name as role_name, r.is_system 
            FROM users u 
            LEFT JOIN roles r ON u.role_id = r.id 
            WHERE u.id = ?
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }

    /**
     * Kullanıcının Beni Hatırla tokenını günceller
     */
    public function updateRememberToken($userId, $token) {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET remember_token = ? WHERE id = ?");
        return $stmt->execute([$token, $userId]);
    }

    /**
     * ID ve Beni Hatırla tokenına göre kullanıcıyı bulur
     */
    public function findByRememberToken($userId, $token) {
        $stmt = $this->db->prepare("
            SELECT * FROM {$this->table} 
            WHERE id = ? 
              AND remember_token = ? 
              AND remember_token IS NOT NULL 
              AND remember_token != ''
            LIMIT 1
        ");
        $stmt->execute([$userId, $token]);
        return $stmt->fetch();
    }
}


