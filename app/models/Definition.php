<?php

class Definition extends Model {
    protected $table = 'definitions';

    /**
     * Tüm ayarları anahtar-değer çifti olarak getirir (tenant_id'ye göre)
     */
    public function getSettings($tenant_id) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE tenant_id = ?");
        $stmt->execute([$tenant_id]);
        $settings = $stmt->fetchAll();
        
        $result = [];
        foreach ($settings as $setting) {
            $result[$setting['key']] = $setting['value'];
        }
        return $result;
    }

    /**
     * Belirli bir ayarı günceller veya yoksa oluşturur (tenant_id'ye göre)
     */
    public function setSetting($key, $value, $tenant_id) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE `key` = ? AND tenant_id = ?");
        $stmt->execute([$key, $tenant_id]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            $stmt = $this->db->prepare("UPDATE {$this->table} SET `value` = ? WHERE `key` = ? AND tenant_id = ?");
            return $stmt->execute([$value, $key, $tenant_id]);
        } else {
            return $this->create([
                'key' => $key, 
                'value' => $value, 
                'tenant_id' => $tenant_id
            ]);
        }
    }
}
