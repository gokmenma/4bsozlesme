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

        if (empty($result['kurum_adi']) && $tenant_id > 0) {
            $stmtTenant = $this->db->prepare("SELECT name FROM tenants WHERE id = ?");
            $stmtTenant->execute([$tenant_id]);
            $t = $stmtTenant->fetch();
            if ($t) {
                $result['kurum_adi'] = $t['name'];
            }
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

    /**
     * Belirli bir ayarı tekil olarak getirir
     */
    public function getSetting($key, $tenant_id, $default = null) {
        $stmt = $this->db->prepare("SELECT `value` FROM {$this->table} WHERE `key` = ? AND tenant_id = ? LIMIT 1");
        $stmt->execute([$key, $tenant_id]);
        $row = $stmt->fetch();
        return $row !== false && $row['value'] !== null ? $row['value'] : $default;
    }

    /**
     * Belirli bir ayarı siler
     */
    public function deleteSetting($key, $tenant_id) {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE `key` = ? AND tenant_id = ?");
        return $stmt->execute([$key, $tenant_id]);
    }

    /**
     * Kurum logo dosya yolunu ve MIME tipini döner
     */
    public function getLogo($tenant_id) {
        $filePath = $this->getSetting('kurum_logo', $tenant_id, '');
        $mimeType = $this->getSetting('kurum_logo_mime', $tenant_id, '');
        return [
            'path' => $filePath,
            'mime' => $mimeType
        ];
    }

    /**
     * Kurum logo bilgilerini günceller
     */
    public function saveLogo($tenant_id, $filePath, $mimeType = '') {
        $this->setSetting('kurum_logo', $filePath, $tenant_id);
        $this->setSetting('kurum_logo_mime', $mimeType, $tenant_id);
        return true;
    }

    /**
     * Kurum logo ayarlarını temizler
     */
    public function removeLogo($tenant_id) {
        $this->setSetting('kurum_logo', '', $tenant_id);
        $this->setSetting('kurum_logo_mime', '', $tenant_id);
        return true;
    }
}
