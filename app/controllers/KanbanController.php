<?php

class KanbanController extends Controller {

    private $taskModel;
    private $userModel;

    public function __construct() {
        $this->taskModel = new Task();
        $this->userModel = new User();
    }

    /**
     * Masaüstü Yapılacaklar ana sayfa verilerini yükler
     */
    public function index() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . routeUrl('/logout'));
            exit;
        }

        $tenant_id = $_SESSION['tenant_id'] ?? 0;
        $user_id = $_SESSION['user_id'];
        $filter = $_GET['filter'] ?? 'all'; // all, my, assigned

        // Kuruma ait dinamik boardları (sütunları) çek
        $boards = $this->taskModel->getBoardsForTenant($tenant_id);

        // Kuruma ait görevleri çek
        $tasks = $this->taskModel->getTasksForTenant($tenant_id, $filter, $user_id);
        
        // Kurumdaki diğer kullanıcıları çek (Atama yapabilmek için)
        $users = $this->userModel->whereWithCreator('tenant_id', $tenant_id);

        return [
            'boards' => $boards,
            'tasks' => $tasks,
            'users' => $users,
            'activeFilter' => $filter
        ];
    }

    /**
     * Yeni görev ekler
     */
    public function store() {
        header('Content-Type: application/json; charset=utf-8');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Geçersiz istek yöntemi.']);
            exit;
        }

        $tenant_id = $_SESSION['tenant_id'] ?? 0;
        $user_id = $_SESSION['user_id'] ?? 0;

        if (!$tenant_id || !$user_id) {
            echo json_encode(['success' => false, 'error' => 'Oturum bilgileri eksik.']);
            exit;
        }

        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $priority = $_POST['priority'] ?? 'medium';
        $board_id = intval($_POST['board_id'] ?? 0);
        $due_date = !empty($_POST['due_date']) ? $_POST['due_date'] : null;
        if ($due_date && preg_match('/^\d{2}\.\d{2}\.\d{4}$/', $due_date)) {
            $parts = explode('.', $due_date);
            $due_date = "{$parts[2]}-{$parts[1]}-{$parts[0]}";
        } elseif ($due_date) {
            $parsedDate = strtotime($due_date);
            $due_date = $parsedDate ? date('Y-m-d', $parsedDate) : null;
        }
        $assignees = $_POST['assignees'] ?? []; // Array of user IDs

        if (empty($title)) {
            echo json_encode(['success' => false, 'error' => 'Lütfen görev başlığı girin.']);
            exit;
        }

        if (!$board_id) {
            // Pick first dynamic board of tenant as fallback
            $boards = $this->taskModel->getBoardsForTenant($tenant_id);
            if (!empty($boards)) {
                $board_id = $boards[0]['id'];
            }
        }

        $taskData = [
            'tenant_id' => $tenant_id,
            'user_id' => $user_id,
            'title' => $title,
            'description' => $description,
            'board_id' => $board_id,
            'priority' => $priority,
            'due_date' => $due_date
        ];

        try {
            $task_id = $this->taskModel->create($taskData);
            if ($task_id) {
                // Atanan kullanıcıları kaydet
                $this->taskModel->setAssignees($task_id, $assignees);
                echo json_encode(['success' => true, 'message' => 'Görev başarıyla oluşturuldu.']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Görev eklenirken bir hata oluştu.']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Sistem hatası: ' . $e->getMessage()]);
        }
        exit;
    }

    /**
     * Görev günceller
     */
    public function update() {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Geçersiz istek yöntemi.']);
            exit;
        }

        $tenant_id = $_SESSION['tenant_id'] ?? 0;
        $task_id = intval($_POST['id'] ?? 0);

        if (!$task_id) {
            echo json_encode(['success' => false, 'error' => 'Görev ID bulunamadı.']);
            exit;
        }

        // Görevin bu kuruma ait olduğunu doğrula
        $task = $this->taskModel->find($task_id);
        if (!$task || $task['tenant_id'] != $tenant_id) {
            echo json_encode(['success' => false, 'error' => 'Bu görevi düzenleme yetkiniz yok.']);
            exit;
        }

        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $priority = $_POST['priority'] ?? 'medium';
        $board_id = intval($_POST['board_id'] ?? 0);
        $due_date = !empty($_POST['due_date']) ? $_POST['due_date'] : null;
        if ($due_date && preg_match('/^\d{2}\.\d{2}\.\d{4}$/', $due_date)) {
            $parts = explode('.', $due_date);
            $due_date = "{$parts[2]}-{$parts[1]}-{$parts[0]}";
        } elseif ($due_date) {
            $parsedDate = strtotime($due_date);
            $due_date = $parsedDate ? date('Y-m-d', $parsedDate) : null;
        }
        $assignees = $_POST['assignees'] ?? [];

        if (empty($title)) {
            echo json_encode(['success' => false, 'error' => 'Lütfen görev başlığı girin.']);
            exit;
        }

        $taskData = [
            'title' => $title,
            'description' => $description,
            'board_id' => $board_id ? $board_id : $task['board_id'],
            'priority' => $priority,
            'due_date' => $due_date
        ];

        try {
            $this->taskModel->update($task_id, $taskData);
            $this->taskModel->setAssignees($task_id, $assignees);
            echo json_encode(['success' => true, 'message' => 'Görev başarıyla güncellendi.']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Güncelleme sırasında hata oluştu: ' . $e->getMessage()]);
        }
        exit;
    }

    /**
     * Sadece board (sütun) sütununu günceller (Sürükle-bırak veya tek tık için)
     */
    public function updateStatus() {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Geçersiz istek.']);
            exit;
        }

        $tenant_id = $_SESSION['tenant_id'] ?? 0;
        $task_id = intval($_POST['id'] ?? 0);
        $board_id = intval($_POST['board_id'] ?? 0);

        if (!$task_id || !$board_id) {
            echo json_encode(['success' => false, 'error' => 'Eksik veya geçersiz parametreler.']);
            exit;
        }

        $task = $this->taskModel->find($task_id);
        if (!$task || $task['tenant_id'] != $tenant_id) {
            echo json_encode(['success' => false, 'error' => 'Yetkisiz erişim.']);
            exit;
        }

        try {
            $this->taskModel->update($task_id, ['board_id' => $board_id]);
            echo json_encode(['success' => true, 'message' => 'Görev sütunu güncellendi.']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Sütun güncellenirken hata oluştu.']);
        }
        exit;
    }

    /**
     * Görev siler
     */
    public function delete() {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Geçersiz istek.']);
            exit;
        }

        $tenant_id = $_SESSION['tenant_id'] ?? 0;
        $task_id = intval($_POST['id'] ?? 0);

        if (!$task_id) {
            echo json_encode(['success' => false, 'error' => 'Görev ID eksik.']);
            exit;
        }

        $task = $this->taskModel->find($task_id);
        if (!$task || $task['tenant_id'] != $tenant_id) {
            echo json_encode(['success' => false, 'error' => 'Bu görevi silme yetkiniz yok.']);
            exit;
        }

        try {
            // Atanan kullanıcı ilişkilerini temizle
            $this->taskModel->setAssignees($task_id, []);
            // Görevi sil
            $this->taskModel->delete($task_id);
            echo json_encode(['success' => true, 'message' => 'Görev silindi.']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Görev silinirken bir hata oluştu.']);
        }
        exit;
    }

    /**
     * Sütun ekler
     */
    public function addBoard() {
        header('Content-Type: application/json; charset=utf-8');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Geçersiz istek.']);
            exit;
        }

        $tenant_id = $_SESSION['tenant_id'] ?? 0;
        $title = trim($_POST['title'] ?? $_POST['t'] ?? $_POST['name'] ?? '');

        if (empty($title)) {
            echo json_encode(['success' => false, 'error' => 'Lütfen sütun başlığı girin.']);
            exit;
        }

        try {
            $board_id = $this->taskModel->createBoard($tenant_id, $title);
            echo json_encode(['success' => true, 'board_id' => $board_id, 'message' => 'Sütun başarıyla eklendi.']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Sütun eklenirken hata oluştu: ' . $e->getMessage()]);
        }
        exit;
    }

    /**
     * Sütun siler
     */
    public function deleteBoard() {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Geçersiz istek.']);
            exit;
        }

        $tenant_id = $_SESSION['tenant_id'] ?? 0;
        $board_id = intval($_POST['board_id'] ?? $_POST['id'] ?? $_POST['b_id'] ?? 0);

        if (!$board_id) {
            echo json_encode(['success' => false, 'error' => 'Sütun ID eksik.']);
            exit;
        }

        try {
            $this->taskModel->deleteBoard($board_id, $tenant_id);
            echo json_encode(['success' => true, 'message' => 'Sütun başarıyla silindi.']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Sütun silinirken hata oluştu: ' . $e->getMessage()]);
        }
        exit;
    }

    /**
     * Sütun sıralamasını günceller
     */
    public function updateBoardOrder() {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Geçersiz istek.']);
            exit;
        }

        $tenant_id = $_SESSION['tenant_id'] ?? 0;
        $orders = $_POST['orders'] ?? []; // Array of board_id => sort_order

        if (empty($orders)) {
            echo json_encode(['success' => false, 'error' => 'Sıralama verisi eksik.']);
            exit;
        }

        try {
            foreach ($orders as $board_id => $sort_order) {
                $this->taskModel->updateBoardOrder(intval($board_id), intval($sort_order), $tenant_id);
            }
            echo json_encode(['success' => true, 'message' => 'Sütun sıralaması güncellendi.']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Sıralama güncellenirken hata oluştu: ' . $e->getMessage()]);
        }
        exit;
    }

    /**
     * Sütun (board) başlığını günceller
     */
    public function updateBoardTitle() {
        header('Content-Type: application/json; charset=utf-8');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Geçersiz istek.']);
            exit;
        }

        $tenant_id = $_SESSION['tenant_id'] ?? 0;
        $board_id = intval($_POST['board_id'] ?? $_POST['id'] ?? $_POST['b_id'] ?? 0);
        $title = trim($_POST['title'] ?? $_POST['t'] ?? $_POST['name'] ?? '');

        if (!$tenant_id) {
            echo json_encode(['success' => false, 'error' => 'Oturum bilgileri eksik. Lütfen tekrar giriş yapın.']);
            exit;
        }

        if (!$board_id) {
            echo json_encode(['success' => false, 'error' => 'Sütun ID eksik.']);
            exit;
        }

        if (empty($title)) {
            echo json_encode(['success' => false, 'error' => 'Sütun başlığı boş olamaz.']);
            exit;
        }

        try {
            $updated = $this->taskModel->updateBoardTitle($board_id, $title, $tenant_id);
            if (!$updated) {
                echo json_encode(['success' => false, 'error' => 'Sütun bulunamadı veya bu sütunu düzenleme yetkiniz yok.']);
                exit;
            }
            echo json_encode(['success' => true, 'message' => 'Sütun başlığı başarıyla güncellendi.']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Güncelleme sırasında hata oluştu: ' . $e->getMessage()]);
        }
        exit;
    }
}
