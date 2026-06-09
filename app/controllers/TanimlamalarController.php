<?php

class TanimlamalarController extends Controller {
    
    public function index() {
        $definitionModel = new Definition();
        $successMessage = null;
        $tenant_id = $_SESSION['tenant_id'] ?? 0;

        // Form gönderildiyse (Update/Create işlemi)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                foreach ($_POST as $key => $value) {
                    $definitionModel->setSetting($key, $value, $tenant_id);
                }
                if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                    echo json_encode(['success' => true, 'message' => 'Değişiklikler başarıyla kaydedildi.']);
                    exit;
                }
                $successMessage = "Değişiklikler başarıyla kaydedildi.";
            } catch (Exception $e) {
                if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                    echo json_encode(['success' => false, 'message' => 'Hata: ' . $e->getMessage()]);
                    exit;
                }
                $errorMessage = "Hata: " . $e->getMessage();
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
