<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class MailGonderService {
    /**
     * PHPMailer ile e-posta gönderir.
     * 
     * @param string $toEmail
     * @param string $subject
     * @param string $body
     * @param string $toName
     * @return bool
     */
    public static function gonder($toEmail, $subject, $body, $toName = '', $tenant_id = null) {
        $autoloadPath = __DIR__ . '/../../vendor/autoload.php';
        if (file_exists($autoloadPath)) {
            require_once $autoloadPath;
        }

        // Varsayılan genel SMTP ayarları (.env dosyasından)
        $host = getenv('SMTP_HOST') ?: 'smtp.gmail.com';
        $port = (int)(getenv('SMTP_PORT') ?: 587);
        $user = getenv('SMTP_USER') ?: 'beyzade83@gmail.com';
        $pass = getenv('SMTP_PASS') ?: 'ehdcrmwlrpgyzjay';
        $fromEmail = getenv('SMTP_FROM_EMAIL') ?: 'beyzade83@gmail.com';
        $fromName = getenv('SMTP_FROM_NAME') ?: 'Kadro Bildirim Sistemi';

        // Eğer tenant_id verilmişse ve kuruma özel SMTP ayarları varsa onları kullanalım
        if ($tenant_id) {
            global $db;
            try {
                $stmt = $db->prepare("SELECT smtp_host, smtp_port, smtp_user, smtp_pass, smtp_from_email, smtp_from_name FROM tenant_settings WHERE tenant_id = ?");
                $stmt->execute([$tenant_id]);
                $settings = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($settings && !empty($settings['smtp_host'])) {
                    $host = $settings['smtp_host'];
                    $port = (int)($settings['smtp_port'] ?: 587);
                    $user = $settings['smtp_user'];
                    $pass = $settings['smtp_pass'];
                    $fromEmail = $settings['smtp_from_email'] ?: $user;
                    $fromName = $settings['smtp_from_name'] ?: 'Kadro Bildirim Sistemi';
                }
            } catch (\Exception $ex) {
                error_log("Tenant SMTP ayarları alınırken hata: " . $ex->getMessage());
            }
        }

        $mail = new PHPMailer(true);

        try {
            // SMTP Sunucu Ayarları
            $mail->isSMTP();
            $mail->Host       = $host;
            $mail->SMTPAuth   = true;
            $mail->Username   = $user;             // Kullanıcı adı
            $mail->Password   = $pass;             // Şifre
            $mail->Timeout    = 10;                // Zaman aşımı süresi
            
            if ($port === 465) {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;    // SSL şifreleme
            } else {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // TLS şifreleme
            }
            
            $mail->Port       = $port;                        // SMTP portu
            $mail->CharSet    = 'UTF-8';                           // Türkçe karakter desteği

            // Yerel XAMPP/SSL Hatalarını Önlemek İçin SSL Doğrulamasını Devre Dışı Bırak
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ];

            // Alıcılar
            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($toEmail, $toName);

            // E-posta İçeriği
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body;

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("E-posta gönderilemedi: {$toEmail}. Hata: {$mail->ErrorInfo}");
            return false;
        }
    }
}
