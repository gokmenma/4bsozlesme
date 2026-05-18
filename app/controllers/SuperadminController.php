<?php
require_once __DIR__ . '/../models/Tenant.php';
require_once __DIR__ . '/../models/User.php';

class SuperadminController extends Controller {
    private $tenantModel;
    private $userModel;

    public function __construct() {
        $this->tenantModel = new Tenant();
        $this->userModel = new User();
        
        // Sadece Superadmin erişebilir
        if ($_SESSION['role'] !== 'superadmin') {
            if ($this->isAjax()) {
                echo json_encode(['success' => false, 'message' => 'Yetkiniz yok.']);
                exit;
            }
            header('Location: ' . routeUrl('/'));
            exit;
        }
    }

    /**
     * Tüm kurumları listeler
     */
    public function tenants() {
        $tenants = $this->tenantModel->all();
        
        // Her kurumun kullanıcı sayısını da alalım (isteğe bağlı ama güzel olur)
        foreach ($tenants as &$t) {
            $stmt = $this->tenantModel->getDb()->prepare("SELECT COUNT(*) as count FROM users WHERE tenant_id = ?");
            $stmt->execute([$t['id']]);
            $t['user_count'] = $stmt->fetch()['count'];
        }

        return [
            'pageTitle' => 'Kurum Yönetimi',
            'pageSubtitle' => 'Sistemdeki tüm kurumları yönetin',
            'tenants' => $tenants
        ];
    }

    /**
     * Kurum detaylarını getir (AJAX)
     */
    public function getTenant() {
        if (ob_get_level()) {
            ob_clean();
        }
        header('Content-Type: application/json; charset=utf-8');
        
        $id = $_GET['id'] ?? 0;
        $tenant = $this->tenantModel->find($id);
        echo json_encode($tenant ?: ['error' => 'Kurum bulunamadı']);
        exit;
    }

    /**
     * Kurum bilgilerini güncelle (AJAX)
     */
    public function updateTenant() {
        if (ob_get_level()) {
            ob_clean();
        }
        header('Content-Type: application/json; charset=utf-8');

        $id = $_POST['id'] ?? 0;
        $data = [
            'name' => $_POST['name'] ?? '',
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $result = $this->tenantModel->update($id, $data);

        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Kurum başarıyla güncellendi.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Kurum güncellenirken bir hata oluştu.']);
        }
        exit;
    }

    /**
     * Kurum sil (AJAX)
     */
    public function deleteTenant() {
        if (ob_get_level()) {
            ob_clean();
        }
        header('Content-Type: application/json; charset=utf-8');

        $id = $_POST['id'] ?? 0;
        
        // Önce bağlı kullanıcıları kontrol et vs (isteğe bağlı)
        
        $result = $this->tenantModel->delete($id);

        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Kurum başarıyla silindi.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Kurum silinirken bir hata oluştu.']);
        }
        exit;
    }

    /**
     * Tüm kurumları JSON olarak döndürür (DataTable AJAX için)
     */
    public function listTenantsJSON() {
        if (ob_get_level()) {
            ob_clean();
        }
        header('Content-Type: application/json; charset=utf-8');

        $tenants = $this->tenantModel->all();
        
        $userId = $_SESSION['user_id'] ?? 0;
        $tenantId = $_SESSION['tenant_id'] ?? 0;

        // Mevcut kullanıcının bağlı olduğu kurumları alalım
        $myTenants = $userId ? $this->userModel->getTenants($userId) : [];
        $myTenantIds = array_column($myTenants, 'id');

        foreach ($tenants as &$t) {
            $stmt = $this->tenantModel->getDb()->prepare("SELECT COUNT(*) as count FROM users WHERE tenant_id = ?");
            $stmt->execute([$t['id']]);
            $t['user_count'] = $stmt->fetch()['count'];
            
            // Format dates and status for easier JS handling
            $t['created_at_formatted'] = !empty($t['created_at']) ? date('d.m.Y H:i', strtotime($t['created_at'])) : '-';
            $t['status_label'] = $t['is_active'] ? 'Aktif' : 'Pasif';
            $t['status_class'] = $t['is_active'] ? 'border-green-100 dark:border-green-900/30 text-green-700 dark:text-green-400' : 'border-red-100 dark:border-red-900/30 text-red-700 dark:text-red-400';
            
            // Kullanıcının kendi kurumu mu?
            $t['is_mine'] = in_array($t['id'], $myTenantIds);
            $t['is_current'] = $t['id'] == $tenantId;
        }

        echo json_encode(['data' => $tenants]);
        exit;
    }

    private function isAjax() {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
    }
}
