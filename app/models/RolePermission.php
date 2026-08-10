<?php

require_once __DIR__ . '/../core/Model.php';

class RolePermission extends Model {
    protected $table = 'role_permissions';

    /**
     * Sistemdeki tüm erişim kontrolüne tabi sayfaların listesi
     */
    public static function getAllDefinedPages(): array {
        return [
            '/'                      => ['title' => 'Ana Sayfa', 'category' => 'Genel', 'icon' => 'house'],
            '/personel-listesi'      => ['title' => 'Personel Yönetimi', 'category' => 'Yönetim', 'icon' => 'users'],
            '/ucret-tanimlari'       => ['title' => 'Ücret Tanımları', 'category' => 'Yönetim', 'icon' => 'coins'],
            '/sozlesme-taslagi'      => ['title' => 'Sözleşme Taslağı', 'category' => 'Diğer İşlemler', 'icon' => 'file-text'],
            '/tanimlamalar'          => ['title' => 'Tanımlamalar', 'category' => 'Diğer İşlemler', 'icon' => 'file-cog'],
            '/yapilacaklar'          => ['title' => 'Yapılacaklar (Kanban)', 'category' => 'Diğer İşlemler', 'icon' => 'list-todo'],
            '/matrah-yonetimi'       => ['title' => 'Matrah Yönetimi', 'category' => 'Yönetim', 'icon' => 'calculator'],
            '/doner-matrahi-olustur' => ['title' => 'Döner Matrahı Oluştur', 'category' => 'Diğer İşlemler', 'icon' => 'banknote'],
            '/kullanicilar'          => ['title' => 'Kullanıcı Yönetimi', 'category' => 'Sistem Yönetimi', 'icon' => 'users'],
            '/yetki-gruplari'        => ['title' => 'Yetki Grupları & İzinler', 'category' => 'Sistem Yönetimi', 'icon' => 'shield'],
            '/kurum-yonetimi'        => ['title' => 'Kurum Yönetimi (Superadmin)', 'category' => 'Sistem Yönetimi', 'icon' => 'building-2'],
            '/abonelik'              => ['title' => 'Abonelik Yönetimi (Superadmin)', 'category' => 'Sistem Yönetimi', 'icon' => 'credit-card'],
            '/ayarlar'               => ['title' => 'Sistem Ayarları (Superadmin)', 'category' => 'Sistem Yönetimi', 'icon' => 'settings'],
            '/geri-bildirimler'      => ['title' => 'Geri Bildirimler (Superadmin)', 'category' => 'Sistem Yönetimi', 'icon' => 'message-square'],
        ];
    }

    /**
     * Belirli bir rolün tanımlı tüm izinlerini [page_route => can_access] olarak getirir
     */
    public function getPermissionsForRole($roleId) {
        $stmt = $this->db->prepare("SELECT page_route, can_access FROM role_permissions WHERE role_id = ?");
        $stmt->execute([$roleId]);
        $rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        $allPages = self::getAllDefinedPages();
        $result = [];

        // Superadmin rolü (ID = 1) varsayılan olarak her şeye izinlidir
        $isSuperadmin = ($roleId == 1);

        foreach ($allPages as $route => $info) {
            if ($isSuperadmin) {
                $result[$route] = 1;
            } elseif (isset($rows[$route])) {
                $result[$route] = (int)$rows[$route];
            } else {
                // Kayıt yoksa varsayılan durum: Ana sayfa izinli, superadmin özel sayfaları kısıtlı, diğerleri izinli
                $superadminOnlyPages = ['/kurum-yonetimi', '/abonelik', '/ayarlar', '/geri-bildirimler'];
                if (in_array($route, $superadminOnlyPages, true)) {
                    $result[$route] = 0;
                } else {
                    $result[$route] = 1;
                }
            }
        }

        return $result;
    }

    /**
     * Rolün belirli bir rotaya erişim izni olup olmadığını doğrular
     */
    public function hasAccess($roleId, $pageRoute, $userRoleName = '') {
        // Superadmin kontrolü
        if ($userRoleName === 'superadmin' || $roleId == 1) {
            return true;
        }

        // Ana sayfa ve profil her zaman erişilebilir
        if ($pageRoute === '/' || $pageRoute === '/profil' || $pageRoute === '') {
            return true;
        }

        // URL normalize et
        $pageRoute = '/' . ltrim($pageRoute, '/');

        $stmt = $this->db->prepare("SELECT can_access FROM role_permissions WHERE role_id = ? AND page_route = ?");
        $stmt->execute([$roleId, $pageRoute]);
        $val = $stmt->fetchColumn();

        if ($val !== false) {
            return (bool)$val;
        }

        // Veritabanında özel kural yoksa varsayılan politika
        $superadminOnlyPages = ['/kurum-yonetimi', '/abonelik', '/ayarlar', '/geri-bildirimler'];
        if (in_array($pageRoute, $superadminOnlyPages, true)) {
            return false;
        }

        return true;
    }

    /**
     * Rolün sayfa izinlerini toplu olarak kaydeder / günceller
     */
    public function setPagePermissions($roleId, array $permissions) {
        // Superadmin rolünün izinleri değiştirilemez
        if ($roleId == 1) {
            return false;
        }

        $allPages = array_keys(self::getAllDefinedPages());

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("
                INSERT INTO role_permissions (role_id, page_route, can_access) 
                VALUES (?, ?, ?) 
                ON DUPLICATE KEY UPDATE can_access = VALUES(can_access), updated_at = NOW()
            ");

            foreach ($allPages as $route) {
                $canAccess = !empty($permissions[$route]) ? 1 : 0;
                $stmt->execute([$roleId, $route, $canAccess]);
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log('RolePermission update error: ' . $e->getMessage());
            return false;
        }
    }
}
