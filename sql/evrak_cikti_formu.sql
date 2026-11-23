ALTER TABLE `evrak_formlari`
  ADD COLUMN IF NOT EXISTS `cikti_sablon` VARCHAR(64) NULL AFTER `tip`;

INSERT INTO `evrak_formlari` (`tenant_id`, `ad`, `tip`, `cikti_sablon`, `aciklama`, `metin`, `sira`, `aktif`)
SELECT NULL, 'İşe Başlama Eğitim Tutanağı', 'cikti', 'ise_baslama_egitim_tutanagi', 'Basılı çıktı (SÇ.FR.12) - personel bilgileri hazır gelir, ıslak imzayla tamamlanır.', NULL, 20, 1
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM `evrak_formlari` WHERE `tenant_id` IS NULL AND `cikti_sablon` = 'ise_baslama_egitim_tutanagi' AND `deleted_at` IS NULL
);

INSERT INTO `evrak_formlari` (`tenant_id`, `ad`, `tip`, `cikti_sablon`, `aciklama`, `metin`, `sira`, `aktif`)
SELECT t.id, 'İşe Başlama Eğitim Tutanağı', 'cikti', 'ise_baslama_egitim_tutanagi', 'Basılı çıktı (SÇ.FR.12) - personel bilgileri hazır gelir, ıslak imzayla tamamlanır.', NULL, 20, 1
FROM `tenants` t
WHERE EXISTS (SELECT 1 FROM `evrak_formlari` f WHERE f.tenant_id = t.id AND f.deleted_at IS NULL)
  AND NOT EXISTS (
    SELECT 1 FROM `evrak_formlari` f2 WHERE f2.tenant_id = t.id AND f2.cikti_sablon = 'ise_baslama_egitim_tutanagi' AND f2.deleted_at IS NULL
);
