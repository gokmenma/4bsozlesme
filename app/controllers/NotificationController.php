<?php

class NotificationController extends Controller {

    private $notificationModel;

    public function __construct() {
        $this->notificationModel = new Notification();
    }

    private function json(array $payload): void {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Tek bir bildirimi okundu işaretler.
     * POST /bildirim-okundu
     */
    public function markRead(): void {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->json(['success' => false, 'error' => 'Geçersiz istek yöntemi.']);
        }
        $user_id = $_SESSION['user_id'] ?? 0;
        $id = intval($_POST['id'] ?? 0);
        if (!$user_id || !$id) {
            $this->json(['success' => false, 'error' => 'Eksik parametre.']);
        }
        try {
            $this->notificationModel->markRead($id, $user_id);
            $this->json(['success' => true, 'unread' => $this->notificationModel->countUnread($user_id)]);
        } catch (Exception $e) {
            $this->json(['success' => false, 'error' => 'İşlem başarısız oldu.']);
        }
    }

    /**
     * Kullanıcının tüm bildirimlerini okundu işaretler.
     * POST /bildirim-tumu-okundu
     */
    public function markAllRead(): void {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->json(['success' => false, 'error' => 'Geçersiz istek yöntemi.']);
        }
        $user_id = $_SESSION['user_id'] ?? 0;
        if (!$user_id) {
            $this->json(['success' => false, 'error' => 'Oturum bilgileri eksik.']);
        }
        try {
            $this->notificationModel->markAllRead($user_id);
            $this->json(['success' => true]);
        } catch (Exception $e) {
            $this->json(['success' => false, 'error' => 'İşlem başarısız oldu.']);
        }
    }
}
