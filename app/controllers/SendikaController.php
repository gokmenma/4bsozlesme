<?php

class SendikaController extends Controller {

    public function index() {
        global $db;
        $tenant_id = $_SESSION['tenant_id'] ?? 0;

        $stmt = $db->prepare("SELECT id, ad_soyad, tc_kimlik FROM personeller WHERE deleted_at IS NULL AND tenant_id = ? ORDER BY ad_soyad ASC");
        $stmt->execute([$tenant_id]);
        $personeller = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmtSendika = $db->prepare("SELECT DISTINCT sendika FROM personel_sendika WHERE deleted_at IS NULL AND tenant_id = ? AND sendika != '' ORDER BY sendika ASC");
        $stmtSendika->execute([$tenant_id]);
        $sendikalar = $stmtSendika->fetchAll(PDO::FETCH_COLUMN);

        return [
            'personeller' => $personeller,
            'sendikalar' => $sendikalar
        ];
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

            $columns = [
                0 => 'ps.id',
                1 => 'p.ad_soyad',
                2 => 'p.tc_kimlik',
                3 => 'ps.sendika',
                4 => 'ps.uye_aidat_tipi',
                5 => 'ps.basvuru_tarihi',
                6 => 'ps.uyelik_tarihi',
                7 => 'ps.cikis_tarihi',
                8 => 'ps.temsilci_mi',
                9 => 'ps.id'
            ];

            $orderColumn = $columns[$orderColumnIndex] ?? 'ps.id';

            $where = ["ps.deleted_at IS NULL", "ps.tenant_id = :tenant_id"];
            $params = [':tenant_id' => $tenant_id];

            if (!empty($searchValue)) {
                $where[] = "(p.ad_soyad LIKE :search1 OR p.tc_kimlik LIKE :search2 OR ps.sendika LIKE :search3)";
                $params[':search1'] = "%$searchValue%";
                $params[':search2'] = "%$searchValue%";
                $params[':search3'] = "%$searchValue%";
            }

            $whereSql = implode(" AND ", $where);

            // Total count
            $totalStmt = $db->prepare("SELECT COUNT(*) FROM personel_sendika WHERE deleted_at IS NULL AND tenant_id = ?");
            $totalStmt->execute([$tenant_id]);
            $totalRecords = $totalStmt->fetchColumn();

            // Filtered count
            $filteredSql = "
                SELECT COUNT(*) 
                FROM personel_sendika ps
                JOIN personeller p ON ps.personel_id = p.id
                WHERE $whereSql
            ";
            $filteredStmt = $db->prepare($filteredSql);
            $filteredStmt->execute($params);
            $totalRecordsWithFilter = $filteredStmt->fetchColumn();

            $limitSql = "";
            if ($length != -1) {
                $limitSql = " LIMIT :start, :length";
            }

            // Data query
            $dataSql = "
                SELECT ps.*, p.ad_soyad, p.tc_kimlik
                FROM personel_sendika ps
                JOIN personeller p ON ps.personel_id = p.id
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
            $data = $dataStmt->fetchAll(PDO::FETCH_ASSOC);

            // Format dates for response
            foreach ($data as &$row) {
                if (!empty($row['basvuru_tarihi']) && $row['basvuru_tarihi'] !== '0000-00-00') {
                    $row['basvuru_tarihi'] = date('d.m.Y', strtotime($row['basvuru_tarihi']));
                } else {
                    $row['basvuru_tarihi'] = '-';
                }

                if (!empty($row['uyelik_tarihi']) && $row['uyelik_tarihi'] !== '0000-00-00') {
                    $row['uyelik_tarihi'] = date('d.m.Y', strtotime($row['uyelik_tarihi']));
                }

                if (!empty($row['cikis_tarihi']) && $row['cikis_tarihi'] !== '0000-00-00') {
                    $row['cikis_tarihi'] = date('d.m.Y', strtotime($row['cikis_tarihi']));
                } else {
                    $row['cikis_tarihi'] = '-';
                }
            }

            header('Content-Type: application/json');
            echo json_encode([
                "draw"            => intval($draw),
                "recordsTotal"    => intval($totalRecords),
                "recordsFiltered" => intval($totalRecordsWithFilter),
                "data"            => $data
            ]);
        } catch (Exception $e) {
            header('Content-Type: application/json', true, 500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }

    public function store() {
        global $db;
        $tenant_id = $_SESSION['tenant_id'] ?? 0;

        $personel_id = $_POST['personel_id'] ?? '';
        $sendika = trim($_POST['sendika'] ?? '');
        $uye_aidat_tipi = trim($_POST['uye_aidat_tipi'] ?? '');
        $basvuru_tarihi = $this->parseDate($_POST['basvuru_tarihi'] ?? '');
        $uyelik_tarihi = $this->parseDate($_POST['uyelik_tarihi'] ?? '');
        $cikis_tarihi = $this->parseDate($_POST['cikis_tarihi'] ?? '');
        $bas_temsilci_mi = isset($_POST['bas_temsilci_mi']) ? (int)$_POST['bas_temsilci_mi'] : 0;
        $temsilci_mi = isset($_POST['temsilci_mi']) ? (int)$_POST['temsilci_mi'] : 0;

        if (empty($personel_id)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Personel seçimi zorunludur.']);
            exit;
        }

        if (empty($sendika)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Sendika adı zorunludur.']);
            exit;
        }

        if (empty($uyelik_tarihi)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Üyelik tarihi zorunludur.']);
            exit;
        }

        if (!empty($cikis_tarihi) && $cikis_tarihi < $uyelik_tarihi) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Çıkış tarihi üyelik tarihinden önce olamaz.']);
            exit;
        }

        if ($this->checkOverlap($personel_id, $uyelik_tarihi, $cikis_tarihi)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Bu personel için belirtilen tarihlerde başka bir sendika kaydı mevcuttur (Çakışma var).']);
            exit;
        }

        $stmt = $db->prepare("INSERT INTO personel_sendika (tenant_id, personel_id, sendika, uye_aidat_tipi, basvuru_tarihi, uyelik_tarihi, cikis_tarihi, bas_temsilci_mi, temsilci_mi, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $success = $stmt->execute([$tenant_id, $personel_id, $sendika, $uye_aidat_tipi, $basvuru_tarihi, $uyelik_tarihi, $cikis_tarihi, $bas_temsilci_mi, $temsilci_mi]);

        header('Content-Type: application/json');
        echo json_encode(['success' => $success]);
        exit;
    }

    public function update() {
        global $db;
        $tenant_id = $_SESSION['tenant_id'] ?? 0;
        $id = $_POST['id'] ?? 0;

        $personel_id = $_POST['personel_id'] ?? '';
        $sendika = trim($_POST['sendika'] ?? '');
        $uye_aidat_tipi = trim($_POST['uye_aidat_tipi'] ?? '');
        $basvuru_tarihi = $this->parseDate($_POST['basvuru_tarihi'] ?? '');
        $uyelik_tarihi = $this->parseDate($_POST['uyelik_tarihi'] ?? '');
        $cikis_tarihi = $this->parseDate($_POST['cikis_tarihi'] ?? '');
        $bas_temsilci_mi = isset($_POST['bas_temsilci_mi']) ? (int)$_POST['bas_temsilci_mi'] : 0;
        $temsilci_mi = isset($_POST['temsilci_mi']) ? (int)$_POST['temsilci_mi'] : 0;

        if (empty($id)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Kayıt ID bulunamadı.']);
            exit;
        }

        if (empty($personel_id)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Personel seçimi zorunludur.']);
            exit;
        }

        if (empty($sendika)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Sendika adı zorunludur.']);
            exit;
        }

        if (empty($uyelik_tarihi)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Üyelik tarihi zorunludur.']);
            exit;
        }

        if (!empty($cikis_tarihi) && $cikis_tarihi < $uyelik_tarihi) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Çıkış tarihi üyelik tarihinden önce olamaz.']);
            exit;
        }

        if ($this->checkOverlap($personel_id, $uyelik_tarihi, $cikis_tarihi, $id)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Bu personel için belirtilen tarihlerde başka bir sendika kaydı mevcuttur (Çakışma var).']);
            exit;
        }

        $stmt = $db->prepare("UPDATE personel_sendika SET personel_id = ?, sendika = ?, uye_aidat_tipi = ?, basvuru_tarihi = ?, uyelik_tarihi = ?, cikis_tarihi = ?, bas_temsilci_mi = ?, temsilci_mi = ?, updated_at = NOW() WHERE id = ? AND tenant_id = ?");
        $success = $stmt->execute([$personel_id, $sendika, $uye_aidat_tipi, $basvuru_tarihi, $uyelik_tarihi, $cikis_tarihi, $bas_temsilci_mi, $temsilci_mi, $id, $tenant_id]);

        header('Content-Type: application/json');
        echo json_encode(['success' => $success]);
        exit;
    }

