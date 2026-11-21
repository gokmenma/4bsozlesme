<?php
require_once __DIR__ . '/../core/Model.php';

class EvrakForm extends Model {
    protected $table = 'evrak_formlari';

    public const TIPLER = [
        'bilgi' => 'Bilgi Formu',
        'kvkk'  => 'KVKK / Onay Metni',
        'beyan' => 'Beyan Formu',
        'belge' => 'Belge Yükleme',
    ];

    public const ALAN_TIPLERI = [
        'text'     => 'Kısa Metin',
        'textarea' => 'Uzun Metin',
        'number'   => 'Sayı',
        'date'     => 'Tarih',
        'tel'      => 'Telefon',
        'email'    => 'E-posta',
        'tc'       => 'T.C. Kimlik No',
        'iban'     => 'IBAN',
        'select'   => 'Açılır Liste',
        'radio'    => 'Tek Seçim',
        'checkbox' => 'Onay Kutusu',
        'file'     => 'Dosya Yükleme',
    ];

    public const PERSONEL_ALANLARI = [
        'telefon'  => 'Telefon',
        'cinsiyet' => 'Cinsiyet',
    ];

    public function listByTenant(int $tenantId, bool $sadeceAktif = false): array {
        $sql = "SELECT f.*, (SELECT COUNT(*) FROM evrak_form_alanlari a WHERE a.form_id = f.id) AS alan_sayisi
                FROM {$this->table} f
                WHERE f.tenant_id = ? AND f.deleted_at IS NULL";
        if ($sadeceAktif) {
            $sql .= " AND f.aktif = 1";
        }
        $sql .= " ORDER BY f.sira ASC, f.id ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$tenantId]);
        return $stmt->fetchAll();
    }

    public function findForTenant(int $id, int $tenantId): ?array {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = ? AND tenant_id = ? AND deleted_at IS NULL LIMIT 1");
        $stmt->execute([$id, $tenantId]);
        $form = $stmt->fetch();
        if (!$form) {
            return null;
        }
        $form['alanlar'] = $this->getFields($id);
        return $form;
    }

    public function getFields(int $formId): array {
        $stmt = $this->db->prepare("SELECT * FROM evrak_form_alanlari WHERE form_id = ? ORDER BY sira ASC, id ASC");
        $stmt->execute([$formId]);
        return $stmt->fetchAll();
    }

