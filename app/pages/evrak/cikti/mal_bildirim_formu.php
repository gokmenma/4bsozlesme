<?php
$gorev = $personel['unvan'] ?? '';
$bolumler = [
    ['BÖLÜM - 1', 'KİMLİK BİLGİLERİ', [['SIRA NO.', 5], ['ADI VE SOYADI', 44], ['DOĞUM TARİHİ', 11], ['DOĞUM YERİ', 10], ['YAKINLIĞI (1)', 11], ['TÜRKİYE CUMHURİYETİ KİMLİK NO.', 19]], 6],
    ['BÖLÜM - 2', 'TAŞINMAZ MAL BİLGİLERİ', [['SIRA NO.', 5], ['DEĞERİ', 14], ['TAŞINMAZIN CİNSİ (2)', 12], ['ADRESİ (mahal, ada, parsel nosu dahil)', 29], ['HİSSE MİKTARI', 11], ['EDİNME TARİHİ', 11], ['MALİKİN TÜRKİYE CUMHURİYETİ KİMLİK NO.', 18]], 8],
    ['BÖLÜM - 3', 'KOOPERATİF BİLGİLERİ', [['SIRA NO.', 5], ['KOOPERATİFİN ADI VE YERİ', 55], ['HİSSE DEĞERİ', 11], ['ÜYELİK TARİHİ', 11], ['HİSSEDARIN TÜRKİYE CUMHURİYETİ KİMLİK NO.', 18]], 4],
];
$satir = function (array $kolonlar, int $adet) {
    foreach ($kolonlar as $k) { echo '<col style="width:' . (int)$k[1] . '%">'; }
};
?>
<section class="page ct mb">
    <style>
        .mb table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .mb td, .mb th { border: 1px solid #000; font-size: 7pt; padding: 1px 3px; text-align: center; vertical-align: middle; font-weight: bold; }
        .mb .bol { text-align: left; font-size: 8pt; background: #e5e5e5; padding: 2px 4px; }
        .mb .bol b { margin-right: 28mm; }
        .mb .v td { height: 4.7mm; font-weight: normal; }
        .mb .info td { font-size: 8.5pt; text-align: left; padding: 2px 4px; }
        .mb .info td.k { font-weight: bold; width: 14%; }
        .mb .aci { font-size: 6.6pt; text-align: justify; line-height: 1.25; border: 1px solid #000; padding: 3px 5px; margin: 0; }
        .mb .ct-logo { font-size: 8pt; }
        .mb .ct-title { font-size: 14pt; padding: 12px 8px !important; }
        .mb .ct-meta td { font-size: 8pt; }
    </style>
    <table class="ct-head">
        <tr><td class="ct-logo"><?php echo $h($kurumAdi); ?></td><td class="ct-title">MAL BİLDİRİM FORMU</td></tr>
    </table>
    <table class="ct-meta">
        <tr><td>Doküman No: KKU.FR.18</td><td>Yayın Tarihi: 14.01.2025</td><td>Revizyon Tarihi: ---------------</td><td>Revizyon No:00</td><td>Sayfa No:1/1</td></tr>
    </table>
    <div style="height:6px"></div>
    <table class="info">
        <colgroup><col style="width:22%"><col style="width:14%"><col style="width:14%"><col style="width:50%"></colgroup>
        <tr><td class="b" style="font-size:12pt;font-weight:bold;text-align:left;vertical-align:middle" rowspan="3" colspan="1">MAL BİLDİRİMİ</td><td colspan="1" class="k"></td><td class="k">KURUMU</td><td>&nbsp;</td></tr>
        <tr><td colspan="1" class="k"></td><td class="k">GÖREVİ</td><td><b><?php echo $h($gorev); ?></b></td></tr>
        <tr><td colspan="1" class="k"></td><td class="k">SİCİL NO.</td><td>&nbsp;</td></tr>
    </table>
    <p class="aci">3628 sayılı Kanunun 2 nci ve Mal Bildiriminde Bulunulması Hakkında Yönetmeliğin 8 nci maddesine göre mal bildiriminde bulunacak olanlar kendileri ile eşleri ve velayetleri altındaki çocuklarının taşınır ve taşınmaz mallan ile arsa ve yapı kooperatifi gibi kooperatiflerde bulunan hisselerinin değerleri ne olursa olsun forman 2 nci ve 3 ncü bölümlerine kaydetmek zorundadırlar. Formun 4 - 8 nci bölümlerine kaydedilmesi gereken her türlü kara, deniz ve hava taşıt araçları, traktör, biçer döver, harman makinası ve diğer zirat makinaları, inşaat ve iş makinaları, hayvanlar, kolleksiyon ve antika ev eşyaları ile haklan, alacaklar, borçlar ve gelirlerden, kendilerine ödeme yapılanlara aylık net ödemenin, ödeme yapılmayanlara ise GİH sınıfındaki 1 . derece Şube Müdürüne yapılan aylık net ödemenin, beş katından fazla tutardaki kısmı beyan edilir.</p>

    <?php foreach ($bolumler as $b): ?>
    <table>
        <?php $satir($b[2], 0); ?>
        <tr><td class="bol" colspan="<?php echo count($b[2]); ?>"><b><?php echo $h($b[0]); ?></b> <b><?php echo $h($b[1]); ?></b></td></tr>
        <tr><?php foreach ($b[2] as $k): ?><td><?php echo $h($k[0]); ?></td><?php endforeach; ?></tr>
        <?php for ($i = 0; $i < $b[3]; $i++): ?><tr class="v"><?php foreach ($b[2] as $k): ?><td>&nbsp;</td><?php endforeach; ?></tr><?php endfor; ?>
    </table>
    <?php endforeach; ?>

    <?php
    $tasit = [['SIRA NO.', 5], ['PLAKA NO.', 14], ['TAŞITIN CİNSİ (3)', 10], ['TAŞITIN MARKASI', 12], ['EDİNME DEĞERİ', 19], ['MODEL YILI', 8], ['EDİNME TARİHİ', 11], ['SAHİBİNİN TÜRKİYE CUMHURİYETİ KİMLİK NO.', 21]];
    $diger = [['SIRA NO.', 5], ['TAŞINIR MALIN CİNSİ (4)', 36], ['EDİNME DEĞERİ', 19], ['MODEL YILI', 8], ['EDİNME TARİHİ', 11], ['SAHİBİNİN TÜRKİYE CUMHURİYETİ KİMLİK NO.', 21]];
    ?>
    <table>
        <?php $satir($tasit, 0); ?>
        <tr><td class="bol" colspan="8"><b>BÖLÜM - 4</b> <b>TAŞINIR MAL BİLGİLERİ</b></td></tr>
        <tr><td class="bol" colspan="8" style="background:#fff"><b>A -TAŞIT BİLGİLERİ</b></td></tr>
        <tr><?php foreach ($tasit as $k): ?><td><?php echo $h($k[0]); ?></td><?php endforeach; ?></tr>
        <?php for ($i = 0; $i < 3; $i++): ?><tr class="v"><?php foreach ($tasit as $k): ?><td>&nbsp;</td><?php endforeach; ?></tr><?php endfor; ?>
    </table>
    <table>
        <?php $satir($diger, 0); ?>
        <tr><td class="bol" colspan="6" style="background:#fff"><b>B - DİĞER TAŞINIR MALLAR</b></td></tr>
        <tr><?php foreach ($diger as $k): ?><td><?php echo $h($k[0]); ?></td><?php endforeach; ?></tr>
        <?php for ($i = 0; $i < 7; $i++): ?><tr class="v"><?php foreach ($diger as $k): ?><td>&nbsp;</td><?php endforeach; ?></tr><?php endfor; ?>
    </table>
</section>
