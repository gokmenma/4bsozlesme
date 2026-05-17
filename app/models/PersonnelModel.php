<?php

class PersonnelModel extends Model {
    protected $table = 'personeller';

    public function find($id, $tenant_id = null) {
        $sql = "
            SELECT p.*, u.unvan, u.ucret, u.ogrenim 
            FROM {$this->table} p 
            LEFT JOIN ucretler u ON p.ucret_id = u.id 
            WHERE p.id = ? AND p.deleted_at IS NULL
        ";
        $params = [$id];
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
