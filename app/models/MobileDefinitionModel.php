<?php

require_once __DIR__ . '/../core/Model.php';

class MobileDefinitionModel extends Model {
    protected $table = 'definitions';

    public const KEYS = [
        'default_wage_period', 'kurum_adi', 'birim_adi',
        'yetkili_ad_soyad', 'yetkili_unvan', 'maas_katsayisi', 'yan_odeme_katsayisi',
    ];

    public function getValues(int $tenantId): array {
        $values = array_fill_keys(self::KEYS, '');
        $values['default_wage_period'] = '2026-1';
        $settings = (new Definition())->getSettings($tenantId);
        foreach (self::KEYS as $key) {
            if (isset($settings[$key]) && $settings[$key] !== null) {
                $values[$key] = (string)$settings[$key];
            }
        }
        return $values;
    }

    public function getPeriods(int $tenantId): array {
        $stmt = $this->db->prepare(
            "SELECT DISTINCT donem FROM ucretler WHERE deleted_at IS NULL AND tenant_id = ? ORDER BY donem DESC"
        );
        $stmt->execute([$tenantId]);
        return array_map('strval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    public function saveValues(int $tenantId, array $values): void {
        $definition = new Definition();
        foreach (self::KEYS as $key) {
            if (array_key_exists($key, $values)) {
                $definition->setSetting($key, $values[$key], $tenantId);
            }
        }
    }
}
