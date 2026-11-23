<?php
require_once __DIR__ . '/../core/Model.php';

class EvrakPaket extends Model {
    protected $table = 'evrak_paketleri';

    public const DURUMLAR = [
        'gonderildi' => 'Gönderildi',
        'acildi'     => 'Dolduruluyor',
        'dolduruldu' => 'İnceleme Bekliyor',
        'iade'       => 'Düzeltme İstendi',
        'onaylandi'  => 'Onaylandı (İmza Bekliyor)',
        'tamamlandi' => 'Tamamlandı',
        'iptal'      => 'İptal Edildi',
    ];

    public const DUZENLENEBILIR = ['gonderildi', 'acildi', 'iade'];
    public const MAX_DOGRULAMA_DENEME = 5;

    public function createPackage(int $tenantId, int $personelId, array $formIds, string $kanal, int $gun, ?string $email, int $userId): array {
        $personel = $this->getPersonnel($personelId, $tenantId);
        if (!$personel) {
            throw new InvalidArgumentException('Personel bulunamadı.');
        }
        $formIds = array_values(array_unique(array_filter(array_map('intval', $formIds))));
        if (empty($formIds)) {
            throw new InvalidArgumentException('En az bir form seçmelisiniz.');
        }
        $gun = max(1, min(30, $gun));

        $formModel = new EvrakForm();
        $formlar = [];
        foreach ($formIds as $fid) {
            $form = $formModel->findForTenant($fid, $tenantId);
            if (!$form || (int)$form['aktif'] !== 1) {
                throw new InvalidArgumentException('Seçilen formlardan biri bulunamadı veya pasif.');
            }
            $formlar[] = $form;
        }
        if (empty(array_filter($formlar, fn($f) => $f['tip'] !== 'cikti'))) {
            throw new InvalidArgumentException('Personele gönderilecek en az bir doldurulabilir form seçmelisiniz. Basılı çıktı formları için "Çıktı Al" butonunu kullanın.');
        }
        usort($formlar, fn($a, $b) => [(int)$a['sira'], (int)$a['id']] <=> [(int)$b['sira'], (int)$b['id']]);

        $token = bin2hex(random_bytes(32));

        $this->db->beginTransaction();
        try {
            $iptal = $this->db->prepare("UPDATE {$this->table} SET durum = 'iptal' WHERE tenant_id = ? AND personel_id = ? AND durum IN ('gonderildi','acildi','iade')");
            $iptal->execute([$tenantId, $personelId]);

            $paketId = (int)$this->create([
                'tenant_id'        => $tenantId,
                'personel_id'      => $personelId,
                'token_hash'       => hash('sha256', $token),
                'son_gecerlilik'   => date('Y-m-d H:i:s', strtotime("+{$gun} days")),
                'durum'            => 'gonderildi',
                'gonderim_kanali'  => $kanal,
                'alici_email'      => $email ?: null,
                'gonderen_user_id' => $userId,
            ]);

            $ins = $this->db->prepare("INSERT INTO evrak_paket_formlari (paket_id, form_id, sira, form_snapshot) VALUES (?, ?, ?, ?)");
            foreach ($formlar as $i => $form) {
                $ins->execute([$paketId, (int)$form['id'], $i + 1, $formModel->buildSnapshot($form)]);
            }
            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }

        return ['id' => $paketId, 'token' => $token, 'personel' => $personel];
    }

    public function renewToken(int $paketId, int $tenantId, int $gun): string {
        $paket = $this->findForTenant($paketId, $tenantId);
        if (!$paket || !in_array($paket['durum'], self::DUZENLENEBILIR, true)) {
            throw new InvalidArgumentException('Bu paket için yeni bağlantı oluşturulamaz.');
        }
        $gun = max(1, min(30, $gun));
        $token = bin2hex(random_bytes(32));
        $stmt = $this->db->prepare("UPDATE {$this->table} SET token_hash = ?, son_gecerlilik = ?, dogrulama_deneme = 0, kilitli_until = NULL WHERE id = ? AND tenant_id = ?");
        $stmt->execute([hash('sha256', $token), date('Y-m-d H:i:s', strtotime("+{$gun} days")), $paketId, $tenantId]);
        return $token;
    }

