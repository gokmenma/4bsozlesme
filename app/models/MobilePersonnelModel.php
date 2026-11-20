<?php

require_once __DIR__ . '/../core/Model.php';

class MobilePersonnelModel extends Model {
    protected $table = 'personeller';

    public const STATUSES = ['aktif', 'pasif', 'dilekce_alindi', 'kadroya_gecti', 'kadroya_gecmeyecek'];

    public function getList(int $tenantId, string $search, string $status, int $page, int $limit): array {
        $limit = max(1, min(50, $limit));
        $page = max(1, $page);
        $offset = ($page - 1) * $limit;

        $where = ['p.deleted_at IS NULL', 'p.tenant_id = :tenant_id'];
        $params = [':tenant_id' => $tenantId];

        if ($search !== '') {
            $where[] = '(p.ad_soyad LIKE :s1 OR p.tc_kimlik LIKE :s2 OR u.unvan LIKE :s3 OR p.telefon LIKE :s4)';
            foreach (['s1', 's2', 's3', 's4'] as $key) {
                $params[':' . $key] = '%' . $search . '%';
            }
        }
        if ($status !== '' && in_array($status, self::STATUSES, true)) {
            $where[] = 'p.durum = :status';
            $params[':status'] = $status;
        }

        $whereSql = implode(' AND ', $where);

        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM personeller p LEFT JOIN ucretler u ON p.ucret_id = u.id WHERE {$whereSql}");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $stmt = $this->db->prepare(
            "SELECT p.id, p.ad_soyad, p.tc_kimlik, p.durum, p.telefon, p.goreve_baslama_tarihi, u.unvan
             FROM personeller p
             LEFT JOIN ucretler u ON p.ucret_id = u.id
             WHERE {$whereSql}
             ORDER BY p.id DESC
             LIMIT :limit OFFSET :offset"
        );
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'items' => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'total' => $total,
            'page' => $page,
            'has_more' => ($offset + $limit) < $total,
        ];
    }

    public function getStatusCounts(int $tenantId): array {
        $stmt = $this->db->prepare(
            'SELECT durum, COUNT(*) AS total FROM personeller WHERE deleted_at IS NULL AND tenant_id = ? GROUP BY durum'
        );
        $stmt->execute([$tenantId]);
        $counts = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $counts[(string)$row['durum']] = (int)$row['total'];
        }
        return $counts;
    }

    public function getDetail(int $tenantId, int $id): ?array {
        $stmt = $this->db->prepare(
            "SELECT p.id, p.ad_soyad, p.tc_kimlik, p.durum, p.telefon, p.cinsiyet, p.ucret_id, p.meslek_kodu,
                    p.goreve_baslama_tarihi, p.ayrilma_tarihi, u.unvan, u.ogrenim
             FROM personeller p
             LEFT JOIN ucretler u ON p.ucret_id = u.id
             WHERE p.id = ? AND p.tenant_id = ? AND p.deleted_at IS NULL
             LIMIT 1"
        );
        $stmt->execute([$id, $tenantId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getWageOptions(int $tenantId): array {
        $stmt = $this->db->prepare("SELECT `value` FROM definitions WHERE `key` = 'default_wage_period' AND tenant_id = ? LIMIT 1");
        $stmt->execute([$tenantId]);
        $period = $stmt->fetchColumn() ?: '2026-1';

        $stmt = $this->db->prepare(
            'SELECT id, unvan, ogrenim, kidem_yili, ucret FROM ucretler
             WHERE deleted_at IS NULL AND tenant_id = ? AND donem = ?
             ORDER BY unvan ASC, ogrenim ASC, kidem_yili ASC'
        );
        $stmt->execute([$tenantId, $period]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function wageExists(int $tenantId, int $wageId): bool {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM ucretler WHERE id = ? AND tenant_id = ? AND deleted_at IS NULL');
        $stmt->execute([$wageId, $tenantId]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function tcExists(int $tenantId, string $tc, int $exceptId = 0): bool {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM personeller WHERE tenant_id = ? AND tc_kimlik = ? AND id <> ? AND deleted_at IS NULL'
        );
        $stmt->execute([$tenantId, $tc, $exceptId]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function createPerson(int $tenantId, array $data): int {
        $stmt = $this->db->prepare('SELECT id FROM personeller WHERE tenant_id = ? AND tc_kimlik = ? AND deleted_at IS NOT NULL LIMIT 1');
        $stmt->execute([$tenantId, $data['tc_kimlik']]);
        $deletedId = (int)$stmt->fetchColumn();

        if ($deletedId > 0) {
            $stmt = $this->db->prepare(
                'UPDATE personeller SET ad_soyad = ?, ucret_id = ?, durum = ?, goreve_baslama_tarihi = ?, ayrilma_tarihi = ?,
                        telefon = ?, meslek_kodu = ?, cinsiyet = ?, deleted_at = NULL, updated_at = NOW()
                 WHERE id = ? AND tenant_id = ?'
            );
            $stmt->execute([
                $data['ad_soyad'], $data['ucret_id'], $data['durum'], $data['goreve_baslama_tarihi'], $data['ayrilma_tarihi'],
                $data['telefon'], $data['meslek_kodu'], $data['cinsiyet'], $deletedId, $tenantId,
            ]);
            return $deletedId;
        }

        $stmt = $this->db->prepare(
            'INSERT INTO personeller (tenant_id, tc_kimlik, ad_soyad, ucret_id, durum, goreve_baslama_tarihi, ayrilma_tarihi,
                                      telefon, meslek_kodu, cinsiyet, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())'
        );
        $stmt->execute([
            $tenantId, $data['tc_kimlik'], $data['ad_soyad'], $data['ucret_id'], $data['durum'], $data['goreve_baslama_tarihi'],
            $data['ayrilma_tarihi'], $data['telefon'], $data['meslek_kodu'], $data['cinsiyet'],
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function updatePerson(int $tenantId, int $id, array $data): bool {
        $stmt = $this->db->prepare(
            'UPDATE personeller SET tc_kimlik = ?, ad_soyad = ?, ucret_id = ?, durum = ?, goreve_baslama_tarihi = ?,
                    ayrilma_tarihi = ?, telefon = ?, meslek_kodu = ?, cinsiyet = ?, updated_at = NOW()
             WHERE id = ? AND tenant_id = ? AND deleted_at IS NULL'
        );
        return $stmt->execute([
            $data['tc_kimlik'], $data['ad_soyad'], $data['ucret_id'], $data['durum'], $data['goreve_baslama_tarihi'],
            $data['ayrilma_tarihi'], $data['telefon'], $data['meslek_kodu'], $data['cinsiyet'], $id, $tenantId,
        ]);
    }

    public function softDelete(int $tenantId, int $id): bool {
        $stmt = $this->db->prepare('UPDATE personeller SET deleted_at = NOW() WHERE id = ? AND tenant_id = ? AND deleted_at IS NULL');
        $stmt->execute([$id, $tenantId]);
        return $stmt->rowCount() > 0;
    }

    public function listLeaves(int $tenantId, int $personId): array {
        $stmt = $this->db->prepare(
            'SELECT id, baslangic_tarihi, bitis_tarihi, gun_sayisi, aciklama FROM ucretsiz_izin
             WHERE personel_id = ? AND tenant_id = ? AND deleted_at IS NULL ORDER BY baslangic_tarihi DESC'
        );
        $stmt->execute([$personId, $tenantId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addLeave(int $tenantId, int $personId, string $start, string $end, string $note): int {
        $days = (int)(new DateTime($start))->diff(new DateTime($end))->days + 1;
        $stmt = $this->db->prepare(
            'INSERT INTO ucretsiz_izin (tenant_id, personel_id, baslangic_tarihi, bitis_tarihi, gun_sayisi, aciklama)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$tenantId, $personId, $start, $end, $days, $note]);
        return (int)$this->db->lastInsertId();
    }

    public function deleteLeave(int $tenantId, int $personId, int $leaveId): bool {
        $stmt = $this->db->prepare(
            'UPDATE ucretsiz_izin SET deleted_at = NOW() WHERE id = ? AND personel_id = ? AND tenant_id = ? AND deleted_at IS NULL'
        );
        $stmt->execute([$leaveId, $personId, $tenantId]);
        return $stmt->rowCount() > 0;
    }
}
