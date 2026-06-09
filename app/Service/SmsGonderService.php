<?php

/**
 * =========================================================================
 *  SmsGonderService — SMSMAX (sosyomaks) XML API entegrasyonu
 * =========================================================================
 *  SMSMAX XML API üzerinden tek/çok alıcıya SMS gönderir.
 *
 *  Endpoint : https://www.sosyomaks.com/api/send/post  (çalışan/üretim — token istemez)
 *  Metot    : POST  (Content-Type: text/xml; charset=utf-8)
 *  Not      : YENİ gateway https://test-sosyomaks.com/sms/api/send/post HTTP 401 ile
 *             "Authorization: Bearer <token>" ister. O sisteme geçildiğinde sms_api_url'i
 *             yeni adresle, sms_api_key'i token ile doldurmak yeterlidir.
 *
 *  Kimlik bilgileri kullanıcının profil ayarlarından (users tablosu) okunur:
 *    - sms_username : SMSMAX kullanıcı adı (genellikle e-posta)
 *    - sms_password : SMSMAX şifresi
 *    - sms_sender   : Onaylı gönderici başlığı (Originator)
 *    - sms_api_url  : (ops.) Özel endpoint; boşsa varsayılan kullanılır
 *
 *  Başarılı yanıt "ID:" ile başlar (örn: "ID: 27765"). Aksi halde dönen
 *  numerik kod bir hata kodudur (01..10).
 */
class SmsGonderService
{
    /** Varsayılan SMSMAX XML API uç noktası (token'sız çalışan üretim adresi) */
    const DEFAULT_ENDPOINT = 'https://www.sosyomaks.com/api/send/post';

    private $username;
    private $password;
    private $originator;
    private $endpoint;
    private $token;

    /**
     * @param string      $username   SMSMAX kullanıcı adı
     * @param string      $password   SMSMAX şifresi
     * @param string      $originator Gönderici başlığı (header)
     * @param string|null $endpoint   Özel uç nokta (boşsa varsayılan)
     * @param string      $token      (ops.) HTTP Authorization: Bearer token'ı.
     *                                 Yeni SMSMAX gateway'i (test-sosyomaks.com) bunu zorunlu kılar.
     */
    public function __construct(string $username, string $password, string $originator = '', ?string $endpoint = null, string $token = '')
    {
        $this->username   = $username;
        $this->password   = $password;
        $this->originator = $originator;
        $this->endpoint   = !empty($endpoint) ? $endpoint : self::DEFAULT_ENDPOINT;
        $this->token      = trim($token);
    }

    /**
     * Giriş yapmış kullanıcının (veya verilen kullanıcı id'sinin) SMS ayarlarıyla
     * bir servis örneği üretir. SMS hizmeti pasifse null döner.
     *
     * @param int|null $userId
     * @return SmsGonderService|null
     */
    public static function forUser(?int $userId = null): ?self
    {
        global $db;
        $userId = $userId ?? ($_SESSION['user_id'] ?? 0);
        if (!$userId) {
            return null;
        }

        $stmt = $db->prepare("SELECT sms_active, sms_username, sms_password, sms_sender, sms_api_url, sms_api_key
                              FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $s = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$s || (int)$s['sms_active'] !== 1) {
            return null;
        }

        return new self(
            $s['sms_username'] ?? '',
            $s['sms_password'] ?? '',
            $s['sms_sender'] ?? '',
            $s['sms_api_url'] ?? null,
            $s['sms_api_key'] ?? ''
        );
    }

