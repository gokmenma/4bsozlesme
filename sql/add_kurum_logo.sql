-- Kurum Logosu Tanımlama Alanı SQL Scripti
-- Bu script, definitions tablosunda kurum logosu için gerekli kayıtları garanti altına alır.

INSERT INTO `definitions` (`tenant_id`, `key`, `value`)
SELECT t.id, 'kurum_logo', ''
FROM `tenants` t
WHERE NOT EXISTS (
    SELECT 1 FROM `definitions` d WHERE d.tenant_id = t.id AND d.`key` = 'kurum_logo'
);

INSERT INTO `definitions` (`tenant_id`, `key`, `value`)
SELECT t.id, 'kurum_logo_mime', ''
FROM `tenants` t
WHERE NOT EXISTS (
    SELECT 1 FROM `definitions` d WHERE d.tenant_id = t.id AND d.`key` = 'kurum_logo_mime'
);

