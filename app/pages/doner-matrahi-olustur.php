<?php 
$pageTitle = "Döner Matrahı Oluştur"; 
$pageSubtitle = "Ay, yıl ve katsayı bilgilerini seçerek döner matrahı oluşturabilirsiniz.";

if (!function_exists('getVal')) {
    function getVal($key, $settings) {
        return htmlspecialchars($settings[$key] ?? '', ENT_QUOTES, 'UTF-8');
    }
}

// Varsayılan ay ve yıl
$currentMonth = date('n') - 1;
$currentYear = date('Y');

$months = [
    1 => 'Ocak', 2 => 'Şubat', 3 => 'Mart', 4 => 'Nisan', 
    5 => 'Mayıs', 6 => 'Haziran', 7 => 'Temmuz', 8 => 'Ağustos', 
    9 => 'Eylül', 10 => 'Ekim', 11 => 'Kasım', 12 => 'Aralık'
];

$monthOptions = [];
foreach ($months as $num => $name) {
    $monthOptions[] = ['value' => (string)$num, 'label' => $name];
}

$yearOptions = [];
for ($y = $currentYear - 2; $y <= $currentYear + 1; $y++) {
    $yearOptions[] = ['value' => (string)$y, 'label' => (string)$y];
}

$statusOptions = [
    ['value' => 'aktif', 'label' => 'Aktif'],
    ['value' => 'pasif', 'label' => 'Pasif'],
    ['value' => 'dilekce_alindi', 'label' => 'Dilekçe Alındı'],
    ['value' => 'kadroya_gecti', 'label' => 'Kadroya Geçti'],
    ['value' => 'kadroya_gecmeyecek', 'label' => 'Kadroya Geçmeyecek'],
    ['value' => 'tum', 'label' => 'Tümü']
];

$dateOperatorOptions = [
    ['value' => 'tum', 'label' => 'Tümü (Tarih Filtresi Yok)'],
    ['value' => 'gt', 'label' => 'Sonra (>)'],
    ['value' => 'lt', 'label' => 'Önce (<)'],
    ['value' => 'gte', 'label' => 'Sonra veya Eşit (>=)'],
    ['value' => 'lte', 'label' => 'Önce veya Eşit (<=)'],
    ['value' => 'equals', 'label' => 'Eşit (=)'],
    ['value' => 'empty', 'label' => 'Tarih Girilmemiş Olanlar'],
    ['value' => 'not_empty', 'label' => 'Tarih Girilmiş Olanlar']
];
?>