    /**
     * Tek bir mesajı bir veya birden çok alıcıya gönderir (SingleTextSMS).
     *
     * @param string          $message  Mesaj metni
     * @param string|string[] $numbers  Alıcı numara(lar) — 5XXXXXXXXX biçiminde
     * @param bool            $turkish  Türkçe karakter desteği
     * @return array{success: bool, message: string, raw: string}
     */
    public function send(string $message, $numbers, bool $turkish = true): array
    {
        $list = is_array($numbers) ? $numbers : [$numbers];
        $list = array_filter(array_map([self::class, 'normalizeNumber'], $list));

        if (empty($list)) {
            return ['success' => false, 'message' => 'Geçerli bir alıcı numarası bulunamadı.', 'raw' => ''];
        }
        if (trim($message) === '') {
            return ['success' => false, 'message' => 'Mesaj metni boş olamaz.', 'raw' => ''];
        }
        if ($this->username === '' || $this->password === '') {
            return ['success' => false, 'message' => 'SMS kullanıcı adı veya şifresi tanımlı değil.', 'raw' => ''];
        }

        // SingleTextSMS Action: 0 => normal, 12 => Türkçe karakter desteği
        $action = $turkish ? '12' : '0';

        $xml  = '<SingleTextSMS>';
        $xml .= '<UserName>' . self::esc($this->username) . '</UserName>';
        $xml .= '<PassWord>' . self::esc($this->password) . '</PassWord>';
        $xml .= '<Action>' . $action . '</Action>';
        $xml .= '<Mesgbody>' . self::esc($message) . '</Mesgbody>';
        $xml .= '<Numbers>' . self::esc(implode(',', $list)) . '</Numbers>';
        $xml .= '<Originator>' . self::esc($this->originator) . '</Originator>';
        $xml .= '<SDate></SDate>';
        $xml .= '<ExDate></ExDate>';
        $xml .= '</SingleTextSMS>';

        return $this->request($xml);
    }

    /**
     * Kısa kullanım: kullanıcının ayarlarıyla doğrudan SMS gönderir.
     *
     * @return array{success: bool, message: string, raw: string}
     */
    public static function gonder(string $message, $numbers, bool $turkish = true, ?int $userId = null): array
    {
        $service = self::forUser($userId);
        if ($service === null) {
            return ['success' => false, 'message' => 'SMS hizmeti aktif değil veya ayarlar eksik.', 'raw' => ''];
        }
        return $service->send($message, $numbers, $turkish);
    }

    /**
     * Hesaba tanımlı onaylı gönderici başlıklarını sorgular (OriginatorSorgula).
     * Başarılıysa başlık listesini döner. Dikkat: operatörler başlığı ekranda
     * boşluksuz gösterebilir (örn. "DUZCE TIP" -> telefonda "DUZCETIP").
     *
     * @return array{success: bool, message: string, originators: string[], raw: string}
     */
    public function queryOriginators(): array
    {
        $xml  = '<OriginatorSorgula>';
        $xml .= '<UserName>' . self::esc($this->username) . '</UserName>';
        $xml .= '<PassWord>' . self::esc($this->password) . '</PassWord>';
        $xml .= '</OriginatorSorgula>';

        $res = $this->transport($xml);
        if (!$res['ok']) {
            return ['success' => false, 'message' => $res['error'], 'originators' => [], 'raw' => $res['body']];
        }

        $body = $res['body'];
        // Sadece hata kodu döndüyse (örn "01") başarısız say
        if (preg_match('/^\d{2}$/', $body)) {
            return ['success' => false, 'message' => self::errorMessage($body), 'originators' => [], 'raw' => $body];
        }

        $list = array_values(array_filter(array_map('trim', explode(',', $body)), fn($v) => $v !== ''));
        return ['success' => true, 'message' => 'Onaylı başlıklar alındı.', 'originators' => $list, 'raw' => $body];
    }

    /**
     * Hesaptaki kalan kontör (kredi) miktarını sorgular (KontorSorgula).
     *
     * @return array{success: bool, message: string, credit: int|null, raw: string}
     */
    public function queryCredit(): array
    {
        $xml  = '<KontorSorgula>';
        $xml .= '<UserName>' . self::esc($this->username) . '</UserName>';
        $xml .= '<PassWord>' . self::esc($this->password) . '</PassWord>';
        $xml .= '</KontorSorgula>';

        $res = $this->transport($xml);
        if (!$res['ok']) {
            return ['success' => false, 'message' => $res['error'], 'credit' => null, 'raw' => $res['body']];
        }

        $body = trim($res['body']);
        // İki haneli hata kodları (01..10) ile gerçek bakiyeyi ayırt et
        if (preg_match('/^(0[1-9]|10)$/', $body)) {
            return ['success' => false, 'message' => self::errorMessage($body), 'credit' => null, 'raw' => $body];
        }
        if (!ctype_digit($body)) {
            return ['success' => false, 'message' => 'Kontör bilgisi alınamadı. API yanıtı: ' . $body, 'credit' => null, 'raw' => $body];
        }

        return ['success' => true, 'message' => 'Kontör bilgisi alındı.', 'credit' => (int)$body, 'raw' => $body];
    }

