-- Yetki Grupları ve Sayfa Erişim İzinleri Veritabanı Yapısı

CREATE TABLE IF NOT EXISTS `roles` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` BIGINT UNSIGNED NULL,
  `name` VARCHAR(100) NOT NULL,
  `description` VARCHAR(255) NULL,
  `is_system` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_tenant` (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `role_permissions` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `role_id` INT UNSIGNED NOT NULL,
  `page_route` VARCHAR(100) NOT NULL,
  `can_access` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_role_page` (`role_id`, `page_route`),
  INDEX `idx_role` (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `activity_logs` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` BIGINT UNSIGNED NULL,
  `user_id` BIGINT UNSIGNED NULL,
  `action` VARCHAR(100) NOT NULL,
  `description` TEXT NULL,
  `ip_address` VARCHAR(45) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_tenant_user` (`tenant_id`, `user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- users tablosuna role_id sütununu ekle (eğer yoksa)
SET @dbname = DATABASE();
SET @tablename = "users";
SET @columnname = "role_id";
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      TABLE_SCHEMA = @dbname
      AND TABLE_NAME = @tablename
      AND COLUMN_NAME = @columnname
  ) > 0,
  "SELECT 1",
  "ALTER TABLE `users` ADD COLUMN `role_id` INT UNSIGNED NULL AFTER `role`"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Varsayılan sistem rollerini ekle
INSERT IGNORE INTO `roles` (`id`, `tenant_id`, `name`, `description`, `is_system`) VALUES
(1, NULL, 'Superadmin', 'Sistem Genel Yöneticisi - Tüm Yetkilere Sahip', 1),
(2, NULL, 'Kurum Yöneticisi', 'Kurum Düzeyinde Tam Yetkili Yönetici', 1),
(3, NULL, 'Personel Sorumlusu', 'Personel ve Ücret İşlemlerinden Sorumlu Kullanıcı', 1),
(4, NULL, 'Standart Kullanıcı', 'Genel Kullanım ve Görüntüleme Yetkisi', 1),
(5, NULL, 'İzleyici (Salt Okunur)', 'Sadece Görüntüleme Yetkisi Olan Kullanıcı', 1);

-- Mevcut kullanıcıların role_id değerini role alanına göre eşitle
UPDATE `users` SET `role_id` = 1 WHERE `role` = 'superadmin' AND (`role_id` IS NULL OR `role_id` = 0);
UPDATE `users` SET `role_id` = 2 WHERE `role` = 'admin' AND (`role_id` IS NULL OR `role_id` = 0);
UPDATE `users` SET `role_id` = 4 WHERE (`role` = 'user' OR `role` IS NULL) AND (`role_id` IS NULL OR `role_id` = 0);