<div class="flex flex-col gap-8 max-w-4xl mx-auto">
  <!-- Header -->
  <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div class="flex flex-col gap-1">
      <h1 class="text-2xl font-bold tracking-tight text-foreground"><?php echo $pageTitle; ?></h1>
      <p class="text-muted-foreground"><?php echo $pageSubtitle; ?></p>
    </div>
    <?php if (($_SESSION['role'] ?? '') === 'superadmin'): ?>
    <div>
      <a href="matrah-yonetimi" class="inline-flex h-10 items-center justify-center rounded-md border border-border bg-background px-4 py-2 text-sm font-medium hover:bg-muted transition-colors">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2"><path d="M12 3v18"/><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M3 9h18"/><path d="M3 15h18"/></svg>
        Matrah Tablosunu Yönet
      </a>
    </div>
    <?php endif; ?>
  </div>

  <!-- Unmatched Warning Banner (Hidden by default) -->
  <div id="unmatched-alert" class="hidden rounded-xl border border-amber-500/30 bg-amber-500/10 p-5 flex flex-col gap-3">
    <div class="flex items-center gap-3 text-amber-600 dark:text-amber-400">
      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" x2="12" y1="9" y2="13"/><line x1="12" x2="12" y1="17" y2="17"/></svg>
      <h3 class="text-base font-semibold leading-none">Eşleşmeyen Unvan / Puan Bilgileri</h3>
    </div>
    <p class="text-sm text-muted-foreground">Matrah tablosunda unvan ve öğrenim düzeyi tam olarak eşleşmediği için bazı personellerin matrah puanları alınamadı. Bu personeller için matrah bilgileri 0 çıkmıştır. Matrah tablosunu düzelterek tekrar deneyebilirsiniz.</p>
    <div id="unmatched-names" class="text-sm border-t border-amber-500/20 pt-2 flex flex-wrap gap-x-2 gap-y-1 font-medium text-foreground"></div>
  </div>

  <form id="doner-matrahi-form" class="flex flex-col gap-8">
    <!-- Dönem Bilgileri Section -->
    <div class="rounded-xl border border-border bg-card shadow-sm relative z-20">
      <div class="p-6 border-b border-border bg-muted/30">
        <div class="flex items-center gap-2">
          <div class="p-2 rounded-lg bg-primary/10 text-primary">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-calendar"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>
          </div>
          <div>
            <h2 class="text-lg font-semibold leading-none tracking-tight">Dönem Bilgileri</h2>
            <p class="text-sm text-muted-foreground mt-1">Döner matrahı hesaplamasının yapılacağı dönemi seçiniz.</p>
          </div>
        </div>
      </div>
      
      <div class="p-6 grid grid-cols-12 gap-6">
        <!-- Ay Seçimi -->
        <div class="space-y-2 col-span-12 md:col-span-6">
          <label for="donem_ay" class="text-sm font-medium leading-none mb-1 block">Ay</label>
          <?php echo renderCustomSelect('donem_ay', 'donem_ay', $monthOptions, $currentMonth, 'w-full h-10'); ?>
        </div>

        <!-- Yıl Seçimi -->
        <div class="space-y-2 col-span-12 md:col-span-6">
          <label for="donem_yil" class="text-sm font-medium leading-none mb-1 block">Yıl</label>
          <?php echo renderCustomSelect('donem_yil', 'donem_yil', $yearOptions, $currentYear, 'w-full h-10'); ?>
        </div>

        <!-- Personel Durumu -->
        <div class="space-y-2 col-span-12 md:col-span-4">
          <label for="personel_durum" class="text-sm font-medium leading-none mb-1 block">Personel Durumu</label>
          <?php echo renderCustomMultiSelect('personel_durum', 'personel_durum', $statusOptions, ['aktif'], 'w-full h-10'); ?>
        </div>

        <!-- Ayrılış Koşulu -->
        <div class="space-y-2 col-span-12 md:col-span-4">
          <label for="ayrilma_op" class="text-sm font-medium leading-none mb-1 block">Ayrılış Tarihi Koşulu</label>
          <?php echo renderCustomSelect('ayrilma_op', 'ayrilma_op', $dateOperatorOptions, 'tum', 'w-full h-10'); ?>
        </div>

        <!-- Ayrılış Tarihi -->
        <div class="space-y-2 col-span-12 md:col-span-4" id="ayrilma_tarihi_val_container">
          <label for="ayrilma_tarihi" class="text-sm font-medium leading-none mb-1 block">Ayrılış Tarihi</label>
          <input type="text" id="ayrilma_tarihi" name="ayrilma_tarihi" class="datepicker flex h-10 w-full rounded-md border border-border bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring" placeholder="Seçiniz..." />
        </div>
      </div>
    </div>

    <?php 
    $maas_katsayisi = (float)getVal('maas_katsayisi', $settings);
    $yan_odeme_katsayisi = (float)getVal('yan_odeme_katsayisi', $settings);
    if ($maas_katsayisi <= 0 || $yan_odeme_katsayisi <= 0): 
    ?>
    <!-- User's alert -->
    <div class="alert border-amber-50 bg-amber-50 text-amber-900 dark:border-amber-950 dark:bg-amber-950 dark:text-amber-100 flex items-center gap-3 p-4 rounded-xl border">
      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="flex-shrink-0"><circle cx="12" cy="12" r="10"></circle><path d="m9 12 2 2 4-4"></path></svg>
      <div class="flex flex-col">
        <h2 class="font-bold text-base">Tanımlı Katsayı Bulunamadı</h2>
        <section class="text-sm">Matrah oluşturabilmek için öncelikle tanımlamalar sayfasından maaş ve yan ödeme katsayılarını girmeniz gerekmektedir.</section>
      </div>
    </div>
    <?php endif; ?>

    <!-- Katsayı Bilgileri Section -->
    <div class="rounded-xl border border-border bg-card shadow-sm relative z-10">
      <div class="p-6 border-b border-border bg-muted/30">
        <div class="flex items-center gap-2">
          <div class="p-2 rounded-lg bg-primary/10 text-primary">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-percent"><line x1="19" x2="5" y1="5" y2="19"/><circle cx="17" cy="17" r="3"/><circle cx="7" cy="7" r="3"/></svg>
          </div>
          <div>
            <h2 class="text-lg font-semibold leading-none tracking-tight">Katsayı Bilgileri</h2>
            <p class="text-sm text-muted-foreground mt-1">Tanımlamalardan gelen katsayı bilgileri (gerektiğinde buradan da güncelleyebilirsiniz).</p>
          </div>
        </div>
      </div>
      <div class="p-6 grid gap-6 md:grid-cols-2">
        <div class="space-y-2">
          <label for="maas_katsayisi" class="text-sm font-medium leading-none">Maaş Katsayısı</label>
          <div class="relative">
            <input type="number" step="0.000001" id="maas_katsayisi" name="maas_katsayisi" value="<?php echo getVal('maas_katsayisi', $settings); ?>" placeholder="0.907796" class="flex h-10 w-full rounded-md border border-border bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-muted-foreground text-xs uppercase font-bold tracking-wider">
              Ratio
            </div>
          </div>
        </div>

        <div class="space-y-2">
          <label for="yan_odeme_katsayisi" class="text-sm font-medium leading-none">Yan Ödeme Katsayısı</label>
          <div class="relative">
            <input type="number" step="0.000001" id="yan_odeme_katsayisi" name="yan_odeme_katsayisi" value="<?php echo getVal('yan_odeme_katsayisi', $settings); ?>" placeholder="0.287912" class="flex h-10 w-full rounded-md border border-border bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-muted-foreground text-xs uppercase font-bold tracking-wider">
              Ratio
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Submit Button -->
    <div class="flex items-center justify-end gap-4">
      <button type="button" onclick="window.history.back()" class="inline-flex h-10 items-center justify-center rounded-md border border-border bg-background px-6 py-2 text-sm font-medium hover:bg-muted transition-colors">
        İptal
      </button>
      <button type="submit" id="btn-olustur" <?php echo ($maas_katsayisi <= 0 || $yan_odeme_katsayisi <= 0) ? 'disabled style="opacity:0.5; cursor:not-allowed;"' : ''; ?> class="inline-flex h-10 items-center justify-center rounded-md bg-zinc-900 dark:bg-white dark:text-zinc-900 px-8 py-2 text-sm font-medium text-white shadow-sm hover:bg-zinc-800 dark:hover:bg-zinc-100 transition-colors">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" x2="12" y1="15" y2="3"/></svg>
        Döner Matrahı Oluştur
      </button>
    </div>
  </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/exceljs@4.4.0/dist/exceljs.min.js"></script>
