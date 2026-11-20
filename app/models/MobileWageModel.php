<?php

require_once __DIR__ . '/../core/Model.php';
require_once __DIR__ . '/Definition.php';

class MobileWageModel extends Model {
    protected $table = 'ucretler';

    public const DEFAULT_OGRENIMLER = ['Lise', 'Ön Lisans', 'Lisans', 'Yüksek Lisans', 'Doktora'];
    public const DEFAULT_KIDEMLER = ['0-5 Yıl (Dahil)', '5-10 Yıl (Dahil)', '10-15 Yıl (Dahil)', '15-20 Yıl (Dahil)', '20 Yıl ve Üzeri'];
    public const SORTS = ['unvan_asc', 'unvan_desc', 'ucret_desc', 'ucret_asc', 'ogrenim_asc'];

    public function getPeriods(int $tenantId): array {
        $stmt = $this->db->prepare(
            "SELECT donem, COUNT(*) AS total
             FROM ucretler
             WHERE deleted_at IS NULL AND tenant_id = ?
             GROUP BY donem
             ORDER BY donem DESC"
        );
        $stmt->execute([$tenantId]);
        $rows = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $rows[] = [
                'donem' => (string)$row['donem'],
                'total' => (int)$row['total'],
            ];
        }
        return $rows;
    }

    public function getDefaultPeriod(int $tenantId): ?string {
        $stmt = $this->db->prepare(
            "SELECT `value` FROM definitions WHERE `key` = 'default_wage_period' AND tenant_id = ? LIMIT 1"
        );
        $stmt->execute([$tenantId]);
        $val = $stmt->fetchColumn();
        if ($val === false || $val === null) {
            return null;
        }
        $trimmed = trim((string)$val);
        return $trimmed !== '' ? $trimmed : null;
    }

    public function resolvePeriod(int $tenantId, string $requested, array $periods): string {
        $requested = trim($requested);
        if ($requested !== '') {
            return $requested;
        }

        $periodNames = array_column($periods, 'donem');
        $defaultPeriod = $this->getDefaultPeriod($tenantId);
        if ($defaultPeriod !== null && in_array($defaultPeriod, $periodNames, true)) {
            return $defaultPeriod;
        }

        if (!empty($periodNames)) {
            return (string)$periodNames[0];
        }

        return '2026-1';
    }

    public function getPeriodSummary(int $tenantId, string $period): array {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) AS total,
                    MIN(ucret) AS min_ucret,
                    MAX(ucret) AS max_ucret,
                    AVG(ucret) AS avg_ucret,
                    COUNT(DISTINCT unvan) AS distinct_unvan
             FROM ucretler
             WHERE deleted_at IS NULL AND tenant_id = ? AND donem = ?"
        );
        $stmt->execute([$tenantId, $period]);
        $agg = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $ogStmt = $this->db->prepare(
            "SELECT ogrenim, COUNT(*) AS total
             FROM ucretler
             WHERE deleted_at IS NULL AND tenant_id = ? AND donem = ?
             GROUP BY ogrenim
             ORDER BY ogrenim ASC"
        );
        $ogStmt->execute([$tenantId, $period]);
        $ogrenimCounts = [];
        foreach ($ogStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $label = trim((string)($row['ogrenim'] ?? ''));
            if ($label === '') {
                continue;
            }
            $ogrenimCounts[] = [
                'ogrenim' => $label,
                'total' => (int)$row['total'],
            ];
        }

        $kdStmt = $this->db->prepare(
            "SELECT DISTINCT kidem_yili
             FROM ucretler
             WHERE deleted_at IS NULL AND tenant_id = ? AND donem = ? AND kidem_yili <> ''
             ORDER BY kidem_yili ASC"
        );
        $kdStmt->execute([$tenantId, $period]);
        $kidemOptions = array_values(array_filter(array_map(
            static fn($v): string => trim((string)$v),
            $kdStmt->fetchAll(PDO::FETCH_COLUMN)
        )));
        $kidemOptions = $this->sortKidemOptions($kidemOptions);

        return [
            'total' => (int)($agg['total'] ?? 0),
            'min_ucret' => isset($agg['min_ucret']) && $agg['min_ucret'] !== null ? (float)$agg['min_ucret'] : null,
            'max_ucret' => isset($agg['max_ucret']) && $agg['max_ucret'] !== null ? (float)$agg['max_ucret'] : null,
            'avg_ucret' => isset($agg['avg_ucret']) && $agg['avg_ucret'] !== null ? round((float)$agg['avg_ucret'], 2) : null,
            'distinct_unvan' => (int)($agg['distinct_unvan'] ?? 0),
            'ogrenim_counts' => $ogrenimCounts,
            'kidem_options' => $kidemOptions,
        ];
    }

    public function getFormOptions(int $tenantId): array {
        $ogStmt = $this->db->prepare(
            "SELECT DISTINCT ogrenim
             FROM ucretler
             WHERE deleted_at IS NULL AND tenant_id = ? AND ogrenim <> ''
             ORDER BY ogrenim ASC"
        );
        $ogStmt->execute([$tenantId]);
        $dbOgrenim = array_values(array_filter(array_map(
            static fn($v): string => trim((string)$v),
            $ogStmt->fetchAll(PDO::FETCH_COLUMN)
        )));

        $normalizeCompact = static fn(string $v): string => mb_strtolower(str_replace(' ', '', trim($v)), 'UTF-8');

        $dbByCompact = [];
        foreach ($dbOgrenim as $val) {
            $key = $normalizeCompact($val);
            if (!isset($dbByCompact[$key])) {
                $dbByCompact[$key] = $val;
            }
        }

        $ogrenimler = [];
        $usedCompact = [];
        foreach (self::DEFAULT_OGRENIMLER as $def) {
            $key = $normalizeCompact($def);
            $chosen = $dbByCompact[$key] ?? $def;
            $ogrenimler[] = $chosen;
            $usedCompact[$key] = true;
        }
        foreach ($dbOgrenim as $val) {
            $key = $normalizeCompact($val);
            if (!isset($usedCompact[$key])) {
                $ogrenimler[] = $val;
                $usedCompact[$key] = true;
            }
        }

        $kdStmt = $this->db->prepare(
            "SELECT DISTINCT kidem_yili
             FROM ucretler
             WHERE deleted_at IS NULL AND tenant_id = ? AND kidem_yili <> ''
             ORDER BY kidem_yili ASC"
        );
        $kdStmt->execute([$tenantId]);
        $dbKidem = array_values(array_filter(array_map(
            static fn($v): string => trim((string)$v),
            $kdStmt->fetchAll(PDO::FETCH_COLUMN)
        )));

        $kidemMap = [];
        foreach (array_merge(self::DEFAULT_KIDEMLER, $dbKidem) as $val) {
            if (!isset($kidemMap[$val])) {
                $kidemMap[$val] = $val;
            }
        }
        $kidemler = $this->sortKidemOptions(array_values($kidemMap));

        return [
            'ogrenimler' => $ogrenimler,
            'kidemler' => $kidemler,
        ];
    }

    public function getList(
        int $tenantId,
        string $period,
        string $search,
        string $ogrenim,
        string $kidem,
        ?float $minUcret,
        ?float $maxUcret,
        string $sort,
        int $page,
        int $limit = 30
    ): array {
        $limit = max(1, min(60, $limit));
        $page = max(1, $page);
        $offset = ($page - 1) * $limit;

        $where = ['u.deleted_at IS NULL', 'u.tenant_id = :tenant_id', 'u.donem = :donem'];
        $params = [
            ':tenant_id' => $tenantId,
            ':donem' => $period,
        ];

        if ($search !== '') {
            $where[] = '(u.unvan LIKE :s1 OR u.ogrenim LIKE :s2 OR u.kidem_yili LIKE :s3)';
            $like = '%' . $search . '%';
            $params[':s1'] = $like;
            $params[':s2'] = $like;
            $params[':s3'] = $like;
        }
        if ($ogrenim !== '') {
            $where[] = 'u.ogrenim = :ogrenim';
            $params[':ogrenim'] = $ogrenim;
        }
        if ($kidem !== '') {
            $where[] = 'u.kidem_yili = :kidem';
            $params[':kidem'] = $kidem;
        }
        if ($minUcret !== null) {
            $where[] = 'u.ucret >= :min_ucret';
            $params[':min_ucret'] = $minUcret;
        }
        if ($maxUcret !== null) {
            $where[] = 'u.ucret <= :max_ucret';
            $params[':max_ucret'] = $maxUcret;
        }

        $whereSql = implode(' AND ', $where);

        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM ucretler u WHERE {$whereSql}");
        foreach ($params as $key => $value) {
            $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $countStmt->bindValue($key, $value, $type);
        }
        $countStmt->execute();
        $total = (int)$countStmt->fetchColumn();

        $orderBy = match ($sort) {
            'unvan_desc' => 'u.unvan DESC, u.ogrenim ASC, u.kidem_yili ASC, u.id DESC',
            'ucret_desc' => 'u.ucret DESC, u.unvan ASC, u.id ASC',
            'ucret_asc' => 'u.ucret ASC, u.unvan ASC, u.id ASC',
            'ogrenim_asc' => 'u.ogrenim ASC, u.unvan ASC, u.kidem_yili ASC, u.id ASC',
            default => 'u.unvan ASC, u.ogrenim ASC, u.kidem_yili ASC, u.id ASC',
        };

        $listSql = "SELECT u.id, u.donem, u.unvan, u.ogrenim, u.kidem_yili, u.ucret, u.is_active, u.created_at, u.updated_at,
                           COALESCE(pc.cnt, 0) AS personel_count
                    FROM ucretler u
                    LEFT JOIN (
                        SELECT ucret_id, COUNT(*) AS cnt
                        FROM personeller
                        WHERE deleted_at IS NULL AND tenant_id = :pc_tenant_id
                        GROUP BY ucret_id
                    ) pc ON pc.ucret_id = u.id
                    WHERE {$whereSql}
                    ORDER BY {$orderBy}
                    LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($listSql);
        $stmt->bindValue(':pc_tenant_id', $tenantId, PDO::PARAM_INT);
        foreach ($params as $key => $value) {
            $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue($key, $value, $type);
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

    public function getDetail(int $tenantId, int $id): ?array {
        $stmt = $this->db->prepare(
            "SELECT u.id, u.donem, u.unvan, u.ogrenim, u.kidem_yili, u.ucret, u.is_active, u.created_at, u.updated_at,
                    (SELECT COUNT(*)
                     FROM personeller p
                     WHERE p.ucret_id = u.id AND p.tenant_id = ? AND p.deleted_at IS NULL) AS personel_count
             FROM ucretler u
             WHERE u.id = ? AND u.tenant_id = ? AND u.deleted_at IS NULL
             LIMIT 1"
        );
        $stmt->execute([$tenantId, $id, $tenantId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function createWage(int $tenantId, array $data): int {
        $stmt = $this->db->prepare(
            "INSERT INTO ucretler (tenant_id, donem, unvan, ogrenim, kidem_yili, ucret, is_active, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, 1, NOW(), NOW())"
        );
        $stmt->execute([
            $tenantId,
            $data['donem'],
            $data['unvan'],
            $data['ogrenim'],
            $data['kidem_yili'],
            $data['ucret'],
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function updateWage(int $tenantId, int $id, array $data): bool {
        $stmt = $this->db->prepare(
            "UPDATE ucretler
             SET donem = ?, unvan = ?, ogrenim = ?, kidem_yili = ?, ucret = ?, updated_at = NOW()
             WHERE id = ? AND tenant_id = ? AND deleted_at IS NULL"
        );
        return $stmt->execute([
            $data['donem'],
            $data['unvan'],
            $data['ogrenim'],
            $data['kidem_yili'],
            $data['ucret'],
            $id,
            $tenantId,
        ]);
    }

    public function countPersonnelUsingWage(int $tenantId, int $id): int {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM personeller WHERE ucret_id = ? AND tenant_id = ? AND deleted_at IS NULL"
        );
        $stmt->execute([$id, $tenantId]);
        return (int)$stmt->fetchColumn();
    }

    public function softDelete(int $tenantId, int $id): bool {
        $stmt = $this->db->prepare(
            "UPDATE ucretler SET deleted_at = NOW() WHERE id = ? AND tenant_id = ? AND deleted_at IS NULL"
        );
        $stmt->execute([$id, $tenantId]);
        return $stmt->rowCount() > 0;
    }

    public function copyPeriod(int $tenantId, string $fromDonem, string $toDonem, float $raisePercent): array {
        $checkStmt = $this->db->prepare(
            "SELECT COUNT(*) FROM ucretler WHERE tenant_id = ? AND donem = ? AND deleted_at IS NULL"
        );
        $checkStmt->execute([$tenantId, $toDonem]);
        if ((int)$checkStmt->fetchColumn() > 0) {
            return [
                'ok' => false,
                'status' => 409,
                'error' => "Hedef dönem ({$toDonem}) için zaten ücret tanımları mevcut. Lütfen farklı bir dönem adı belirleyin.",
            ];
        }

        $srcStmt = $this->db->prepare(
            "SELECT unvan, ogrenim, kidem_yili, ucret, is_active
             FROM ucretler
             WHERE tenant_id = ? AND donem = ? AND deleted_at IS NULL"
        );
        $srcStmt->execute([$tenantId, $fromDonem]);
        $sources = $srcStmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($sources)) {
            return [
                'ok' => false,
                'status' => 422,
                'error' => "Kaynak dönemde ({$fromDonem}) kopyalanacak ücret tanımı bulunamadı.",
            ];
        }

        $ownTx = !$this->db->inTransaction();
        if ($ownTx) {
            $this->db->beginTransaction();
        }

        try {
            $insertStmt = $this->db->prepare(
                "INSERT INTO ucretler (tenant_id, donem, unvan, ogrenim, kidem_yili, ucret, is_active, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())"
            );

            $count = 0;
            foreach ($sources as $row) {
                $newUcret = round((float)$row['ucret'] * (1 + $raisePercent / 100), 2);
                $insertStmt->execute([
                    $tenantId,
                    $toDonem,
                    $row['unvan'],
                    $row['ogrenim'],
                    $row['kidem_yili'],
                    $newUcret,
                    (int)($row['is_active'] ?? 1),
                ]);
                $count++;
            }

            if ($ownTx) {
                $this->db->commit();
            }

            return ['ok' => true, 'count' => $count];
        } catch (Throwable $e) {
            if ($ownTx && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function deletePeriod(int $tenantId, string $donem): array {
        $periodCountStmt = $this->db->prepare(
            "SELECT COUNT(DISTINCT donem) FROM ucretler WHERE tenant_id = ? AND deleted_at IS NULL"
        );
        $periodCountStmt->execute([$tenantId]);
        if ((int)$periodCountStmt->fetchColumn() <= 1) {
            return [
                'ok' => false,
                'status' => 422,
                'error' => 'Sistemde en az bir ücret dönemi bulunmalıdır. Son kalan ücret dönemini silemezsiniz.',
            ];
        }

        $existsStmt = $this->db->prepare(
            "SELECT COUNT(*) FROM ucretler WHERE tenant_id = ? AND donem = ? AND deleted_at IS NULL"
        );
        $existsStmt->execute([$tenantId, $donem]);
        if ((int)$existsStmt->fetchColumn() === 0) {
            return [
                'ok' => false,
                'status' => 404,
                'error' => 'Silinecek ücret dönemi bulunamadı.',
            ];
        }

        $usedStmt = $this->db->prepare(
            "SELECT COUNT(*)
             FROM personeller p
             INNER JOIN ucretler u ON p.ucret_id = u.id
             WHERE u.donem = ? AND u.tenant_id = ? AND p.tenant_id = ? AND p.deleted_at IS NULL AND u.deleted_at IS NULL"
        );
        $usedStmt->execute([$donem, $tenantId, $tenantId]);
        $usedCount = (int)$usedStmt->fetchColumn();
        if ($usedCount > 0) {
            return [
                'ok' => false,
                'status' => 409,
                'error' => "Bu dönemdeki ücret tanımları şu anda {$usedCount} personel tarafından aktif olarak kullanılmaktadır. Dönemi silebilmek için önce bu personellerin ücretlerini değiştirmeniz gerekmektedir.",
            ];
        }

        $ownTx = !$this->db->inTransaction();
        if ($ownTx) {
            $this->db->beginTransaction();
        }

        try {
            $delStmt = $this->db->prepare(
                "UPDATE ucretler SET deleted_at = NOW() WHERE tenant_id = ? AND donem = ? AND deleted_at IS NULL"
            );
            $delStmt->execute([$tenantId, $donem]);
            $deleted = $delStmt->rowCount();

            $otherStmt = $this->db->prepare(
                "SELECT DISTINCT donem
                 FROM ucretler
                 WHERE tenant_id = ? AND donem <> ? AND deleted_at IS NULL
                 ORDER BY donem DESC
                 LIMIT 1"
            );
            $otherStmt->execute([$tenantId, $donem]);
            $otherPeriod = $otherStmt->fetchColumn();
            $otherPeriod = $otherPeriod !== false ? (string)$otherPeriod : null;

            $defaultPeriod = $this->getDefaultPeriod($tenantId);
            if ($defaultPeriod === $donem && $otherPeriod !== null) {
                $updDefStmt = $this->db->prepare(
                    "UPDATE definitions SET `value` = ? WHERE `key` = 'default_wage_period' AND tenant_id = ?"
                );
                $updDefStmt->execute([$otherPeriod, $tenantId]);
                $defaultPeriod = $otherPeriod;
            }

            if ($ownTx) {
                $this->db->commit();
            }

            return [
                'ok' => true,
                'deleted' => $deleted,
                'next_period' => $defaultPeriod ?? $otherPeriod ?? '2026-1',
            ];
        } catch (Throwable $e) {
            if ($ownTx && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function setDefaultPeriod(int $tenantId, string $donem): bool {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM ucretler WHERE tenant_id = ? AND donem = ? AND deleted_at IS NULL"
        );
        $stmt->execute([$tenantId, $donem]);
        if ((int)$stmt->fetchColumn() === 0) {
            return false;
        }

        $defModel = new Definition();
        return (bool)$defModel->setSetting('default_wage_period', $donem, $tenantId);
    }

    private function sortKidemOptions(array $options): array {
        usort($options, static function (string $a, string $b): int {
            $numA = preg_match('/^(\d+)/', $a, $mA) ? (int)$mA[1] : PHP_INT_MAX;
            $numB = preg_match('/^(\d+)/', $b, $mB) ? (int)$mB[1] : PHP_INT_MAX;
            if ($numA !== $numB) {
                return $numA <=> $numB;
            }
            return strcmp($a, $b);
        });
        return $options;
    }
}
