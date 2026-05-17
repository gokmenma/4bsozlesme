<?php

class PersonnelController extends Controller {
    
    public function list() {
        global $db;
        
        $tenant_id = $_SESSION['tenant_id'] ?? 0;
        
        $personnels = [];

        // Ücret tanımlarını çekelim (yeni personel formu için)
        $stmt_ucret = $db->prepare("SELECT id, unvan, ucret, ogrenim, kidem_yili FROM ucretler WHERE deleted_at IS NULL AND tenant_id = ? ORDER BY unvan ASC");
        $stmt_ucret->execute([$tenant_id]);
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

        // Unique TC Check for this tenant
        $checkStmt = $db->prepare("SELECT id FROM personeller WHERE tenant_id = ? AND tc_kimlik = ? AND deleted_at IS NULL LIMIT 1");
        $checkStmt->execute([$tenant_id, $tc_kimlik]);
        if ($checkStmt->fetch()) {
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Bu TC Kimlik numarasına sahip bir personel zaten kayıtlı.']);
                exit;
            }
            $_SESSION['error'] = 'Bu TC Kimlik numarasına sahip bir personel zaten kayıtlı.';
            header('Location: ' . routeUrl('personel-listesi'));
            exit;
        }

        $data = [
            'tc_kimlik' => $tc_kimlik,
            'ad_soyad' => $_POST['ad_soyad'],
            'ucret_id' => $_POST['ucret_id'],
            'durum' => $_POST['durum'] ?? 'aktif',
            'goreve_baslama_tarihi' => date('Y-m-d', strtotime($_POST['goreve_baslama_tarihi'])),
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

        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
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

        $data = [
            'tc_kimlik' => $tc_kimlik,
            'ad_soyad' => $_POST['ad_soyad'],
            'ucret_id' => $_POST['ucret_id'],
            'durum' => $_POST['durum'] ?? 'aktif',
            'goreve_baslama_tarihi' => date('Y-m-d', strtotime($_POST['goreve_baslama_tarihi'])),
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
        $stmt = $db->prepare("
            SELECT p.*, u.unvan, u.ucret, u.ogrenim 
            FROM personeller p 
            LEFT JOIN ucretler u ON p.ucret_id = u.id 
            WHERE p.id = ? AND p.tenant_id = ?
        ");
        $tenant_id = $_SESSION['tenant_id'] ?? 0;
        $stmt->execute([$id, $tenant_id]);
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
        
        $stmt = $db->prepare("
            SELECT p.*, u.unvan, u.ucret, u.ogrenim 
            FROM personeller p 
            LEFT JOIN ucretler u ON p.ucret_id = u.id 
            WHERE p.id = ? AND p.tenant_id = ?
        ");
        $tenant_id = $_SESSION['tenant_id'] ?? 0;
        $stmt->execute([$id, $tenant_id]);
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
                7 => 'u.ucret',
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

            $filteredSql = "SELECT COUNT(*) FROM personeller p LEFT JOIN ucretler u ON p.ucret_id = u.id WHERE $whereSql";
            $filteredStmt = $db->prepare($filteredSql);
            $filteredStmt->execute($params);
            $totalRecordsWithFilter = $filteredStmt->fetchColumn();

            $limitSql = "";
            if ($length != -1) {
                $limitSql = " LIMIT :start, :length";
            }

            $dataSql = "
                SELECT p.*, u.unvan, u.ucret, u.ogrenim 
                FROM personeller p 
                LEFT JOIN ucretler u ON p.ucret_id = u.id 
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
}
