<?php

/**
 * ISO 27001 Standartlarına Uygun Güvenlik ve Şifreleme Yardımcısı
 * - Dosyaları AES-256-GCM ile diskte şifreli saklar (Ham dosya saklanmaz).
 * - ID ve hassas parametreleri URL/Form için şifreler/çözer.
 */
class Security {

    /**
     * Şifreleme anahtarını ortam değişkenlerinden alır (.env)
     * Hardcoded fallback ASLA bulunmaz.
     */
    private static function getKey(): string {
        $envKey = getenv('APP_KEY') ?: ($_ENV['APP_KEY'] ?? ($_SERVER['APP_KEY'] ?? null));
        if (empty($envKey)) {
            $envKey = getenv('FILE_ENCRYPTION_KEY') ?: ($_ENV['FILE_ENCRYPTION_KEY'] ?? ($_SERVER['FILE_ENCRYPTION_KEY'] ?? null));
        }

        if (empty($envKey)) {
            error_log('Security Error: Encryption key (APP_KEY) is not defined in environment variables.');
            throw new RuntimeException('Sistem şifreleme anahtarı yapılandırılmamış.');
        }

        if (strpos($envKey, 'base64:') === 0) {
            $binaryKey = base64_decode(substr($envKey, 7));
        } elseif (ctype_xdigit($envKey) && strlen($envKey) === 64) {
            $binaryKey = hex2bin($envKey);
        } else {
            $binaryKey = hash('sha256', $envKey, true);
        }

        if (strlen($binaryKey) !== 32) {
            error_log('Security Error: Invalid encryption key length.');
            throw new RuntimeException('Geçersiz şifreleme anahtarı uzunluğu.');
        }

        return $binaryKey;
    }

    /**
     * Fiziksel bir dosyayı AES-256-GCM ile şifreleyerek hedefe yazar.
     * Disk üzerinde asla şifresiz/ham dosya bırakılmaz.
     * 
     * @param string $sourcePath Kaynak geçici dosya yolu
     * @param string $destPath Hedef şifreli dosya (.enc)
     * @param string $mimeType Dosyanın MIME türü
     * @return bool
     */
    public static function encryptFile(string $sourcePath, string $destPath, string $mimeType = 'application/octet-stream'): bool {
        try {
            if (!file_exists($sourcePath) || !is_readable($sourcePath)) {
                error_log("Security::encryptFile - Kaynak dosya okunamadı: {$sourcePath}");
                return false;
            }

            $data = file_get_contents($sourcePath);
            if ($data === false) {
                return false;
            }

            $key = self::getKey();
            $cipher = 'aes-256-gcm';
            $ivLength = openssl_cipher_iv_length($cipher);
            $iv = random_bytes($ivLength);
            $tag = '';

            $encryptedData = openssl_encrypt(
                $data,
                $cipher,
                $key,
                OPENSSL_RAW_DATA,
                $iv,
                $tag,
                '',
                16
            );

            if ($encryptedData === false) {
                error_log('Security::encryptFile - openssl_encrypt başarısız.');
                return false;
            }

            // Dosya Başlığı: MAGIC (4B) + MIME Uzunluğu (1B) + MIME Metni + IV (12B) + TAG (16B) + Şifreli Veri
            $magic = 'ENC1';
            $mimeLength = pack('C', min(strlen($mimeType), 255));
            $safeMime = substr($mimeType, 0, 255);

            $payload = $magic . $mimeLength . $safeMime . $iv . $tag . $encryptedData;

            $destDir = dirname($destPath);
            if (!is_dir($destDir)) {
                mkdir($destDir, 0770, true);
            }

            $written = file_put_contents($destPath, $payload, LOCK_EX);
            return $written !== false;
        } catch (Throwable $e) {
            error_log('Security::encryptFile Exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Şifrelenmiş dosyayı hafızada deşifre eder (Disk üzerine yazmaz).
     * 
     * @param string $encryptedPath
     * @return array|null ['content' => string, 'mime' => string]
     */
    public static function decryptFile(string $encryptedPath): ?array {
        try {
            if (!file_exists($encryptedPath) || !is_readable($encryptedPath)) {
                return null;
            }

            $payload = file_get_contents($encryptedPath);
            if ($payload === false || strlen($payload) < 33) {
                return null;
            }

            // Magic kontrolü
            $magic = substr($payload, 0, 4);
            if ($magic !== 'ENC1') {
                error_log('Security::decryptFile - Geçersiz dosya formatı.');
                return null;
            }

            $mimeLength = unpack('C', substr($payload, 4, 1))[1];
            $offset = 5;

            $mimeType = substr($payload, $offset, $mimeLength);
            $offset += $mimeLength;

            $ivLength = 12; // aes-256-gcm
            $tagLength = 16;

            $iv = substr($payload, $offset, $ivLength);
            $offset += $ivLength;

            $tag = substr($payload, $offset, $tagLength);
            $offset += $tagLength;

            $encryptedData = substr($payload, $offset);

            $key = self::getKey();
            $decrypted = openssl_decrypt(
                $encryptedData,
                'aes-256-gcm',
                $key,
                OPENSSL_RAW_DATA,
                $iv,
                $tag
            );

            if ($decrypted === false) {
                error_log('Security::decryptFile - openssl_decrypt başarısız (yetkisiz veya bozuk dosya).');
                return null;
            }

            return [
                'content' => $decrypted,
                'mime' => $mimeType ?: 'application/octet-stream'
            ];
        } catch (Throwable $e) {
            error_log('Security::decryptFile Exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Hassas verileri / ID'leri şifreler (URL-Safe)
     */
    public static function encrypt($data): string {
        try {
            $string = (string)$data;
            $key = self::getKey();
            $cipher = 'aes-256-gcm';
            $iv = random_bytes(12);
            $tag = '';

            $encrypted = openssl_encrypt(
                $string,
                $cipher,
                $key,
                OPENSSL_RAW_DATA,
                $iv,
                $tag,
                '',
                16
            );

            if ($encrypted === false) {
                return '';
            }

            $combined = $iv . $tag . $encrypted;
            return rtrim(strtr(base64_encode($combined), '+/', '-_'), '=');
        } catch (Throwable $e) {
            error_log('Security::encrypt Exception: ' . $e->getMessage());
            return '';
        }
    }

    /**
     * Şifrelenmiş hassas veriyi / ID'yi çözer
     */
    public static function decrypt(string $token): ?string {
        try {
            $base64 = strtr($token, '-_', '+/');
            $base64 .= str_repeat('=', (4 - strlen($base64) % 4) % 4);
            $raw = base64_decode($base64, true);

            if ($raw === false || strlen($raw) < 28) {
                return null;
            }

            $iv = substr($raw, 0, 12);
            $tag = substr($raw, 12, 16);
            $ciphertext = substr($raw, 28);

            $key = self::getKey();
            $decrypted = openssl_decrypt(
                $ciphertext,
                'aes-256-gcm',
                $key,
                OPENSSL_RAW_DATA,
                $iv,
                $tag
            );

            return $decrypted !== false ? $decrypted : null;
        } catch (Throwable $e) {
            error_log('Security::decrypt Exception: ' . $e->getMessage());
            return null;
        }
    }
}

