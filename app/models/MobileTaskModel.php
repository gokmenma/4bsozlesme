<?php

require_once __DIR__ . '/../core/Model.php';

class MobileTaskModel extends Model {
    protected $table = 'kanban_tasks';

    public const PRIORITIES = ['low', 'medium', 'high'];

    public function getBoards(int $tenantId): array {
        $stmt = $this->db->prepare(
            "SELECT id, title, sort_order FROM kanban_boards WHERE tenant_id = ? ORDER BY sort_order ASC, id ASC"
        );
        $stmt->execute([$tenantId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function boardExists(int $tenantId, int $boardId): bool {
        $stmt = $this->db->prepare("SELECT 1 FROM kanban_boards WHERE id = ? AND tenant_id = ? LIMIT 1");
        $stmt->execute([$boardId, $tenantId]);
        return (bool)$stmt->fetchColumn();
    }

    public function getUsers(int $tenantId): array {
        $stmt = $this->db->prepare("SELECT id, name FROM users WHERE tenant_id = ? ORDER BY name ASC");
        $stmt->execute([$tenantId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTasks(int $tenantId, int $userId, string $scope): array {
        $sql = "SELECT t.id, t.board_id, t.title, t.description, t.priority, t.due_date, t.user_id,
                       t.created_at, u.name AS creator_name
                FROM kanban_tasks t
                LEFT JOIN users u ON u.id = t.user_id
                WHERE t.tenant_id = ?";
        $params = [$tenantId];

        if ($scope === 'assigned') {
            $sql .= " AND t.id IN (SELECT task_id FROM kanban_task_users WHERE user_id = ?)";
            $params[] = $userId;
        } elseif ($scope === 'mine') {
            $sql .= " AND t.user_id = ?";
            $params[] = $userId;
        }

        $sql .= " ORDER BY t.id DESC LIMIT 500";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!$tasks) {
            return [];
        }

        $ids = array_map(static fn($t) => (int)$t['id'], $tasks);
        $in = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->db->prepare(
            "SELECT ktu.task_id, u.id, u.name FROM kanban_task_users ktu
             JOIN users u ON u.id = ktu.user_id
             WHERE ktu.task_id IN ($in) AND u.tenant_id = ?"
        );
        $stmt->execute(array_merge($ids, [$tenantId]));
        $assignees = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $assignees[(int)$row['task_id']][] = ['id' => (int)$row['id'], 'name' => $row['name']];
        }

        foreach ($tasks as &$task) {
            $task['id'] = (int)$task['id'];
            $task['board_id'] = $task['board_id'] !== null ? (int)$task['board_id'] : null;
            $task['user_id'] = (int)$task['user_id'];
            $task['assignees'] = $assignees[$task['id']] ?? [];
        }
        unset($task);

        return $tasks;
    }

    public function getTask(int $tenantId, int $taskId): ?array {
        $stmt = $this->db->prepare("SELECT id, board_id FROM kanban_tasks WHERE id = ? AND tenant_id = ? LIMIT 1");
        $stmt->execute([$taskId, $tenantId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function createTask(int $tenantId, int $userId, array $data): int {
        $stmt = $this->db->prepare(
            "INSERT INTO kanban_tasks (tenant_id, user_id, board_id, title, description, priority, due_date)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $tenantId, $userId, $data['board_id'], $data['title'],
            $data['description'], $data['priority'], $data['due_date'],
        ]);
        $taskId = (int)$this->db->lastInsertId();
        $this->setAssignees($tenantId, $taskId, $data['assignees']);
        return $taskId;
    }

    public function updateTask(int $tenantId, int $taskId, array $data): bool {
        $stmt = $this->db->prepare(
            "UPDATE kanban_tasks SET board_id = ?, title = ?, description = ?, priority = ?, due_date = ?
             WHERE id = ? AND tenant_id = ?"
        );
        $stmt->execute([
            $data['board_id'], $data['title'], $data['description'],
            $data['priority'], $data['due_date'], $taskId, $tenantId,
        ]);
        $this->setAssignees($tenantId, $taskId, $data['assignees']);
        return true;
    }

    public function moveTask(int $tenantId, int $taskId, int $boardId): bool {
        $stmt = $this->db->prepare("UPDATE kanban_tasks SET board_id = ? WHERE id = ? AND tenant_id = ?");
        $stmt->execute([$boardId, $taskId, $tenantId]);
        return true;
    }

    public function deleteTask(int $tenantId, int $taskId): bool {
        $this->db->prepare(
            "DELETE FROM kanban_task_users WHERE task_id IN (SELECT id FROM kanban_tasks WHERE id = ? AND tenant_id = ?)"
        )->execute([$taskId, $tenantId]);
        $stmt = $this->db->prepare("DELETE FROM kanban_tasks WHERE id = ? AND tenant_id = ?");
        $stmt->execute([$taskId, $tenantId]);
        return $stmt->rowCount() > 0;
    }

    private function setAssignees(int $tenantId, int $taskId, array $userIds): void {
        $this->db->prepare("DELETE FROM kanban_task_users WHERE task_id = ?")->execute([$taskId]);
        $userIds = array_values(array_unique(array_filter(array_map('intval', $userIds), static fn($v) => $v > 0)));
        if (!$userIds) {
            return;
        }
        $in = implode(',', array_fill(0, count($userIds), '?'));
        $stmt = $this->db->prepare("SELECT id FROM users WHERE tenant_id = ? AND id IN ($in)");
        $stmt->execute(array_merge([$tenantId], $userIds));
        $valid = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $insert = $this->db->prepare("INSERT INTO kanban_task_users (task_id, user_id) VALUES (?, ?)");
        foreach ($valid as $uid) {
            $insert->execute([$taskId, (int)$uid]);
        }
    }
}
