<?php

/**
 * Temel Controller Sınıfı
 * Görünümleri yüklemek ve veri aktarmak için kullanılır.
 */
class Controller {
    /**
     * View (görünüm) dosyasını yükler
     * @param string $view 'pages/home' gibi
     * @param array $data Görünüme aktarılacak veriler
     */
    public function view($view, $data = []) {
        // Verileri değişkenlere dönüştür
        extract($data);
        
        $viewFile = 'app/' . $view . '.php';
        
        if (file_exists($viewFile)) {
            require_once $viewFile;
        } else {
            die("Görünüm dosyası bulunamadı: " . $viewFile);
        }
    }

    /**
     * Model yükler
     * @param string $model 'User' gibi
     */
    public function model($model) {
        $modelFile = 'app/models/' . $model . '.php';
        
        if (file_exists($modelFile)) {
            require_once $modelFile;
            return new $model();
        } else {
            die("Model dosyası bulunamadı: " . $modelFile);
        }
    }
}
