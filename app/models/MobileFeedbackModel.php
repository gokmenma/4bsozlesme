<?php

require_once __DIR__ . '/../core/Model.php';

class MobileFeedbackModel extends Model {
    protected $table = 'feedbacks';

    public function list(string $status): array {
        $rows = (new Feedback())->getAllWithRelations($status);
        return array_map(static fn($r) => [
            'id' => (int)$r['id'], 'type' => $r['type'], 'subject' => $r['subject'], 'message' => $r['message'],
            'page_url' => $r['page_url'], 'status' => $r['status'], 'admin_note' => $r['admin_note'],
            'created_at' => $r['created_at'], 'user_name' => $r['user_name'], 'user_email' => $r['user_email'],
            'tenant_name' => $r['tenant_name'],
        ], $rows);
    }

    public function counts(): array {
        return (new Feedback())->countsByStatus();
    }

    public function getOne(int $id): ?array {
        $row = (new Feedback())->find($id);
        return $row ?: null;
    }

    public function respond(array $feedback, string $status, string $note): void {
        $model = new Feedback();
        $id = (int)$feedback['id'];
        $model->updateStatus($id, $status);
        $model->updateNote($id, $note);

        $statusChanged = $feedback['status'] !== $status;
        $noteChanged = trim((string)($feedback['admin_note'] ?? '')) !== $note;
        if (($statusChanged || $noteChanged) && !empty($feedback['user_id'])) {
            $labels = ['new' => 'Yeni', 'in_progress' => 'İnceleniyor', 'resolved' => 'Çözüldü'];
            $subject = (string)($feedback['subject'] ?? 'Geri bildirim');
            if ($noteChanged && $note !== '') {
                $title = 'Geri bildiriminize yanıt verildi';
                $body = '“' . $subject . '” · Durum: ' . $labels[$status] . "\n" . $note;
            } else {
                $title = 'Geri bildiriminizin durumu güncellendi';
                $body = '“' . $subject . '” · Durum: ' . $labels[$status];
            }
            (new Notification())->notify(
                (int)$feedback['user_id'], $title, $body, 'feedback', null,
                !empty($feedback['tenant_id']) ? (int)$feedback['tenant_id'] : null
            );
        }
    }

    public function deleteOne(int $id): void {
        $this->db->prepare("DELETE FROM feedbacks WHERE id = ?")->execute([$id]);
    }
}
