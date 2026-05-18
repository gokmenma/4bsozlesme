<?php

class PersonnelController extends Controller {
    
    public function list() {
        global $db;
        
        $tenant_id = $_SESSION['tenant_id'] ?? 0;
        
        $personnels = [];

        // Varsayılan bütçe dönemini alalım
        $defModel = new Definition();
        $settings = $defModel->getSettings($tenant_id);
        $active_period = $settings['default_wage_period'] ?? '2026-1';

        // Ücret tanımlarını çekelim (sadece aktif olan dönemin kayıtları)
        $stmt_ucret = $db->prepare("SELECT id, unvan, ucret, ogrenim, kidem_yili FROM ucretler WHERE deleted_at IS NULL AND tenant_id = ? AND donem = ? ORDER BY unvan ASC");
        $stmt_ucret->execute([$tenant_id, $active_period]);
        $ucretler = $stmt_ucret->fetchAll();

        return [
            'personnels' => $personnels,
            'ucretler' => $ucretler
        ];
    }

    public function store() {
        global $db;
        
        $tenant_id = $_SESSION['tenant_id'] ?? 1;
        $tc_kimlik = $_POST['tc_kimlik'];

        $isAjax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') 
            || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
            || isset($_POST['ajax']) || isset($_GET['ajax']);

        // Unique TC Check for this tenant
        $checkStmt = $db->prepare("SELECT id FROM personeller WHERE tenant_id = ? AND tc_kimlik = ? AND deleted_at IS NULL LIMIT 1");
        $checkStmt->execute([$tenant_id, $tc_kimlik]);
        if ($checkStmt->fetch()) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Bu TC Kimlik numarasına sahip bir personel zaten kayıtlı.']);
                exit;
            }
            $_SESSION['error'] = 'Bu TC Kimlik numarasına sahip bir personel zaten kayıtlı.';
            header('Location: ' . routeUrl('personel-listesi'));
            exit;
        }

        $baslama = $_POST['goreve_baslama_tarihi'] ?? '';
        if (preg_match('/^\d{2}\.\d{2}\.\d{4}$/', $baslama)) {
            $parts = explode('.', $baslama);
            $baslama = "{$parts[2]}-{$parts[1]}-{$parts[0]}";
        } else {
            $parsedDate = strtotime($baslama);
            $baslama = $parsedDate ? date('Y-m-d', $parsedDate) : date('Y-m-d');
        }

        $data = [
            'tc_kimlik' => $tc_kimlik,
            'ad_soyad' => $_POST['ad_soyad'],
            'ucret_id' => $_POST['ucret_id'],
            'durum' => $_POST['durum'] ?? 'aktif',
            'goreve_baslama_tarihi' => $baslama,
            'telefon' => $_POST['telefon'] ?? '',
            'meslek_kodu' => $_POST['meslek_kodu'] ?? '',
            'cinsiyet' => $_POST['cinsiyet'] ?? 'erkek',
            'created_at' => date('Y-m-d H:i:s'),
            'tenant_id' => $tenant_id
        ];

        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        
        $sql = "INSERT INTO personeller ({$columns}) VALUES ({$placeholders})";
        $stmt = $db->prepare($sql);
        $success = $stmt->execute(array_values($data));

        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => $success]);
            exit;
        }

        header('Location: ' . routeUrl('personel-listesi'));
        exit;
    }

    public function get() {
        $id = $_GET['id'] ?? null;
        $tenant_id = $_SESSION['tenant_id'] ?? 0;

        if (!$id) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'ID required']);
            exit;
        }

        $personnelModel = new PersonnelModel();
        $personnel = $personnelModel->find($id, $tenant_id);

        if (!$personnel) {
            header('Content-Type: application/json', true, 403);
            echo json_encode(['error' => 'Unauthorized or not found']);
            exit;
        }

        // Goreve baslama tarihi formatlama
        if (!empty($personnel['goreve_baslama_tarihi'])) {
            $personnel['goreve_baslama_tarihi'] = date('d.m.Y', strtotime($personnel['goreve_baslama_tarihi']));
        }

        header('Content-Type: application/json');
        echo json_encode($personnel);
        exit;
    }

    public function update() {
        $id = $_POST['id'] ?? null;
        $tenant_id = $_SESSION['tenant_id'] ?? 0;

        if (!$id) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'ID required']);
            exit;
        }

        global $db;
        $tc_kimlik = $_POST['tc_kimlik'];

        // Unique TC Check for this tenant, excluding the current personnel ID
        $checkStmt = $db->prepare("SELECT id FROM personeller WHERE tenant_id = ? AND tc_kimlik = ? AND id != ? AND deleted_at IS NULL LIMIT 1");
        $checkStmt->execute([$tenant_id, $tc_kimlik, $id]);
        if ($checkStmt->fetch()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Bu TC Kimlik numarasına sahip başka bir personel zaten kayıtlı.']);
            exit;
        }

        $personnelModel = new PersonnelModel();
        $personnel = $personnelModel->find($id, $tenant_id);

        if (!$personnel) {
            header('Content-Type: application/json', true, 403);
            echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
            exit;
        }

        $baslama = $_POST['goreve_baslama_tarihi'] ?? '';
        if (preg_match('/^\d{2}\.\d{2}\.\d{4}$/', $baslama)) {
            $parts = explode('.', $baslama);
            $baslama = "{$parts[2]}-{$parts[1]}-{$parts[0]}";
        } else {
            $parsedDate = strtotime($baslama);
            $baslama = $parsedDate ? date('Y-m-d', $parsedDate) : date('Y-m-d');
        }

        $data = [
            'tc_kimlik' => $tc_kimlik,
            'ad_soyad' => $_POST['ad_soyad'],
            'ucret_id' => $_POST['ucret_id'],
            'durum' => $_POST['durum'] ?? 'aktif',
            'goreve_baslama_tarihi' => $baslama,
            'telefon' => $_POST['telefon'] ?? '',
            'meslek_kodu' => $_POST['meslek_kodu'] ?? '',
            'cinsiyet' => $_POST['cinsiyet'] ?? 'erkek',
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $success = $personnelModel->update($id, $data);

        header('Content-Type: application/json');
        echo json_encode(['success' => $success]);
        exit;
    }

    public function aiScan() {
        if (!isset($_FILES['image'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Görsel yüklenmedi.']);
            exit;
        }

        $file = $_FILES['image'];
        $base64Image = base64_encode(file_get_contents($file['tmp_name']));

        $result = AiService::scanImage($base64Image);

        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }

    public function previewContract() {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'ID required']);
            exit;
        }

        global $db;
        
        // Fetch personnel with wage details including ogrenim
        $tenant_id = $_SESSION['tenant_id'] ?? 0;
        $defModel = new Definition();
        $settings = $defModel->getSettings($tenant_id);
        $default_period = $settings['default_wage_period'] ?? '2026-1';

        $stmt = $db->prepare("
            SELECT p.*, 
                   u.unvan, 
                   COALESCE(u_def.ucret, u.ucret) as ucret, 
                   u.ogrenim 
            FROM personeller p 
            LEFT JOIN ucretler u ON p.ucret_id = u.id 
            LEFT JOIN ucretler u_def ON u_def.tenant_id = p.tenant_id 
                                    AND u_def.unvan = u.unvan 
                                    AND u_def.ogrenim = u.ogrenim 
                                    AND u_def.kidem_yili = u.kidem_yili
                                    AND u_def.donem = ?
                                    AND u_def.deleted_at IS NULL
            WHERE p.id = ? AND p.tenant_id = ?
        ");
        $stmt->execute([$default_period, $id, $tenant_id]);
        $p = $stmt->fetch();

        if (!$p) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Personnel not found']);
            exit;
        }

        // Fetch settings
        $defModel = new Definition();
        $settings = $defModel->getSettings($tenant_id);

        // Fetch latest template
        $templateModel = $this->model('Template');
        $template = $templateModel->getLatest();
        $content = $template ? $template['content'] : '';
        $content = preg_replace('/^(?:\s|&nbsp;|<p>(?:\s|&nbsp;|<br\s*\/?>)*<\/p>|<div>(?:\s|&nbsp;|<br\s*\/?>)*<\/div>)+/iu', '', $content);
        $content = preg_replace('/(?:\s|&nbsp;|<p>(?:\s|&nbsp;|<br\s*\/?>)*<\/p>|<div>(?:\s|&nbsp;|<br\s*\/?>)*<\/div>)+$/iu', '', $content);

        if (empty($content)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'No contract template found. Lütfen önce "Sözleşme Taslağı" sayfasından bir şablon kaydedin.']);
            exit;
        }

        // Replacements
        $replacements = [
            '{{KURUM_ADI}}' => $settings['kurum_adi'] ?? 'Kurum Adı Tanımlanmamış',
            '{{BIRIM_ADI}}' => $settings['birim_adi'] ?? 'Birim Adı Tanımlanmamış',
            '{{YETKILI_AD}}' => $settings['yetkili_ad_soyad'] ?? 'Yetkili Tanımlanmamış',
            '{{YETKILI_UNVAN}}' => $settings['yetkili_unvan'] ?? 'Yetkili Ünvanı Tanımlanmamış',
            '{{PERSONEL_AD}}' => $p['ad_soyad'],
            '{{TC_NO}}' => $p['tc_kimlik'],
            '{{GOREV}}' => $p['unvan'] ?? 'Görev Tanımlanmamış',
            '{{EGITIM_DURUMU}}' => $p['ogrenim'] ?? 'Eğitim Tanımlanmamış',
            '{{BRUT_UCRET}}' => number_format($p['ucret'] ?? 0, 2, ',', '.') . ' TL',
            '{{BASLANGIC_TARIHI}}' => $p['goreve_baslama_tarihi'] ? date('d.m.Y', strtotime($p['goreve_baslama_tarihi'])) : '-',
            '{{BITIS_TARIHI}}' => '31.12.' . date('Y'),
        ];

        foreach ($replacements as $key => $value) {
            $content = str_replace($key, '<span class="variable-tag">' . htmlspecialchars($value) . '</span>', $content);
        }

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'content' => $content,
            'has_border' => (isset($template['has_border']) && $template['has_border']) ? true : false,
            'personnel_name' => $p['ad_soyad']
        ]);
        exit;
    }

    public function downloadWord() {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            die('ID required');
        }

        global $db;
        
        $tenant_id = $_SESSION['tenant_id'] ?? 0;
        $defModel = new Definition();
        $settings = $defModel->getSettings($tenant_id);
        $default_period = $settings['default_wage_period'] ?? '2026-1';

        $stmt = $db->prepare("
            SELECT p.*, 
                   u.unvan, 
                   COALESCE(u_def.ucret, u.ucret) as ucret, 
                   u.ogrenim 
            FROM personeller p 
            LEFT JOIN ucretler u ON p.ucret_id = u.id 
            LEFT JOIN ucretler u_def ON u_def.tenant_id = p.tenant_id 
                                    AND u_def.unvan = u.unvan 
                                    AND u_def.ogrenim = u.ogrenim 
                                    AND u_def.kidem_yili = u.kidem_yili
                                    AND u_def.donem = ?
                                    AND u_def.deleted_at IS NULL
            WHERE p.id = ? AND p.tenant_id = ?
        ");
        $stmt->execute([$default_period, $id, $tenant_id]);
        $p = $stmt->fetch();

        if (!$p) {
            die('Personnel not found');
        }

        $defModel = new Definition();
        $settings = $defModel->getSettings($tenant_id);

        $templateModel = $this->model('Template');
        $template = $templateModel->getLatest();
        $content = $template ? $template['content'] : '';

        if (empty($content)) {
            die('Template not found');
        }

        $replacements = [
            '{{KURUM_ADI}}' => $settings['kurum_adi'] ?? '',
            '{{BIRIM_ADI}}' => $settings['birim_adi'] ?? '',
            '{{YETKILI_AD}}' => $settings['yetkili_ad_soyad'] ?? '',
            '{{YETKILI_UNVAN}}' => $settings['yetkili_unvan'] ?? '',
            '{{PERSONEL_AD}}' => $p['ad_soyad'],
            '{{TC_NO}}' => $p['tc_kimlik'],
            '{{GOREV}}' => $p['unvan'] ?? '',
            '{{EGITIM_DURUMU}}' => $p['ogrenim'] ?? '',
            '{{BRUT_UCRET}}' => number_format($p['ucret'] ?? 0, 2, ',', '.') . ' TL',
            '{{BASLANGIC_TARIHI}}' => $p['goreve_baslama_tarihi'] ? date('d.m.Y', strtotime($p['goreve_baslama_tarihi'])) : '-',
            '{{BITIS_TARIHI}}' => '31.12.' . date('Y'),
        ];

        foreach ($replacements as $key => $value) {
            $content = str_replace($key, $value, $content);
        }

        $filename = "Sozlesme_" . str_replace(' ', '_', $p['ad_soyad']) . ".doc";
        
        header("Content-Type: application/vnd.ms-word");
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Content-disposition: attachment; filename=\"$filename\"");
        
        $html = '
        <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word" xmlns="http://www.w3.org/TR/REC-html40">
        <head>
            <meta charset="utf-8">
            <title>Sözleşme</title>
            <style>
                body { font-family: "Times New Roman", Times, serif; font-size: 12pt; line-height: 1.5; }
                table { border-collapse: collapse; width: 100%; }
                th, td { border: 1px solid black; padding: 5px; }
                .text-center { text-align: center; }
                .font-bold { font-weight: bold; }
            </style>
        </head>
        <body>
            ' . $content . '
        </body>
        </html>';
        
        echo $html;
        exit;
    }

    public function importExcel() {
        global $db;
        $json = file_get_contents('php://input');
        $input = json_decode($json, true);

        if (!$input || !isset($input['data'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Geçersiz veri.']);
            exit;
        }

        $data = $input['data'];
        $update_wages = $input['update_wages'] ?? false;
        $tenant_id = $_SESSION['tenant_id'] ?? 1;
        $count = 0;

        $db->beginTransaction();
        try {
            $stmt_personnel = $db->prepare("
                INSERT INTO personeller (tenant_id, tc_kimlik, ad_soyad, ucret_id, durum, goreve_baslama_tarihi, cinsiyet, telefon, meslek_kodu, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt_check_personnel = $db->prepare("
                SELECT id FROM personeller 
                WHERE tenant_id = ? AND tc_kimlik = ? AND deleted_at IS NULL LIMIT 1
            ");

            $stmt_update_personnel = $db->prepare("
                UPDATE personeller SET 
                    ad_soyad = ?, ucret_id = ?, durum = ?, goreve_baslama_tarihi = ?, 
                    cinsiyet = ?, telefon = ?, meslek_kodu = ?, updated_at = ? 
                WHERE id = ?
            ");

            // Match only on title, education, and seniority
            $stmt_find_wage = $db->prepare("
                SELECT id, ucret FROM ucretler 
                WHERE tenant_id = ? AND unvan = ? AND ogrenim = ? AND kidem_yili = ? 
                AND deleted_at IS NULL LIMIT 1
            ");

            $stmt_insert_wage = $db->prepare("
                INSERT INTO ucretler (tenant_id, unvan, ogrenim, kidem_yili, ucret, is_active, created_at)
                VALUES (?, ?, ?, ?, ?, 1, ?)
            ");

            $stmt_update_wage = $db->prepare("
                UPDATE ucretler SET ucret = ?, updated_at = ? WHERE id = ?
            ");

            foreach ($data as $row) {
                $tc = $row['TC Kimlik No*'] ?? $row['TC Kimlik No'] ?? $row['tc_kimlik'] ?? null;
                $ad = $row['Ad Soyad*'] ?? $row['Ad Soyad'] ?? $row['ad_soyad'] ?? null;
                $cinsiyet = $row['Cinsiyet*'] ?? $row['Cinsiyet'] ?? $row['cinsiyet'] ?? null;
                
                if (!$tc || !$ad) continue;

                // Wage components
                $row_ogrenim = trim($row['Öğrenim Durumu*'] ?? $row['Öğrenim Durumu'] ?? $row['ogrenim'] ?? '');
                $row_kidem = trim($row['Kıdem Yılı*'] ?? $row['Kıdem Yılı'] ?? $row['kidem_yili'] ?? '');
                $row_unvan = trim($row['Unvan*'] ?? $row['Unvan'] ?? $row['ucret_tanimi'] ?? '');
                $row_ucret_val = $row['Ücret*'] ?? $row['Ücret'] ?? $row['ucret_val'] ?? 0;

                // Format ucret as decimal
                if (is_string($row_ucret_val)) {
                    $row_ucret_val = str_replace(['.', ','], ['', '.'], $row_ucret_val);
                }
                $ucret_float = (float)$row_ucret_val;

                if (empty($row_ogrenim) || empty($row_kidem) || empty($row_unvan)) continue;

                // Find existing wage definition (by 3 main fields)
                $stmt_find_wage->execute([$tenant_id, $row_unvan, $row_ogrenim, $row_kidem]);
                $wage_record = $stmt_find_wage->fetch(PDO::FETCH_ASSOC);

                if ($wage_record) {
                    $ucret_id = $wage_record['id'];
                    $db_ucret = (float)$wage_record['ucret'];

                    // Update if different and user requested
                    if ($update_wages && abs($ucret_float - $db_ucret) > 0.001) {
                        $stmt_update_wage->execute([$ucret_float, date('Y-m-d H:i:s'), $ucret_id]);
                    }
                } else {
                    // Create new if not found
                    $stmt_insert_wage->execute([
                        $tenant_id,
                        $row_unvan,
                        $row_ogrenim,
                        $row_kidem,
                        $ucret_float,
                        date('Y-m-d H:i:s')
                    ]);
                    $ucret_id = $db->lastInsertId();
                }

                if (!$ucret_id) continue;

                $telefon = $row['Telefon'] ?? $row['telefon'] ?? '';
                $meslek = $row['Meslek Kodu'] ?? $row['meslek_kodu'] ?? '';
                $baslama = $row['Göreve Başlama Tarihi'] ?? $row['goreve_baslama_tarihi'] ?? date('Y-m-d');

                if (preg_match('/^\d{2}\.\d{2}\.\d{4}$/', $baslama)) {
                    $parts = explode('.', $baslama);
                    $baslama = "{$parts[2]}-{$parts[1]}-{$parts[0]}";
                } elseif (is_numeric($baslama)) {
                    $baslama = date('Y-m-d', ($baslama - 25569) * 86400);
                } else {
                    $baslama = date('Y-m-d', strtotime($baslama));
                }

                $stmt_check_personnel->execute([$tenant_id, (string)$tc]);
                $existing_personnel = $stmt_check_personnel->fetch(PDO::FETCH_ASSOC);

                if ($existing_personnel) {
                    $stmt_update_personnel->execute([
                        (string)$ad,
                        $ucret_id,
                        'aktif',
                        $baslama,
                        (string)$cinsiyet,
                        (string)$telefon,
                        (string)$meslek,
                        date('Y-m-d H:i:s'),
                        $existing_personnel['id']
                    ]);
                } else {
                    $stmt_personnel->execute([
                        $tenant_id,
                        (string)$tc,
                        (string)$ad,
                        $ucret_id,
                        'aktif',
                        $baslama,
                        (string)$cinsiyet,
                        (string)$telefon,
                        (string)$meslek,
                        date('Y-m-d H:i:s')
                    ]);
                }
                $count++;
            }

            $db->commit();
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'count' => $count]);
        } catch (Exception $e) {
            $db->rollBack();
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }

    public function delete() {
        global $db;
        $id = $_POST['id'] ?? 0;
        $tenant_id = $_SESSION['tenant_id'] ?? 0;

        if ($id > 0 && $tenant_id > 0) {
            // Sadece kendi tenant'ına ait kaydı sil
            $stmt = $db->prepare("UPDATE personeller SET deleted_at = NOW() WHERE id = ? AND tenant_id = ?");
            $success = $stmt->execute([$id, $tenant_id]);
            echo json_encode(['success' => $success]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Geçersiz veri veya yetkisiz erişim']);
        }
    }

    public function downloadSample() {
        $filename = "personel_yukleme_sablonu.csv";
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        fputcsv($output, ['TC Kimlik No', 'Ad Soyad', 'Öğrenim Durumu', 'Kıdem Yılı', 'Ücret Tanımı', 'Telefon', 'Göreve Başlama Tarihi', 'Meslek Kodu']);
        fputcsv($output, ['12345678901', 'Örnek Personel', 'Lisans', '0-5 Yıl (Dahil)', 'Uzman Yazılımcı', '05001234567', '29.04.2026', '1234.56']);
        
        fclose($output);
        exit;
    }
    public function fetchDataTable() {
        try {
            global $db;
            $tenant_id = $_SESSION['tenant_id'] ?? 0;

            $draw = $_POST['draw'] ?? 1;
            $start = $_POST['start'] ?? 0;
            $length = $_POST['length'] ?? 10;
            $searchValue = $_POST['search']['value'] ?? '';
            $orderColumnIndex = $_POST['order'][0]['column'] ?? 0;
            $orderDir = $_POST['order'][0]['dir'] ?? 'desc';
            $columnFilterState = $_POST['columnFilterState'] ?? [];

            $columns = [
                0 => 'p.id',
                1 => 'p.id',
                2 => 'p.ad_soyad',
                3 => 'p.tc_kimlik',
                4 => 'p.cinsiyet',
                5 => 'u.unvan',
                6 => 'u.ogrenim',
                7 => 'COALESCE(u_def.ucret, u.ucret)',
                8 => 'p.durum',
                9 => 'p.goreve_baslama_tarihi',
                10 => 'p.goreve_baslama_tarihi', 
                11 => 'p.telefon',
                12 => 'p.id'
            ];

            $orderColumn = $columns[$orderColumnIndex] ?? 'p.id';

            $where = ["p.deleted_at IS NULL", "p.tenant_id = :tenant_id"];
            $params = [':tenant_id' => $tenant_id];

            if (!empty($searchValue)) {
                $where[] = "(p.ad_soyad LIKE :search1 OR p.tc_kimlik LIKE :search2 OR u.unvan LIKE :search3 OR p.telefon LIKE :search4)";
                $params[':search1'] = "%$searchValue%";
                $params[':search2'] = "%$searchValue%";
                $params[':search3'] = "%$searchValue%";
                $params[':search4'] = "%$searchValue%";
            }

            if (!empty($columnFilterState) && is_array($columnFilterState)) {
                foreach ($columnFilterState as $colIdx => $config) {
                    if (empty($config['rules'])) continue;
                    
                    $colField = $columns[$colIdx] ?? null;
                    if (!$colField) continue;

                    if ($colIdx == 10) {
                        $colField = "DATE_ADD(p.goreve_baslama_tarihi, INTERVAL 3 YEAR)";
                    }

                    $matchLogic = ($config['match'] === 'any') ? ' OR ' : ' AND ';
                    $rulesSql = [];
                    
                    foreach ($config['rules'] as $ruleIdx => $rule) {
                        $op = $rule['operator'];
                        $val = $rule['value'];
                        $type = $rule['type'];
                        $paramName = ":col_{$colIdx}_{$ruleIdx}";

                        if ($type === 'date' && !empty($val)) {
                            $val = date('Y-m-d', strtotime($val));
                        } elseif ($type === 'numeric' && !empty($val)) {
                            $val = str_replace(['.', ','], ['', '.'], $val);
                            $val = preg_replace('/[^\d.]/', '', $val);
                        }

                        if ($colIdx == 8) {
                            $mapping = [
                                'aktif' => 'aktif',
                                'pasif' => 'pasif',
                                'dilekçe alındı' => 'dilekce_alindi',
                                'kadroya geçti' => 'kadroya_gecti',
                                'kadroya geçmeyecek' => 'kadroya_gecmeyecek'
                            ];
                            $val = $mapping[mb_strtolower($val)] ?? $val;
                        }

                        if ($val === '') continue;

                        switch ($op) {
                            case 'contains':
                                $rulesSql[] = "$colField LIKE $paramName";
                                $params[$paramName] = "%$val%";
                                break;
                            case 'equals':
                                $rulesSql[] = "$colField = $paramName";
                                $params[$paramName] = $val;
                                break;
                            case 'starts':
                                $rulesSql[] = "$colField LIKE $paramName";
                                $params[$paramName] = "$val%";
                                break;
                            case 'ends':
                                $rulesSql[] = "$colField LIKE $paramName";
                                $params[$paramName] = "%$val";
                                break;
                            case 'gt':
                                $rulesSql[] = "$colField > $paramName";
                                $params[$paramName] = $val;
                                break;
                            case 'lt':
                                $rulesSql[] = "$colField < $paramName";
                                $params[$paramName] = $val;
                                break;
                            case 'gte':
                                $rulesSql[] = "$colField >= $paramName";
                                $params[$paramName] = $val;
                                break;
                            case 'lte':
                                $rulesSql[] = "$colField <= $paramName";
                                $params[$paramName] = $val;
                                break;
                        }
                    }

                    if (!empty($rulesSql)) {
                        $where[] = "(" . implode($matchLogic, $rulesSql) . ")";
                    }
                }
            }

            $whereSql = implode(" AND ", $where);

            $totalStmt = $db->prepare("SELECT COUNT(*) FROM personeller WHERE deleted_at IS NULL AND tenant_id = ?");
            $totalStmt->execute([$tenant_id]);
            $totalRecords = $totalStmt->fetchColumn();

            $defModel = new Definition();
            $settings = $defModel->getSettings($tenant_id);
            $default_period = $settings['default_wage_period'] ?? '2026-1';
            $params[':default_period'] = $default_period;

            $filteredSql = "
                SELECT COUNT(*) 
                FROM personeller p 
                LEFT JOIN ucretler u ON p.ucret_id = u.id 
                LEFT JOIN ucretler u_def ON u_def.tenant_id = p.tenant_id 
                                        AND u_def.unvan = u.unvan 
                                        AND u_def.ogrenim = u.ogrenim 
                                        AND u_def.kidem_yili = u.kidem_yili
                                        AND u_def.donem = :default_period
                                        AND u_def.deleted_at IS NULL
                WHERE $whereSql
            ";
            $filteredStmt = $db->prepare($filteredSql);
            $filteredStmt->execute($params);
            $totalRecordsWithFilter = $filteredStmt->fetchColumn();

            $limitSql = "";
            if ($length != -1) {
                $limitSql = " LIMIT :start, :length";
            }

            $dataSql = "
                SELECT p.*, 
                       u.unvan, 
                       COALESCE(u_def.ucret, u.ucret) as ucret, 
                       u.ogrenim 
                FROM personeller p 
                LEFT JOIN ucretler u ON p.ucret_id = u.id 
                LEFT JOIN ucretler u_def ON u_def.tenant_id = p.tenant_id 
                                        AND u_def.unvan = u.unvan 
                                        AND u_def.ogrenim = u.ogrenim 
                                        AND u_def.kidem_yili = u.kidem_yili
                                        AND u_def.donem = :default_period
                                        AND u_def.deleted_at IS NULL
                WHERE $whereSql
                ORDER BY $orderColumn $orderDir
                $limitSql
            ";
            $dataStmt = $db->prepare($dataSql);
            foreach ($params as $key => $val) {
                $dataStmt->bindValue($key, $val);
            }
            if ($length != -1) {
                $dataStmt->bindValue(':start', (int)$start, PDO::PARAM_INT);
                $dataStmt->bindValue(':length', (int)$length, PDO::PARAM_INT);
            }
            $dataStmt->execute();
            $personnels = $dataStmt->fetchAll(PDO::FETCH_ASSOC);

            if (ob_get_length()) ob_clean();
            header('Content-Type: application/json');
            echo json_encode([
                "draw" => intval($draw),
                "recordsTotal" => intval($totalRecords),
                "recordsFiltered" => intval($totalRecordsWithFilter),
                "data" => $personnels
            ], JSON_UNESCAPED_UNICODE);
            exit;
        } catch (\Exception $e) {
            if (ob_get_length()) ob_clean();
            header('Content-Type: application/json', true, 500);
            echo json_encode([
                "error" => $e->getMessage(),
                "trace" => $e->getTraceAsString()
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    public function printDocument() {
        $id = $_GET['id'] ?? null;
        $type = $_GET['type'] ?? 'dilekce';

        if (!$id) {
            die('ID required');
        }

        global $db;
        
        $stmt_p = $db->prepare("SELECT * FROM personeller WHERE id = ? AND deleted_at IS NULL LIMIT 1");
        $stmt_p->execute([$id]);
        $p = $stmt_p->fetch();

        if (!$p) {
            die('Personnel not found');
        }

        $tenant_id = $p['tenant_id'];

        $defModel = new Definition();
        $settings = $defModel->getSettings($tenant_id);
        $default_period = $settings['default_wage_period'] ?? '2026-1';

        $stmt_wage = $db->prepare("
            SELECT u.unvan, 
                   COALESCE(u_def.ucret, u.ucret) as ucret, 
                   u.ogrenim 
            FROM ucretler u 
            LEFT JOIN ucretler u_def ON u_def.tenant_id = ? 
                                    AND u_def.unvan = u.unvan 
                                    AND u_def.ogrenim = u.ogrenim 
                                    AND u_def.kidem_yili = u.kidem_yili
                                    AND u_def.donem = ?
                                    AND u_def.deleted_at IS NULL
            WHERE u.id = ? AND u.deleted_at IS NULL LIMIT 1
        ");
        $stmt_wage->execute([$tenant_id, $default_period, $p['ucret_id']]);
        $w = $stmt_wage->fetch();

        $unvan = $w['unvan'] ?? '';
        $ucret = $w['ucret'] ?? 0;
        $ogrenim = $w['ogrenim'] ?? '';

        $name = $p['ad_soyad'];
        $tc = $p['tc_kimlik'];
        $telefon = $p['telefon'];
        $baslama = $p['goreve_baslama_tarihi'] ? date('d.m.Y', strtotime($p['goreve_baslama_tarihi'])) : '';
        $cinsiyet = strtolower($p['cinsiyet'] ?? 'erkek');

        $todayStr = date('d.m.Y');

        if ($type === 'dilekce') {
            $custom_petition = $settings['custom_petition_template'] ?? '';

            $defaultContent = 
                '<p style="text-align: center; font-size: 11pt; margin-bottom: 2pt;"><strong>DÜZCE ÜNİVERSİTESİ REKTÖRLÜĞÜNE</strong></p>' .
                '<p style="text-align: center; font-size: 11pt; margin-bottom: 12pt;">(...................................................................)</p>' .
                '<p><br></p>' .
                '<p style="text-indent: 1.5cm; text-align: justify; margin-bottom: 12pt;">Üniversiteniz ................................................................... biriminde, 657 sayılı Devlet Memurları Kanunu\'nun 4/B maddesi uyarınca <strong>' . $unvan . '</strong> pozisyonunda sözleşmeli personel olarak <strong>' . $baslama . '</strong> tarihinden itibaren görev yapmaktayım.</p>' .
                '<p style="text-indent: 1.5cm; text-align: justify; margin-bottom: 12pt;">26 Ocak 2023 tarih ve 32085 sayılı Resmi Gazete\'de yayınlanan 7433 sayılı "<em>Devlet Memurları Kanunu ve Bazı Kanunlar ile 663 Sayılı Kanun Hükmünde Kararnamelerde Değişiklik Yapılmasına Dair Kanun</em>" ile 657 sayılı Devlet Memurları Kanununa eklenen "<em>...Bu kapsamda istihdam edilen sözleşmeli personelden aynı kurumda üç yıllık çalışma süresini tamamlayanlar bu sürenin bitiminden itibaren otuz gün içinde talepte bulunmaları hâlinde bulundukları yerde aynı unvanlı memur kadrolarına atanır.</em>" hükmü gereğince çalışmakta olduğum pozisyona uygun bir kadroya atanmak istiyorum. Atamaya esas kullanılmak üzere gereken belgeler dilekçemin ekinde mevcuttur.</p>' .
                '<p style="text-indent: 1.5cm; text-align: justify; margin-bottom: 24pt;">Gereğinin yapılmasını müsaadelerinizi arz ederim. ' . $todayStr . '</p>' .
                '<p style="text-align: right; margin-bottom: 24pt;"><strong>' . $name . ' / ' . $tc . ' / İMZA</strong></p>' .
                '<p style="margin-bottom: 4pt;"><strong><u>EK:</u></strong></p>' .
                '<p style="margin-bottom: 4pt;">1- Nüfus Cüzdanı Fotokopisi</p>' .
                '<p style="margin-bottom: 4pt;">2- Son öğrenim durumunu gösterir diploma aslı ve fotokopisi veya Mezun Belgesi (güncel e-devlet çıktısı)</p>' .
                '<p style="margin-bottom: 4pt;">3- Askerlik Durum Belgesi (güncel e-devlet çıktısı) / Askerliğini yapanlar için Terhis Belgesi aslı ve fotokopisi,</p>' .
                '<p style="margin-bottom: 12pt;">4- Tam teşekküllü devlet hastanesi ya da Üniversite hastanesinden alınacak sağlık kurulu (heyet) raporu (aslı ve fotokopisi ya da e-devlet çıktısı)</p>' .
                '<p style="margin-bottom: 8pt;"><strong><u>ADRES:</u></strong> ...................................................................</p>' .
                '<p style="margin-bottom: 8pt;"><strong><u>TEL:</u></strong> ' . ($telefon ?: '...................................................');

            $content = $custom_petition ?: $defaultContent;

            if ($cinsiyet === 'kadın' || $cinsiyet === 'kadin') {
                $dom = new DOMDocument();
                libxml_use_internal_errors(true);
                $dom->loadHTML('<?xml encoding="utf-8" ?>' . $content);
                libxml_clear_errors();
                
                $xpath = new DOMXPath($dom);
                $elements = $xpath->query('//p | //div | //li | //span');
                
                $toRemove = [];
                foreach ($elements as $el) {
                    $text = mb_strtolower($el->textContent, 'UTF-8');
                    if (strpos($text, 'askerlik') !== false || strpos($text, 'terhis') !== false) {
                        $toRemove[] = $el;
                    }
                }
                
                foreach ($toRemove as $el) {
                    if ($el->parentNode) {
                        $el->parentNode->removeChild($el);
                    }
                }
                
                $remaining = $xpath->query('//p | //div | //li | //span');
                $index = 1;
                foreach ($remaining as $el) {
                    $txt = trim($el->textContent);
                    if (preg_match('/^(\d+)\s*([-.]+)\s*(.*)/u', $txt, $matches)) {
                        $punct = $matches[2];
                        $rawHtml = $dom->saveHTML($el);
                        $pattern = '/(>\s*)\d+\s*([-.]+)\s*/u';
                        $newHtml = preg_replace($pattern, '${1}' . $index . $punct . ' ', $rawHtml, 1);
                        
                        if ($newHtml !== null && $newHtml !== $rawHtml) {
                            $tempDom = new DOMDocument();
                            libxml_use_internal_errors(true);
                            $tempDom->loadHTML('<?xml encoding="utf-8" ?>' . $newHtml);
                            libxml_clear_errors();
                            $newNode = $dom->importNode($tempDom->getElementsByTagName('body')->item(0)->firstChild, true);
                            if ($newNode && $el->parentNode) {
                                $el->parentNode->replaceChild($newNode, $el);
                            }
                        }
                        $index++;
                    }
                }
                
                $bodyHtml = '';
                $body = $dom->getElementsByTagName('body')->item(0);
                if ($body) {
                    foreach ($body->childNodes as $child) {
                        $bodyHtml .= $dom->saveHTML($child);
                    }
                }
                $content = $bodyHtml;
            }

            $content = str_replace(
                ['{{UNVAN}}', '{{AD_SOYAD}}', '{{TC_NO}}', '{{GOREVE_BASLAMA}}', '{{TELEFON}}', '{{TODAY}}'],
                [$unvan, $name, $tc, $baslama, $telefon ?: '...................................................', $todayStr],
                $content
            );
            $hasBorder = false;
        } else {
            $templateModel = $this->model('Template');
            $stmt_t = $db->prepare("SELECT content, has_border FROM templates WHERE tenant_id = ? ORDER BY id DESC LIMIT 1");
            $stmt_t->execute([$tenant_id]);
            $template = $stmt_t->fetch();
            $content = $template ? $template['content'] : '';
            $hasBorder = $template ? (bool)$template['has_border'] : false;

            if (empty($content)) {
                die('No contract template found.');
            }

            $replacements = [
                '{{KURUM_ADI}}' => $settings['kurum_adi'] ?? 'Kurum Adı Tanımlanmamış',
                '{{BIRIM_ADI}}' => $settings['birim_adi'] ?? 'Birim Adı Tanımlanmamış',
                '{{YETKILI_AD}}' => $settings['yetkili_ad_soyad'] ?? 'Yetkili Tanımlanmamış',
                '{{YETKILI_UNVAN}}' => $settings['yetkili_unvan'] ?? 'Yetkili Ünvanı Tanımlanmamış',
                '{{PERSONEL_AD}}' => $name,
                '{{TC_NO}}' => $tc,
                '{{GOREV}}' => $unvan,
                '{{EGITIM_DURUMU}}' => $ogrenim,
                '{{BRUT_UCRET}}' => number_format($ucret, 2, ',', '.') . ' TL',
                '{{BASLANGIC_TARIHI}}' => $baslama ?: '-',
                '{{BITIS_TARIHI}}' => '31.12.' . date('Y'),
            ];

            foreach ($replacements as $key => $value) {
                $content = str_replace($key, $value, $content);
            }
        }
        ?>
        <!DOCTYPE html>
        <html lang="tr">
        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?php echo htmlspecialchars($name) . " - " . ($type === 'dilekce' ? 'Kadro Dilekçesi' : 'Sözleşme Taslağı'); ?></title>
            <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
            <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
            <link rel="stylesheet" href="<?php echo routeUrl('assets/css/contract-document.css'); ?>">
            
            <style>
                * { box-sizing: border-box !important; }
                
                body { 
                    margin: 0 !important; 
                    padding: 0 !important; 
                    background: #f4f4f5 !important; 
                    color: #18181b !important; 
                    font-family: 'Times New Roman', Times, serif !important;
                    -webkit-print-color-adjust: exact !important; 
                    print-color-adjust: exact !important; 
                }
                
                .print-container {
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    padding: 40px 20px;
                    min-height: 100vh;
                    box-sizing: border-box;
                }
                
                /* ==========================================================================
                   DİLEKÇE FORMATI (Desktop list.php ile %100 Uyumlu)
                   ========================================================================== */
                <?php if ($type === 'dilekce'): ?>
                @page { size: A4 portrait; margin: 4.5cm 2cm 2.5cm 2cm !important; }
                
                .paper-sheet {
                    background: white;
                    width: 21cm;
                    min-height: 29.7cm;
                    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
                    border-radius: 8px;
                    box-sizing: border-box;
                    position: relative;
                    padding: 4.5cm 2cm 2.5cm 2cm !important;
                }
                
                p, div, span, strong, em, li {
                    font-family: "Times New Roman", Times, serif !important;
                    font-size: 11pt !important;
                    line-height: 1.6 !important;
                    color: black !important;
                }
                
                .ql-editor {
                    padding: 0 !important;
                    font-family: "Times New Roman", Times, serif !important;
                    font-size: 11pt !important;
                    line-height: 1.6 !important;
                    text-align: justify;
                }
                
                .ql-editor p {
                    padding-left: 2.3cm !important;
                    padding-right: 2.3cm !important;
                    margin-bottom: 8px !important;
                }
                
                /* Dilekçe Başlığı (İlk 3 paragraf) */
                .ql-editor p:nth-child(1), 
                .ql-editor p:nth-child(2), 
                .ql-editor p:nth-child(3) {
                    padding-left: 0 !important;
                    padding-right: 0 !important;
                    text-align: center !important;
                }
                
                @media print {
                    body { background: white !important; color: black !important; }
                    .print-container { padding: 0 !important; }
                    .paper-sheet {
                        box-shadow: none !important;
                        border-radius: 0 !important;
                        width: 100% !important;
                        min-height: auto !important;
                        padding: 0 !important; /* Tarayıcı @page marjinlerini kullansın */
                    }
                    .print-actions { display: none !important; }
                }
                
                /* ==========================================================================
                   SÖZLEŞME FORMATI (Desktop list.php ile %100 Uyumlu)
                   ========================================================================== */
                <?php else: ?>
                @page { size: A4 portrait; margin: 0 !important; }
                
                .paper-sheet {
                    background: white;
                    width: 210mm !important;
                    min-height: 297mm !important;
                    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
                    border-radius: 8px;
                    padding: 0cm 1.5cm 0cm !important;
                    box-sizing: border-box !important;
                    position: relative;
                }
                
                .paper-sheet * {
                    font-family: "Times New Roman", Times, serif !important;
                    font-size: 10.5pt !important;
                    line-height: 1.6 !important;
                    color: black !important;
                }
                
                .ql-editor {
                    padding: 0 !important;
                }
                
                @media print {
                    html, body { 
                        background: white !important;
                        width: 210mm !important;
                        height: auto !important;
                        overflow: visible !important;
                    }
                    .print-container { padding: 0 !important; }
                    .paper-sheet {
                        margin: 0 auto !important; 
                        box-shadow: none !important; 
                        border-radius: 0 !important;
                        width: 210mm !important;
                        height: auto !important;
                        min-height: 297mm !important;
                        max-height: none !important;
                        overflow: visible !important;
                    }
                    .print-actions { display: none !important; }
                }
                <?php endif; ?>

                /* Ortak Buton Kontrolleri */
                .print-actions {
                    position: fixed;
                    bottom: 24px;
                    right: 24px;
                    display: flex;
                    gap: 12px;
                    z-index: 9999;
                }
                .btn-print {
                    background: #4f46e5;
                    color: white;
                    border: none;
                    padding: 12px 24px;
                    border-radius: 50px;
                    font-size: 14px;
                    font-weight: bold;
                    cursor: pointer;
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    box-shadow: 0 10px 15px -3px rgba(79, 70, 229, 0.4);
                    transition: all 0.2s ease;
                    font-family: 'Inter', sans-serif !important;
                }
                .btn-print:hover {
                    background: #4338ca;
                    transform: translateY(-2px);
                }
                .btn-print:active {
                    transform: translateY(0);
                }
            </style>
        </head>
        <body>
            <div class="print-container">
                <div class="paper-sheet <?php echo ($type === 'sozlesme') ? 'contract-document ' . ($hasBorder ? 'has-border' : '') : ''; ?>">
                    <div class="ql-container ql-snow" style="border:none">
                        <div class="ql-editor" id="<?php echo ($type === 'sozlesme') ? 'print-content' : ''; ?>">
                            <?php echo $content; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="print-actions">
                <button onclick="window.print();" class="btn-print">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8" rx="1"/></svg>
                    Yazdır
                </button>
            </div>
            
            <script>
                window.addEventListener('DOMContentLoaded', () => {
                    setTimeout(() => {
                        window.print();
                    }, 800);
                });
            </script>
        </body>
        </html>
        <?php
        exit;
    }
}
