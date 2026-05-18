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
    public static function gonder($toEmail, $subject, $body, $toName = '') {
        $autoloadPath = __DIR__ . '/../../vendor/autoload.php';
        if (file_exists($autoloadPath)) {
            require_once $autoloadPath;
        }

        $mail = new PHPMailer(true);

        try {
            // SMTP Sunucu Ayarları
            $mail->isSMTP();
            $mail->Host       = getenv('SMTP_HOST') ?: 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = getenv('SMTP_USER') ?: 'beyzade83@gmail.com';             // Gmail adresiniz
            $mail->Password   = getenv('SMTP_PASS') ?: 'ehdcrmwlrpgyzjay';                // Google Uygulama Şifreniz
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;    // TLS şifreleme
            $mail->Port       = (int)(getenv('SMTP_PORT') ?: 587);                        // SMTP portu
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
            $mail->setFrom(
                getenv('SMTP_FROM_EMAIL') ?: 'beyzade83@gmail.com', 
                getenv('SMTP_FROM_NAME') ?: 'Kadro Bildirim Sistemi'
            );
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
