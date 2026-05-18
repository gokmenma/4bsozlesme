<?php
require_once __DIR__ . '/../core/Model.php';

class Task extends Model {
    protected $table = 'kanban_tasks';

    public function __construct() {
        parent::__construct();
        $this->ensureTableExists();
    }

    /**
     * Otomatik veritabanı tablosu kontrolü ve oluşturulması
     */
    private function ensureTableExists() {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table} (
            id INT AUTO_INCREMENT PRIMARY KEY,
            tenant_id INT NOT NULL,
            user_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT DEFAULT NULL,
            status ENUM('todo', 'in_progress', 'done') DEFAULT 'todo',
            priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
            due_date DATE DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX (tenant_id),
            INDEX (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $this->db->exec($sql);

        $sql_assignees = "CREATE TABLE IF NOT EXISTS kanban_task_users (
            task_id INT NOT NULL,
            user_id INT NOT NULL,
            PRIMARY KEY (task_id, user_id),
            INDEX (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $this->db->exec($sql_assignees);

        $sql_boards = "CREATE TABLE IF NOT EXISTS kanban_boards (
            id INT AUTO_INCREMENT PRIMARY KEY,
            tenant_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            sort_order INT NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX (tenant_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $this->db->exec($sql_boards);

        // Alter kanban_tasks to add board_id column if not exists
        try {
            $stmt = $this->db->prepare("SHOW COLUMNS FROM kanban_tasks LIKE 'board_id'");
            $stmt->execute();
            if (!$stmt->fetch()) {
                $this->db->exec("ALTER TABLE kanban_tasks ADD COLUMN board_id INT DEFAULT NULL, ADD INDEX (board_id)");
            }
        } catch (Exception $e) {
            // Already exists or safe skip
        }
    }

    /**
     * Kuruma ait dinamik boardları oluşturur ve mevcut görevleri bu boardlara geçirir (Migration)
     */
    public function migrateTenantToDynamicBoards($tenant_id) {
        $stmt = $this->db->prepare("SELECT COUNT(*) as cnt FROM kanban_boards WHERE tenant_id = ?");
        $stmt->execute([$tenant_id]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($res['cnt'] > 0) {
            return; // Migration already complete or custom boards exist
        }

        // Create Default Boards
        $defaultBoards = [
            ['title' => 'Backlog', 'order' => 0, 'old_status' => 'todo'],
            ['title' => 'In Progress', 'order' => 1, 'old_status' => 'in_progress'],
            ['title' => 'Done', 'order' => 2, 'old_status' => 'done']
        ];

        foreach ($defaultBoards as $dbInfo) {
            $stmtIns = $this->db->prepare("INSERT INTO kanban_boards (tenant_id, title, sort_order) VALUES (?, ?, ?)");
            $stmtIns->execute([$tenant_id, $dbInfo['title'], $dbInfo['order']]);
            $board_id = $this->db->lastInsertId();

            // Migrate tasks having this status to the newly created board ID
            $stmtMigrate = $this->db->prepare("UPDATE kanban_tasks SET board_id = ? WHERE tenant_id = ? AND status = ? AND board_id IS NULL");
            $stmtMigrate->execute([$board_id, $tenant_id, $dbInfo['old_status']]);
        }
    }

    /**
     * Kuruma ait dinamik boardları çeker (Ve gerekirse migrate eder)
     */
    public function getBoardsForTenant($tenant_id) {
        $this->migrateTenantToDynamicBoards($tenant_id);

        $stmt = $this->db->prepare("
            SELECT * FROM kanban_boards 
            WHERE tenant_id = ? 
            ORDER BY sort_order ASC, 
            CASE 
                WHEN LOWER(title) LIKE '%backlog%' OR LOWER(title) LIKE '%yapılacak%' OR LOWER(title) LIKE '%yapilacak%' OR LOWER(title) LIKE '%todo%' THEN 1
                WHEN LOWER(title) LIKE '%progress%' OR LOWER(title) LIKE '%yapılıyor%' OR LOWER(title) LIKE '%yapiliyor%' THEN 2
                WHEN LOWER(title) LIKE '%done%' OR LOWER(title) LIKE '%tamam%' THEN 3
                ELSE 4
            END ASC,
            id ASC
        ");
        $stmt->execute([$tenant_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Kurum için yeni bir board (sütun) ekler
     */
    public function createBoard($tenant_id, $title, $sort_order = null) {
        if ($sort_order === null) {
            $stmt = $this->db->prepare("SELECT MAX(sort_order) as max_order FROM kanban_boards WHERE tenant_id = ?");
            $stmt->execute([$tenant_id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $sort_order = ($row['max_order'] !== null) ? intval($row['max_order']) + 1 : 0;
        }

        $stmt = $this->db->prepare("INSERT INTO kanban_boards (tenant_id, title, sort_order) VALUES (?, ?, ?)");
        $stmt->execute([$tenant_id, $title, $sort_order]);
        return $this->db->lastInsertId();
    }

    /**
     * Bir boardu siler ve içindeki görevleri temizler
     */
    public function deleteBoard($board_id, $tenant_id) {
        // Find tasks in this board
        $stmt = $this->db->prepare("SELECT id FROM kanban_tasks WHERE board_id = ? AND tenant_id = ?");
        $stmt->execute([$board_id, $tenant_id]);
        $taskIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($taskIds)) {
            // Delete assignee relationships
            $inQuery = implode(',', array_fill(0, count($taskIds), '?'));
            $stmtClean = $this->db->prepare("DELETE FROM kanban_task_users WHERE task_id IN ($inQuery)");
            $stmtClean->execute($taskIds);

            // Delete tasks
            $stmtDelTasks = $this->db->prepare("DELETE FROM kanban_tasks WHERE board_id = ? AND tenant_id = ?");
            $stmtDelTasks->execute([$board_id, $tenant_id]);
        }

        // Delete the board
        $stmtDelBoard = $this->db->prepare("DELETE FROM kanban_boards WHERE id = ? AND tenant_id = ?");
        return $stmtDelBoard->execute([$board_id, $tenant_id]);
    }

    /**
     * Boardların sıralamasını (sort_order) günceller
     */
    public function updateBoardOrder($board_id, $sort_order, $tenant_id) {
        $stmt = $this->db->prepare("UPDATE kanban_boards SET sort_order = ? WHERE id = ? AND tenant_id = ?");
        return $stmt->execute([$sort_order, $board_id, $tenant_id]);
    }

    /**
     * Kuruma ait görevleri filtreleyerek getirir (ve atananları içerir)
     */
    public function getTasksForTenant($tenant_id, $filter = 'all', $my_user_id = null) {
        $sql = "SELECT t.*, u.name as creator_name FROM {$this->table} t 
                LEFT JOIN users u ON t.user_id = u.id 
                WHERE t.tenant_id = ?";
        
        $params = [$tenant_id];

        if ($filter === 'my' && $my_user_id !== null) {
            $sql .= " AND (t.user_id = ? OR t.id IN (SELECT task_id FROM kanban_task_users WHERE user_id = ?))";
            $params[] = $my_user_id;
            $params[] = $my_user_id;
        } elseif ($filter === 'assigned' && $my_user_id !== null) {
            $sql .= " AND t.id IN (SELECT task_id FROM kanban_task_users WHERE user_id = ?)";
            $params[] = $my_user_id;
        }

        $sql .= " ORDER BY t.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($tasks as &$task) {
            $task['assignees'] = $this->getAssignees($task['id']);
        }

        return $tasks;
    }

    /**
     * Bir göreve atanan kullanıcıları getirir
     */
    public function getAssignees($task_id) {
        $stmt = $this->db->prepare("
            SELECT u.id, u.name, u.email FROM users u 
            JOIN kanban_task_users ktu ON u.id = ktu.user_id 
            WHERE ktu.task_id = ?
        ");
        $stmt->execute([$task_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Görevin atanan kullanıcılarını günceller (ilişki tablosunu senkronize eder)
     */
    public function setAssignees($task_id, $user_ids) {
        $stmt = $this->db->prepare("DELETE FROM kanban_task_users WHERE task_id = ?");
        $stmt->execute([$task_id]);

        if (empty($user_ids)) return;

        $stmt = $this->db->prepare("INSERT INTO kanban_task_users (task_id, user_id) VALUES (?, ?)");
        foreach ($user_ids as $uid) {
            if ($uid > 0) {
                $stmt->execute([$task_id, $uid]);
            }
        }
    }

    /**
     * Sütun (board) başlığını günceller
     */
    public function updateBoardTitle($board_id, $title, $tenant_id) {
        $stmt = $this->db->prepare("UPDATE kanban_boards SET title = ? WHERE id = ? AND tenant_id = ?");
        return $stmt->execute([$title, $board_id, $tenant_id]);
    }
}
