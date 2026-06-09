<?php

class FeedbackController extends Controller {

    private $feedbackModel;

    public function __construct() {
        $this->feedbackModel = new Feedback();
    }

    /**
     * Standart JSON yanıtı üretir ve çıkış yapar.
     */
    private function json(array $payload): void {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Oturum sahibinin superadmin olup olmadığını döner.
     */
    private function isSuperadmin(): bool {
        return ($_SESSION['role'] ?? 'user') === 'superadmin';
    }

    /**
     * Kullanıcı geri bildirimi gönderir (yüzen widget formu).
     * POST /geri-bildirim-gonder
     */
    public function store(): void {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->json(['success' => false, 'error' => 'Geçersiz istek yöntemi.']);
        }

        $user_id = $_SESSION['user_id'] ?? 0;
        if (!$user_id) {
            $this->json(['success' => false, 'error' => 'Oturum bilgileri eksik.']);
        }

        $type = $_POST['type'] ?? 'suggestion';
        if (!in_array($type, Feedback::TYPES, true)) {
            $type = 'suggestion';
        }

        $subject = trim($_POST['subject'] ?? '');
        $message = trim($_POST['message'] ?? '');
        $page_url = trim($_POST['page_url'] ?? '');
        if ($page_url !== '' && mb_strlen($page_url) > 512) {
            $page_url = mb_substr($page_url, 0, 512);
        }

        if ($subject === '' || $message === '') {
            $this->json(['success' => false, 'error' => 'Lütfen konu ve mesaj alanlarını doldurun.']);
        }
        if (mb_strlen($subject) > 255) {
            $subject = mb_substr($subject, 0, 255);
        }

        try {
            $this->feedbackModel->create([
                'tenant_id' => $_SESSION['tenant_id'] ?? null,
                'user_id'   => $user_id,
                'type'      => $type,
                'subject'   => $subject,
                'message'   => $message,
                'page_url'  => $page_url !== '' ? $page_url : null,
                'status'    => 'new',
            ]);
            $this->json(['success' => true, 'message' => 'Geri bildiriminiz için teşekkürler! İletiniz bize ulaştı.']);
        } catch (Exception $e) {
            $this->json(['success' => false, 'error' => 'Geri bildirim kaydedilirken bir hata oluştu.']);
        }
    }

    /**
     * Superadmin yönetim paneli (sayfa rotası).
     * GET /geri-bildirimler
     */
    public function adminIndex(): array {
        if (!$this->isSuperadmin()) {
            header('Location: ' . routeUrl('/'));
            exit;
        }

        $statusFilter = $_GET['status'] ?? 'all';
        $feedbacks = $this->feedbackModel->getAllWithRelations($statusFilter);
        $counts = $this->feedbackModel->countsByStatus();

        return [
            'feedbacks'    => $feedbacks,
            'counts'       => $counts,
            'activeStatus' => $statusFilter,
        ];
    }

    /**
     * Geri bildirim durumunu günceller (superadmin).
     * POST /geri-bildirim-guncelle
     *
     * Durum ve yanıtı tek seferde günceller; değişiklik varsa geri bildirimi
     * gönderen kullanıcıya bir bildirim oluşturur.
     */
    public function update(): void {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->json(['success' => false, 'error' => 'Geçersiz istek yöntemi.']);
        }
        if (!$this->isSuperadmin()) {
            $this->json(['success' => false, 'error' => 'Bu işlem için yetkiniz yok.']);
        }

        $id = intval($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? '';
        $note = trim($_POST['note'] ?? '');

        if (!$id) {
            $this->json(['success' => false, 'error' => 'Geri bildirim ID eksik.']);
        }
        if (!in_array($status, Feedback::STATUSES, true)) {
            $this->json(['success' => false, 'error' => 'Geçersiz durum değeri.']);
        }

        $feedback = $this->feedbackModel->find($id);
        if (!$feedback) {
            $this->json(['success' => false, 'error' => 'Geri bildirim bulunamadı.']);
        }

        $oldStatus = $feedback['status'];
        $oldNote = trim((string)($feedback['admin_note'] ?? ''));

        try {
            $this->feedbackModel->updateStatus($id, $status);
            $this->feedbackModel->updateNote($id, $note);

            // Değişiklik varsa kullanıcıya bildirim üret
            $statusChanged = ($oldStatus !== $status);
            $noteChanged = ($oldNote !== $note);
            if (($statusChanged || $noteChanged) && !empty($feedback['user_id'])) {
                $this->createFeedbackNotification($feedback, $status, $note, $noteChanged);
            }

            $this->json(['success' => true, 'message' => 'Geri bildirim güncellendi.']);
        } catch (Exception $e) {
            $this->json(['success' => false, 'error' => 'Güncelleme sırasında hata oluştu.']);
        }
    }

    /**
     * Geri bildirim güncellemesi için kullanıcıya bildirim oluşturur.
     */
    private function createFeedbackNotification(array $feedback, string $status, string $note, bool $noteChanged): void {
        $statusLabels = ['new' => 'Yeni', 'in_progress' => 'İnceleniyor', 'resolved' => 'Çözüldü'];
        $statusLabel = $statusLabels[$status] ?? $status;
        $subject = $feedback['subject'] ?? 'Geri bildirim';

        if ($noteChanged && $note !== '') {
            $title = 'Geri bildiriminize yanıt verildi';
            $body = '“' . $subject . '” · Durum: ' . $statusLabel . "\n" . $note;
        } else {
            $title = 'Geri bildiriminizin durumu güncellendi';
            $body = '“' . $subject . '” · Durum: ' . $statusLabel;
        }

        $notification = new Notification();
        $notification->notify(
            (int)$feedback['user_id'],
            $title,
            $body,
            'feedback',
            null,
            !empty($feedback['tenant_id']) ? (int)$feedback['tenant_id'] : null
        );
    }

    /**
     * Geri bildirimi siler (superadmin).
     * POST /geri-bildirim-sil
     */
    public function delete(): void {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->json(['success' => false, 'error' => 'Geçersiz istek yöntemi.']);
        }
        if (!$this->isSuperadmin()) {
            $this->json(['success' => false, 'error' => 'Bu işlem için yetkiniz yok.']);
        }

        $id = intval($_POST['id'] ?? 0);
        if (!$id) {
            $this->json(['success' => false, 'error' => 'Geri bildirim ID eksik.']);
        }

        try {
            $this->feedbackModel->delete($id);
            $this->json(['success' => true, 'message' => 'Geri bildirim silindi.']);
        } catch (Exception $e) {
            $this->json(['success' => false, 'error' => 'Silme sırasında hata oluştu.']);
        }
    }
}
