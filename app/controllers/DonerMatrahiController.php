<?php

class DonerMatrahiController extends Controller {
    
    public function index() {
        $definitionModel = new Definition();
        $tenant_id = $_SESSION['tenant_id'] ?? 0;
        
        $settings = $definitionModel->getSettings($tenant_id);

        return [
            'settings' => $settings,
            'pageTitle' => 'Döner Matrahı Oluştur',
            'pageSubtitle' => 'Bu sayfadan dönem bilgileri ve katsayılar ile döner matrahı oluşturabilirsiniz.'
        ];
    }

    public function downloadBasis() {
        global $db;
        $tenant_id = $_SESSION['tenant_id'] ?? 1;

        $ay = (int)($_GET['donem_ay'] ?? date('n'));
        $yil = (int)($_GET['donem_yil'] ?? date('Y'));
        $maas_katsayisi = (float)($_GET['maas_katsayisi'] ?? 0.0);
        $yan_odeme_katsayisi = (float)($_GET['yan_odeme_katsayisi'] ?? 0.0);

        // Find period last day
        $lastDay = date('Y-m-t', strtotime("$yil-$ay-01"));

        // Fetch all active personnel whose hire date <= lastDay
        $stmt = $db->prepare("
            SELECT p.*, u.unvan, u.ogrenim
            FROM personeller p
            LEFT JOIN ucretler u ON p.ucret_id = u.id
            WHERE p.deleted_at IS NULL 
              AND p.tenant_id = ? 
              AND p.durum = 'aktif'
              AND p.goreve_baslama_tarihi <= ?
        ");
        $stmt->execute([$tenant_id, $lastDay]);
        $personnels = $stmt->fetchAll();

        $rows = [];
        $unmatched = [];
        $sira = 1;
        foreach ($personnels as $p) {
            // Calculate completed seniority years as of period end date
            $baslama = new DateTime($p['goreve_baslama_tarihi']);
            $periodEnd = new DateTime($lastDay);
            $diff = $baslama->diff($periodEnd);
            $kidem_yili = $diff->y;

            // Search in matrah table by unvan, ogrenim, and closest hizmet_yili
            $stmt_m = $db->prepare("
                SELECT * FROM matrah 
                WHERE unvan = ? AND ogrenim = ? 
                ORDER BY ABS(hizmet_yili - ?) ASC 
                LIMIT 1
            ");
            $stmt_m->execute([$p['unvan'], $p['ogrenim'], $kidem_yili]);
            $m = $stmt_m->fetch();

            if (!$m) {
                // Fallback to closest matching unvan only
                $stmt_m2 = $db->prepare("
                    SELECT * FROM matrah 
                    WHERE unvan = ? 
                    ORDER BY ABS(hizmet_yili - ?) ASC 
                    LIMIT 1
                ");
                $stmt_m2->execute([$p['unvan'], $kidem_yili]);
                $m = $stmt_m2->fetch();
            }

            if (!$m) {
                $unmatched[] = $p['ad_soyad'] . ' (' . ($p['unvan'] ?? 'Unvan Belirtilmemiş') . ')';
            }

            // Defaults if still not found
            $gosterge_puan = $m ? (int)$m['gosterge_puan'] : 0;
            $ek_gosterge_puan = $m ? (int)$m['ek_gosterge_puan'] : 0;
            $yan_odeme_puan = $m ? (int)$m['yan_odeme_puan'] : 0;
            $ozel_hizmet_puan = $m ? (int)$m['ozel_hizmet_puan'] : 0;
            $derece = $m ? $m['derece'] : '';

            // Calculate formulas
            $aylik = round($gosterge_puan * $maas_katsayisi, 2);
            $ek_gost = round($ek_gosterge_puan * $maas_katsayisi, 2);
            $yan_ode = round($yan_odeme_puan * $yan_odeme_katsayisi, 2);
            $oz_hiz = round($ozel_hizmet_puan * $maas_katsayisi * 95, 2);
            $toplam = round($aylik + $ek_gost + $yan_ode + $oz_hiz, 2);

            $rows[] = [
                'sira' => $sira,
                'tc' => (string)$p['tc_kimlik'],
                'aylik' => $aylik,
                'ek_gost' => $ek_gost,
                'yan_ode' => $yan_ode,
                'oz_hiz' => $oz_hiz,
                'toplam' => $toplam,
                'derece' => $derece,
                'ad_soyad' => $p['ad_soyad'],
                'kidem' => $kidem_yili
            ];

            $sira++;
        }

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data' => $rows,
            'unmatched' => $unmatched
        ]);
        exit;
    }
}
