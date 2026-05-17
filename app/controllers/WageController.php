<?php

class WageController extends Controller {
    
    public function list() {
        global $db;
        
        $tenant_id = $_SESSION['tenant_id'] ?? 0;
        $stmt = $db->prepare("SELECT * FROM ucretler WHERE deleted_at IS NULL AND tenant_id = ? ORDER BY unvan ASC");
        $stmt->execute([$tenant_id]);
        $ucretler = $stmt->fetchAll();

        return [
            'ucretler' => $ucretler
        ];
    }

    public function get() {
        global $db;
        $id = $_GET['id'] ?? null;
        $tenant_id = $_SESSION['tenant_id'] ?? 0;

        if (!$id) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'ID required']);
            exit;
        }

        $stmt = $db->prepare("SELECT * FROM ucretler WHERE id = ? AND tenant_id = ? AND deleted_at IS NULL");
        $stmt->execute([$id, $tenant_id]);
        $ucret = $stmt->fetch();

        if (!$ucret) {
            header('Content-Type: application/json', true, 403);
            echo json_encode(['error' => 'Unauthorized or not found']);
            exit;
        }

        header('Content-Type: application/json');
        echo json_encode($ucret);
        exit;
    }

    public function store() {
        global $db;
        
        try {
            $data = [
                'unvan' => $_POST['unvan'] ?? '',
                'ogrenim' => $_POST['ogrenim'] ?? '',
                'kidem_yili' => $_POST['kidem_yili'] ?? '',
                'ucret' => $_POST['ucret'] ?? 0,
                'tenant_id' => !empty($_SESSION['tenant_id']) ? $_SESSION['tenant_id'] : 1,
                'created_at' => date('Y-m-d H:i:s')
            ];

            $columns = implode(', ', array_keys($data));
            $placeholders = implode(', ', array_fill(0, count($data), '?'));
            
            $sql = "INSERT INTO ucretler ({$columns}) VALUES ({$placeholders})";
            $stmt = $db->prepare($sql);
            $success = $stmt->execute(array_values($data));

            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                header('Content-Type: application/json');
                echo json_encode(['success' => $success]);
                exit;
            }

            header('Location: ' . routeUrl('ucret-tanimlari'));
            exit;
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit;
        }
    }

    public function update() {
        global $db;
        
        try {
            $id = $_POST['id'] ?? null;
            $tenant_id = $_SESSION['tenant_id'] ?? 0;

            if (!$id) {
                throw new Exception('ID required');
            }

            // Yetki kontrolü
            $checkStmt = $db->prepare("SELECT id FROM ucretler WHERE id = ? AND tenant_id = ?");
            $checkStmt->execute([$id, $tenant_id]);
            if (!$checkStmt->fetch()) {
                throw new Exception('Unauthorized access');
            }

            $data = [
                'unvan' => $_POST['unvan'],
                'ogrenim' => $_POST['ogrenim'],
                'kidem_yili' => $_POST['kidem_yili'],
                'ucret' => $_POST['ucret'],
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $sets = [];
            foreach ($data as $key => $value) {
                $sets[] = "$key = ?";
            }
            $sql = "UPDATE ucretler SET " . implode(', ', $sets) . " WHERE id = ? AND tenant_id = ?";
            $params = array_values($data);
            $params[] = $id;
            $params[] = $tenant_id;

            $stmt = $db->prepare($sql);
            $success = $stmt->execute($params);

            header('Content-Type: application/json');
            echo json_encode(['success' => $success]);
            exit;
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit;
        }
    }

    public function delete() {
        global $db;
        $id = $_POST['id'] ?? null;
        $tenant_id = $_SESSION['tenant_id'] ?? 0;

        if (!$id) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'ID required']);
            exit;
        }

        // Yetki ve kullanım kontrolü
        $checkStmt = $db->prepare("SELECT COUNT(*) FROM personeller WHERE ucret_id = ? AND tenant_id = ? AND deleted_at IS NULL");
        $checkStmt->execute([$id, $tenant_id]);
        $count = $checkStmt->fetchColumn();

        if ($count > 0) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false, 
                'error' => 'Bu ücret tanımı şu anda ' . $count . ' personel tarafından kullanılmaktadır. Silmeden önce ilgili personellerin unvanlarını değiştirmeniz gerekmektedir.'
            ]);
            exit;
        }

        $stmt = $db->prepare("UPDATE ucretler SET deleted_at = ? WHERE id = ? AND tenant_id = ?");
        $success = $stmt->execute([date('Y-m-d H:i:s'), $id, $tenant_id]);

        header('Content-Type: application/json');
        echo json_encode(['success' => $success]);
        exit;
    }

    public function import() {
        global $db;
        
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $data = $input['data'] ?? [];
            
            if (empty($data)) {
                throw new Exception('Veri bulunamadı.');
            }

            $tenant_id = $_SESSION['tenant_id'] ?? 1;
            $count = 0;

            $db->beginTransaction();

            $sql = "INSERT INTO ucretler (unvan, ogrenim, kidem_yili, ucret, tenant_id, created_at) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $db->prepare($sql);

            foreach ($data as $row) {
                if (empty($row['unvan'])) continue;

                $stmt->execute([
                    $row['unvan'],
                    $row['ogrenim'] ?? '',
                    $row['kidem_yili'] ?? '',
                    floatval($row['ucret'] ?? 0),
                    $tenant_id,
                    date('Y-m-d H:i:s')
                ]);
                $count++;
            }

            $db->commit();

            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'count' => $count]);
            exit;
        } catch (Exception $e) {
            if ($db->inTransaction()) $db->rollBack();
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit;
        }
    }
}
