<?php
$adSoyad = $personel['ad_soyad'] ?? $paket['ad_soyad'] ?? '';
$tc = $personel['tc_kimlik'] ?? $paket['tc_kimlik'] ?? '';
$gorev = $personel['unvan'] ?? '';
$secili = 'İdari/Sözleşmeli Personel';
$turler = ['Akademik Personel', 'İdari/Sözleşmeli Personel', 'Sürekli İşçi', 'Yüklenici firma Personeli', 'Misafir Öğretir Elemanı', 'Emekli Akademik Personel', 'Emekli İdari Personel'];
?>
<section class="page ct kk">
    <style>
        .kk { font-family: "Times New Roman", Times, serif; }
        .kk table { width: 100%; border-collapse: collapse; }
        .kk td { border: 1px solid #000; padding: 6px 10px; font-size: 11pt; vertical-align: middle; }
        .kk .l { width: 38%; text-transform: uppercase; }
        .kk .ek { text-align: right; font-size: 11pt; }
        .kk .bas { text-align: center; font-size: 13pt; margin: 6px 0 18px; text-transform: uppercase; line-height: 1.5; }
        .kk .alt-b { font-size: 12pt; font-weight: bold; margin: 18px 0 6px; text-transform: uppercase; }
        .kk .opt { display: flex; align-items: center; gap: 8px; margin: 2px 0; }
        .kk .grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: 10px 20px; }
    </style>
    <div class="ek">EK-2</div>
    <div class="bas"><b>DÜZCE ÜNİVERSİTESİ</b><br>PERSONEL KİMLİK KARTI TALEP FORMU</div>

    <table>
        <tr>
            <td class="l">Talep Edilen Kimlik Kartı Türü</td>
            <td><?php foreach ($turler as $t): ?><div class="opt"><span class="kutu"><?php echo $t === $secili ? '✔' : ''; ?></span><?php echo $h($t); ?></div><?php endforeach; ?></td>
        </tr>
    </table>

    <div class="alt-b">Personelin</div>
    <table>
        <tr><td class="l">T.C. Kimlik Nosu</td><td><b><?php echo $h($tc); ?></b></td></tr>
        <tr><td class="l">Adı Soyadı</td><td><b><?php echo $h($adSoyad); ?></b></td></tr>
        <tr><td class="l">Unvanı</td><td><b><?php echo $h($gorev); ?></b></td></tr>
        <tr><td class="l">Kurum Sicil No</td><td>&nbsp;</td></tr>
        <tr><td class="l">Kadro Birimi</td><td>Düzce Üniversitesi</td></tr>
        <tr><td class="l">Görev Yaptığı Birim</td><td>Sağlık Uygulama ve Araştırma Merkezi</td></tr>
    </table>

    <div class="alt-b">Kimlik Kartı Talebine İlişkin</div>
    <table>
        <tr>
            <td class="l">Talep Nedeni</td>
            <td><div class="grid2">
                <span class="opt"><span class="kutu">✔</span>Yeni</span><span class="opt"><span class="kutu"></span>Kayıp/Çalıntı</span>
                <span class="opt"><span class="kutu"></span>Hasarlı/Yıpranmış</span><span class="opt"><span class="kutu"></span>Unvan Değişikliği</span>
            </div></td>
        </tr>
        <tr><td class="l">Unvan Değişikliği İse Önceki Unvanı</td><td style="height:34px">&nbsp;</td></tr>
        <tr><td class="l">Kayıp/Çalıntı/Gasp İse Yer ve Tarih</td><td style="height:44px;text-align:right;vertical-align:bottom">Tarih: ..../..../.......</td></tr>
    </table>

    <div class="alt-b">Kimlik Kartı İçin Yatırılan Bedele İlişkin</div>
    <table>
        <tr><td class="l">Banka Dekont Nosu ve Tarihi</td><td style="height:50px"><div style="display:flex;justify-content:space-between">No:......................... <span>Tarih: ..../..../.........</span></div></td></tr>
    </table>

    <p style="text-align:center;margin:16px 0 0;font-size:11pt">Yukarıda yazılı olan bilgilere göre yeni kimlik kartımın basılarak tarafıma verilmesini arz ederim.<br><b style="font-size:10pt">( Halk Bankası Düzce Şubesi IBAN Numarası:TR020001200932700006000020 )</b></p>
    <div style="margin:22px 0 0 auto;width:200px;text-align:center;font-size:10.5pt;font-weight:bold">Tarih<br><?php echo $h($adSoyad); ?><br>İmza</div>
    <p style="font-size:9.5pt;margin-top:34px">Not: Yeni Kimlik kartı talebinde bulunan personel bu form ve fotoğraf ile birlikte <u>görev yaptığı birime</u> müracaat edecektir.</p>
</section>
