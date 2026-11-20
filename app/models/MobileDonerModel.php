<?php

require_once __DIR__ . '/../core/Model.php';

class MobileDonerModel extends Model {
    protected $table = 'personeller';

    public const OPS = ['tum', 'empty', 'not_empty', 'gt', 'lt', 'gte', 'lte', 'equals'];

    public function build(int $tenantId, int $month, int $year, array $statuses, string $op, ?string $date, float $maas, float $yan): array {
        $lastDay = date('Y-m-t', strtotime(sprintf('%04d-%02d-01', $year, $month)));
        $where = "p.deleted_at IS NULL AND p.tenant_id = ? AND p.goreve_baslama_tarihi <= ?";
        $params = [$tenantId, $lastDay];

        if ($statuses) {
            $where .= " AND p.durum IN (" . implode(',', array_fill(0, count($statuses), '?')) . ")";
            array_push($params, ...$statuses);
        }

        if ($op === 'empty') {
            $where .= " AND p.ayrilma_tarihi IS NULL";
        } elseif ($op === 'not_empty') {
            $where .= " AND p.ayrilma_tarihi IS NOT NULL";
        } elseif ($op !== 'tum' && $date !== null) {
            $operators = ['gt' => '>', 'lt' => '<', 'gte' => '>=', 'lte' => '<=', 'equals' => '='];
            $where .= " AND p.ayrilma_tarihi {$operators[$op]} ?";
            $params[] = $date;
        }

        $stmt = $this->db->prepare(
            "SELECT p.ad_soyad, p.tc_kimlik, p.goreve_baslama_tarihi, u.unvan, u.ogrenim
             FROM personeller p LEFT JOIN ucretler u ON p.ucret_id = u.id
             WHERE $where ORDER BY p.ad_soyad ASC"
        );
        $stmt->execute($params);
        $people = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $periodEnd = new DateTime($lastDay);
        $cache = [];
        $rows = [];
        $unmatched = [];

        foreach ($people as $i => $p) {
            $kidem = (new DateTime($p['goreve_baslama_tarihi']))->diff($periodEnd)->y;
            $cacheKey = ($p['unvan'] ?? '') . '|' . ($p['ogrenim'] ?? '') . '|' . $kidem;
            if (!array_key_exists($cacheKey, $cache)) {
                $cache[$cacheKey] = $this->findMatrah((string)$p['unvan'], (string)$p['ogrenim'], $kidem);
            }
            $m = $cache[$cacheKey];
            if (!$m) {
                $unmatched[] = $p['ad_soyad'] . ' (' . ($p['unvan'] ?: 'Unvan Belirtilmemiş') . ')';
            }

            $aylik = round(($m ? (int)$m['gosterge_puan'] : 0) * $maas, 2);
            $ekGost = round(($m ? (int)$m['ek_gosterge_puan'] : 0) * $maas, 2);
            $yanOde = round(($m ? (int)$m['yan_odeme_puan'] : 0) * $yan, 2);
            $ozHiz = round(($m ? (int)$m['ozel_hizmet_puan'] : 0) * $maas * 95, 2);

            $rows[] = [
                'sira' => $i + 1,
                'tc' => (string)$p['tc_kimlik'],
                'aylik' => $aylik,
                'ek_gost' => $ekGost,
                'yan_ode' => $yanOde,
                'oz_hiz' => $ozHiz,
                'toplam' => round($aylik + $ekGost + $yanOde + $ozHiz, 2),
                'derece' => $m ? (string)$m['derece'] : '',
                'ad_soyad' => $p['ad_soyad'],
                'kidem' => $kidem,
            ];
        }

        return ['rows' => $rows, 'unmatched' => $unmatched];
    }

    private function findMatrah(string $unvan, string $ogrenim, int $kidem): ?array {
        $stmt = $this->db->prepare(
            "SELECT * FROM matrah WHERE unvan = ? AND ogrenim = ? ORDER BY ABS(hizmet_yili - ?) ASC LIMIT 1"
        );
        $stmt->execute([$unvan, $ogrenim, $kidem]);
        $m = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($m) {
            return $m;
        }
        $stmt = $this->db->prepare("SELECT * FROM matrah WHERE unvan = ? ORDER BY ABS(hizmet_yili - ?) ASC LIMIT 1");
        $stmt->execute([$unvan, $kidem]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}
