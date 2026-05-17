<?php

class TemplateController extends Controller {
    
    public function index() {
        $templateModel = $this->model('Template');
        $template = $templateModel->getLatest();
        $defaultTemplate = $templateModel->getDefaultTemplate();
        
        return [
            'template' => $template,
            'defaultTemplate' => $defaultTemplate
        ];
    }
    
    public function save() {
        // Çıktı tamponlamasını temizle ve başlat
        if (ob_get_level()) ob_end_clean();
        ob_start();
        
        header('Content-Type: application/json');
        
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                echo json_encode(['success' => false, 'message' => 'Geçersiz istek metodu.']);
                return;
            }
            
            $name = $_POST['name'] ?? 'Hizmet Sözleşmesi Şablonu';
            $content = $_POST['content'] ?? '';
            $has_border = isset($_POST['has_border']) ? (int)$_POST['has_border'] : 0;
            
            if (empty($content)) {
                echo json_encode(['success' => false, 'message' => 'İçerik boş olamaz.']);
                return;
            }
            
            $templateModel = $this->model('Template');
            $result = $templateModel->saveTemplate($name, $content, $has_border);
            
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Şablon başarıyla kaydedildi.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Kaydedilirken bir hata oluştu.']);
            }
        } catch (Exception $e) {
            if (ob_get_level()) ob_clean();
            echo json_encode(['success' => false, 'message' => 'Sistem Hatası: ' . $e->getMessage()]);
            return;
        }
        
        // Eğer beklenmedik bir çıktı oluştuysa temizle
        $output = ob_get_clean();
        if (!empty($output) && strpos($output, '{') !== 0) {
            // Sadece JSON kısmını gönder (eğer varsa)
            $jsonStart = strpos($output, '{');
            if ($jsonStart !== false) {
                echo substr($output, $jsonStart);
            } else {
                echo json_encode(['success' => false, 'message' => 'Beklenmedik Sunucu Çıktısı: ' . $output]);
            }
        } else {
            echo $output;
        }
    }
}
