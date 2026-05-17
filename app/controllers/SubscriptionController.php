<?php

class SubscriptionController extends Controller {
    private $db;

    public function __construct() {
        global $db;
        $this->db = $db;
    }

    public function index() {
        // Paketleri getir
        $stmt = $this->db->query("SELECT * FROM subscription_plans WHERE is_active = 1");
        $plans = $stmt->fetchAll();

        // Satın alma geçmişini getir (Mevcut tenant için veya superadmin ise hepsi)
        $historyQuery = "SELECT s.*, sp.name as plan_name, t.name as tenant_name, u.name as user_name 
                        FROM subscriptions s 
                        JOIN subscription_plans sp ON s.plan_id = sp.id 
                        LEFT JOIN tenants t ON s.tenant_id = t.id
                        LEFT JOIN users u ON s.user_id = u.id";
        
        if ($_SESSION['role'] !== 'superadmin') {
            $historyQuery .= " WHERE s.tenant_id = " . (int)$_SESSION['tenant_id'];
        }
        
        $historyQuery .= " ORDER BY s.created_at DESC";
        $history = $this->db->query($historyQuery)->fetchAll();

        // Özet Bilgiler (Summary Stats)
        $stats = [
            'total_revenue' => 0,
            'active_count' => 0,
            'total_count' => count($history)
        ];

        foreach ($history as $item) {
            $stats['total_revenue'] += $item['amount'];
            if ($item['status'] === 'active') {
                $stats['active_count']++;
            }
        }

        return [
            'pageTitle' => 'Abonelik Yönetimi',
            'pageSubtitle' => 'Abonelik paketlerinizi ve ödeme geçmişinizi yönetin',
            'plans' => $plans,
            'history' => $history,
            'stats' => $stats
        ];
    }
    public function purchase() {
        $plan_id = $_POST['plan_id'];
        $tenant_id = $_SESSION['tenant_id'] ?? null;

        // Superadmin ise ve tenant_id yoksa, ilk bulduğu aktif tenant'a ata (Test için)
        if (!$tenant_id && $_SESSION['role'] === 'superadmin') {
            $stmt = $this->db->query("SELECT id FROM tenants LIMIT 1");
            $tenant = $stmt->fetch();
            if ($tenant) {
                $tenant_id = $tenant['id'];
            }
        }

        if (!$tenant_id) {
            echo json_encode(['success' => false, 'message' => 'Abonelik için bir işletme hesabı (tenant) seçili olmalıdır. Lütfen profilinizden işletmenizi kontrol edin.']);
            exit;
        }

        // Plan bilgilerini al
        $stmt = $this->db->prepare("SELECT * FROM subscription_plans WHERE id = ?");
        $stmt->execute([$plan_id]);
        $plan = $stmt->fetch();

        if (!$plan) {
            echo json_encode(['success' => false, 'message' => 'Geçersiz paket.']);
            exit;
        }

        $startDate = date('Y-m-d');
        $endDate = date('Y-m-d', strtotime("+{$plan['duration_days']} days"));
        $user_id = $_SESSION['user_id'] ?? null;

        $stmt = $this->db->prepare("INSERT INTO subscriptions (tenant_id, user_id, plan_id, start_date, end_date, amount, status) VALUES (?, ?, ?, ?, ?, ?, 'active')");
        $result = $stmt->execute([$tenant_id, $user_id, $plan_id, $startDate, $endDate, $plan['price']]);

        echo json_encode(['success' => $result, 'message' => $result ? 'Abonelik başarıyla başlatıldı!' : 'Bir hata oluştu.']);
    }

    public function storePlan() {
        $this->checkSuperadmin();
        $data = [
            'name' => $_POST['name'],
            'price' => $_POST['price'],
            'duration_days' => $_POST['duration_days'],
            'features' => $_POST['features'],
            'is_active' => 1
        ];

        $stmt = $this->db->prepare("INSERT INTO subscription_plans (name, price, duration_days, features, is_active) VALUES (?, ?, ?, ?, ?)");
        $result = $stmt->execute([$data['name'], $data['price'], $data['duration_days'], $data['features'], $data['is_active']]);

        echo json_encode(['success' => $result]);
    }

    public function getPlan() {
        $this->checkSuperadmin();
        $id = $_GET['id'];
        $stmt = $this->db->prepare("SELECT * FROM subscription_plans WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode($stmt->fetch());
    }

    public function updatePlan() {
        $this->checkSuperadmin();
        $id = $_POST['id'];
        $data = [
            'name' => $_POST['name'],
            'price' => $_POST['price'],
            'duration_days' => $_POST['duration_days'],
            'features' => $_POST['features']
        ];

        $stmt = $this->db->prepare("UPDATE subscription_plans SET name = ?, price = ?, duration_days = ?, features = ? WHERE id = ?");
        $result = $stmt->execute([$data['name'], $data['price'], $data['duration_days'], $data['features'], $id]);

        echo json_encode(['success' => $result]);
    }

    public function deletePlan() {
        $this->checkSuperadmin();
        $id = $_POST['id'];
        $stmt = $this->db->prepare("UPDATE subscription_plans SET is_active = 0 WHERE id = ?");
        $result = $stmt->execute([$id]);
        echo json_encode(['success' => $result]);
    }

    private function checkSuperadmin() {
        if ($_SESSION['role'] !== 'superadmin') {
            echo json_encode(['success' => false, 'message' => 'Yetkiniz yok.']);
            exit;
        }
    }
}
