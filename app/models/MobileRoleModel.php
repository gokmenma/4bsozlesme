<?php

require_once __DIR__ . '/../core/Model.php';

class MobileRoleModel extends Model {
    protected $table = 'roles';

    public function list(?int $tenantId, bool $isSuper): array {
        return (new Role())->getAllForTenant($isSuper ? null : $tenantId);
    }

    public function getRole(int $id): ?array {
        $stmt = $this->db->prepare("SELECT id, name, description, tenant_id, is_system FROM roles WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function canManage(array $role, int $tenantId, bool $isSuper): bool {
        if ($isSuper) {
            return true;
        }
        return empty($role['is_system']) && (int)($role['tenant_id'] ?? 0) === $tenantId;
    }

    public function permissions(int $roleId): array {
        return (new RolePermission())->getPermissionsForRole($roleId);
    }

    public function pages(): array {
        $out = [];
        foreach (RolePermission::getAllDefinedPages() as $route => $info) {
            $out[] = ['route' => $route, 'title' => $info['title'], 'category' => $info['category']];
        }
        return $out;
    }

    public function createRole(string $name, string $description, int $tenantId): int {
        return (int)(new Role())->createRole($name, $description, $tenantId);
    }

    public function updateRole(int $id, string $name, string $description): bool {
        return (bool)(new Role())->updateRole($id, $name, $description);
    }

    public function deleteRole(int $id): bool {
        return (bool)(new Role())->deleteRole($id);
    }

    public function userCount(int $id): int {
        return (new Role())->getUserCount($id);
    }

    public function savePermissions(int $roleId, array $permissions): bool {
        return (new RolePermission())->setPagePermissions($roleId, $permissions);
    }
}