    /**
     * SMS gönderim isteğini yürütür ve yanıtı çözümler.
     *
     * @return array{success: bool, message: string, raw: string}
     */
    private function request(string $xmlPayload): array
    {
        $res = $this->transport($xmlPayload);
        if (!$res['ok']) {
            error_log('SMS gönderilemedi: ' . $res['error']);
            return ['success' => false, 'message' => $res['error'], 'raw' => $res['body']];
        }

        $response = trim($res['body']);

        // "ID:" ile başlıyorsa gönderim başarılıdır
        if (stripos($response, 'ID:') === 0) {
            return ['success' => true, 'message' => 'SMS başarıyla gönderildi. (' . $response . ')', 'raw' => $response];
        }

        return ['success' => false, 'message' => self::errorMessage($response), 'raw' => $response];
    }

    /**
     * Ham cURL taşıması: XML gönderir, HTTP/bağlantı hatalarını yakalar.
     *
     * @return array{ok: bool, body: string, error: string}
     */
    private function transport(string $xmlPayload): array
    {
        $headers = [
            'Content-Type: text/xml; charset=utf-8',
            'Content-Length: ' . strlen($xmlPayload),
        ];
        // Yeni gateway HTTP seviyesinde Bearer token ister (401 / WWW-Authenticate: Bearer)
        if ($this->token !== '') {
            $headers[] = 'Authorization: Bearer ' . $this->token;
        }

        $ch = curl_init($this->endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $xmlPayload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HTTPHEADER     => $headers,
        ]);

        $response = curl_exec($ch);
        $error    = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false) {
            return ['ok' => false, 'body' => '', 'error' => 'Bağlantı hatası: ' . $error];
        }
        if ($httpCode === 401) {
            return ['ok' => false, 'body' => (string)$response, 'error' => 'Yetkisiz (401): Gateway geçerli bir Bearer token bekliyor. "API Token (Bearer)" alanına SMSMAX\'ten aldığınız token\'ı girin.'];
        }
        if ($httpCode !== 200) {
            return ['ok' => false, 'body' => (string)$response, 'error' => 'Sunucu hatası. HTTP kodu: ' . $httpCode];
        }

        return ['ok' => true, 'body' => (string)$response, 'error' => ''];
    }

    /**
     * SMSMAX hata kodunu açıklamalı mesaja çevirir.
     */
    private static function errorMessage(string $code): string
    {
        // Yanıt bazen kod harici metin içerebilir; ilk numerik token'ı yakala
        $key = preg_match('/\b(\d{2})\b/', $code, $m) ? $m[1] : trim($code);

        $errors = [
            '01' => 'Hatalı kullanıcı adı, şifre ya da bayi kodu.',
            '02' => 'Yetersiz kredi veya ödenmemiş fatura borcu.',
            '03' => 'Tanımsız Action parametresi.',
            '05' => 'XML düğümü eksik ya da hatalı.',
            '06' => 'Tanımsız gönderici başlığı (Originator).',
            '07' => 'Mesaj kodu (ID) bulunamadı.',
            '09' => 'Tarih alanları hatalı.',
            '10' => 'SMS gönderilemedi.',
        ];

        return $errors[$key] ?? ('SMS gönderilemedi. API yanıtı: ' . $code);
    }

    /**
     * Numarayı API'nin beklediği 5XXXXXXXXX biçimine normalize eder.
     * Türkiye numaralarındaki +90 / 90 / baştaki 0 önekleri temizlenir.
     */
    private static function normalizeNumber(string $number): string
    {
        $digits = preg_replace('/\D+/', '', $number);
        if ($digits === '') {
            return '';
        }
        // Ülke kodu 90 ile başlıyorsa kaldır
        if (strlen($digits) === 12 && strpos($digits, '90') === 0) {
            $digits = substr($digits, 2);
        }
        // Baştaki 0'ı kaldır (0532... -> 532...)
        if (strlen($digits) === 11 && $digits[0] === '0') {
            $digits = substr($digits, 1);
        }
        // Geçerli mobil numara 10 hanedir ve 5 ile başlar
        return (strlen($digits) === 10 && $digits[0] === '5') ? $digits : '';
    }

    /** XML için güvenli kaçış */
    private static function esc(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