    public function saveForm(int $tenantId, array $data, int $userId): int {
        $ad = trim((string)($data['ad'] ?? ''));
        $tip = (string)($data['tip'] ?? 'bilgi');
        if ($ad === '' || mb_strlen($ad) > 255) {
            throw new InvalidArgumentException('Form adı zorunludur (en fazla 255 karakter).');
        }
        if (!isset(self::TIPLER[$tip])) {
            throw new InvalidArgumentException('Geçersiz form tipi.');
        }
        $alanlar = $this->normalizeFields($data['alanlar'] ?? []);

        $row = [
            'ad'       => $ad,
            'tip'      => $tip,
            'aciklama' => mb_substr(trim((string)($data['aciklama'] ?? '')), 0, 500),
            'metin'    => trim((string)($data['metin'] ?? '')) ?: null,
            'aktif'    => !empty($data['aktif']) ? 1 : 0,
            'sira'     => (int)($data['sira'] ?? 0),
        ];

        $this->db->beginTransaction();
        try {
            $id = (int)($data['id'] ?? 0);
            if ($id > 0) {
                if (!$this->findForTenant($id, $tenantId)) {
                    throw new InvalidArgumentException('Form bulunamadı.');
                }
                $this->update($id, $row);
                $del = $this->db->prepare("DELETE FROM evrak_form_alanlari WHERE form_id = ?");
                $del->execute([$id]);
            } else {
                $row['tenant_id'] = $tenantId;
                $row['created_by'] = $userId;
                $id = (int)$this->create($row);
            }
            $this->insertFields($id, $alanlar);
            $this->db->commit();
            return $id;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function softDelete(int $id, int $tenantId): bool {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET deleted_at = ? WHERE id = ? AND tenant_id = ? AND deleted_at IS NULL");
        $stmt->execute([date('Y-m-d H:i:s'), $id, $tenantId]);
        return $stmt->rowCount() > 0;
    }

    public function copySystemTemplates(int $tenantId, int $userId): int {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE tenant_id IS NULL AND deleted_at IS NULL ORDER BY sira ASC, id ASC");
        $stmt->execute();
        $sablonlar = $stmt->fetchAll();

        $adet = 0;
        $this->db->beginTransaction();
        try {
            foreach ($sablonlar as $s) {
                $yeniId = (int)$this->create([
                    'tenant_id'  => $tenantId,
                    'ad'         => $s['ad'],
                    'tip'        => $s['tip'],
                    'aciklama'   => $s['aciklama'],
                    'metin'      => $s['metin'],
                    'sira'       => $s['sira'],
                    'aktif'      => 1,
                    'created_by' => $userId,
                ]);
                $this->insertFields($yeniId, $this->getFields((int)$s['id']));
                $adet++;
            }
            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
        return $adet;
    }

    public function buildSnapshot(array $form): string {
        $alanlar = array_map(fn($a) => [
            'etiket'         => $a['etiket'],
            'anahtar'        => $a['anahtar'],
            'tip'            => $a['tip'],
            'secenekler'     => self::parseOptions($a['secenekler'] ?? ''),
            'yardim'         => $a['yardim'] ?? '',
            'zorunlu'        => (int)$a['zorunlu'],
            'personel_alani' => $a['personel_alani'] ?? null,
        ], $form['alanlar'] ?? []);

        return json_encode([
            'id'       => (int)$form['id'],
            'ad'       => $form['ad'],
            'tip'      => $form['tip'],
            'aciklama' => $form['aciklama'],
            'metin'    => $form['metin'],
            'alanlar'  => $alanlar,
        ], JSON_UNESCAPED_UNICODE);
    }

    public static function parseOptions($raw): array {
        if (is_array($raw)) {
            $list = $raw;
        } else {
            $list = explode(',', (string)$raw);
        }
        return array_values(array_filter(array_map(fn($o) => trim((string)$o), $list), fn($o) => $o !== ''));
    }

    private function insertFields(int $formId, array $alanlar): void {
        $stmt = $this->db->prepare("INSERT INTO evrak_form_alanlari (form_id, etiket, anahtar, tip, secenekler, yardim, zorunlu, personel_alani, sira) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        foreach (array_values($alanlar) as $i => $a) {
            $stmt->execute([
                $formId,
                $a['etiket'],
                $a['anahtar'],
                $a['tip'],
                $a['secenekler'] !== '' ? $a['secenekler'] : null,
                $a['yardim'] !== '' ? $a['yardim'] : null,
                (int)$a['zorunlu'],
                $a['personel_alani'] ?: null,
                $i + 1,
            ]);
        }
    }

    private function normalizeFields($alanlar): array {
        if (!is_array($alanlar)) {
            return [];
        }
        $sonuc = [];
        $kullanilan = [];
        foreach ($alanlar as $a) {
            $etiket = trim((string)($a['etiket'] ?? ''));
            if ($etiket === '') {
                continue;
            }
            $tip = (string)($a['tip'] ?? 'text');
            if (!isset(self::ALAN_TIPLERI[$tip])) {
                $tip = 'text';
            }
            $anahtar = self::slug((string)($a['anahtar'] ?? '')) ?: self::slug($etiket) ?: 'alan';
            $temel = $anahtar;
            $n = 2;
            while (isset($kullanilan[$anahtar])) {
                $anahtar = substr($temel, 0, 60) . '_' . $n++;
            }
            $kullanilan[$anahtar] = true;

            $personelAlani = (string)($a['personel_alani'] ?? '');
            if (!isset(self::PERSONEL_ALANLARI[$personelAlani])) {
                $personelAlani = '';
            }

            $secenekler = in_array($tip, ['select', 'radio'], true)
                ? implode(',', self::parseOptions($a['secenekler'] ?? ''))
                : '';
            if (in_array($tip, ['select', 'radio'], true) && $secenekler === '') {
                throw new InvalidArgumentException("\"{$etiket}\" alanı için en az bir seçenek giriniz.");
            }

            $sonuc[] = [
                'etiket'         => mb_substr($etiket, 0, 255),
                'anahtar'        => $anahtar,
                'tip'            => $tip,
                'secenekler'     => $secenekler,
                'yardim'         => mb_substr(trim((string)($a['yardim'] ?? '')), 0, 255),
                'zorunlu'        => !empty($a['zorunlu']) ? 1 : 0,
                'personel_alani' => $personelAlani,
            ];
        }
        if (count($sonuc) > 100) {
            throw new InvalidArgumentException('Bir formda en fazla 100 alan olabilir.');
        }
        return $sonuc;
    }

    public static function templateVars(array $settings, array $personel): array {
        return [
            '{{KURUM_ADI}}'  => $settings['kurum_adi'] ?? '',
            '{{BIRIM_ADI}}'  => $settings['birim_adi'] ?? '',
            '{{PERSONEL_AD}}'=> $personel['ad_soyad'] ?? '',
            '{{TC_NO}}'      => $personel['tc_kimlik'] ?? '',
            '{{GOREV}}'      => $personel['unvan'] ?? '',
            '{{TARIH}}'      => date('d.m.Y'),
        ];
    }

    public static function renderText(?string $metin, array $vars): string {
        if ($metin === null || $metin === '') {
            return '';
        }
        $html = htmlspecialchars(strtr($metin, $vars), ENT_QUOTES, 'UTF-8');
        $paragraflar = preg_split("/\R{2,}/", $html);
        return implode('', array_map(fn($p) => '<p>' . nl2br(trim($p)) . '</p>', $paragraflar));
    }

    public static function slug(string $metin): string {
        $tr = ['ç' => 'c', 'Ç' => 'c', 'ğ' => 'g', 'Ğ' => 'g', 'ı' => 'i', 'I' => 'i', 'İ' => 'i', 'ö' => 'o', 'Ö' => 'o', 'ş' => 's', 'Ş' => 's', 'ü' => 'u', 'Ü' => 'u'];
        $metin = strtolower(strtr($metin, $tr));
        $metin = preg_replace('/[^a-z0-9]+/', '_', $metin);
        return substr(trim($metin, '_'), 0, 60);
    }
}