    public function findByToken(string $token): ?array {
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            return null;
        }
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE token_hash = ? LIMIT 1");
        $stmt->execute([hash('sha256', $token)]);
        $paket = $stmt->fetch();
        return $paket ?: null;
    }

    public function findById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function findForTenant(int $id, int $tenantId): ?array {
        $stmt = $this->db->prepare("
            SELECT ep.*, p.ad_soyad, p.tc_kimlik, p.telefon, u.name AS gonderen_ad
            FROM {$this->table} ep
            JOIN personeller p ON p.id = ep.personel_id
            LEFT JOIN users u ON u.id = ep.gonderen_user_id
            WHERE ep.id = ? AND ep.tenant_id = ? LIMIT 1");
        $stmt->execute([$id, $tenantId]);
        return $stmt->fetch() ?: null;
    }

    public function listByTenant(int $tenantId, string $durum = '', string $arama = ''): array {
        $sql = "
            SELECT ep.id, ep.personel_id, ep.durum, ep.gonderim_kanali, ep.son_gecerlilik, ep.acilma_tarihi,
                   ep.gonderim_tarihi, ep.onay_tarihi, ep.created_at, p.ad_soyad, p.tc_kimlik, u.name AS gonderen_ad,
                   (SELECT COUNT(*) FROM evrak_paket_formlari pf WHERE pf.paket_id = ep.id) AS form_sayisi,
                   (SELECT COUNT(*) FROM evrak_dosyalari d WHERE d.paket_id = ep.id AND d.tur = 'imzali_nusha') AS imzali_sayisi
            FROM {$this->table} ep
            JOIN personeller p ON p.id = ep.personel_id
            LEFT JOIN users u ON u.id = ep.gonderen_user_id
            WHERE ep.tenant_id = ?";
        $params = [$tenantId];
        if ($durum !== '' && isset(self::DURUMLAR[$durum])) {
            $sql .= " AND ep.durum = ?";
            $params[] = $durum;
        }
        if ($arama !== '') {
            $sql .= " AND (p.ad_soyad LIKE ? OR p.tc_kimlik LIKE ?)";
            $params[] = '%' . $arama . '%';
            $params[] = '%' . $arama . '%';
        }
        $sql .= " ORDER BY ep.id DESC LIMIT 500";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function statusCounts(int $tenantId): array {
        $stmt = $this->db->prepare("SELECT durum, COUNT(*) AS adet FROM {$this->table} WHERE tenant_id = ? GROUP BY durum");
        $stmt->execute([$tenantId]);
        $sonuc = [];
        foreach ($stmt->fetchAll() as $r) {
            $sonuc[$r['durum']] = (int)$r['adet'];
        }
        return $sonuc;
    }

    public function getPersonnel(int $personelId, int $tenantId): ?array {
        $stmt = $this->db->prepare("
            SELECT p.id, p.tenant_id, p.ad_soyad, p.tc_kimlik, p.telefon, p.cinsiyet, p.goreve_baslama_tarihi, u.unvan, u.ogrenim
            FROM personeller p
            LEFT JOIN ucretler u ON u.id = p.ucret_id
            WHERE p.id = ? AND p.tenant_id = ? AND p.deleted_at IS NULL LIMIT 1");
        $stmt->execute([$personelId, $tenantId]);
        return $stmt->fetch() ?: null;
    }

    public function personnelOptions(int $tenantId): array {
        $stmt = $this->db->prepare("SELECT id, ad_soyad, RIGHT(tc_kimlik, 4) AS tc_son4 FROM personeller WHERE tenant_id = ? AND deleted_at IS NULL ORDER BY ad_soyad ASC LIMIT 3000");
        $stmt->execute([$tenantId]);
        return $stmt->fetchAll();
    }

    public function isLocked(array $paket): bool {
        return !empty($paket['kilitli_until']) && strtotime($paket['kilitli_until']) > time();
    }

    public function isExpired(array $paket): bool {
        return strtotime($paket['son_gecerlilik']) < time();
    }

    public function verifyIdentity(array $paket, string $tcSon4): bool {
        $personel = $this->getPersonnel((int)$paket['personel_id'], (int)$paket['tenant_id']);
        $beklenen = $personel ? substr((string)$personel['tc_kimlik'], -4) : '';
        $gecerli = $beklenen !== '' && preg_match('/^\d{4}$/', $tcSon4) && hash_equals($beklenen, $tcSon4);

        if ($gecerli) {
            $stmt = $this->db->prepare("UPDATE {$this->table} SET dogrulama_deneme = 0, kilitli_until = NULL, acilma_tarihi = COALESCE(acilma_tarihi, ?), durum = IF(durum = 'gonderildi', 'acildi', durum) WHERE id = ?");
            $stmt->execute([date('Y-m-d H:i:s'), (int)$paket['id']]);
            return true;
        }

        $deneme = (int)$paket['dogrulama_deneme'] + 1;
        $kilit = $deneme >= self::MAX_DOGRULAMA_DENEME ? date('Y-m-d H:i:s', strtotime('+30 minutes')) : null;
        $stmt = $this->db->prepare("UPDATE {$this->table} SET dogrulama_deneme = ?, kilitli_until = ? WHERE id = ?");
        $stmt->execute([$kilit ? 0 : $deneme, $kilit, (int)$paket['id']]);
        return false;
    }

    public function getForms(int $paketId, bool $dijitalOnly = false): array {
        $stmt = $this->db->prepare("SELECT form_id, form_snapshot FROM evrak_paket_formlari WHERE paket_id = ? ORDER BY sira ASC");
        $stmt->execute([$paketId]);
        $formlar = [];
        foreach ($stmt->fetchAll() as $r) {
            $snap = json_decode($r['form_snapshot'], true);
            if (is_array($snap)) {
                if ($dijitalOnly && ($snap['tip'] ?? '') === 'cikti') {
                    continue;
                }
                $snap['id'] = (int)$r['form_id'];
                $formlar[] = $snap;
            }
        }
        return $formlar;
    }

    public function getAnswers(int $paketId): array {
        $stmt = $this->db->prepare("SELECT form_id, alan_anahtar, deger, sifreli FROM evrak_yanitlari WHERE paket_id = ?");
        $stmt->execute([$paketId]);
        $sonuc = [];
        foreach ($stmt->fetchAll() as $r) {
            $deger = $r['deger'];
            if ((int)$r['sifreli'] === 1 && $deger !== null && $deger !== '') {
                $deger = Security::decrypt($deger);
            }
            $sonuc[(int)$r['form_id']][$r['alan_anahtar']] = $deger ?? '';
        }
        return $sonuc;
    }

    public function saveAnswers(int $paketId, array $form, array $girdi): void {
        $stmt = $this->db->prepare("
            INSERT INTO evrak_yanitlari (paket_id, form_id, alan_anahtar, deger, sifreli) VALUES (?, ?, ?, ?, 1)
            ON DUPLICATE KEY UPDATE deger = VALUES(deger), sifreli = 1");
        foreach ($form['alanlar'] as $alan) {
            if ($alan['tip'] === 'file') {
                continue;
            }
            $deger = self::cleanValue($alan, $girdi[$alan['anahtar']] ?? '');
            $sifreli = $deger === '' ? '' : Security::encrypt($deger);
            if ($deger !== '' && $sifreli === '') {
                throw new RuntimeException('Yanıt şifrelenemedi.');
            }
            $stmt->execute([$paketId, (int)$form['id'], $alan['anahtar'], $sifreli]);
        }
    }

    public static function cleanValue(array $alan, $deger): string {
        if (is_array($deger)) {
            $deger = '';
        }
        $deger = trim((string)$deger);
        switch ($alan['tip']) {
            case 'checkbox':
                return $deger !== '' && $deger !== '0' ? '1' : '';
            case 'iban':
                return strtoupper(preg_replace('/\s+/', '', $deger));
            case 'tc':
            case 'number':
                return preg_replace('/\D+/', '', $deger);
            case 'tel':
                return preg_replace('/[^\d+]/', '', $deger);
            case 'textarea':
                return mb_substr($deger, 0, 5000);
            default:
                return mb_substr($deger, 0, 500);
        }
    }

    public function validateForm(array $form, array $yanitlar, array $dosyaAnahtarlari): array {
        $hatalar = [];
        foreach ($form['alanlar'] as $alan) {
            $k = $alan['anahtar'];
            if ($alan['tip'] === 'file') {
                if ((int)$alan['zorunlu'] === 1 && empty($dosyaAnahtarlari[$k])) {
                    $hatalar[$k] = 'Bu belgeyi yüklemeniz gerekiyor.';
                }
                continue;
            }
            $v = (string)($yanitlar[$k] ?? '');
            if ($v === '') {
                if ((int)$alan['zorunlu'] === 1) {
                    $hatalar[$k] = $alan['tip'] === 'checkbox' ? 'Devam etmek için onaylamanız gerekiyor.' : 'Bu alan zorunludur.';
                }
                continue;
            }
            $hata = self::validateValue($alan, $v);
            if ($hata !== null) {
                $hatalar[$k] = $hata;
            }
        }
        return $hatalar;
    }

    public static function validateValue(array $alan, string $v): ?string {
        switch ($alan['tip']) {
            case 'email':
                return filter_var($v, FILTER_VALIDATE_EMAIL) ? null : 'Geçerli bir e-posta adresi giriniz.';
            case 'tel':
                $rakam = preg_replace('/\D+/', '', $v);
                return (strlen($rakam) >= 10 && strlen($rakam) <= 13) ? null : 'Geçerli bir telefon numarası giriniz.';
            case 'date':
                $d = DateTime::createFromFormat('Y-m-d', $v);
                return ($d && $d->format('Y-m-d') === $v) ? null : 'Geçerli bir tarih giriniz.';
            case 'number':
                return ctype_digit($v) && strlen($v) <= 6 ? null : 'Geçerli bir sayı giriniz.';
            case 'tc':
                return self::isValidTc($v) ? null : 'Geçerli bir T.C. Kimlik No giriniz.';
            case 'iban':
                return self::isValidIban($v) ? null : 'Geçerli bir IBAN giriniz (TR ile başlayan 26 karakter).';
            case 'select':
            case 'radio':
                return in_array($v, $alan['secenekler'] ?? [], true) ? null : 'Listeden bir seçim yapınız.';
        }
        return null;
    }

    public static function isValidTc(string $tc): bool {
        if (!preg_match('/^[1-9]\d{10}$/', $tc)) {
            return false;
        }
        $d = array_map('intval', str_split($tc));
        $tek = $d[0] + $d[2] + $d[4] + $d[6] + $d[8];
        $cift = $d[1] + $d[3] + $d[5] + $d[7];
        if ((($tek * 7) - $cift) % 10 !== $d[9]) {
            return false;
        }
        return (array_sum(array_slice($d, 0, 10)) % 10) === $d[10];
    }

    public static function isValidIban(string $iban): bool {
        if (!preg_match('/^TR\d{24}$/', $iban)) {
            return false;
        }
        $yeni = substr($iban, 4) . substr($iban, 0, 4);
        $sayisal = '';
        foreach (str_split($yeni) as $c) {
            $sayisal .= ctype_alpha($c) ? (string)(ord($c) - 55) : $c;
        }
        $mod = 0;
        foreach (str_split($sayisal, 7) as $parca) {
            $mod = (int)(($mod . $parca)) % 97;
        }
        return $mod === 1;
    }

    public function deletePackage(int $paketId, int $tenantId): bool {
        $paket = $this->findForTenant($paketId, $tenantId);
        if (!$paket) {
            return false;
        }
        $dosyaModel = new EvrakDosya();
        $stmt = $this->db->prepare("SELECT * FROM evrak_dosyalari WHERE paket_id = ?");
        $stmt->execute([$paketId]);
        foreach ($stmt->fetchAll() as $dosya) {
            $dosyaModel->remove($dosya);
        }
        $sil = $this->db->prepare("DELETE FROM {$this->table} WHERE id = ? AND tenant_id = ?");
        $sil->execute([$paketId, $tenantId]);
        return $sil->rowCount() > 0;
    }

    public function setStatus(int $paketId, string $durum, array $ek = []): bool {
        if (!isset(self::DURUMLAR[$durum])) {
            return false;
        }
        return $this->update($paketId, array_merge(['durum' => $durum], $ek));
    }

    public function transferToPersonnel(array $paket): array {
        $formlar = $this->getForms((int)$paket['id']);
        $yanitlar = $this->getAnswers((int)$paket['id']);
        $guncelle = [];
        foreach ($formlar as $form) {
            foreach ($form['alanlar'] as $alan) {
                $hedef = $alan['personel_alani'] ?? null;
                $v = (string)($yanitlar[$form['id']][$alan['anahtar']] ?? '');
                if (!$hedef || $v === '') {
                    continue;
                }
                if ($hedef === 'telefon') {
                    $rakam = substr(preg_replace('/\D+/', '', $v), -10);
                    if (strlen($rakam) === 10) {
                        $guncelle['telefon'] = '(' . substr($rakam, 0, 3) . ')' . substr($rakam, 3, 3) . '-' . substr($rakam, 6, 2) . '-' . substr($rakam, 8, 2);
                    }
                } elseif ($hedef === 'cinsiyet') {
                    $map = ['kadın' => 'kadin', 'kadin' => 'kadin', 'erkek' => 'erkek'];
                    $anahtar = mb_strtolower($v, 'UTF-8');
                    if (isset($map[$anahtar])) {
                        $guncelle['cinsiyet'] = $map[$anahtar];
                    }
                }
            }
        }
        if (!empty($guncelle)) {
            $set = implode(', ', array_map(fn($k) => "`{$k}` = ?", array_keys($guncelle)));
            $stmt = $this->db->prepare("UPDATE personeller SET {$set}, updated_at = ? WHERE id = ? AND tenant_id = ?");
            $stmt->execute(array_merge(array_values($guncelle), [date('Y-m-d H:i:s'), (int)$paket['personel_id'], (int)$paket['tenant_id']]));
        }
        return array_keys($guncelle);
    }
}
