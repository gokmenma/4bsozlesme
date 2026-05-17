<?php

require_once __DIR__ . '/../models/Tenant.php';

class UserController extends Controller {
    private $userModel;

    public function __construct() {
        $this->userModel = new User();
        
        // Admin veya Superadmin kontrolü
        if ($_SESSION['role'] !== 'superadmin' && $_SESSION['role'] !== 'admin') {
            if ($this->isAjax()) {
                echo json_encode(['success' => false, 'message' => 'Yetkiniz yok.']);
                exit;
            }
            header('Location: ' . routeUrl('/'));
            exit;
        }
    }

    public function list() {
        $tenant_id = $_SESSION['tenant_id'];
        $subtitle = 'Bu kuruma bağlı kullanıcıları yönetin';
        
        // Superadmin ise ve tenant_id parametresi gelmişse o kurumu filtrele
        if ($_SESSION['role'] === 'superadmin' && !empty($_GET['tenant_id'])) {
            $tenant_id = $_GET['tenant_id'];
            $tenantModel = new Tenant();
            $tenant = $tenantModel->find($tenant_id);
            if ($tenant) {
                $subtitle = htmlspecialchars($tenant['name']) . ' kurumuna bağlı kullanıcılar';
            }
        }

        $viewType = $_GET['view_type'] ?? 'mine';
        if ($_SESSION['role'] === 'superadmin' && $viewType === 'all') {
            $users = $this->userModel->allWithCreator();
            $subtitle = 'Sistemdeki tüm kullanıcıları yönetin';
        } else {
            $viewType = 'mine';
            $users = $this->userModel->whereWithCreator('tenant_id', $tenant_id);
        }

        global $db;
        $users = array_map(function($user) use ($db) {
            $stmt = $db->prepare("SELECT s.*, sp.name as plan_name FROM subscriptions s 
                                JOIN subscription_plans sp ON s.plan_id = sp.id
                                WHERE s.tenant_id = ? ORDER BY s.id DESC LIMIT 1");
            $stmt->execute([$user['tenant_id'] ?? null]);
            $user['subscription'] = $stmt->fetch();

            if (!empty($user['tenant_id'])) {
                $stmtTenant = $db->prepare("SELECT name FROM tenants WHERE id = ?");
                $stmtTenant->execute([$user['tenant_id']]);
                $t = $stmtTenant->fetch();
                $user['tenant_name'] = $t['name'] ?? null;
            } else {
                $user['tenant_name'] = null;
            }

            return $user;
        }, $users);

        return [
            'pageTitle' => 'Kullanıcı Yönetimi',
            'pageSubtitle' => $subtitle,
            'users' => $users,
            'viewType' => $viewType
        ];
    }

    public function store() {
        try {
            $role = $_POST['role'] ?? 'user';
            $tenant_id = $_SESSION['tenant_id'];
            $email = $_POST['email'];

            // Aynı kurumda aynı e-posta var mı kontrol et
            $existing = $this->userModel->getDb()->prepare("SELECT id FROM users WHERE email = ? AND tenant_id = ?");
            $existing->execute([$email, $tenant_id]);
            if ($existing->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Bu e-posta adresi bu kurumda zaten kayıtlı.']);
                exit;
            }

            // Güvenlik Kilidi: Oturum açan kullanıcı ID'si 1 değilse kimseye superadmin yetkisi atayamaz!
            if ($_SESSION['user_id'] != 1 && $role === 'superadmin') {
                $role = 'admin';
            }

            $trial_ends_at = date('Y-m-d', strtotime('+1 month'));
            if ($_SESSION['role'] === 'superadmin' && !empty($_POST['trial_ends_at'])) {
                $trial_ends_at = date('Y-m-d', strtotime($_POST['trial_ends_at']));
            }

            $data = [
                'name' => $_POST['name'],
                'email' => $email,
                'password' => password_hash($_POST['password'], PASSWORD_DEFAULT),
                'role' => $role,
                'tenant_id' => $tenant_id,
                'created_by' => $_SESSION['user_id'] ?? null,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
                'trial_ends_at' => $trial_ends_at
            ];

            $result = $this->userModel->create($data);

            if ($result) {
                // user_tenants tablosuna da ekle
                if ($tenant_id) {
                    $tenantModel = new Tenant();
                    $tenantModel->associateWithUser($tenant_id, $result, $role);
                }
                echo json_encode(['success' => true, 'message' => 'Kullanıcı başarıyla eklendi.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Kullanıcı eklenirken bir hata oluştu.']);
            }
        } catch (Exception $e) {
            $message = $e->getMessage();
            if (strpos($message, '1062') !== false) {
                $message = 'Bu e-posta adresi bu kurumda zaten kullanımda.';
            }
            echo json_encode(['success' => false, 'message' => 'Hata: ' . $message]);
        }
    }

    public function get() {
        try {
            $id = $_GET['id'];
            $user = $this->userModel->find($id);

            // Yetki kontrolü: Kendi tenant'ında mı?
            if ($_SESSION['role'] !== 'superadmin' && $user['tenant_id'] != $_SESSION['tenant_id']) {
                echo json_encode(['success' => false, 'message' => 'Bu kullanıcıya erişim yetkiniz yok.']);
                exit;
            }

            echo json_encode($user);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Hata: ' . $e->getMessage()]);
        }
    }

    public function update() {
        try {
            $id = $_POST['id'];
            $user = $this->userModel->find($id);

            // Yetki kontrolü
            if ($_SESSION['role'] !== 'superadmin' && $user['tenant_id'] != $_SESSION['tenant_id']) {
                echo json_encode(['success' => false, 'message' => 'Bu kullanıcıyı güncelleme yetkiniz yok.']);
                exit;
            }

            $role = $_POST['role'] ?? $user['role'];
            $tenant_id = $_SESSION['tenant_id'];
            $email = $_POST['email'];

            // Aynı kurumda başka bir kullanıcıda aynı e-posta var mı kontrol et
            $existing = $this->userModel->getDb()->prepare("SELECT id FROM users WHERE email = ? AND tenant_id = ? AND id != ?");
            $existing->execute([$email, $tenant_id, $id]);
            if ($existing->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Bu e-posta adresi bu kurumda başka bir kullanıcı tarafından kullanılıyor.']);
                exit;
            }

            // Güvenlik Kilidi: ID'si 1 olan ana kurucu kullanıcıyı kimse güncelleyemez, silemez veya yetkilerini değiştiremez!
            if ($id == 1 && $_SESSION['user_id'] != 1) {
                echo json_encode(['success' => false, 'message' => 'Ana kurucu kullanıcının bilgilerini veya yetkilerini değiştirme hakkınız yoktur.']);
                exit;
            }

            // Güvenlik Kilidi: Oturum açan kullanıcı ID'si 1 değilse kimseye superadmin yetkisi atayamaz!
            if ($_SESSION['user_id'] != 1 && $role === 'superadmin') {
                if ($user['role'] !== 'superadmin') {
                    $role = $user['role']; // Rol yükseltmeyi engelle, eski rolünü koru
                }
            }

            $data = [
                'name' => $_POST['name'],
                'email' => $email,
                'role' => $role,
                'tenant_id' => $tenant_id,
                'updated_at' => date('Y-m-d H:i:s')
            ];

            if ($_SESSION['role'] === 'superadmin' && !empty($_POST['trial_ends_at'])) {
                $data['trial_ends_at'] = date('Y-m-d', strtotime($_POST['trial_ends_at']));
            }

            if (!empty($_POST['password'])) {
                $data['password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
            }

            $result = $this->userModel->update($id, $data);

            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Kullanıcı başarıyla güncellendi.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Kullanıcı güncellenirken bir hata oluştu.']);
            }
        } catch (Exception $e) {
            $message = $e->getMessage();
            if (strpos($message, '1062') !== false) {
                $message = 'Bu e-posta adresi bu kurumda zaten kullanımda.';
            }
            echo json_encode(['success' => false, 'message' => 'Hata: ' . $message]);
        }
    }

    public function delete() {
        try {
            $id = $_POST['id'];
            $user = $this->userModel->find($id);

            // Yetki kontrolü
            if ($_SESSION['role'] !== 'superadmin' && $user['tenant_id'] != $_SESSION['tenant_id']) {
                echo json_encode(['success' => false, 'message' => 'Bu kullanıcıyı silme yetkiniz yok.']);
                exit;
            }

            // Güvenlik Kilidi: ID'si 1 olan ana kurucu kullanıcıyı hiç kimse silemez!
            if ($id == 1) {
                echo json_encode(['success' => false, 'message' => 'Ana kurucu kullanıcı sistemden silinemez!']);
                exit;
            }

            // Kendini silemez
            if ($id == $_SESSION['user_id']) {
                echo json_encode(['success' => false, 'message' => 'Kendi hesabınızı buradan silemezsiniz.']);
                exit;
            }

            $result = $this->userModel->delete($id);

            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Kullanıcı başarıyla silindi.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Kullanıcı silinirken bir hata oluştu.']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Hata: ' . $e->getMessage()]);
        }
    }

    private function isAjax() {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
    }
}
