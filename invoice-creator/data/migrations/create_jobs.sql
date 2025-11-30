-- Jobs table for invoice-creator
CREATE TABLE IF NOT EXISTS `jobs` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `client_id` int unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `rate` decimal(10,2) DEFAULT NULL,
  `rate_type` enum('hourly','daily') NOT NULL DEFAULT 'hourly',
  `target_type` varchar(20) NOT NULL DEFAULT 'client', -- 'client' or 'client_client'
  `target_name` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_client_id` (`client_id`),
  CONSTRAINT `fk_jobs_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
