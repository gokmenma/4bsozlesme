<?php
$konular = [
    'Yapacağı işe ilişkin iş sağlığı ve güvenliği kuralları',
    'Çalışma ortamından kaynaklanan tehlike ve riskler',
    'Risklere karşı alınması gereken önlemler',
    'İşyerinde uyulması gereken kurallar',
    'Kullanılacak iş ekipmanlarının güvenli kullanımı',
    'Kişisel koruyucu donanım kullanımı',
    'Acil durum, yangın ve tahliye kuralları',
    'İş kazası ve ramak kala bildirim süreçleri',
    'İşyerine özgü iş sağlığı ve güvenliği tedbirleri',
];
$adSoyad = $personel['ad_soyad'] ?? $paket['ad_soyad'] ?? '';
$tc = $personel['tc_kimlik'] ?? $paket['tc_kimlik'] ?? '';
$gorev = $personel['unvan'] ?? '';
?>
<section class="page ct">
    <table class="ct-head">
        <tr>
            <td class="ct-logo"><?php echo $h($kurumAdi); ?></td>
            <td class="ct-title">İŞE BAŞLAMA EĞİTİM TUTANAĞI</td>
        </tr>
    </table>
    <table class="ct-meta">
        <tr>
            <td>Doküman No: SÇ.FR.12</td>
            <td>Yayın Tarihi: 13.05.2026</td>
            <td>Revizyon Tarihi: ---------------</td>
            <td>Revizyon No:00</td>
            <td>Sayfa 1 / 1</td>
        </tr>
    </table>

    <div class="ct-lines">
        <p>İşyeri Unvanı: Hastane</p>
        <p>İşyeri Adresi: Düzce Üniversitesi Sağlık Uygulama ve Araştırma Merkezi</p>
        <p>Çalışanın Adı Soyadı: <b><?php echo $h($adSoyad); ?></b></p>
        <p>T.C. Kimlik No: <b><?php echo $h($tc); ?></b></p>
        <p>Görevi/Bölümü: <b><?php echo $h($gorev); ?></b></p>
        <p>İşe Başlama Tarihi: ...... / ...... / ........</p>
        <p>Eğitim Tarihi: ...... / ...... / ........</p>
        <p>Eğitim Süresi: ...... Saat (En az 2 saat olarak düzenlenmiştir.)</p>
    </div>

    <h2 class="ct-h">EĞİTİM KONULARI</h2>
    <ul class="ct-list">
        <?php foreach ($konular as $k): ?>
        <li><span class="kutu"></span><span><?php echo $h($k); ?></span></li>
        <?php endforeach; ?>
    </ul>

    <h2 class="ct-h">TUTANAK</h2>
    <p class="ct-text">Yukarıda bilgileri bulunan çalışana, fiilen çalışmaya başlamadan önce işe başlama eğitimi yüz yüze ve uygulamalı olarak verilmiştir. Çalışan, verilen eğitim hakkında bilgilendirildiğini ve iş sağlığı ve güvenliği kurallarına uyacağını kabul etmiştir.</p>

    <table class="ct-sign">
        <tr><th></th><th>Ad Soyad</th><th>Görevi</th><th>İmza</th></tr>
        <tr><th>Eğitimi Veren</th><td></td><td></td><td></td></tr>
        <tr><th>Çalışan</th><td><?php echo $h($adSoyad); ?></td><td><?php echo $h($gorev); ?></td><td></td></tr>
        <tr><th>İşveren / İşveren Vekili</th><td></td><td></td><td></td></tr>
    </table>
</section>
