<?php

require_once __DIR__ . '/../core/Model.php';

class MobileUserModel extends Model {
    protected $table = 'users';

    public function list(?int $tenantId, string $search): array {
        $sql = "SELECT u.id, u.name, u.username, u.email, u.role, u.role_id, u.tenant_id, u.is_active,
                       r.name AS role_name, t.name AS tenant_name, u.created_at
                FROM users u
                LEFT JOIN roles r ON r.id = u.role_id
                LEFT JOIN tenants t ON t.id = u.tenant_id
                WHERE 1 = 1";
        $params = [];
        if ($tenantId !== null) {
            $sql .= " AND u.tenant_id = ?";
            $params[] = $tenantId;
        }
        if ($search !== '') {
            $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search) . '%';
            $sql .= " AND (u.name LIKE ? OR u.email LIKE ? OR u.username LIKE ?)";
            array_push($params, $like, $like, $like);
        }
        $sql .= " ORDER BY u.name ASC LIMIT 300";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function get(int $id): ?array {
        $stmt = $this->db->prepare("SELECT id, name, username, email, role, role_id, tenant_id, is_active FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function emailTaken(string $email, ?int $tenantId, int $exceptId = 0): bool {
        $stmt = $this->db->prepare("SELECT 1 FROM users WHERE email = ? AND tenant_id <=> ? AND id <> ? LIMIT 1");
        $stmt->execute([$email, $tenantId, $exceptId]);
        return (bool)$stmt->fetchColumn();
    }

    public function createUser(array $data): int {
        $stmt = $this->db->prepare(
            "INSERT INTO users (name, username, email, password, role, role_id, tenant_id, created_by, created_at, updated_at, trial_ends_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), ?)"
        );
        $stmt->execute([
            $data['name'], $data['username'], $data['email'], password_hash($data['password'], PASSWORD_DEFAULT),
            $data['role'], $data['role_id'], $data['tenant_id'], $data['created_by'], date('Y-m-d', strtotime('+1 month')),
        ]);
        $id = (int)$this->db->lastInsertId();
        if ($data['tenant_id']) {
            (new Tenant())->associateWithUser($data['tenant_id'], $id, $data['role']);
        }
        return $id;
    }

    public function updateUser(int $id, array $data): void {
        $sql = "UPDATE users SET name = ?, username = ?, email = ?, role = ?, role_id = ?, updated_at = NOW()";
        $params = [$data['name'], $data['username'], $data['email'], $data['role'], $data['role_id']];
        if (!empty($data['password'])) {
            $sql .= ", password = ?";
            $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        $sql .= " WHERE id = ?";
        $params[] = $id;
        $this->db->prepare($sql)->execute($params);
    }

    public function setActive(int $id, bool $active): void {
        $this->db->prepare("UPDATE users SET is_active = ?, updated_at = NOW() WHERE id = ?")
            ->execute([$active ? 1 : 0, $id]);
    }

    public function deleteUser(int $id): bool {
        $this->db->prepare("DELETE FROM user_tenants WHERE user_id = ?")->execute([$id]);
        $stmt = $this->db->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    public function getPasswordHash(int $id): ?string {
        $stmt = $this->db->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $hash = $stmt->fetchColumn();
        return $hash === false ? null : (string)$hash;
    }

    public function updatePassword(int $id, string $password): void {
        $this->db->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?")
            ->execute([password_hash($password, PASSWORD_DEFAULT), $id]);
    }
}
