INSERT INTO `evrak_formlari` (`tenant_id`, `ad`, `tip`, `cikti_sablon`, `aciklama`, `sira`, `aktif`)
SELECT NULL, v.ad, 'cikti', v.sablon, v.aciklama, v.sira, 1
FROM (
  SELECT 'Psikososyal Risk Analiz Formu' AS ad, 'psikososyal_risk_analiz' AS sablon, 'Basılı çıktı (KKU.FR.12) - adı soyadı hazır gelir.' AS aciklama, 21 AS sira
  UNION ALL SELECT 'Personel Kimlik Kartı Talep Formu', 'kimlik_karti_talep', 'Basılı çıktı (EK-2) - T.C. kimlik no, adı soyadı ve unvan hazır gelir.', 22
  UNION ALL SELECT 'Aile Durumu Bildirimi (Asgari Geçim İndirimi)', 'aile_durumu_bildirimi', 'Basılı çıktı (EK-1, yatay) - T.C. kimlik no, adı soyadı ve görev hazır gelir.', 23
  UNION ALL SELECT 'Mal Bildirim Formu', 'mal_bildirim_formu', 'Basılı çıktı (KKU.FR.18) - görev hazır gelir.', 24
) v
WHERE NOT EXISTS (
  SELECT 1 FROM `evrak_formlari` f WHERE f.tenant_id IS NULL AND f.cikti_sablon = v.sablon AND f.deleted_at IS NULL
);

INSERT INTO `evrak_formlari` (`tenant_id`, `ad`, `tip`, `cikti_sablon`, `aciklama`, `sira`, `aktif`)
SELECT t.id, v.ad, 'cikti', v.sablon, v.aciklama, v.sira, 1
FROM `tenants` t
JOIN (
  SELECT 'Psikososyal Risk Analiz Formu' AS ad, 'psikososyal_risk_analiz' AS sablon, 'Basılı çıktı (KKU.FR.12) - adı soyadı hazır gelir.' AS aciklama, 21 AS sira
  UNION ALL SELECT 'Personel Kimlik Kartı Talep Formu', 'kimlik_karti_talep', 'Basılı çıktı (EK-2) - T.C. kimlik no, adı soyadı ve unvan hazır gelir.', 22
  UNION ALL SELECT 'Aile Durumu Bildirimi (Asgari Geçim İndirimi)', 'aile_durumu_bildirimi', 'Basılı çıktı (EK-1, yatay) - T.C. kimlik no, adı soyadı ve görev hazır gelir.', 23
  UNION ALL SELECT 'Mal Bildirim Formu', 'mal_bildirim_formu', 'Basılı çıktı (KKU.FR.18) - görev hazır gelir.', 24
) v
WHERE EXISTS (SELECT 1 FROM `evrak_formlari` f WHERE f.tenant_id = t.id AND f.deleted_at IS NULL)
  AND NOT EXISTS (
    SELECT 1 FROM `evrak_formlari` f2 WHERE f2.tenant_id = t.id AND f2.cikti_sablon = v.sablon AND f2.deleted_at IS NULL
  );
