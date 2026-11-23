<?php
$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$kurumAdi = $settings['kurum_adi'] ?? '';
$birimAdi = $settings['birim_adi'] ?? '';
$tarih = $paket['gonderim_tarihi'] ? date('d.m.Y', strtotime($paket['gonderim_tarihi'])) : date('d.m.Y');
$deger = function (array $alan, $v, int $formId) use ($h, $dosyalar) {
    if ($alan['tip'] === 'file') {
        return isset($dosyalar[$formId][$alan['anahtar']]) ? 'Dijital olarak teslim edildi' : 'Teslim edilmedi';
    }
    $v = (string)$v;
    if ($v === '') {
        return '&nbsp;';
    }
    if ($alan['tip'] === 'date' && preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $v, $m)) {
        return $h("{$m[3]}.{$m[2]}.{$m[1]}");
    }
    if ($alan['tip'] === 'iban') {
        return $h(trim(chunk_split($v, 4, ' ')));
    }
    return nl2br($h($v));
};
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="robots" content="noindex, nofollow">
<title><?php echo $h($paket['ad_soyad']); ?> - İşe Giriş Evrakları</title>
<style>
    @page { size: A4; margin: 16mm 16mm 18mm; }
    @page land { size: A4 landscape; margin: 8mm 12mm; }
    .ct-land { page: land; width: 297mm !important; min-height: 210mm !important; }
    * { box-sizing: border-box; }
    body { margin: 0; background: #e4e4e7; font-family: "Times New Roman", Times, serif; font-size: 12pt; color: #000; }
    .toolbar { position: sticky; top: 0; z-index: 5; display: flex; justify-content: center; gap: 10px; padding: 12px; background: #18181b; }
    .toolbar button { font: 600 14px system-ui, sans-serif; padding: 8px 18px; border-radius: 8px; border: 0; cursor: pointer; }
    .toolbar .primary { background: #fff; color: #18181b; }
    .toolbar .ghost { background: transparent; color: #fff; border: 1px solid #52525b; }
    .page { width: 210mm; min-height: 297mm; margin: 16px auto; padding: 16mm; background: #fff; box-shadow: 0 2px 12px rgba(0,0,0,.15); display: flex; flex-direction: column; }
    .head { text-align: center; border-bottom: 1.5px solid #000; padding-bottom: 8px; margin-bottom: 14px; }
    .head .kurum { font-weight: bold; font-size: 13pt; text-transform: uppercase; }
    .head .birim { font-size: 11pt; }
    .head h1 { font-size: 14pt; margin: 10px 0 0; text-transform: uppercase; }
    .kimlik { width: 100%; border-collapse: collapse; margin-bottom: 12px; font-size: 11pt; }
    .kimlik td { border: 1px solid #000; padding: 4px 8px; }
    .kimlik td.l { width: 22%; font-weight: bold; background: #f4f4f5; }
    table.alanlar { width: 100%; border-collapse: collapse; font-size: 11pt; }
    table.alanlar td { border: 1px solid #000; padding: 5px 8px; vertical-align: top; }
    table.alanlar td.l { width: 38%; font-weight: bold; }
    .metin { font-size: 11pt; text-align: justify; line-height: 1.45; margin-bottom: 12px; }
    .metin p { margin: 0 0 8px; text-indent: 1.25cm; }
    .onay { display: flex; gap: 8px; align-items: flex-start; margin: 6px 0; font-size: 11pt; }
    .kutu { width: 14px; height: 14px; border: 1px solid #000; display: inline-flex; align-items: center; justify-content: center; font-size: 11px; flex-shrink: 0; margin-top: 2px; }
    .imza { margin-top: auto; padding-top: 24px; display: grid; grid-template-columns: 1fr 1fr; gap: 24px; font-size: 11pt; }
    .imza .blok { text-align: center; }
    .imza .baslik { font-weight: bold; margin-bottom: 4px; }
    .imza .satir { margin-top: 42px; border-top: 1px dotted #000; padding-top: 4px; }
    .ct { font-size: 12pt; }
    .ct table { width: 100%; border-collapse: collapse; }
    .ct-head td, .ct-meta td { border: 1px solid #000; padding: 6px 10px; }
    .ct-logo { width: 32%; font-weight: bold; font-size: 10pt; text-align: center; text-transform: uppercase; }
    .ct-title { text-align: center; font-weight: bold; font-size: 15pt; padding: 18px 10px !important; }
    .ct-meta td { font-size: 9.5pt; font-weight: bold; text-align: center; border-top: 0; }
    .ct-lines { margin-top: 26px; padding: 0 12mm; }
    .ct-lines p { margin: 0 0 9px; }
    .ct-h { font-size: 13pt; margin: 18px 12mm 8px; text-transform: uppercase; }
    .ct-list { list-style: none; margin: 0; padding: 0 12mm 0 22mm; }
    .ct-list li { display: flex; gap: 10px; align-items: flex-start; margin-bottom: 5px; font-size: 11pt; }
    .ct .kutu { margin-top: 3px; }
    .ct-text { padding: 0 12mm; text-align: justify; line-height: 1.45; margin: 0 0 14px; font-size: 11.5pt; }
    .ct-sign { margin: 0 12mm; width: calc(100% - 24mm) !important; }
    .ct-sign th, .ct-sign td { border: 1px solid #000; padding: 8px 8px; font-size: 11pt; text-align: center; }
    .ct-sign th:first-child { width: 28%; }
    .ct-sign tr:not(:first-child) { height: 30px; }
    .alt { font-size: 8.5pt; color: #52525b; margin-top: 10px; text-align: right; font-family: system-ui, sans-serif; }
    @media print {
        body { background: #fff; }
        .toolbar { display: none; }
        .page { margin: 0; padding: 0; width: auto; min-height: calc(297mm - 34mm); box-shadow: none; page-break-after: always; }
        .page:last-child { page-break-after: auto; }
        .page.ct-land { width: 273mm !important; min-height: 0 !important; }
    }
</style>
</head>
<body>
<div class="toolbar">
    <button type="button" class="primary" onclick="window.print()">Yazdır</button>
    <button type="button" class="ghost" onclick="window.close()">Kapat</button>
</div>

<?php foreach ($formlar as $form):
    $fid = (int)$form['id'];
    if (($form['tip'] ?? '') === 'cikti') {
        $sablon = (string)($form['cikti_sablon'] ?? '');
        if (isset(EvrakForm::CIKTI_SABLONLARI[$sablon]) && is_file(__DIR__ . '/cikti/' . $sablon . '.php')) {
            include __DIR__ . '/cikti/' . $sablon . '.php';
        }
        continue;
    }
    $y = $yanitlar[$fid] ?? [];
    $onaylar = array_filter($form['alanlar'], fn($a) => $a['tip'] === 'checkbox');
    $digerleri = array_filter($form['alanlar'], fn($a) => $a['tip'] !== 'checkbox');
?>
<section class="page">
    <div class="head">
        <?php if ($kurumAdi): ?><div class="kurum"><?php echo $h($kurumAdi); ?></div><?php endif; ?>
        <?php if ($birimAdi): ?><div class="birim"><?php echo $h($birimAdi); ?></div><?php endif; ?>
        <h1><?php echo $h($form['ad']); ?></h1>
    </div>

    <table class="kimlik">
        <tr><td class="l">Adı Soyadı</td><td><?php echo $h($personel['ad_soyad'] ?? $paket['ad_soyad']); ?></td><td class="l">T.C. Kimlik No</td><td><?php echo $h($personel['tc_kimlik'] ?? $paket['tc_kimlik']); ?></td></tr>
        <tr><td class="l">Görevi</td><td><?php echo $h($personel['unvan'] ?? ''); ?></td><td class="l">Göreve Başlama</td><td><?php echo !empty($personel['goreve_baslama_tarihi']) ? $h(date('d.m.Y', strtotime($personel['goreve_baslama_tarihi']))) : ''; ?></td></tr>
    </table>

    <?php if (!empty($form['metin'])): ?>
    <div class="metin"><?php echo EvrakForm::renderText($form['metin'], $vars); ?></div>
    <?php endif; ?>

    <?php if (!empty($digerleri)): ?>
    <table class="alanlar">
        <?php foreach ($digerleri as $alan): ?>
        <tr><td class="l"><?php echo $h($alan['etiket']); ?></td><td><?php echo $deger($alan, $y[$alan['anahtar']] ?? '', $fid); ?></td></tr>
        <?php endforeach; ?>
    </table>
    <?php endif; ?>

    <?php foreach ($onaylar as $alan): ?>
    <div class="onay"><span class="kutu"><?php echo ($y[$alan['anahtar']] ?? '') === '1' ? '✔' : ''; ?></span><span><?php echo $h($alan['etiket']); ?></span></div>
    <?php endforeach; ?>

    <p style="font-size:11pt;margin-top:14px">Yukarıdaki bilgilerin doğru olduğunu, değişiklik olması halinde kuruma bildireceğimi beyan ederim.</p>

    <div class="imza">
        <div class="blok">
            <div class="baslik">Beyan Eden</div>
            <div><?php echo $h($personel['ad_soyad'] ?? $paket['ad_soyad']); ?></div>
            <div>Tarih: <?php echo $h($tarih); ?></div>
            <div class="satir">İmza</div>
        </div>
        <div class="blok">
            <div class="baslik">Teslim Alan</div>
            <div>&nbsp;</div>
            <div>Adı Soyadı / Unvanı</div>
            <div class="satir">İmza</div>
        </div>
    </div>
    <div class="alt">Evrak No: <?php echo (int)$paket['id']; ?>-<?php echo $fid; ?> · Dijital olarak doldurulmuştur.</div>
</section>
<?php endforeach; ?>
</body>
</html>
