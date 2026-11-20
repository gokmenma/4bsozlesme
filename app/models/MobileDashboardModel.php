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

    public function getKadroPersonnel(int $tenantId, int $daysAhead = 60, int $limit = 100): array {
        $daysAhead = max(1, min(365, $daysAhead));
        $limit = max(1, min(200, $limit));
        $stmt = $this->db->prepare(
            "SELECT * FROM (
                SELECT p.id, p.ad_soyad, p.telefon, p.durum, p.goreve_baslama_tarihi, u.unvan,
                       DATE_ADD(DATE_ADD(p.goreve_baslama_tarihi, INTERVAL 3 YEAR),
                                INTERVAL (SELECT COALESCE(SUM(DATEDIFF(i.bitis_tarihi, i.baslangic_tarihi) + 1), 0)
                                          FROM ucretsiz_izin i
                                          WHERE i.personel_id = p.id AND i.deleted_at IS NULL) DAY) AS kadro_tarihi
                FROM personeller p
                LEFT JOIN ucretler u ON p.ucret_id = u.id
                WHERE p.deleted_at IS NULL AND p.tenant_id = ?
                  AND p.durum IN ('aktif', 'dilekce_alindi')
                  AND p.goreve_baslama_tarihi IS NOT NULL
             ) k
             WHERE k.kadro_tarihi <= DATE_ADD(CURRENT_DATE, INTERVAL {$daysAhead} DAY)
             ORDER BY k.kadro_tarihi DESC, k.id DESC"
        );
        $stmt->execute([$tenantId]);
        $today = date('Y-m-d');
        $due = [];
        $upcoming = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $row['id'] = (int)$row['id'];
            $row['days_left'] = (int)round((strtotime((string)$row['kadro_tarihi']) - strtotime($today)) / 86400);
            if ($row['days_left'] <= 0) {
                $due[] = $row;
            } else {
                $upcoming[] = $row;
            }
        }
        usort($upcoming, static fn($a, $b) => $a['days_left'] <=> $b['days_left']);
        return [
            'due_count' => count($due),
            'upcoming_count' => count($upcoming),
            'due' => array_slice($due, 0, $limit),
            'upcoming' => array_slice($upcoming, 0, $limit),
        ];
    }

    public function getKadroTarget(int $tenantId, int $personId): ?array {
        $stmt = $this->db->prepare(
            "SELECT p.id, p.ad_soyad, p.telefon,
                    DATE_ADD(DATE_ADD(p.goreve_baslama_tarihi, INTERVAL 3 YEAR),
                             INTERVAL (SELECT COALESCE(SUM(DATEDIFF(i.bitis_tarihi, i.baslangic_tarihi) + 1), 0)
                                       FROM ucretsiz_izin i
                                       WHERE i.personel_id = p.id AND i.deleted_at IS NULL) DAY) AS kadro_tarihi
             FROM personeller p
             WHERE p.id = ? AND p.tenant_id = ? AND p.deleted_at IS NULL AND p.goreve_baslama_tarihi IS NOT NULL"
        );
        $stmt->execute([$personId, $tenantId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
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
