<?php
$adSoyad = $personel['ad_soyad'] ?? $paket['ad_soyad'] ?? '';
$tc = $personel['tc_kimlik'] ?? $paket['tc_kimlik'] ?? '';
$gorev = $personel['unvan'] ?? '';
$maddeler = [
    '1-Bu bildirim, işverenlerce yukarıdaki muhteviyatına uygun olarak çoğaltılıp kullanılabilecektir.',
    '2-Bildirim, ilk işe girişte verilir. Çalışanın asgari geçim indiriminden yararlanan eş veya çocuk durumunda bir değişiklik meydana gelmesi halinde bildirim yeniden verilir.',
    '3-Nafakasını sağladıkları çocuklara ait asgari geçim indiriminden yararlanacak olan eş tarafından, mahkeme ilamının onaylı bir örneği bildirime eklenir.',
    '4-Çocuklar, sosyal güvenlik yönünden tabi oldukları bildirimine dâhil edilir.',
    '5-İndirimin uygulamasında “çocuk” tabiri, mükellefle birlikte oturan veya mükellef tarafından bakılan (nafaka verilenler, evlat edinilenler ile ana veya babasını kaybetmiş torunlardan mükellefle birlikte oturanlar dâhil) 18 yaşını veya tahsilde olup 25 yaşını doldurmamış çocukları, “eş” tabiri ise, aralarında yasal evlilik bağı bulunan kişileri ifade eder.',
    '6-Sadece ücret geliri elde eden eş, “çalışmayan ve herhangi bir geliri olmayan eş” kapsamında değerlendirilmeyecektir. Bunlar dışındakiler ise çalışmayan ve herhangi bir geliri olmayan eş kapsamında değerlendirilecek olup, münhasıran emekli maaşı alanlar da çalışmayan ve herhangi bir geliri olmayan eş olarak kabul edilecektir.',
];
?>
<section class="page ct ad ct-land">
    <style>
        .ad { font-family: "Times New Roman", Times, serif; }
        .ad table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .ad td, .ad th { border: 1px solid #000; padding: 3px 6px; font-size: 9.5pt; text-align: center; vertical-align: middle; font-weight: normal; }
        .ad .t { text-align: center; font-weight: bold; font-size: 13pt; margin: 0 0 4px; }
        .ad .ek { font-weight: bold; font-size: 12pt; margin-bottom: 2px; }
        .ad .b { font-weight: bold; }
        .ad .lft { text-align: left; }
        .ad .opt { display: inline-flex; align-items: center; gap: 8px; }
        .ad .rows td { height: 5mm; padding: 0 4px; }
        .ad .not { font-size: 7.8pt; line-height: 1.25; margin: 4px 0 0; text-align: justify; }
        .ad .duz { text-align: center; font-size: 9.5pt; margin: 4px 0; line-height: 1.25; }
    </style>
    <div class="ek">EK: 1</div>
    <div class="t">AİLE DURUMU BİLDİRİMİ</div>
    <table>
        <colgroup><col style="width:9%"><col style="width:16%"><col style="width:35%"><col style="width:25%"><col style="width:15%"></colgroup>
        <tr>
            <td rowspan="3" class="lft">Bildirimi<br>Verenin</td>
            <td class="lft">T.C./Vergi Kimlik No</td>
            <td class="lft"><b><?php echo $h($tc); ?></b></td>
            <td class="lft">Sosyal Güvenlik No/Sicil No/Kurum Sicil No</td>
            <td>&nbsp;</td>
        </tr>
        <tr><td class="lft">Adı Soyadı</td><td class="lft"><b><?php echo $h($adSoyad); ?></b></td><td class="lft">Görevi</td><td class="lft"><b><?php echo $h($gorev); ?></b></td></tr>
        <tr><td class="lft">Medeni Hali</td><td colspan="3"><div style="display:flex;justify-content:space-around"><span class="opt">Bekar <span class="kutu"></span></span><span class="opt">Evli <span class="kutu"></span></span><span class="opt">Diğer <span class="kutu"></span></span></div></td></tr>
    </table>
    <table>
        <tr><td class="b" colspan="3">EŞİN</td></tr>
        <tr><td class="b" style="width:25%">Adı Soyadı</td><td class="b" style="width:35%">İş Durumu</td><td class="b">Gelirine/Gelirlerine İlişkin Açıklama</td></tr>
        <tr>
            <td rowspan="2" style="height:12mm">&nbsp;</td>
            <td style="padding:0"><table style="border:0"><tr><td style="border-top:0;border-left:0">Çalışıyor</td><td style="border-top:0">Çalışmıyor</td><td style="border-top:0">Geliri Olan</td><td style="border-top:0;border-right:0">Geliri Olmayan</td></tr><tr><td style="border-left:0;border-bottom:0"><span class="kutu"></span></td><td style="border-bottom:0"><span class="kutu"></span></td><td style="border-bottom:0"><span class="kutu"></span></td><td style="border-right:0;border-bottom:0"><span class="kutu"></span></td></tr></table></td>
            <td rowspan="2">&nbsp;</td>
        </tr>
        <tr><td style="display:none"></td></tr>
    </table>
    <table>
        <tr><td class="b" colspan="11">MÜKELLEFLE OTURAN VEYA MÜKELLEF TARAFINDAN BAKILAN ÇOCUKLARIN DURUMU</td></tr>
        <tr>
            <td rowspan="2" class="b" style="width:13%">Adı Soyadı</td>
            <td rowspan="2" class="b" style="width:10%">T.C. Kimlik No</td>
            <td rowspan="2" class="b" style="width:9%">Doğum Tarihi (Varsa ay ve günü de yazılacaktır)</td>
            <td rowspan="2" class="b" style="width:4%">Cinsiyet</td>
            <td rowspan="2" class="b" style="width:7%">Baba Adı</td>
            <td rowspan="2" class="b" style="width:7%">Ana Adı</td>
            <td rowspan="2" class="b" style="width:16%">Öz, Üvey, Evlat Edinilmiş, Nafakası Sağlanılan Çocuk, Ana Babasını Kaybetmiş Torun</td>
            <td colspan="3" class="b" style="width:22%">Öğretime Devam Ediyorsa</td>
            <td rowspan="2" class="b" style="width:12%">Açıklama</td>
        </tr>
        <tr><td class="b">Kayıt Tarihi</td><td class="b">Okul Adı</td><td class="b">Sınıfı</td></tr>
        <?php for ($i = 0; $i < 6; $i++): ?>
        <tr class="rows"><?php for ($j = 0; $j < 11; $j++): ?><td>&nbsp;</td><?php endfor; ?></tr>
        <?php endfor; ?>
    </table>
    <p class="not">Alt Satırdaki hususları da göz önüne almak suretiyle düzenlediğim asgari geçim indirimine ait bildirimdir.</p>
    <div class="duz"><b>Düzenleyenin</b><br><b>Adı Soyadı</b><br><b>İmzası/Tarih</b> &nbsp;&nbsp;&nbsp;.../.../......</div>
    <?php foreach ($maddeler as $m): ?><p class="not" style="margin:1px 0"><?php echo $h($m); ?></p><?php endforeach; ?>
</section>
