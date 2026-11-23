<?php

class EvrakGonderService {
    public static function teslimEt(array $paket, string $link, string $kanal, string $email, int $tenantId, int $userId): array {
        $settings = (new Definition())->getSettings($tenantId);
        $kurum = $settings['kurum_adi'] ?? 'Kurum';
        $sonGun = date('d.m.Y', strtotime($paket['son_gecerlilik']));

        if ($kanal === 'sms') {
            if (empty($paket['telefon'])) {
                return ['success' => false, 'message' => 'Personelin telefon numarası kayıtlı değil. Bağlantıyı kopyalayıp iletebilirsiniz.'];
            }
            $mesaj = "{$kurum}: Sayın {$paket['ad_soyad']}, işe giriş evraklarınızı doldurmak için bağlantı: {$link} Son gün: {$sonGun}";
            $r = SmsGonderService::gonder($mesaj, $paket['telefon'], true, $userId);
            return $r['success']
                ? ['success' => true, 'message' => 'Bağlantı SMS ile gönderildi.']
                : ['success' => false, 'message' => 'SMS gönderilemedi: ' . $r['message'] . ' Bağlantıyı kopyalayıp iletebilirsiniz.'];
        }

        if ($kanal === 'mail') {
            $ad = htmlspecialchars($paket['ad_soyad'], ENT_QUOTES, 'UTF-8');
            $kurumHtml = htmlspecialchars($kurum, ENT_QUOTES, 'UTF-8');
            $linkHtml = htmlspecialchars($link, ENT_QUOTES, 'UTF-8');
            $body = "<p>Sayın {$ad},</p><p>{$kurumHtml} işe giriş evraklarınızı aşağıdaki bağlantıdan doldurabilirsiniz. Bağlantıyı açtığınızda doğrulama için T.C. Kimlik numaranızın son 4 hanesi istenecektir.</p>"
                . "<p><a href=\"{$linkHtml}\" style=\"display:inline-block;padding:10px 18px;background:#18181b;color:#fff;border-radius:8px;text-decoration:none\">Evrakları Doldur</a></p>"
                . "<p style=\"color:#71717a;font-size:13px\">Bağlantı {$sonGun} tarihine kadar geçerlidir. Bu e-postayı siz talep etmediyseniz dikkate almayınız.</p>";
            try {
                $ok = MailGonderService::gonder($email, $kurum . ' - İşe Giriş Evrakları', $body, $paket['ad_soyad'], $tenantId);
            } catch (Throwable $e) {
                error_log('Evrak mail hatası: ' . $e->getMessage());
                $ok = false;
            }
            return $ok
                ? ['success' => true, 'message' => 'Bağlantı e-posta ile gönderildi.']
                : ['success' => false, 'message' => 'E-posta gönderilemedi. Bağlantıyı kopyalayıp iletebilirsiniz.'];
        }

        return ['success' => true, 'message' => 'Bağlantı oluşturuldu. Kopyalayıp personele iletebilirsiniz.'];
    }
}
