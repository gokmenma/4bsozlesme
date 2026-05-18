<?php

class DashboardController extends Controller {
    
    public function index() {
        global $db;
        $tenant_id = $_SESSION['tenant_id'] ?? 0;
        
        // 1. Toplam Personel
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM personeller WHERE deleted_at IS NULL AND tenant_id = ?");
        $stmt->execute([$tenant_id]);
        $totalPersonnel = $stmt->fetch()['total'];
        
        // 2. Aktif Personeller (aktif, dilekce_alindi ve kadroya_gecmeyecek)
        $stmt = $db->prepare("
            SELECT durum, COUNT(*) as count 
            FROM personeller 
            WHERE deleted_at IS NULL AND tenant_id = ?
            GROUP BY durum
        ");
        $stmt->execute([$tenant_id]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $activeOnlyCount = 0;
        $dilekceAlindiCount = 0;
        $kadroyaGecmeyecekCount = 0;
        $kadroyaGectiCount = 0;
        $pasifCount = 0;
        
        foreach ($rows as $row) {
            if ($row['durum'] === 'aktif') {
                $activeOnlyCount = (int)$row['count'];
            } elseif ($row['durum'] === 'dilekce_alindi') {
                $dilekceAlindiCount = (int)$row['count'];
            } elseif ($row['durum'] === 'kadroya_gecmeyecek') {
                $kadroyaGecmeyecekCount = (int)$row['count'];
            } elseif ($row['durum'] === 'kadroya_gecti') {
                $kadroyaGectiCount = (int)$row['count'];
            } elseif ($row['durum'] === 'pasif') {
                $pasifCount = (int)$row['count'];
            }
        }
        
        $activePersonnel = $activeOnlyCount + $dilekceAlindiCount + $kadroyaGecmeyecekCount;
        
        // 3. Bu Ay Eklenen Personel
        $firstDayOfMonth = date('Y-m-01 00:00:00');
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM personeller WHERE deleted_at IS NULL AND created_at >= ? AND tenant_id = ?");
        $stmt->execute([$firstDayOfMonth, $tenant_id]);
        $newPersonnelThisMonth = $stmt->fetch()['total'];
        
        // 4. Toplam Ücret Tanımı
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM ucretler WHERE deleted_at IS NULL AND tenant_id = ?");
        $stmt->execute([$tenant_id]);
        $totalWages = $stmt->fetch()['total'];

        // Son eklenen 5 personel
        $stmt = $db->prepare("
            SELECT p.*, u.unvan 
            FROM personeller p 
            LEFT JOIN ucretler u ON p.ucret_id = u.id 
            WHERE p.deleted_at IS NULL AND p.tenant_id = ? 
            ORDER BY p.created_at DESC 
            LIMIT 5
        ");
        $stmt->execute([$tenant_id]);
        $recentPersonnel = $stmt->fetchAll();

        // Kadroya geçişi gelenler (Göreve başlama + 3 yıl dolanlar)
        $stmt = $db->prepare("
            SELECT p.*, u.unvan 
            FROM personeller p 
            LEFT JOIN ucretler u ON p.ucret_id = u.id 
            WHERE p.deleted_at IS NULL 
            AND p.tenant_id = ? 
            AND p.goreve_baslama_tarihi IS NOT NULL
            AND DATE_ADD(p.goreve_baslama_tarihi, INTERVAL 3 YEAR) <= CURRENT_DATE
            ORDER BY DATE_ADD(p.goreve_baslama_tarihi, INTERVAL 3 YEAR) DESC 
            LIMIT 5
        ");
        $stmt->execute([$tenant_id]);
        $eligiblePersonnel = $stmt->fetchAll();

        // Chart verisi: Son 14 günün kayıt sayıları
        $chartData = [];
        for ($i = 13; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $stmt = $db->prepare("SELECT COUNT(*) as total FROM personeller WHERE deleted_at IS NULL AND DATE(created_at) = ? AND tenant_id = ?");
            $stmt->execute([$date, $tenant_id]);
            $count = $stmt->fetch()['total'];
            $chartData[] = [
                'label' => date('d M', strtotime($date)),
                'value' => $count
            ];
        }

        return [
            'stats' => [
                'total_personnel' => $totalPersonnel,
                'active_personnel' => $activePersonnel,
                'active_only' => $activeOnlyCount,
                'dilekce_alindi' => $dilekceAlindiCount,
                'kadroya_gecmeyecek' => $kadroyaGecmeyecekCount,
                'kadroya_gecti' => $kadroyaGectiCount,
                'pasif' => $pasifCount,
                'new_personnel_this_month' => $newPersonnelThisMonth,
                'total_wages' => $totalWages
            ],
            'recent_personnel' => $recentPersonnel,
            'eligible_personnel' => $eligiblePersonnel,
            'chart_data' => $chartData
        ];
    }
}
