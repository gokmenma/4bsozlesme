<?php

require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/Tenant.php';
require_once __DIR__ . '/../models/User.php';

class TenantController extends Controller {
    
    /**
     * Kurum değiştirme işlemi
     */
    public function switch() {
        $tenantId = $_GET['id'] ?? null;
        if (!$tenantId) {
            header('Location: ' . routeUrl('/'));
            exit;
        }

        $userId = $_SESSION['user_id'];
        $userModel = new User();
        $tenants = $userModel->getTenants($userId);

        // Kullanıcının bu kuruma yetkisi var mı kontrol et
        $hasAccess = false;
        foreach ($tenants as $t) {
            if ($t['id'] == $tenantId) {
                $hasAccess = true;
                break;
            }
        }

        if ($hasAccess) {
            $_SESSION['tenant_id'] = $tenantId;
            // Kullanıcının ana tenant_id'sini de güncellemek gerekebilir veya sadece session'da kalsın
            // Şimdilik sadece session'da güncelliyoruz ki hızlıca geçiş yapsın.
            $userModel->update($userId, ['tenant_id' => $tenantId]);
        }

        header('Location: ' . $_SERVER['HTTP_REFERER'] ?? routeUrl('/'));
        exit;
    }

    /**
     * Yeni kurum ekleme işlemi
     */
    public function store() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . routeUrl('/'));
            exit;
        }

        $name = $_POST['name'] ?? '';
        if (empty($name)) {
            $_SESSION['error'] = 'Kurum adı boş olamaz.';
            header('Location: ' . $_SERVER['HTTP_REFERER']);
            exit;
        }

        $tenantModel = new Tenant();
        $userModel = new User();
        $userId = $_SESSION['user_id'];
        $tenants = $userModel->getTenants($userId);
        $currentTenantId = $_SESSION['tenant_id'];

        // Abonelik limit kontrolü
        global $db;
        $stmt = $db->prepare("SELECT s.*, sp.features FROM subscriptions s 
                              JOIN subscription_plans sp ON s.plan_id = sp.id
                              WHERE s.tenant_id = ? AND s.status = 'active' 
                              ORDER BY s.id DESC LIMIT 1");
        $stmt->execute([$currentTenantId]);
        $activeSub = $stmt->fetch();

        $tenantLimit = 1;
        if ($_SESSION['role'] === 'superadmin') {
            $tenantLimit = 999;
        } elseif ($activeSub) {
            $features = $activeSub['features'];
            if (stripos($features, 'Sınırsız') !== false) {
                $tenantLimit = 999;
            } else {
                // Virgülle ayrılmış özellikleri kontrol et
                $featureList = explode(',', $features);
                foreach ($featureList as $feature) {
                    if (preg_match('/(\d+)\s*Kurum/i', trim($feature), $matches)) {
                        $tenantLimit = (int)$matches[1];
                        break;
                    }
                }
            }
        }

        if (count($tenants) >= $tenantLimit) {
            $_SESSION['error'] = 'Kurum ekleme limitine ulaştınız. Lütfen paketinizi yükseltin.';
            header('Location: ' . $_SERVER['HTTP_REFERER']);
            exit;
        }
        
        // Slug oluşturma
        $slug = mb_strtolower($name, 'UTF-8');
        $slug = str_replace(['ı', 'ğ', 'ü', 'ş', 'ö', 'ç'], ['i', 'g', 'u', 's', 'o', 'c'], $slug);
        $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
        $slug = preg_replace('/[\s-]+/', ' ', $slug);
        $slug = preg_replace('/\s/', '-', $slug);
        $slug = trim($slug, '-');

        $tenantId = $tenantModel->create([
            'name' => $name,
            'slug' => $slug,
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        if ($tenantId) {
            $userId = $_SESSION['user_id'];
            $tenantModel->associateWithUser($tenantId, $userId, 'admin');
            
            // Otomatik olarak yeni kuruma geçiş yap
            $_SESSION['tenant_id'] = $tenantId;
            $userModel = new User();
            $userModel->update($userId, ['tenant_id' => $tenantId]);
            
            $_SESSION['success'] = 'Yeni kurum başarıyla eklendi ve seçildi.';
        } else {
            $_SESSION['error'] = 'Kurum eklenirken bir hata oluştu.';
        }

        header('Location: ' . routeUrl('/'));
        exit;
    }
}
