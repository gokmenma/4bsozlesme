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
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'beyzade83@gmail.com';             // Gmail adresiniz
            $mail->Password   = 'ehdcrmwlrpgyzjay';                // Google Uygulama Şifreniz
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;    // TLS şifreleme
            $mail->Port       = 587;                               // SMTP portu
            $mail->CharSet    = 'UTF-8';                           // Türkçe karakter desteği

            // Alıcılar
            $mail->setFrom('beyzade83@gmail.com', 'Kadro Bildirim Sistemi');
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
