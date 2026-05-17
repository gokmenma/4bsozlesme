<?php

class PersonnelModel extends Model {
    protected $table = 'personeller';

    public function find($id, $tenant_id = null) {
        if ($tenant_id === null) {
            $tenant_id = $_SESSION['tenant_id'] ?? 0;
        }

        // Fetch default period
        $stmt_period = $this->db->prepare("SELECT `value` FROM definitions WHERE `key` = 'default_wage_period' AND tenant_id = ? LIMIT 1");
        $stmt_period->execute([$tenant_id]);
        $default_period = $stmt_period->fetchColumn() ?: '2026-1';

        $sql = "
            SELECT p.*, 
                   u.unvan, 
                   COALESCE(u_def.ucret, u.ucret) as ucret, 
                   u.ogrenim 
            FROM {$this->table} p 
            LEFT JOIN ucretler u ON p.ucret_id = u.id 
            LEFT JOIN ucretler u_def ON u_def.tenant_id = p.tenant_id 
                                    AND u_def.unvan = u.unvan 
                                    AND u_def.ogrenim = u.ogrenim 
                                    AND u_def.kidem_yili = u.kidem_yili
                                    AND u_def.donem = ?
                                    AND u_def.deleted_at IS NULL
            WHERE p.id = ? AND p.deleted_at IS NULL
        ";
        $params = [$default_period, $id];
        if ($tenant_id !== null) {
            $sql .= " AND p.tenant_id = ?";
            $params[] = $tenant_id;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }

    public function getPaginated($page = 1, $limit = 10, $search = '', $filters = [], $sort = 'id', $order = 'DESC') {
        $offset = ($page - 1) * $limit;
        
        $where = "WHERE p.deleted_at IS NULL";
        $params = [];

        if (!empty($search)) {
            $where .= " AND (p.ad_soyad LIKE :search OR p.tc_kimlik LIKE :search)";
            $params[':search'] = "%$search%";
        }

        if (!empty($filters['status'])) {
            $where .= " AND p.durum = :status";
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['unvan'])) {
            $where .= " AND u.unvan = :unvan";
            $params[':unvan'] = $filters['unvan'];
        }

        // Allowed sort columns for security
        $allowedSort = ['id', 'ad_soyad', 'tc_kimlik', 'unvan', 'durum', 'goreve_baslama_tarihi'];
        if (!in_array($sort, $allowedSort)) $sort = 'id';
        $order = (strtoupper($order) === 'ASC') ? 'ASC' : 'DESC';

        // Count total records
        $countSql = "SELECT COUNT(*) FROM {$this->table} p LEFT JOIN ucretler u ON p.ucret_id = u.id $where";
        $countStmt = $this->db->prepare($countSql);
        foreach ($params as $key => $val) {
            $countStmt->bindValue($key, $val);
        }
        $countStmt->execute();
        $totalRecords = $countStmt->fetchColumn();
        $totalPages = ceil($totalRecords / $limit);

        // Fetch records with join
        $sql = "
            SELECT p.*, u.unvan, u.ogrenim 
            FROM {$this->table} p 
            LEFT JOIN ucretler u ON p.ucret_id = u.id 
            $where
            ORDER BY $sort $order
            LIMIT :limit OFFSET :offset
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->execute();
        $records = $stmt->fetchAll();

        return [
            'data' => $records,
            'pagination' => [
                'total' => (int)$totalRecords,
                'totalPages' => (int)$totalPages,
                'currentPage' => (int)$page,
                'limit' => (int)$limit,
                'search' => $search,
                'filters' => $filters,
                'sort' => $sort,
                'order' => $order
            ]
        ];
    }
}
