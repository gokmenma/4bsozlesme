<?php

require_once __DIR__ . '/../core/Model.php';

class User extends Model {
    protected $table = 'users';

    /**
     * Kullanıcı adına göre kullanıcıyı bulur
     */
    public function findByUsername($email) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE email = ?");
        $stmt->execute([$email]);
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
     * Tüm kullanıcıları ekleyen kişinin adıyla getirir
     */
    public function allWithCreator() {
        $stmt = $this->db->query("
            SELECT u.*, cu.name as creator_name 
            FROM users u 
            LEFT JOIN users cu ON u.created_by = cu.id
        ");
        return $stmt->fetchAll();
    }

    /**
     * Belirli bir sütuna göre filtreler ve ekleyen kişinin adıyla getirir
     */
    public function whereWithCreator($column, $value) {
        $stmt = $this->db->prepare("
            SELECT u.*, cu.name as creator_name 
            FROM users u 
            LEFT JOIN users cu ON u.created_by = cu.id 
            WHERE u.`{$column}` = ?
        ");
        $stmt->execute([$value]);
        return $stmt->fetchAll();
    }
}

