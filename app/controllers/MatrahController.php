<?php

class MatrahController extends Controller {

    public function index() {
        global $db;

        // Restriction: Only Superadmin users can view and perform these actions
        if (($_SESSION['role'] ?? '') !== 'superadmin') {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Yetkiniz yok. Bu işlemi yalnızca Superadmin yapabilir.']);
                exit;
            }
            header('Location: ' . routeUrl('/'));
            exit;
        }

        // Server-Side Processing for DataTables (support both POST and GET)
        if (isset($_REQUEST['draw'])) {
            file_put_contents('c:/xampp/htdocs/sozlesme-4b/scratch/log.txt', print_r($_REQUEST, true));
            header('Content-Type: application/json');

            $draw = (int)($_REQUEST['draw'] ?? 1);
            $start = (int)($_REQUEST['start'] ?? 0);
            $length = (int)($_REQUEST['length'] ?? 10);
            $searchValue = $_REQUEST['search']['value'] ?? '';

            // Order mapping
            $columns = ['unvan', 'ogrenim', 'hizmet_yili', 'gosterge_puan', 'ek_gosterge_puan', 'yan_odeme_puan', 'ozel_hizmet_puan', 'derece'];
            $orderColumnIndex = (int)($_REQUEST['order'][0]['column'] ?? 0);
            $orderDir = $_REQUEST['order'][0]['dir'] ?? 'asc';
            if ($orderDir !== 'asc' && $orderDir !== 'desc') {
                $orderDir = 'asc';
            }
            $orderBy = $columns[$orderColumnIndex] ?? 'unvan';

            // Total Count
            $totalCount = (int)$db->query("SELECT COUNT(*) FROM matrah")->fetchColumn();

            // Build search conditions
            $where = "WHERE 1=1";
            $params = [];

            if (!empty($searchValue)) {
                $where .= " AND (unvan LIKE ? OR ogrenim LIKE ? OR derece LIKE ?)";
                $params[] = "%$searchValue%";
                $params[] = "%$searchValue%";
                $params[] = "%$searchValue%";
            }

            // Custom Column Filtering via rule arrays from client
            $columnFilters = json_decode($_REQUEST['columnFilters'] ?? '', true);
            if (!empty($columnFilters)) {
                foreach ($columnFilters as $colIndex => $config) {
                    if (!isset($config['rules']) || empty($config['rules'])) continue;
                    
                    $colName = $columns[(int)$colIndex] ?? '';
                    if (empty($colName)) continue;

                    $matchMode = ($config['match'] ?? 'all') === 'all' ? 'AND' : 'OR';
                    $ruleClauses = [];

                    $isNumeric = in_array($colName, ['hizmet_yili', 'gosterge_puan', 'ek_gosterge_puan', 'yan_odeme_puan', 'ozel_hizmet_puan']);

                    foreach ($config['rules'] as $rule) {
                        if (!isset($rule['value']) || $rule['value'] === '') continue;

                        $op = $rule['operator'] ?? '';
                        $v = $rule['value'];

                        if ($isNumeric) {
                            $cleanVal = floatval(preg_replace('/[^\d.]/', '', str_replace(',', '.', $v)));
                            if ($op === 'gt') { $ruleClauses[] = "$colName > ?"; $params[] = $cleanVal; }
                            elseif ($op === 'lt') { $ruleClauses[] = "$colName < ?"; $params[] = $cleanVal; }
                            elseif ($op === 'gte') { $ruleClauses[] = "$colName >= ?"; $params[] = $cleanVal; }
                            elseif ($op === 'lte') { $ruleClauses[] = "$colName <= ?"; $params[] = $cleanVal; }
                            elseif ($op === 'equals') { $ruleClauses[] = "$colName = ?"; $params[] = $cleanVal; }
                        } else {
                            $cleanVal = trim($v);
                            if ($op === 'contains') { $ruleClauses[] = "$colName LIKE ?"; $params[] = "%$cleanVal%"; }
                            elseif ($op === 'equals') { $ruleClauses[] = "$colName = ?"; $params[] = $cleanVal; }
                            elseif ($op === 'starts') { $ruleClauses[] = "$colName LIKE ?"; $params[] = "$cleanVal%"; }
                            elseif ($op === 'ends') { $ruleClauses[] = "$colName LIKE ?"; $params[] = "%$cleanVal"; }
                        }
                    }

                    if (!empty($ruleClauses)) {
                        $where .= " AND (" . implode(" $matchMode ", $ruleClauses) . ")";
                    }
                }
            }

            $filteredStmt = $db->prepare("SELECT COUNT(*) FROM matrah $where");
            $filteredStmt->execute($params);
            $filteredCount = (int)$filteredStmt->fetchColumn();

            $querySql = "SELECT * FROM matrah $where ORDER BY $orderBy $orderDir LIMIT $length OFFSET $start";
            $dataStmt = $db->prepare($querySql);
            $dataStmt->execute($params);
            $rows = $dataStmt->fetchAll();

            $data = [];
            foreach ($rows as $row) {
                $data[] = [
                    htmlspecialchars($row['unvan']),
                    htmlspecialchars($row['ogrenim']),
                    htmlspecialchars($row['hizmet_yili']),
                    htmlspecialchars($row['gosterge_puan']),
                    htmlspecialchars($row['ek_gosterge_puan']),
                    htmlspecialchars($row['yan_odeme_puan']),
                    htmlspecialchars($row['ozel_hizmet_puan']),
                    htmlspecialchars($row['derece']),
                    '<div class="flex items-center justify-end gap-2">
                      <button onclick=\'openModal("edit", ' . json_encode($row) . ')\' class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-border bg-background hover:bg-muted transition-colors" title="Düzenle">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>
                      </button>
                      <button onclick=\'openModal("copy", ' . json_encode($row) . ')\' class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-border bg-background hover:bg-muted transition-colors text-indigo-600 dark:text-indigo-400" title="Kopyala">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
                      </button>
                      <button onclick="openDeleteModal(' . $row['id'] . ')" class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-destructive/30 bg-background text-destructive hover:bg-destructive/10 transition-colors" title="Sil">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                      </button>
                    </div>'
                ];
            }

            echo json_encode([
                'draw' => $draw,
                'recordsTotal' => $totalCount,
                'recordsFiltered' => $filteredCount,
                'data' => $data
            ]);
            exit;
        }

