<?php

require_once __DIR__ . '/../core/Model.php';

class MobileSubscriptionModel extends Model {
    protected $table = 'subscriptions';

    public function plans(): array {
        return $this->db->query("SELECT id, name, price, duration_days, features FROM subscription_plans WHERE is_active = 1 ORDER BY price ASC")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function history(): array {
        return $this->db->query(
            "SELECT s.id, s.tenant_id, s.start_date, s.end_date, s.amount, s.status, s.payment_status, s.created_at,
                    sp.name AS plan_name, t.name AS tenant_name, u.name AS user_name
             FROM subscriptions s
             JOIN subscription_plans sp ON sp.id = s.plan_id
             LEFT JOIN tenants t ON t.id = s.tenant_id
             LEFT JOIN users u ON u.id = s.user_id
             ORDER BY s.created_at DESC LIMIT 200"
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    public function exists(int $id): bool {
        $stmt = $this->db->prepare("SELECT 1 FROM subscriptions WHERE id = ?");
        $stmt->execute([$id]);
        return (bool)$stmt->fetchColumn();
    }

    public function setState(int $id, string $status, string $payment): void {
        $this->db->prepare("UPDATE subscriptions SET status = ?, payment_status = ? WHERE id = ?")->execute([$status, $payment, $id]);
    }

    public function deleteSubscription(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM subscriptions WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    public function planExists(int $id): bool {
        $stmt = $this->db->prepare("SELECT 1 FROM subscription_plans WHERE id = ? AND is_active = 1");
        $stmt->execute([$id]);
        return (bool)$stmt->fetchColumn();
    }

    public function createPlan(array $d): int {
        $this->db->prepare("INSERT INTO subscription_plans (name, price, duration_days, features, is_active) VALUES (?, ?, ?, ?, 1)")
            ->execute([$d['name'], $d['price'], $d['duration_days'], $d['features']]);
        return (int)$this->db->lastInsertId();
    }

    public function updatePlan(int $id, array $d): void {
        $this->db->prepare("UPDATE subscription_plans SET name = ?, price = ?, duration_days = ?, features = ? WHERE id = ?")
            ->execute([$d['name'], $d['price'], $d['duration_days'], $d['features'], $id]);
    }

    public function deactivatePlan(int $id): void {
        $this->db->prepare("UPDATE subscription_plans SET is_active = 0 WHERE id = ?")->execute([$id]);
    }
}
