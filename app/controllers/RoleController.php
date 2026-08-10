<?php

class RoleController extends Controller {
    private $roleModel;
    private $rolePermissionModel;
    private $activityLogModel;

    public function __construct() {
        $this->roleModel = new Role();
        $this->rolePermissionModel = new RolePermission();
        $this->activityLogModel = new ActivityLog();

        // Admin, Superadmin veya /yetki-gruplari yetkisi tanımlanmış grup kontrolü
        $userRole = $_SESSION['role'] ?? 'user';
        $userRoleId = $_SESSION['role_id'] ?? null;
        if ($userRole !== 'superadmin' && $userRole !== 'admin' && !$this->rolePermissionModel->hasAccess($userRoleId, '/yetki-gruplari', $userRole)) {
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'message' => 'Bu işlem için yetkiniz bulunmamaktadır.']);
                exit;
            }
            header('Location: ' . routeUrl('/'));
            exit;
        }
    }

    /**
     * Yetki grupları listesi ve sayfa izin matrisi sayfasını görüntüler
     */
    public function index() {
        global $pageTitle, $pageSubtitle;
        $pageTitle = 'Yetki Grupları & Sayfa İzinleri';
        $pageSubtitle = 'Sistemdeki rollerin hangi sayfalara erişebileceğini belirleyin';

        $tenantId = $_SESSION['tenant_id'] ?? null;
        $roles = $this->roleModel->getAllForTenant($tenantId);
        $definedPages = RolePermission::getAllDefinedPages();

        return [
            'roles' => $roles,
            'definedPages' => $definedPages,
            'pageTitle' => $pageTitle,
            'pageSubtitle' => $pageSubtitle
        ];
    }

    /**
     * Yeni yetki grubu ekler (AJAX)
     */
    public function store() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $tenantId = $_SESSION['tenant_id'] ?? null;

            if (empty($name)) {
                echo json_encode(['success' => false, 'message' => 'Lütfen yetki grubu adını girin.']);
                exit;
            }

            $roleId = $this->roleModel->createRole($name, $description, $tenantId);

            if ($roleId) {
                $this->activityLogModel->log(
                    'create_role',
                    "Yeni yetki grubu oluşturuldu: {$name} (ID: {$roleId})"
                );
                echo json_encode([
                    'success' => true,
                    'message' => 'Yetki grubu başarıyla eklendi.',
                    'role_id' => $roleId
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Yetki grubu eklenirken bir hata oluştu.']);
            }
        } catch (Exception $e) {
            error_log('RoleController store error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Bir sunucu hatası oluştu.']);
        }
        exit;
    }

    /**
     * Yetki grubunu günceller (AJAX)
     */
    public function update() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $id = (int)($_POST['id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');

            if ($id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Geçersiz yetki grubu.']);
                exit;
            }

            $success = $this->roleModel->updateRole($id, $name, $description);

            if ($success) {
                $this->activityLogModel->log(
                    'update_role',
                    "Yetki grubu güncellendi (ID: {$id}, Ad: {$name})"
                );
                echo json_encode(['success' => true, 'message' => 'Yetki grubu güncellendi.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Sistem rolleri düzenlenemez veya güncelleme başarısız.']);
            }
        } catch (Exception $e) {
            error_log('RoleController update error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Bir sunucu hatası oluştu.']);
        }
        exit;
    }

    /**
     * Yetki grubunu siler (AJAX)
     */
    public function delete() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $id = (int)($_POST['id'] ?? 0);

            if ($id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Geçersiz yetki grubu.']);
                exit;
            }

            $userCount = $this->roleModel->getUserCount($id);
            if ($userCount > 0) {
                echo json_encode([
                    'success' => false,
                    'message' => "Bu yetki grubuna tanımlı {$userCount} kullanıcı bulunmaktadır. Silmeden önce kullanıcıların rolünü değiştirin."
                ]);
                exit;
            }

            $success = $this->roleModel->deleteRole($id);

            if ($success) {
                $this->activityLogModel->log('delete_role', "Yetki grubu silindi (ID: {$id})");
                echo json_encode(['success' => true, 'message' => 'Yetki grubu başarıyla silindi.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Sistem rolleri silinemez veya işlem başarısız.']);
            }
        } catch (Exception $e) {
            error_log('RoleController delete error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Bir sunucu hatası oluştu.']);
        }
        exit;
    }

    /**
     * Belirli bir rolün sayfa izinlerini getirir (AJAX)
     */
    public function getPermissions() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $roleId = (int)($_GET['role_id'] ?? 0);
            if ($roleId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Geçersiz rol.']);
                exit;
            }

            $permissions = $this->rolePermissionModel->getPermissionsForRole($roleId);
            $role = $this->roleModel->find($roleId);

            echo json_encode([
                'success' => true,
                'role' => $role,
                'permissions' => $permissions
            ]);
        } catch (Exception $e) {
            error_log('RoleController getPermissions error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Bir sunucu hatası oluştu.']);
        }
        exit;
    }

    /**
     * Belirli bir rol için sayfa izinlerini kaydeder (AJAX)
     */
    public function savePermissions() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $roleId = (int)($_POST['role_id'] ?? 0);
            $permissions = $_POST['permissions'] ?? [];

            if ($roleId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Geçersiz rol.']);
                exit;
            }

            if ($roleId == 1) {
                echo json_encode(['success' => false, 'message' => 'Superadmin rolünün izinleri kısıtlanamaz.']);
                exit;
            }

            $success = $this->rolePermissionModel->setPagePermissions($roleId, $permissions);

            if ($success) {
                $role = $this->roleModel->find($roleId);
                $roleName = $role['name'] ?? 'Bilinmeyen Rol';
                $this->activityLogModel->log(
                    'update_permissions',
                    "{$roleName} (ID: {$roleId}) yetki grubunun sayfa izinleri güncellendi."
                );

                echo json_encode(['success' => true, 'message' => 'Sayfa erişim izinleri başarıyla güncellendi.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'İzinler kaydedilirken bir hata oluştu.']);
            }
        } catch (Exception $e) {
            error_log('RoleController savePermissions error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Bir sunucu hatası oluştu.']);
        }
        exit;
    }
}
