<?php
require_once __DIR__ . '/../core/Model.php';

class EvrakDosya extends Model {
    protected $table = 'evrak_dosyalari';

    public const MAX_BOYUT = 10485760;
    public const IZINLI_MIME = [
        'application/pdf' => 'pdf',
        'image/jpeg'      => 'jpg',
        'image/png'       => 'png',
        'image/webp'      => 'webp',
        'image/heic'      => 'heic',
        'image/heif'      => 'heif',
    ];

    public static function baseDir(): string {
        $env = getenv('EVRAK_STORAGE_PATH') ?: ($_ENV['EVRAK_STORAGE_PATH'] ?? '');
        $dir = $env !== '' ? $env : dirname(__DIR__, 2) . '/storage/private/evrak';
        return rtrim($dir, '/');
    }

    public function store(array $paket, array $upload, string $tur, ?int $formId, ?string $alanAnahtar, string $yukleyen, ?int $userId): array {
        if (!isset($upload['error']) || is_array($upload['error']) || $upload['error'] !== UPLOAD_ERR_OK) {
            throw new InvalidArgumentException('Dosya yüklenemedi. Lütfen tekrar deneyin.');
        }
        if (!is_uploaded_file($upload['tmp_name'])) {
            throw new InvalidArgumentException('Geçersiz dosya.');
        }
        $boyut = (int)$upload['size'];
        if ($boyut <= 0 || $boyut > self::MAX_BOYUT) {
            throw new InvalidArgumentException('Dosya boyutu en fazla 10 MB olabilir.');
        }
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($upload['tmp_name']) ?: '';
        if (!isset(self::IZINLI_MIME[$mime])) {
            throw new InvalidArgumentException('Yalnızca PDF, JPG, PNG, WEBP veya HEIC dosyası yükleyebilirsiniz.');
        }
        if ($tur === 'imzali_nusha' && $mime !== 'application/pdf' && strpos($mime, 'image/') !== 0) {
            throw new InvalidArgumentException('İmzalı nüsha PDF veya görsel olmalıdır.');
        }

        $goreli = (int)$paket['tenant_id'] . '/' . (int)$paket['id'] . '/' . bin2hex(random_bytes(16)) . '.enc';
        $hedef = self::baseDir() . '/' . $goreli;
        if (!Security::encryptFile($upload['tmp_name'], $hedef, $mime)) {
            throw new RuntimeException('Dosya güvenli olarak kaydedilemedi.');
        }
        @unlink($upload['tmp_name']);

        $orijinal = preg_replace('/[^\p{L}\p{N}\s._-]+/u', '', basename((string)($upload['name'] ?? 'belge')));
        $orijinal = mb_substr($orijinal !== '' ? $orijinal : 'belge.' . self::IZINLI_MIME[$mime], 0, 200);

        if ($tur === 'yukleme' && $formId && $alanAnahtar) {
            foreach ($this->listForField((int)$paket['id'], $formId, $alanAnahtar) as $eski) {
                $this->remove($eski);
            }
        }

        $id = (int)$this->create([
            'paket_id'         => (int)$paket['id'],
            'form_id'          => $formId,
            'alan_anahtar'     => $alanAnahtar,
            'tur'              => $tur,
            'dosya_yolu'       => $goreli,
            'mime'             => $mime,
            'boyut'            => $boyut,
            'orijinal_ad'      => $orijinal,
            'yukleyen'         => $yukleyen,
            'yukleyen_user_id' => $userId,
        ]);

        return ['id' => $id, 'orijinal_ad' => $orijinal, 'mime' => $mime, 'boyut' => $boyut];
    }

    public function listForPackage(int $paketId): array {
        $stmt = $this->db->prepare("SELECT id, form_id, alan_anahtar, tur, mime, boyut, orijinal_ad, yukleyen, created_at FROM {$this->table} WHERE paket_id = ? ORDER BY id ASC");
        $stmt->execute([$paketId]);
        return $stmt->fetchAll();
    }

    public function listForField(int $paketId, int $formId, string $alanAnahtar): array {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE paket_id = ? AND form_id = ? AND alan_anahtar = ? AND tur = 'yukleme'");
        $stmt->execute([$paketId, $formId, $alanAnahtar]);
        return $stmt->fetchAll();
    }

    public function uploadedKeys(int $paketId): array {
        $stmt = $this->db->prepare("SELECT form_id, alan_anahtar, orijinal_ad FROM {$this->table} WHERE paket_id = ? AND tur = 'yukleme'");
        $stmt->execute([$paketId]);
        $sonuc = [];
        foreach ($stmt->fetchAll() as $r) {
            $sonuc[(int)$r['form_id']][$r['alan_anahtar']] = $r['orijinal_ad'];
        }
        return $sonuc;
    }

    public function findInPackage(int $id, int $paketId): ?array {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = ? AND paket_id = ? LIMIT 1");
        $stmt->execute([$id, $paketId]);
        return $stmt->fetch() ?: null;
    }

    public function read(array $dosya): ?array {
        $yol = self::baseDir() . '/' . $dosya['dosya_yolu'];
        if (strpos($dosya['dosya_yolu'], '..') !== false) {
            return null;
        }
        return Security::decryptFile($yol);
    }

    public function remove(array $dosya): bool {
        if (strpos($dosya['dosya_yolu'], '..') === false) {
            $yol = self::baseDir() . '/' . $dosya['dosya_yolu'];
            if (is_file($yol) && !unlink($yol)) {
                error_log('EvrakDosya: dosya silinemedi ' . $dosya['dosya_yolu']);
            }
            $klasor = dirname($yol);
            if (is_dir($klasor) && count(scandir($klasor)) === 2) {
                @rmdir($klasor);
            }
        }
        return $this->delete((int)$dosya['id']);
    }
}
