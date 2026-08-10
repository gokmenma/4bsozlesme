-- Users tablosuna username sütunu ekleme ve mevcut kullanıcıların email adreslerinin @ öncesini username olarak atama

ALTER TABLE `users` ADD COLUMN `username` VARCHAR(100) NULL AFTER `email`;

UPDATE `users` 
SET `username` = SUBSTRING_INDEX(`email`, '@', 1) 
WHERE `username` IS NULL OR `username` = '';
