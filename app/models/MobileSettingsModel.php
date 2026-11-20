<?php

require_once __DIR__ . '/../core/Model.php';

class MobileSettingsModel extends Model {
    protected $table = 'tenant_settings';

    public const SECRETS = ['sms_api_key', 'smtp_pass'];

    public function getRaw(int $tenantId): array {
        $stmt = $this->db->prepare("SELECT * FROM tenant_settings WHERE tenant_id = ?");
        $stmt->execute([$tenantId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        return $row + [
            'kadro_bildirim_aktif' => 1, 'sms_api_url' => '', 'sms_api_key' => '', 'sms_sender' => '',
            'sms_active' => 0, 'sms_entegrator' => 'NETGSM', 'smtp_host' => '', 'smtp_port' => null,
            'smtp_user' => '', 'smtp_pass' => '', 'smtp_from_email' => '', 'smtp_from_name' => '',
        ];
    }

    public function getPublic(int $tenantId): array {
        $row = $this->getRaw($tenantId);
        $out = [];
        foreach (['kadro_bildirim_aktif', 'sms_active'] as $k) {
            $out[$k] = (bool)$row[$k];
        }
        foreach (['sms_api_url', 'sms_sender', 'sms_entegrator', 'smtp_host', 'smtp_user', 'smtp_from_email', 'smtp_from_name'] as $k) {
            $out[$k] = (string)($row[$k] ?? '');
        }
        $out['smtp_port'] = $row['smtp_port'] !== null && $row['smtp_port'] !== '' ? (int)$row['smtp_port'] : '';
        $out['has_sms_api_key'] = (string)$row['sms_api_key'] !== '';
        $out['has_smtp_pass'] = (string)$row['smtp_pass'] !== '';
        return $out;
    }

    public function save(int $tenantId, array $d): void {
        $current = $this->getRaw($tenantId);
        $apiKey = $d['sms_api_key'] !== '' ? $d['sms_api_key'] : (string)$current['sms_api_key'];
        $smtpPass = $d['smtp_pass'] !== '' ? $d['smtp_pass'] : (string)$current['smtp_pass'];

        $stmt = $this->db->prepare("SELECT id FROM tenant_settings WHERE tenant_id = ?");
        $stmt->execute([$tenantId]);
        $values = [
            $d['kadro_bildirim_aktif'], $d['sms_api_url'], $apiKey, $d['sms_sender'], $d['sms_active'], $d['sms_entegrator'],
            $d['smtp_host'], $d['smtp_port'], $d['smtp_user'], $smtpPass, $d['smtp_from_email'], $d['smtp_from_name'],
        ];

        if ($stmt->fetch()) {
            $this->db->prepare(
                "UPDATE tenant_settings SET kadro_bildirim_aktif = ?, sms_api_url = ?, sms_api_key = ?, sms_sender = ?,
                 sms_active = ?, sms_entegrator = ?, smtp_host = ?, smtp_port = ?, smtp_user = ?, smtp_pass = ?,
                 smtp_from_email = ?, smtp_from_name = ?, updated_at = NOW() WHERE tenant_id = ?"
            )->execute([...$values, $tenantId]);
        } else {
            $this->db->prepare(
                "INSERT INTO tenant_settings (kadro_bildirim_aktif, sms_api_url, sms_api_key, sms_sender, sms_active,
                 sms_entegrator, smtp_host, smtp_port, smtp_user, smtp_pass, smtp_from_email, smtp_from_name, tenant_id)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            )->execute([...$values, $tenantId]);
        }
    }
}
