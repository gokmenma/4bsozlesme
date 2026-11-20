<?php

require_once __DIR__ . '/../core/Model.php';

class MobileDashboardModel extends Model {
    protected $table = 'personeller';

    public function getPersonnelSummary(int $tenantId): array {
        $stmt = $this->db->prepare(
            "SELECT durum, COUNT(*) AS total FROM personeller
             WHERE deleted_at IS NULL AND tenant_id = ? GROUP BY durum"
        );
        $stmt->execute([$tenantId]);
        $byStatus = [];
        $total = 0;
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $count = (int)$row['total'];
            $byStatus[(string)$row['durum']] = $count;
            $total += $count;
        }

        $active = ($byStatus['aktif'] ?? 0) + ($byStatus['dilekce_alindi'] ?? 0) + ($byStatus['kadroya_gecmeyecek'] ?? 0);

        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM personeller
             WHERE deleted_at IS NULL AND tenant_id = ? AND created_at >= ?"
        );
        $stmt->execute([$tenantId, date('Y-m-01 00:00:00')]);
        $newThisMonth = (int)$stmt->fetchColumn();

        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM personeller
             WHERE deleted_at IS NULL AND tenant_id = ?
               AND goreve_baslama_tarihi IS NOT NULL
               AND DATE_ADD(goreve_baslama_tarihi, INTERVAL 3 YEAR) <= CURRENT_DATE"
        );
        $stmt->execute([$tenantId]);
        $eligible = (int)$stmt->fetchColumn();

        return [
            'total' => $total,
            'active' => $active,
            'by_status' => $byStatus,
            'new_this_month' => $newThisMonth,
            'kadro_eligible' => $eligible,
        ];
    }

    public function getRecentPersonnel(int $tenantId, int $limit = 5): array {
        $limit = max(1, min(20, $limit));
        $stmt = $this->db->prepare(
            "SELECT p.id, p.ad_soyad, p.durum, p.created_at, u.unvan
             FROM personeller p
             LEFT JOIN ucretler u ON p.ucret_id = u.id
             WHERE p.deleted_at IS NULL AND p.tenant_id = ?
             ORDER BY p.created_at DESC
             LIMIT {$limit}"
        );
        $stmt->execute([$tenantId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getWageCount(int $tenantId): int {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM ucretler WHERE deleted_at IS NULL AND tenant_id = ?");
        $stmt->execute([$tenantId]);
        return (int)$stmt->fetchColumn();
    }

    public function getTaskSummary(int $tenantId, int $userId): array {
        $stmt = $this->db->prepare(
            "SELECT t.status, COUNT(*) AS total, SUM(t.due_date IS NOT NULL AND t.due_date < CURRENT_DATE AND t.status <> 'done') AS overdue
             FROM kanban_tasks t
             WHERE t.tenant_id = ?
               AND (t.user_id = ? OR t.id IN (SELECT task_id FROM kanban_task_users WHERE user_id = ?))
             GROUP BY t.status"
        );
        $stmt->execute([$tenantId, $userId, $userId]);
        $summary = ['todo' => 0, 'in_progress' => 0, 'done' => 0, 'overdue' => 0];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $status = (string)$row['status'];
            if (isset($summary[$status])) {
                $summary[$status] = (int)$row['total'];
            }
            $summary['overdue'] += (int)$row['overdue'];
        }
        return $summary;
    }

    public function getUpcomingTasks(int $tenantId, int $userId, int $limit = 4): array {
        $limit = max(1, min(10, $limit));
        $stmt = $this->db->prepare(
            "SELECT t.id, t.title, t.status, t.priority, t.due_date
             FROM kanban_tasks t
             WHERE t.tenant_id = ? AND t.status <> 'done'
               AND (t.user_id = ? OR t.id IN (SELECT task_id FROM kanban_task_users WHERE user_id = ?))
             ORDER BY (t.due_date IS NULL), t.due_date ASC, t.id DESC
             LIMIT {$limit}"
        );
        $stmt->execute([$tenantId, $userId, $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
