<?php

class TanimlamalarController extends Controller {
    
    public function index() {
        $definitionModel = new Definition();
        $successMessage = null;
        $tenant_id = $_SESSION['tenant_id'] ?? 0;

        // Form gönderildiyse (Update/Create işlemi)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json; charset=utf-8');
            try {
                foreach ($_POST as $key => $value) {
                    $definitionModel->setSetting($key, $value, $tenant_id);
                }
                echo json_encode(['success' => true, 'message' => 'Değişiklikler başarıyla kaydedildi.']);
                exit;
            } catch (Throwable $e) {
                echo json_encode(['success' => false, 'message' => 'Hata: ' . $e->getMessage()]);
                exit;
            }
        }

        // Mevcut verileri getir (Read işlemi)
        $settings = $definitionModel->getSettings($tenant_id);

        // View'a verileri aktar
        return [
            'settings' => $settings,
            'successMessage' => $successMessage
        ];
    }
}