    public function delete() {
        global $db;
        $id = $_POST['id'] ?? 0;
        $tenant_id = $_SESSION['tenant_id'] ?? 0;

        if ($id > 0 && $tenant_id > 0) {
            $stmt = $db->prepare("UPDATE personel_sendika SET deleted_at = NOW() WHERE id = ? AND tenant_id = ?");
            $success = $stmt->execute([$id, $tenant_id]);
            header('Content-Type: application/json');
            echo json_encode(['success' => $success]);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Geçersiz parametre.']);
        }
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
        $tenant_id = $_SESSION['tenant_id'] ?? 1;
        $count = 0;
        $errors = [];
        $row_idx = 0;

        $db->beginTransaction();
        try {
            $stmt_find_person = $db->prepare("SELECT id, ad_soyad FROM personeller WHERE tc_kimlik = ? AND tenant_id = ? AND deleted_at IS NULL LIMIT 1");
            
            $stmt_insert = $db->prepare("INSERT INTO personel_sendika (tenant_id, personel_id, sendika, uye_aidat_tipi, basvuru_tarihi, uyelik_tarihi, cikis_tarihi, bas_temsilci_mi, temsilci_mi, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");

            foreach ($data as $row) {
                $row_idx++;
                $row_num = $row_idx + 1; // Row 1 is header in Excel

                // Normalize header keys
                $tc = trim((string)($row['TC Kimlik No'] ?? $row['tc_kimlik'] ?? $row['TC Kimlik'] ?? ''));
                $sendika = trim((string)($row['Sendika'] ?? $row['sendika'] ?? ''));
                $uye_aidat_tipi = trim((string)($row['Üye Aidat Tipi'] ?? $row['uye_aidat_tipi'] ?? ''));
                $basvuru_tarihi_raw = trim((string)($row['Başvuru Tarihi'] ?? $row['basvuru_tarihi'] ?? ''));
                $uyelik_tarihi_raw = trim((string)($row['Üyelik Tarihi'] ?? $row['uyelik_tarihi'] ?? ''));
                $cikis_tarihi_raw = trim((string)($row['Çıkış Tarihi'] ?? $row['cikis_tarihi'] ?? ''));
                $bas_temsilci_mi_raw = trim((string)($row['Baş Temsilci Mi'] ?? $row['bas_temsilci_mi'] ?? '0'));
                $temsilci_mi_raw = trim((string)($row['Temsilci Mi'] ?? $row['temsilci_mi'] ?? '0'));

                if (empty($tc)) {
                    $errors[] = "Satır {$row_num}: TC Kimlik No boş olamaz.";
                    continue;
                }

                if (empty($sendika)) {
                    $errors[] = "Satır {$row_num}: Sendika adı boş olamaz.";
                    continue;
                }

                if (empty($uyelik_tarihi_raw)) {
                    $errors[] = "Satır {$row_num}: Üyelik Tarihi boş olamaz.";
                    continue;
                }

                // Find person id
                $stmt_find_person->execute([$tc, $tenant_id]);
                $person = $stmt_find_person->fetch(PDO::FETCH_ASSOC);

                if (!$person) {
                    $errors[] = "Satır {$row_num}: {$tc} TC Kimlik numarasına sahip personel sistemde bulunamadı.";
                    continue;
                }

                $personel_id = $person['id'];

                // Parse dates
                $basvuru_tarihi = $this->parseExcelDate($basvuru_tarihi_raw);
                $uyelik_tarihi = $this->parseExcelDate($uyelik_tarihi_raw);
                $cikis_tarihi = $this->parseExcelDate($cikis_tarihi_raw);

                if (empty($uyelik_tarihi)) {
                    $errors[] = "Satır {$row_num}: Üyelik Tarihi formatı geçersiz.";
                    continue;
                }

                if (!empty($cikis_tarihi) && $cikis_tarihi < $uyelik_tarihi) {
                    $errors[] = "Satır {$row_num}: Çıkış Tarihi Üyelik Tarihinden önce olamaz.";
                    continue;
                }

                // Check overlap
                if ($this->checkOverlap($personel_id, $uyelik_tarihi, $cikis_tarihi)) {
                    $errors[] = "Satır {$row_num}: {$person['ad_soyad']} ({$tc}) için çakışan sendika üyelik tarihi aralığı tespit edildi.";
                    continue;
                }

                $bas_temsilci_mi = $this->parseBoolean($bas_temsilci_mi_raw);
                $temsilci_mi = $this->parseBoolean($temsilci_mi_raw);

                $stmt_insert->execute([
                    $tenant_id,
                    $personel_id,
                    $sendika,
                    $uye_aidat_tipi,
                    $basvuru_tarihi,
                    $uyelik_tarihi,
                    $cikis_tarihi,
                    $bas_temsilci_mi,
                    $temsilci_mi
                ]);
                $count++;
            }

            $db->commit();
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'count' => $count, 'errors' => $errors]);
        } catch (Exception $e) {
            $db->rollBack();
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }

    private function parseDate($dateStr) {
        if (empty($dateStr)) return null;
        if (preg_match('/^\d{2}\.\d{2}\.\d{4}$/', $dateStr)) {
            $parts = explode('.', $dateStr);
            return "{$parts[2]}-{$parts[1]}-{$parts[0]}";
        }
        $parsed = strtotime($dateStr);
        return $parsed ? date('Y-m-d', $parsed) : null;
    }

    private function parseExcelDate($val) {
        $val = trim($val);
        if (empty($val)) return null;

        if (preg_match('/^\d{2}\.\d{2}\.\d{4}$/', $val)) {
            $parts = explode('.', $val);
            return "{$parts[2]}-{$parts[1]}-{$parts[0]}";
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $val)) {
            return $val;
        }
        if (is_numeric($val)) {
            return date('Y-m-d', ($val - 25569) * 86400);
        }
        $parsed = strtotime($val);
        return $parsed ? date('Y-m-d', $parsed) : null;
    }

    private function parseBoolean($val) {
        $val = mb_strtolower(trim($val), 'UTF-8');
        if (in_array($val, ['1', 'evet', 'yes', 'true', 'aktif'])) {
            return 1;
        }
        return 0;
    }

    private function checkOverlap($personel_id, $uyelik_tarihi, $cikis_tarihi, $exclude_id = null) {
        global $db;
        $tenant_id = $_SESSION['tenant_id'] ?? 0;

        $new_start = $uyelik_tarihi;
        $new_end = empty($cikis_tarihi) ? '2099-12-31' : $cikis_tarihi;

        $query = "SELECT id, uyelik_tarihi, cikis_tarihi FROM personel_sendika WHERE personel_id = ? AND tenant_id = ? AND deleted_at IS NULL";
        $params = [$personel_id, $tenant_id];

        if ($exclude_id) {
            $query .= " AND id != ?";
            $params[] = $exclude_id;
        }

        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($records as $r) {
            $ex_start = $r['uyelik_tarihi'];
            $ex_end = empty($r['cikis_tarihi']) || $r['cikis_tarihi'] === '0000-00-00' ? '2099-12-31' : $r['cikis_tarihi'];

            if ($new_start <= $ex_end && $ex_start <= $new_end) {
                return true; // Overlap exists
            }
        }

        return false;
    }
}
