<?php
$adSoyad = $personel['ad_soyad'] ?? $paket['ad_soyad'] ?? '';
$evetHayir = [
    'Tedavi Gerektiren Psikolojik bir hastalık geçirdiniz mi?',
    'Kronik ve tedavi gerektiren bir hastalığınız var mı?',
    'Sürekli kullandığınız bir ilaç veya özel bir gereksinim var mı?',
    'Kendinize zarar verici davranışlarınız oldu mu? (bedeninize, kollarınıza kesiler atmak gibi)',
];
$engel = ['Duyma', 'Görme', 'Fiziksel', 'Zihinsel'];
?>
<section class="page ct psk">
    <style>
        .psk table { width: 100%; border-collapse: collapse; }
        .psk td { border: 1px solid #000; padding: 7px 8px; font-size: 10.5pt; vertical-align: middle; }
        .psk .l { width: 33%; font-weight: bold; text-transform: uppercase; font-size: 10pt; }
        .psk .sec { font-weight: bold; text-transform: uppercase; background: #eee; font-size: 10pt; }
        .psk .opts { display: flex; flex-wrap: wrap; gap: 10px 18px; }
        .psk .opt { display: inline-flex; align-items: center; gap: 6px; white-space: nowrap; }
        .psk .sp { display: flex; justify-content: space-between; align-items: center; gap: 12px; }
        .psk .q { font-weight: bold; }
        .psk .gap { height: 12px; }
    </style>
    <table class="ct-head">
        <tr><td class="ct-logo"><?php echo $h($kurumAdi); ?></td><td class="ct-title">PSİKOSOSYAL RİSK ANALİZ FORMU</td></tr>
    </table>
    <table class="ct-meta">
        <tr><td>Doküman No: KKU.FR.12</td><td>Yayın Tarihi: 07.03.2022</td><td>Revizyon Tarihi: ---------------</td><td>Revizyon No:00</td></tr>
    </table>
    <div class="gap"></div>
    <table>
        <tr><td class="l">Adı Soyadı</td><td><b><?php echo $h($adSoyad); ?></b></td></tr>
        <tr><td class="l">Yaşınız / Cinsiyetiniz</td><td>&nbsp;</td></tr>
        <tr><td class="l">İkametgah Adresiniz</td><td>&nbsp;</td></tr>
        <tr><td class="l">Ev ve Cep Telefonu</td><td>&nbsp;</td></tr>
        <tr><td class="sec" colspan="2">Acil durumda ulaşabilecek kişilerin bilgileri</td></tr>
        <tr><td class="l">Adı Soyadı / Telefon</td><td>&nbsp;</td></tr>
        <tr>
            <td class="l" style="text-align:center">Medeni Durumunuz</td>
            <td><div class="opts">
                <?php foreach (['Bekar', 'Evli', 'Ayrı Yaşıyor', 'Boşanmış', 'Dul', 'Birlikte Yaşıyor'] as $m): ?>
                <span class="opt"><span class="kutu"></span><?php echo $h($m); ?></span>
                <?php endforeach; ?>
            </div></td>
        </tr>
        <?php foreach ($evetHayir as $soru): ?>
        <tr>
            <td class="q"><?php echo $h($soru); ?></td>
            <td><div class="sp"><span class="opt"><span class="kutu"></span>Hayır</span><span class="opt"><span class="kutu"></span>Evet (belirtiniz...................................)</span></div></td>
        </tr>
        <?php endforeach; ?>
        <tr>
            <td class="q">Alkol veya sigara gibi bağımlılık yapan maddeleri bırakmak amacıyla her hangi bir tedavi aldınız mı?</td>
            <td><div class="sp"><span class="opt"><span class="kutu"></span>Hayır</span><span class="opt"><span class="kutu"></span>Evet</span></div></td>
        </tr>
        <?php foreach (['Her hangi bir engeliniz var mı? Varsa engel türünü belirtir misiniz?', 'Hanenizde engelli birey var mı? Varsa engellilik türünü belirtiniz?'] as $soru): ?>
        <tr>
            <td class="q"><?php echo $h($soru); ?></td>
            <td>
                <div class="opts"><span class="opt"><span class="kutu"></span>Yok</span><?php foreach ($engel as $e): ?><span class="opt"><span class="kutu"></span><?php echo $h($e); ?></span><?php endforeach; ?></div>
                <div style="margin-top:8px">Varsa ihtiyaç belirtiniz...............................................................</div>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</section>