<script>
window.toggleCustomMultiSelectOption = function(id, el) {
    if (window.event) window.event.stopPropagation();
    const $el = $(el);
    const value = $el.data('value');
    const $component = $('#' + id);
    
    const isTum = (value === 'tum');
    
    if (isTum) {
        // If clicking "Tümü", deselect all others and only keep "Tümü"
        $component.find('[role="option"]').removeClass('selected bg-zinc-50 dark:bg-zinc-800 text-zinc-900 dark:text-white font-bold');
        $component.find('.check-icon').addClass('hidden');
        
        $el.addClass('selected bg-zinc-50 dark:bg-zinc-800 text-zinc-900 dark:text-white font-bold');
        $el.find('.check-icon').removeClass('hidden');
    } else {
        // If clicking a specific option, toggle it
        $el.toggleClass('selected bg-zinc-50 dark:bg-zinc-800 text-zinc-900 dark:text-white font-bold');
        $el.find('.check-icon').toggleClass('hidden');
        
        // Deselect "Tümü" if it was selected
        const $tumOpt = $component.find('[role="option"][data-value="tum"]');
        if ($tumOpt.length > 0) {
            $tumOpt.removeClass('selected bg-zinc-50 dark:bg-zinc-800 text-zinc-900 dark:text-white font-bold');
            $tumOpt.find('.check-icon').addClass('hidden');
        }
    }
    
    // Gather all selected options
    const selectedVals = [];
    const selectedLabels = [];
    $component.find('[role="option"].selected').each(function() {
        selectedVals.push($(this).data('value'));
        selectedLabels.push($(this).find('.option-label').text().trim());
    });
    
    // If nothing is selected, default to "Tümü" if it exists, or show "Seçiniz..."
    if (selectedVals.length === 0) {
        const $tumOpt = $component.find('[role="option"][data-value="tum"]');
        if ($tumOpt.length > 0) {
            $tumOpt.addClass('selected bg-zinc-50 dark:bg-zinc-800 text-zinc-900 dark:text-white font-bold');
            $tumOpt.find('.check-icon').removeClass('hidden');
            selectedVals.push('tum');
            selectedLabels.push('Tümü');
        }
    }
    
    const hiddenVal = selectedVals.join(',');
    let labelText = '';
    
    if (selectedVals.includes('tum')) {
        labelText = 'Tümü';
    } else {
        labelText = selectedLabels.join(', ');
        if (labelText.length > 30) {
            labelText = selectedVals.length + ' Durum Seçildi';
        }
    }
    
    $component.find('input[type="hidden"]').val(hiddenVal).trigger('change');
    $component.find('.selected-label').text(labelText);
};

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('doner-matrahi-form');
    const btn = document.getElementById('btn-olustur');

    function toggleAyrilmaDateInput() {
        const op = document.getElementById('ayrilma_op-value').value;
        const container = document.getElementById('ayrilma_tarihi_val_container');
        if (op === 'tum' || op === 'empty' || op === 'not_empty') {
            container.style.opacity = '0.5';
            container.style.pointerEvents = 'none';
            document.getElementById('ayrilma_tarihi').disabled = true;
            document.getElementById('ayrilma_tarihi').value = '';
        } else {
            container.style.opacity = '1';
            container.style.pointerEvents = 'auto';
            document.getElementById('ayrilma_tarihi').disabled = false;
        }
    }

    // Trigger on change
    $('#ayrilma_op-value').on('change', function() {
        toggleAyrilmaDateInput();
    });

    // Initial run
    toggleAyrilmaDateInput();

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = `<svg class="animate-spin mr-2" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg> Oluşturuluyor...`;

        const ay = document.getElementById('donem_ay-value').value;
        const yil = document.getElementById('donem_yil-value').value;
        const maasKatsayisi = document.getElementById('maas_katsayisi').value;
        const yanOdemeKatsayisi = document.getElementById('yan_odeme_katsayisi').value;
        
        const durum = document.getElementById('personel_durum-value').value;
        const ayrilmaOp = document.getElementById('ayrilma_op-value').value;
        const ayrilmaTarihi = document.getElementById('ayrilma_tarihi').value;

        fetch(`doner-matrahi-indir?donem_ay=${ay}&donem_yil=${yil}&maas_katsayisi=${maasKatsayisi}&yan_odeme_katsayisi=${yanOdemeKatsayisi}&personel_durum=${durum}&ayrilma_op=${ayrilmaOp}&ayrilma_tarihi=${ayrilmaTarihi}`)
        .then(res => res.json())
        .then(async response => {
            if (response.success) {
                const workbook = new ExcelJS.Workbook();
                const worksheet = workbook.addWorksheet('Doner Matrahi');

                const headers = [
                    'SIRA NO', 'TC KİMLİK NO', 'AYLIK', 'EK GÖST.', 'İNİ.ÖDE.', 'EĞ.ÖR.ÖDE.', 
                    'İDA.GÖR.ÖDE.', 'YAN ÖDE', 'ÖZ.HİZ.TAZ.', 'EK ÖZ.HİZM.', 'MAL.KESİ.', 
                    'Dİ.KES.', 'DERECE', 'SGGVM', 'DENGE', 'EK ÖD.MATR.', 'TEO', 
                    'GV.İSTİSNA', 'ADI SOYADI', 'HİZMET YILI'
                ];

                // Add header row
                const headerRow = worksheet.addRow(headers);
                headerRow.font = { bold: true, name: 'Arial', size: 10 };
                headerRow.eachCell(cell => {
                    cell.fill = {
                        type: 'pattern',
                        pattern: 'solid',
                        fgColor: { argb: 'FFF3F4F6' } // Light gray background
                    };
                    cell.alignment = { vertical: 'middle', horizontal: 'center' };
                    cell.border = {
                        top: { style: 'thin', color: { argb: 'FF000000' } },
                        bottom: { style: 'thin', color: { argb: 'FF000000' } },
                        left: { style: 'thin', color: { argb: 'FF000000' } },
                        right: { style: 'thin', color: { argb: 'FF000000' } }
                    };
                });

                response.data.forEach(item => {
                    const rIdx = worksheet.rowCount + 1;

                    // Add row data
                    const row = worksheet.addRow([
                        item.sira,
                        item.tc.toString(),
                        item.aylik,
                        item.ek_gost,
                        0, 0, 0,
                        item.yan_ode,
                        item.oz_hiz,
                        0, 0, 0,
                        item.derece || "",
                        0, 0,
                        { formula: `C${rIdx}+D${rIdx}+E${rIdx}+F${rIdx}+G${rIdx}+H${rIdx}+I${rIdx}+J${rIdx}`, result: item.toplam },
                        0, 0,
                        item.ad_soyad,
                        item.kidem
                    ]);

                    // Formatting each data cell
                    row.eachCell((cell, colIdx) => {
                        cell.font = { name: 'Arial', size: 10 };
                        cell.border = {
                            top: { style: 'thin', color: { argb: 'FF000000' } },
                            bottom: { style: 'thin', color: { argb: 'FF000000' } },
                            left: { style: 'thin', color: { argb: 'FF000000' } },
                            right: { style: 'thin', color: { argb: 'FF000000' } }
                        };

                        // Force string text for TC and Degree specifically
                        if (colIdx === 2 || colIdx === 13) {
                            cell.numFmt = '@';
                        } else if ([3,4,5,6,7,8,9,10,11,12,14,15,16,17,18].includes(colIdx)) {
                            // Professional number format
                            cell.numFmt = '#,##0.00';
                            cell.alignment = { horizontal: 'right' };
                        }
                    });
                });

                // Auto-fit column widths
                worksheet.columns.forEach(column => {
                    let maxLen = 0;
                    column.eachCell({ includeEmpty: true }, cell => {
                        let v = cell.value;
                        if (v && typeof v === 'object' && v.formula) v = v.result;
                        if (v !== undefined && v !== null) {
                            let s = v.toString();
                            if (s.length > maxLen) maxLen = s.length;
                        }
                    });
                    column.width = maxLen < 12 ? 12 : maxLen + 4;
                });

                // Write and download buffer
                const buffer = await workbook.xlsx.writeBuffer();
                const blob = new Blob([buffer], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
                const link = document.createElement('a');
                link.href = URL.createObjectURL(blob);
                link.download = `Sozlesmeli_Tesvik_Ek_Odeme_Matrahi_${ay}_${yil}.xlsx`;
                link.click();

                // Process unmatched personnel
                const alertBanner = document.getElementById('unmatched-alert');
                const alertNames = document.getElementById('unmatched-names');

                if (response.unmatched && response.unmatched.length > 0) {
                    alertBanner.classList.remove('hidden');
                    alertNames.innerHTML = response.unmatched.map(name => `<span class="bg-amber-500/10 text-amber-800 dark:text-amber-200 px-2.5 py-1 rounded-md border border-amber-500/20">${name}</span>`).join('');
                    
                    if (window.showToast) {
                        window.showToast({
                            category: 'warning',
                            title: 'Eşleşmeyen Personeller Var',
                            description: `${response.unmatched.length} personelin matrahı eşleşmediği için 0 çıktı. Lütfen uyarı panelini inceleyin.`
                        });
                    }
                } else {
                    alertBanner.classList.add('hidden');
                    if (window.showToast) {
                        window.showToast({
                            category: 'success',
                            title: 'Başarılı',
                            description: 'Döner matrahı başarıyla oluşturuldu ve excel dosyası indirildi.'
                        });
                    }
                }
            } else {
                alert('Veriler çekilirken bir hata oluştu.');
            }
        })
        .catch(err => {
            console.error(err);
            alert('İndirme sırasında bir hata oluştu.');
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = originalText;
        });
    });
});
</script>
