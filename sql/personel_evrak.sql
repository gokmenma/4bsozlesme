-- Personel İşe Giriş Evrakları (Dijital Evrak Paketi)

CREATE TABLE IF NOT EXISTS `evrak_formlari` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` BIGINT UNSIGNED NULL,
  `ad` VARCHAR(255) NOT NULL,
  `tip` VARCHAR(20) NOT NULL DEFAULT 'bilgi',
  `aciklama` VARCHAR(500) NULL,
  `metin` MEDIUMTEXT NULL,
  `sira` INT NOT NULL DEFAULT 0,
  `aktif` TINYINT(1) NOT NULL DEFAULT 1,
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  INDEX `idx_evrak_form_tenant` (`tenant_id`, `deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `evrak_form_alanlari` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `form_id` INT UNSIGNED NOT NULL,
  `etiket` VARCHAR(255) NOT NULL,
  `anahtar` VARCHAR(64) NOT NULL,
  `tip` VARCHAR(20) NOT NULL DEFAULT 'text',
  `secenekler` TEXT NULL,
  `yardim` VARCHAR(255) NULL,
  `zorunlu` TINYINT(1) NOT NULL DEFAULT 0,
  `personel_alani` VARCHAR(64) NULL,
  `sira` INT NOT NULL DEFAULT 0,
  UNIQUE KEY `uk_evrak_alan_anahtar` (`form_id`, `anahtar`),
  INDEX `idx_evrak_alan_form` (`form_id`),
  CONSTRAINT `fk_evrak_alan_form` FOREIGN KEY (`form_id`) REFERENCES `evrak_formlari` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `evrak_paketleri` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` BIGINT UNSIGNED NOT NULL,
  `personel_id` BIGINT UNSIGNED NOT NULL,
  `token_hash` CHAR(64) NOT NULL,
  `son_gecerlilik` DATETIME NOT NULL,
  `durum` VARCHAR(20) NOT NULL DEFAULT 'gonderildi',
  `gonderim_kanali` VARCHAR(20) NOT NULL DEFAULT 'link',
  `alici_email` VARCHAR(255) NULL,
  `iade_notu` VARCHAR(1000) NULL,
  `dogrulama_deneme` INT NOT NULL DEFAULT 0,
  `kilitli_until` DATETIME NULL,
  `acilma_tarihi` DATETIME NULL,
  `gonderim_tarihi` DATETIME NULL,
  `onay_tarihi` DATETIME NULL,
  `onaylayan_user_id` BIGINT UNSIGNED NULL,
  `gonderen_user_id` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_evrak_paket_token` (`token_hash`),
  INDEX `idx_evrak_paket_tenant` (`tenant_id`, `durum`),
  INDEX `idx_evrak_paket_personel` (`personel_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `evrak_paket_formlari` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `paket_id` INT UNSIGNED NOT NULL,
  `form_id` INT UNSIGNED NOT NULL,
  `sira` INT NOT NULL DEFAULT 0,
  `form_snapshot` MEDIUMTEXT NOT NULL,
  UNIQUE KEY `uk_evrak_paket_form` (`paket_id`, `form_id`),
  CONSTRAINT `fk_evrak_pf_paket` FOREIGN KEY (`paket_id`) REFERENCES `evrak_paketleri` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `evrak_yanitlari` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `paket_id` INT UNSIGNED NOT NULL,
  `form_id` INT UNSIGNED NOT NULL,
  `alan_anahtar` VARCHAR(64) NOT NULL,
  `deger` TEXT NULL,
  `sifreli` TINYINT(1) NOT NULL DEFAULT 0,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_evrak_yanit` (`paket_id`, `form_id`, `alan_anahtar`),
  CONSTRAINT `fk_evrak_yanit_paket` FOREIGN KEY (`paket_id`) REFERENCES `evrak_paketleri` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `evrak_dosyalari` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `paket_id` INT UNSIGNED NOT NULL,
  `form_id` INT UNSIGNED NULL,
  `alan_anahtar` VARCHAR(64) NULL,
  `tur` VARCHAR(20) NOT NULL DEFAULT 'yukleme',
  `dosya_yolu` VARCHAR(255) NOT NULL,
  `mime` VARCHAR(100) NOT NULL,
  `boyut` INT UNSIGNED NOT NULL DEFAULT 0,
  `orijinal_ad` VARCHAR(255) NOT NULL,
  `yukleyen` VARCHAR(20) NOT NULL DEFAULT 'personel',
  `yukleyen_user_id` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_evrak_dosya_paket` (`paket_id`),
  CONSTRAINT `fk_evrak_dosya_paket` FOREIGN KEY (`paket_id`) REFERENCES `evrak_paketleri` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sistem şablonları (tenant_id NULL). Kurumlar "Hazır şablonları yükle" ile kendine kopyalar.

INSERT INTO `evrak_formlari` (`id`, `tenant_id`, `ad`, `tip`, `aciklama`, `metin`, `sira`, `aktif`) VALUES
(1, NULL, 'Kişisel Bilgi Formu', 'bilgi', 'İletişim, adres, eğitim ve banka bilgileri', NULL, 1, 1),
(2, NULL, 'KVKK Aydınlatma Metni ve Açık Rıza Beyanı', 'kvkk', '6698 sayılı Kanun kapsamında bilgilendirme ve onay',
'{{KURUM_ADI}} olarak, 6698 sayılı Kişisel Verilerin Korunması Kanunu ("KVKK") kapsamında veri sorumlusu sıfatıyla, iş ilişkisinin kurulması ve sürdürülmesi amacıyla kimlik, iletişim, özlük, eğitim, sağlık, finans ve aile bilgilerinizi işlemekteyiz.

Kişisel verileriniz; personel özlük dosyasının oluşturulması, bordro ve ücret işlemlerinin yürütülmesi, SGK ve vergi mevzuatından doğan yükümlülüklerin yerine getirilmesi, yetkili kamu kurumlarına bilgi verilmesi amaçlarıyla, kanunlarda açıkça öngörülmesi ve bir sözleşmenin kurulması veya ifasıyla doğrudan ilgili olması hukuki sebeplerine dayanarak işlenmektedir.

Verileriniz yalnızca mevzuatın izin verdiği ölçüde Sosyal Güvenlik Kurumu, Gelir İdaresi Başkanlığı, bankalar ve yetkili kamu kurum ve kuruluşlarıyla paylaşılabilir.

KVKK''nın 11. maddesi uyarınca; verilerinizin işlenip işlenmediğini öğrenme, bilgi talep etme, düzeltilmesini veya silinmesini isteme ve kanunda sayılan diğer haklarınızı kullanmak için {{KURUM_ADI}} adresine yazılı olarak başvurabilirsiniz.

Ben, {{PERSONEL_AD}} (T.C. {{TC_NO}}), yukarıdaki aydınlatma metnini okuduğumu ve anladığımı beyan ederim.', 2, 1),
(3, NULL, 'Aile Durumu ve Vergi Beyanı', 'beyan', 'Medeni durum, eş ve çocuk bilgileri', NULL, 3, 1),
(4, NULL, 'Belge Yükleme', 'belge', 'İşe giriş için gerekli belgelerin fotoğraf veya PDF olarak yüklenmesi', NULL, 4, 1);

INSERT INTO `evrak_form_alanlari` (`form_id`, `etiket`, `anahtar`, `tip`, `secenekler`, `yardim`, `zorunlu`, `personel_alani`, `sira`) VALUES
(1, 'Cep Telefonu', 'telefon', 'tel', NULL, NULL, 1, 'telefon', 1),
(1, 'E-posta Adresi', 'email', 'email', NULL, NULL, 0, NULL, 2),
(1, 'Cinsiyet', 'cinsiyet', 'select', 'Kadın,Erkek', NULL, 1, 'cinsiyet', 3),
(1, 'Doğum Tarihi', 'dogum_tarihi', 'date', NULL, NULL, 1, NULL, 4),
(1, 'Doğum Yeri', 'dogum_yeri', 'text', NULL, NULL, 1, NULL, 5),
(1, 'Kan Grubu', 'kan_grubu', 'select', '0 Rh+,0 Rh-,A Rh+,A Rh-,B Rh+,B Rh-,AB Rh+,AB Rh-', NULL, 0, NULL, 6),
(1, 'İkamet Adresi', 'adres', 'textarea', NULL, NULL, 1, NULL, 7),
(1, 'Öğrenim Durumu', 'ogrenim', 'select', 'İlköğretim,Lise,Ön Lisans,Lisans,Yüksek Lisans,Doktora', NULL, 1, NULL, 8),
(1, 'Mezun Olunan Okul / Bölüm', 'okul', 'text', NULL, NULL, 0, NULL, 9),
(1, 'Askerlik Durumu', 'askerlik', 'select', 'Yapıldı,Muaf,Tecilli,Kadın (Uygulanmaz)', NULL, 0, NULL, 10),
(1, 'IBAN', 'iban', 'iban', NULL, 'Maaş ödemesi yapılacak hesap (TR ile başlayan 26 karakter)', 1, NULL, 11),
(1, 'Acil Durumda Aranacak Kişi', 'acil_kisi', 'text', NULL, 'Ad Soyad ve yakınlık derecesi', 1, NULL, 12),
(1, 'Acil Durum Telefonu', 'acil_telefon', 'tel', NULL, NULL, 1, NULL, 13),
(2, 'Aydınlatma metnini okudum, anladım.', 'aydinlatma_onay', 'checkbox', NULL, NULL, 1, NULL, 1),
(2, 'Kişisel verilerimin yukarıda belirtilen amaçlarla işlenmesine açık rıza veriyorum.', 'acik_riza', 'checkbox', NULL, NULL, 1, NULL, 2),
(3, 'Medeni Durum', 'medeni_hal', 'select', 'Bekar,Evli,Boşanmış,Dul', NULL, 1, NULL, 1),
(3, 'Eşinin Adı Soyadı', 'es_ad_soyad', 'text', NULL, 'Evli değilseniz boş bırakınız', 0, NULL, 2),
(3, 'Eşinin T.C. Kimlik No', 'es_tc', 'tc', NULL, NULL, 0, NULL, 3),
(3, 'Eşi Çalışıyor mu?', 'es_calisiyor', 'radio', 'Evet,Hayır', NULL, 0, NULL, 4),
(3, 'Çocuk Sayısı', 'cocuk_sayisi', 'number', NULL, NULL, 0, NULL, 5),
(3, 'Çocuk Bilgileri', 'cocuklar', 'textarea', NULL, 'Her satıra bir çocuk: Ad Soyad - Doğum Tarihi - T.C. No', 0, NULL, 6),
(3, 'Engellilik Durumu', 'engellilik', 'select', 'Yok,1. Derece,2. Derece,3. Derece', NULL, 0, NULL, 7),
(4, 'Nüfus Cüzdanı (ön ve arka yüz)', 'kimlik', 'file', NULL, NULL, 1, NULL, 1),
(4, 'Diploma / Mezuniyet Belgesi', 'diploma', 'file', NULL, NULL, 1, NULL, 2),
(4, 'Vesikalık Fotoğraf', 'fotograf', 'file', NULL, NULL, 1, NULL, 3),
(4, 'Sağlık Raporu', 'saglik_raporu', 'file', NULL, NULL, 0, NULL, 4),
(4, 'Adli Sicil Kaydı', 'adli_sicil', 'file', NULL, 'e-Devlet üzerinden alınabilir', 0, NULL, 5),
(4, 'İkametgah Belgesi', 'ikametgah', 'file', NULL, 'e-Devlet üzerinden alınabilir', 0, NULL, 6);
