<?php

class ProfileController extends Controller {
    private $userModel;

    public function __construct() {
        $this->userModel = new User();
        
        if (!isset($_SESSION['user_id'])) {
            if ($this->isAjax()) {
                echo json_encode(['success' => false, 'message' => 'Oturum açmanız gerekiyor.']);
                exit;
            }
            header('Location: ' . routeUrl('/login'));
            exit;
        }
    }

    public function index() {
        global $db;
        $user = $this->userModel->find($_SESSION['user_id']);
        
        // Active or last subscription
        $stmt = $db->prepare("SELECT s.*, sp.name as plan_name, sp.price as plan_price, sp.features as plan_features 
                              FROM subscriptions s 
                              JOIN subscription_plans sp ON s.plan_id = sp.id
                              WHERE s.user_id = ? OR s.tenant_id = ? 
                              ORDER BY s.id DESC LIMIT 1");
        $stmt->execute([$user['id'], $user['tenant_id'] ?? null]);
        $subscription = $stmt->fetch();

        // Get all active plans that can be purchased
        $plansStmt = $db->query("SELECT * FROM subscription_plans WHERE is_active = 1");
        $plans = $plansStmt->fetchAll();

        // Get purchase history for this tenant (or all if superadmin)
        $historyQuery = "SELECT s.*, sp.name as plan_name, u.name as user_name 
                         FROM subscriptions s 
                         JOIN subscription_plans sp ON s.plan_id = sp.id 
                         LEFT JOIN users u ON s.user_id = u.id";
        
        if ($user['role'] !== 'superadmin') {
            $historyQuery .= " WHERE s.tenant_id = " . (int)$user['tenant_id'];
        }
        
        $historyQuery .= " ORDER BY s.created_at DESC";
        $history = $db->query($historyQuery)->fetchAll();

        return [
            'pageTitle' => 'Profil',
            'pageSubtitle' => 'Hesap bilgilerinizi yönetin',
            'user' => $user,
            'subscription' => $subscription,
            'plans' => $plans,
            'history' => $history
        ];
    }

    public function update() {
        $id = $_SESSION['user_id'];
        $data = [
            'name' => $_POST['name'],
            'email' => $_POST['email'],
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $result = $this->userModel->update($id, $data);

        if ($result) {
            $_SESSION['user_name'] = $_POST['name'];
            $_SESSION['user_email'] = $_POST['email'];
            echo json_encode(['success' => true, 'message' => 'Profil başarıyla güncellendi.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Profil güncellenirken bir hata oluştu.']);
        }
    }

    public function changePassword() {
        $id = $_SESSION['user_id'];
        $user = $this->userModel->find($id);

        if (!password_verify($_POST['current_password'], $user['password'])) {
            echo json_encode(['success' => false, 'message' => 'Mevcut şifreniz hatalı.']);
            exit;
        }

        if ($_POST['new_password'] !== $_POST['confirm_password']) {
            echo json_encode(['success' => false, 'message' => 'Yeni şifreler eşleşmiyor.']);
            exit;
        }

        $data = [
            'password' => password_hash($_POST['new_password'], PASSWORD_DEFAULT),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $result = $this->userModel->update($id, $data);

        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Şifreniz başarıyla değiştirildi.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Şifre değiştirilirken bir hata oluştu.']);
        }
    }

    public function deleteAccount() {
        $id = $_SESSION['user_id'];
        $user = $this->userModel->find($id);

        if (!password_verify($_POST['password'], $user['password'])) {
            echo json_encode(['success' => false, 'message' => 'Şifreniz hatalı.']);
            exit;
        }

        $result = $this->userModel->delete($id);

        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Hesabınız silindi.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Hesap silinirken bir hata oluştu.']);
        }
    }

    private function isAjax() {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
    }
}
