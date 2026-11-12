-- Kariri.app — patch de schema para Hostinger/MariaDB 11.x
-- Execute uma única vez no banco de staging e depois no banco de produção.
-- Faça backup antes. Não contém usuários, senhas ou tokens.

SET SQL_MODE = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';
START TRANSACTION;

-- 1) Campos de perfil usados pelo frontend
ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `birth_city_id` BIGINT UNSIGNED NULL,
  ADD COLUMN IF NOT EXISTS `birth_city_name` VARCHAR(120) NULL,
  ADD COLUMN IF NOT EXISTS `current_city_id` BIGINT UNSIGNED NULL,
  ADD COLUMN IF NOT EXISTS `current_city_name` VARCHAR(120) NULL,
  ADD COLUMN IF NOT EXISTS `cariri_city_id` BIGINT UNSIGNED NULL,
  ADD COLUMN IF NOT EXISTS `city_preference` VARCHAR(120) NULL,
  ADD COLUMN IF NOT EXISTS `marketing_consent` TINYINT(1) NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `marketing_consent_at` TIMESTAMP NULL;

-- As constraints só devem ser executadas se ainda não existirem.
-- No banco original do projeto, essas três colunas não existiam.
SET @fk_birth := (
  SELECT IF(COUNT(*) = 0,
    'ALTER TABLE `users` ADD CONSTRAINT `users_birth_city_id_foreign` FOREIGN KEY (`birth_city_id`) REFERENCES `cities` (`id`) ON DELETE SET NULL',
    'SELECT 1')
  FROM information_schema.REFERENTIAL_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND CONSTRAINT_NAME = 'users_birth_city_id_foreign'
);
PREPARE stmt_birth FROM @fk_birth;
EXECUTE stmt_birth;
DEALLOCATE PREPARE stmt_birth;

SET @fk_current := (
  SELECT IF(COUNT(*) = 0,
    'ALTER TABLE `users` ADD CONSTRAINT `users_current_city_id_foreign` FOREIGN KEY (`current_city_id`) REFERENCES `cities` (`id`) ON DELETE SET NULL',
    'SELECT 1')
  FROM information_schema.REFERENTIAL_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND CONSTRAINT_NAME = 'users_current_city_id_foreign'
);
PREPARE stmt_current FROM @fk_current;
EXECUTE stmt_current;
DEALLOCATE PREPARE stmt_current;

SET @fk_cariri := (
  SELECT IF(COUNT(*) = 0,
    'ALTER TABLE `users` ADD CONSTRAINT `users_cariri_city_id_foreign` FOREIGN KEY (`cariri_city_id`) REFERENCES `cities` (`id`) ON DELETE SET NULL',
    'SELECT 1')
  FROM information_schema.REFERENTIAL_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND CONSTRAINT_NAME = 'users_cariri_city_id_foreign'
);
PREPARE stmt_cariri FROM @fk_cariri;
EXECUTE stmt_cariri;
DEALLOCATE PREPARE stmt_cariri;

-- 2) Capa e galeria de locais
ALTER TABLE `places`
  ADD COLUMN IF NOT EXISTS `cover_image` VARCHAR(2048) NULL,
  ADD COLUMN IF NOT EXISTS `images` JSON NULL;

-- 3) Padronização dos status de locais
-- Primeiro ampliamos temporariamente o ENUM para que 'published' e 'archived'
-- possam ser gravados sem o erro MySQL/MariaDB #1265.
ALTER TABLE `places`
  MODIFY COLUMN `status` ENUM('active','inactive','draft','pending','published','rejected','archived') NOT NULL DEFAULT 'active';

UPDATE `places` SET `status` = 'published' WHERE `status` = 'active';
UPDATE `places` SET `status` = 'archived' WHERE `status` = 'inactive';

-- Depois removemos os valores legados do ENUM.
ALTER TABLE `places`
  MODIFY COLUMN `status` ENUM('draft','pending','published','rejected','archived') NOT NULL DEFAULT 'published';

-- 4) Claims de empresas
CREATE TABLE IF NOT EXISTS `business_claims` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `place_id` BIGINT UNSIGNED NOT NULL,
  `phone` VARCHAR(40) NULL,
  `message` TEXT NOT NULL,
  `proof` VARCHAR(500) NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
  `reject_reason` TEXT NULL,
  `reviewed_by` BIGINT UNSIGNED NULL,
  `reviewed_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `business_claims_status_created_at_index` (`status`, `created_at`),
  KEY `business_claims_place_id_user_id_index` (`place_id`, `user_id`),
  CONSTRAINT `business_claims_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `business_claims_place_id_foreign` FOREIGN KEY (`place_id`) REFERENCES `places` (`id`) ON DELETE CASCADE,
  CONSTRAINT `business_claims_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5) Marcar como aplicadas apenas as migrations realmente cobertas por este SQL.
-- Se você optar por executar "php artisan migrate", NÃO execute este bloco antes.
SET @next_batch := (SELECT COALESCE(MAX(`batch`), 0) + 1 FROM `migrations`);
INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_10_23_000001_add_profile_fields_to_users_table', @next_batch
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_10_23_000001_add_profile_fields_to_users_table');
INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_10_23_000002_create_reviews_table', @next_batch
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_10_23_000002_create_reviews_table');
INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_10_24_000001_create_business_claims_table', @next_batch
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_10_24_000001_create_business_claims_table');
INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_10_25_000001_sync_production_schema', @next_batch
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_10_25_000001_sync_production_schema');

COMMIT;