        // AJAX POST Requests
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json');

            $action = $_POST['action'] ?? '';
            
            if ($action === 'create') {
                $unvan = trim($_POST['unvan'] ?? '');
                $ogrenim = trim($_POST['ogrenim'] ?? '');
                $hizmet_yili = (int)($_POST['hizmet_yili'] ?? 0);
                $gosterge_puan = (int)($_POST['gosterge_puan'] ?? 0);
                $ek_gosterge_puan = (int)($_POST['ek_gosterge_puan'] ?? 0);
                $yan_odeme_puan = (int)($_POST['yan_odeme_puan'] ?? 0);
                $ozel_hizmet_puan = (int)($_POST['ozel_hizmet_puan'] ?? 0);
                $derece = trim($_POST['derece'] ?? '');

                if (empty($unvan) || empty($ogrenim)) {
                    echo json_encode(['success' => false, 'message' => 'Unvan ve Öğrenim alanları zorunludur.']);
                    exit;
                }

                $stmt = $db->prepare("
                    INSERT INTO matrah 
                    (unvan, ogrenim, hizmet_yili, gosterge_puan, ek_gosterge_puan, yan_odeme_puan, ozel_hizmet_puan, derece) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$unvan, $ogrenim, $hizmet_yili, $gosterge_puan, $ek_gosterge_puan, $yan_odeme_puan, $ozel_hizmet_puan, $derece]);

                echo json_encode(['success' => true, 'message' => 'Matrah kaydı başarıyla eklendi.']);
                exit;
            }

            if ($action === 'update') {
                $id = (int)($_POST['id'] ?? 0);
                $unvan = trim($_POST['unvan'] ?? '');
                $ogrenim = trim($_POST['ogrenim'] ?? '');
                $hizmet_yili = (int)($_POST['hizmet_yili'] ?? 0);
                $gosterge_puan = (int)($_POST['gosterge_puan'] ?? 0);
                $ek_gosterge_puan = (int)($_POST['ek_gosterge_puan'] ?? 0);
                $yan_odeme_puan = (int)($_POST['yan_odeme_puan'] ?? 0);
                $ozel_hizmet_puan = (int)($_POST['ozel_hizmet_puan'] ?? 0);
                $derece = trim($_POST['derece'] ?? '');

                if ($id <= 0 || empty($unvan) || empty($ogrenim)) {
                    echo json_encode(['success' => false, 'message' => 'Geçerli bir kayıt seçiniz ve zorunlu alanları doldurunuz.']);
                    exit;
                }

                $stmt = $db->prepare("
                    UPDATE matrah 
                    SET unvan = ?, ogrenim = ?, hizmet_yili = ?, gosterge_puan = ?, ek_gosterge_puan = ?, yan_odeme_puan = ?, ozel_hizmet_puan = ?, derece = ? 
                    WHERE id = ?
                ");
                $stmt->execute([$unvan, $ogrenim, $hizmet_yili, $gosterge_puan, $ek_gosterge_puan, $yan_odeme_puan, $ozel_hizmet_puan, $derece, $id]);

                echo json_encode(['success' => true, 'message' => 'Matrah kaydı başarıyla güncellendi.']);
                exit;
            }

            if ($action === 'delete') {
                $id = (int)($_POST['id'] ?? 0);

                if ($id <= 0) {
                    echo json_encode(['success' => false, 'message' => 'Geçerli bir kayıt seçilmedi.']);
                    exit;
                }

                $stmt = $db->prepare("DELETE FROM matrah WHERE id = ?");
                $stmt->execute([$id]);

                echo json_encode(['success' => true, 'message' => 'Matrah kaydı başarıyla silindi.']);
                exit;
            }

            echo json_encode(['success' => false, 'message' => 'Geçersiz işlem.']);
            exit;
        }

        // Fetch just the count for instant loading
        $totalCount = (int)$db->query("SELECT COUNT(*) FROM matrah")->fetchColumn();

        $pageTitle = 'Matrah Tablosu Yönetimi';
        $pageSubtitle = 'Bu sayfadan matrah tablosundaki kayıtları ekleyebilir, güncelleyebilir ve silebilirsiniz.';
        
        include 'app/pages/matrah/yonetim.php';
        return;
    }
}
