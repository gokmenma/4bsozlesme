<?php

require_once __DIR__ . '/../core/Model.php';

class ActivityLog extends Model {
    protected $table = 'activity_logs';

    /**
     * İşlem günlüğü kaydeder
     */
    public function log($action, $description = '', $userId = null, $tenantId = null) {
        try {
            $userId = $userId ?? ($_SESSION['user_id'] ?? null);
            $tenantId = $tenantId ?? ($_SESSION['tenant_id'] ?? null);
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;

            return $this->create([
                'tenant_id' => $tenantId,
                'user_id' => $userId,
                'action' => trim($action),
                'description' => trim($description),
                'ip_address' => $ipAddress
            ]);
        } catch (Exception $e) {
            error_log('ActivityLog log error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Kurum işlem günlüklerini getirir
     */
    public function getLogsByTenant($tenantId, $limit = 100) {
        $stmt = $this->db->prepare("
            SELECT al.*, u.name as user_name, u.email as user_email 
            FROM activity_logs al 
            LEFT JOIN users u ON al.user_id = u.id 
            WHERE al.tenant_id = ? 
            ORDER BY al.id DESC 
            LIMIT ?
        ");
        $stmt->bindValue(1, (int)$tenantId, PDO::PARAM_INT);
        $stmt->bindValue(2, (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
