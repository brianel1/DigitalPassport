-- Skills & Career schema snapshot, 17 November 2026.
-- FRESH installations only. Existing installations: php artisan migrate.
-- Contains one explicitly fictional demo student; no real user data, tokens, jobs or matches.
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
CREATE DATABASE IF NOT EXISTS `digitalpassport` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `digitalpassport`;

DROP TABLE IF EXISTS `assessment_answers`;
CREATE TABLE `assessment_answers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `attempt_id` bigint(20) unsigned NOT NULL,
  `question_id` bigint(20) unsigned NOT NULL,
  `selected_option` tinyint(3) unsigned DEFAULT NULL,
  `is_correct` tinyint(1) DEFAULT NULL,
  `points_awarded` smallint(5) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `assessment_answers_attempt_id_question_id_unique` (`attempt_id`,`question_id`),
  KEY `assessment_answers_question_id_foreign` (`question_id`),
  CONSTRAINT `assessment_answers_attempt_id_foreign` FOREIGN KEY (`attempt_id`) REFERENCES `assessment_attempts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `assessment_answers_question_id_foreign` FOREIGN KEY (`question_id`) REFERENCES `assessment_questions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `assessment_attempts`;
CREATE TABLE `assessment_attempts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `assessment_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `score` int(10) unsigned DEFAULT NULL,
  `maximum_score` int(10) unsigned DEFAULT NULL,
  `percentage` decimal(5,2) DEFAULT NULL,
  `passed` tinyint(1) DEFAULT NULL,
  `started_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `submitted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `assessment_snapshot` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`assessment_snapshot`)),
  PRIMARY KEY (`id`),
  KEY `assessment_attempts_assessment_id_foreign` (`assessment_id`),
  KEY `assessment_attempts_user_id_assessment_id_index` (`user_id`,`assessment_id`),
  CONSTRAINT `assessment_attempts_assessment_id_foreign` FOREIGN KEY (`assessment_id`) REFERENCES `assessments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `assessment_attempts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `assessment_questions`;
CREATE TABLE `assessment_questions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `assessment_id` bigint(20) unsigned NOT NULL,
  `question` text NOT NULL,
  `options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`options`)),
  `correct_option` tinyint(3) unsigned NOT NULL,
  `points` smallint(5) unsigned NOT NULL DEFAULT 1,
  `position` smallint(5) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `assessment_questions_assessment_id_position_unique` (`assessment_id`,`position`),
  CONSTRAINT `assessment_questions_assessment_id_foreign` FOREIGN KEY (`assessment_id`) REFERENCES `assessments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `assessments`;
CREATE TABLE `assessments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `category` varchar(50) NOT NULL,
  `description` text NOT NULL,
  `time_limit_minutes` smallint(5) unsigned NOT NULL,
  `passing_score` tinyint(3) unsigned NOT NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `skill_names` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`skill_names`)),
  `version` int(10) unsigned NOT NULL DEFAULT 1,
  `parent_assessment_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `assessments_created_by_foreign` (`created_by`),
  KEY `assessments_is_published_index` (`is_published`),
  KEY `assessments_parent_assessment_id_foreign` (`parent_assessment_id`),
  CONSTRAINT `assessments_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `assessments_parent_assessment_id_foreign` FOREIGN KEY (`parent_assessment_id`) REFERENCES `assessments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `assistant_sessions`;
CREATE TABLE `assistant_sessions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `generated_cv_id` bigint(20) unsigned DEFAULT NULL,
  `title` varchar(120) NOT NULL DEFAULT 'New conversation',
  `mode` varchar(20) NOT NULL DEFAULT 'coach',
  `messages` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`messages`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `language` varchar(2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `assistant_sessions_user_id_foreign` (`user_id`),
  KEY `assistant_sessions_generated_cv_id_foreign` (`generated_cv_id`),
  CONSTRAINT `assistant_sessions_generated_cv_id_foreign` FOREIGN KEY (`generated_cv_id`) REFERENCES `generated_cvs` (`id`) ON DELETE SET NULL,
  CONSTRAINT `assistant_sessions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `cache`;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `cache_locks`;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `certification_skill`;
CREATE TABLE `certification_skill` (
  `certification_id` bigint(20) unsigned NOT NULL,
  `skill_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`certification_id`,`skill_id`),
  KEY `certification_skill_skill_id_foreign` (`skill_id`),
  CONSTRAINT `certification_skill_certification_id_foreign` FOREIGN KEY (`certification_id`) REFERENCES `certifications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `certification_skill_skill_id_foreign` FOREIGN KEY (`skill_id`) REFERENCES `skills` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `certifications`;
CREATE TABLE `certifications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `issuing_organization` varchar(255) NOT NULL,
  `issue_date` date NOT NULL,
  `expiry_date` date DEFAULT NULL,
  `credential_id` varchar(255) DEFAULT NULL,
  `credential_url` varchar(255) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `reviewed_by` bigint(20) unsigned DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `admin_remarks` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `submission_revision` int(10) unsigned NOT NULL DEFAULT 1,
  `submitted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `certifications_user_id_foreign` (`user_id`),
  KEY `certifications_reviewed_by_foreign` (`reviewed_by`),
  KEY `certifications_status_index` (`status`),
  CONSTRAINT `certifications_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `certifications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `cv_exports`;
CREATE TABLE `cv_exports` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `generated_cv_id` bigint(20) unsigned NOT NULL,
  `format` varchar(10) NOT NULL,
  `path` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cv_exports_generated_cv_id_foreign` (`generated_cv_id`),
  CONSTRAINT `cv_exports_generated_cv_id_foreign` FOREIGN KEY (`generated_cv_id`) REFERENCES `generated_cvs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `evidence_files`;
CREATE TABLE `evidence_files` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `evidenceable_type` varchar(255) NOT NULL,
  `evidenceable_id` bigint(20) unsigned NOT NULL,
  `disk` varchar(40) NOT NULL DEFAULT 'local',
  `path` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `mime_type` varchar(120) NOT NULL,
  `size` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `evidence_files_user_id_foreign` (`user_id`),
  KEY `evidence_files_evidenceable_type_evidenceable_id_index` (`evidenceable_type`,`evidenceable_id`),
  CONSTRAINT `evidence_files_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `failed_jobs`;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `generated_cvs`;
CREATE TABLE `generated_cvs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `target_job_listing_id` bigint(20) unsigned DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`content`)),
  `version` int(10) unsigned NOT NULL DEFAULT 1,
  `status` varchar(20) NOT NULL DEFAULT 'draft',
  `model_name` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `source_type` varchar(30) NOT NULL DEFAULT 'profile',
  `source_filename` varchar(255) DEFAULT NULL,
  `source_path` varchar(255) DEFAULT NULL,
  `source_text` longtext DEFAULT NULL,
  `analysis` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`analysis`)),
  `provenance` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`provenance`)),
  PRIMARY KEY (`id`),
  KEY `generated_cvs_target_job_listing_id_foreign` (`target_job_listing_id`),
  KEY `generated_cvs_user_id_updated_at_index` (`user_id`,`updated_at`),
  CONSTRAINT `generated_cvs_target_job_listing_id_foreign` FOREIGN KEY (`target_job_listing_id`) REFERENCES `job_listings` (`id`) ON DELETE SET NULL,
  CONSTRAINT `generated_cvs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `job_batches`;
CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `job_feedback`;
CREATE TABLE `job_feedback` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `job_listing_id` bigint(20) unsigned NOT NULL,
  `rating` varchar(20) NOT NULL,
  `reason` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `job_feedback_user_id_job_listing_id_unique` (`user_id`,`job_listing_id`),
  KEY `job_feedback_job_listing_id_foreign` (`job_listing_id`),
  CONSTRAINT `job_feedback_job_listing_id_foreign` FOREIGN KEY (`job_listing_id`) REFERENCES `job_listings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `job_feedback_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `job_listings`;
CREATE TABLE `job_listings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `source` varchar(40) NOT NULL,
  `external_id` varchar(191) NOT NULL,
  `title` varchar(255) NOT NULL,
  `company` varchar(255) NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `work_type` varchar(50) DEFAULT NULL,
  `salary_min` int(10) unsigned DEFAULT NULL,
  `salary_max` int(10) unsigned DEFAULT NULL,
  `salary_currency` varchar(10) DEFAULT NULL,
  `description` text NOT NULL,
  `required_skills` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`required_skills`)),
  `application_url` varchar(255) NOT NULL,
  `published_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `fetched_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `raw_payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`raw_payload`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `link_checked_at` timestamp NULL DEFAULT NULL,
  `link_status` smallint(5) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `job_listings_source_external_id_unique` (`source`,`external_id`),
  KEY `job_listings_title_location_index` (`title`,`location`),
  KEY `job_listings_source_index` (`source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `job_matches`;
CREATE TABLE `job_matches` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `recommendation_run_id` bigint(20) unsigned NOT NULL,
  `job_listing_id` bigint(20) unsigned NOT NULL,
  `match_score` decimal(5,2) NOT NULL,
  `matched_skills` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`matched_skills`)),
  `missing_skills` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`missing_skills`)),
  `explanation` text NOT NULL,
  `rank` smallint(5) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `score_breakdown` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`score_breakdown`)),
  PRIMARY KEY (`id`),
  UNIQUE KEY `job_matches_recommendation_run_id_job_listing_id_unique` (`recommendation_run_id`,`job_listing_id`),
  KEY `job_matches_job_listing_id_foreign` (`job_listing_id`),
  KEY `job_matches_recommendation_run_id_rank_index` (`recommendation_run_id`,`rank`),
  CONSTRAINT `job_matches_job_listing_id_foreign` FOREIGN KEY (`job_listing_id`) REFERENCES `job_listings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `job_matches_recommendation_run_id_foreign` FOREIGN KEY (`recommendation_run_id`) REFERENCES `job_recommendation_runs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `job_preferences`;
CREATE TABLE `job_preferences` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `preferred_fields` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`preferred_fields`)),
  `preferred_locations` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`preferred_locations`)),
  `work_types` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`work_types`)),
  `accepts_remote` tinyint(1) NOT NULL DEFAULT 1,
  `minimum_salary` decimal(12,2) DEFAULT NULL,
  `maximum_salary` decimal(12,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `job_preferences_user_id_unique` (`user_id`),
  CONSTRAINT `job_preferences_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `job_recommendation_runs`;
CREATE TABLE `job_recommendation_runs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `profile_snapshot` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`profile_snapshot`)),
  `preference_snapshot` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`preference_snapshot`)),
  `model_name` varchar(255) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'completed',
  `failure_reason` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `ai_insights` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`ai_insights`)),
  PRIMARY KEY (`id`),
  KEY `job_recommendation_runs_user_id_foreign` (`user_id`),
  CONSTRAINT `job_recommendation_runs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `job_sync_runs`;
CREATE TABLE `job_sync_runs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `source` varchar(40) NOT NULL,
  `status` varchar(20) NOT NULL,
  `saved_count` int(10) unsigned NOT NULL DEFAULT 0,
  `message` varchar(500) DEFAULT NULL,
  `started_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `jobs`;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `migrations`;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `passport_exports`;
CREATE TABLE `passport_exports` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `format` varchar(10) NOT NULL DEFAULT 'pdf',
  `path` varchar(255) NOT NULL,
  `record_snapshot` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`record_snapshot`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `passport_exports_user_id_foreign` (`user_id`),
  CONSTRAINT `passport_exports_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `password_reset_tokens`;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `personal_access_tokens`;
CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) unsigned NOT NULL,
  `name` text NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  KEY `personal_access_tokens_expires_at_index` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `profile_shares`;
CREATE TABLE `profile_shares` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `token_hash` varchar(64) NOT NULL,
  `encrypted_token` text NOT NULL,
  `selection` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`selection`)),
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `revoked_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `profile_shares_token_hash_unique` (`token_hash`),
  KEY `profile_shares_user_id_foreign` (`user_id`),
  CONSTRAINT `profile_shares_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `project_skill`;
CREATE TABLE `project_skill` (
  `project_id` bigint(20) unsigned NOT NULL,
  `skill_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`project_id`,`skill_id`),
  KEY `project_skill_skill_id_foreign` (`skill_id`),
  CONSTRAINT `project_skill_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `project_skill_skill_id_foreign` FOREIGN KEY (`skill_id`) REFERENCES `skills` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `projects`;
CREATE TABLE `projects` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `role` varchar(255) DEFAULT NULL,
  `technologies` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`technologies`)),
  `project_url` varchar(255) DEFAULT NULL,
  `repository_url` varchar(255) DEFAULT NULL,
  `completion_date` date DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `reviewed_by` bigint(20) unsigned DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `admin_remarks` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `submission_revision` int(10) unsigned NOT NULL DEFAULT 1,
  `submitted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `projects_user_id_foreign` (`user_id`),
  KEY `projects_reviewed_by_foreign` (`reviewed_by`),
  KEY `projects_status_index` (`status`),
  CONSTRAINT `projects_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `projects_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `sessions`;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `skills`;
CREATE TABLE `skills` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `name` varchar(100) NOT NULL,
  `category` varchar(50) NOT NULL,
  `proficiency` varchar(30) NOT NULL,
  `description` text DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `reviewed_by` bigint(20) unsigned DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `admin_remarks` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `skills_user_id_name_unique` (`user_id`,`name`),
  KEY `skills_reviewed_by_foreign` (`reviewed_by`),
  KEY `skills_status_index` (`status`),
  CONSTRAINT `skills_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `skills_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `student_profiles`;
CREATE TABLE `student_profiles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `headline` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `phone` varchar(40) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `linkedin_url` varchar(255) DEFAULT NULL,
  `github_url` varchar(255) DEFAULT NULL,
  `expected_graduation_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `student_profiles_user_id_unique` (`user_id`),
  CONSTRAINT `student_profiles_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `student_visits`;
CREATE TABLE `student_visits` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `snapshot` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`snapshot`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `student_visits_user_id_unique` (`user_id`),
  CONSTRAINT `student_visits_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `system_events`;
CREATE TABLE `system_events` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `kind` varchar(40) NOT NULL,
  `status` varchar(20) NOT NULL,
  `message` varchar(500) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `system_events_user_id_foreign` (`user_id`),
  CONSTRAINT `system_events_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(20) NOT NULL DEFAULT 'student',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `programme` varchar(100) DEFAULT NULL,
  `matric_no` varchar(40) DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_matric_no_unique` (`matric_no`),
  KEY `users_role_index` (`role`),
  KEY `users_is_active_index` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `verification_reviews`;
CREATE TABLE `verification_reviews` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `reviewer_id` bigint(20) unsigned NOT NULL,
  `reviewable_type` varchar(255) NOT NULL,
  `reviewable_id` bigint(20) unsigned NOT NULL,
  `decision` varchar(20) NOT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `record_snapshot` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`record_snapshot`)),
  PRIMARY KEY (`id`),
  KEY `verification_reviews_reviewer_id_foreign` (`reviewer_id`),
  KEY `verification_reviews_reviewable_type_reviewable_id_index` (`reviewable_type`,`reviewable_id`),
  CONSTRAINT `verification_reviews_reviewer_id_foreign` FOREIGN KEY (`reviewer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES (1,'0001_01_01_000000_create_users_table',1);
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES (2,'0001_01_01_000001_create_cache_table',1);
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES (3,'0001_01_01_000002_create_jobs_table',1);
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES (4,'2026_10_27_131708_create_personal_access_tokens_table',1);
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES (5,'2026_11_03_000000_add_role_and_student_fields_to_users_table',1);
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES (6,'2026_11_03_010000_create_portfolio_and_assessment_tables',1);
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES (7,'2026_11_03_020000_create_job_and_cv_tables',1);
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES (8,'2026_11_04_000000_add_active_status_to_users_table',1);
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES (9,'2026_11_14_160000_add_cv_imports_and_assistant_sessions',2);
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES (10,'2026_11_15_010000_add_job_score_breakdown',3);
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES (11,'2026_11_15_120000_complete_career_workflows',4);
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES (12,'2026_11_15_130000_map_authored_assessment_skills',5);
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES (13,'2026_11_16_100000_add_assistant_language',6);
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES (14,'2026_11_16_110000_support_decimal_salary_preferences',7);

-- Authored assessment bank. User, evidence, listing, match and CV records are created by real workflows.
INSERT INTO `assessments` (`id`,`created_by`,`title`,`skill_names`,`category`,`description`,`time_limit_minutes`,`passing_score`,`is_published`,`created_at`,`updated_at`) VALUES ('1',NULL,'JavaScript Fundamentals','[\"JavaScript\"]','programming','Test JavaScript syntax, functions, arrays, asynchronous work, and browser events.','15','60','1','2026-11-16 19:33:30','2026-11-16 19:33:30');
INSERT INTO `assessment_questions` (`id`,`assessment_id`,`question`,`options`,`correct_option`,`points`,`position`,`created_at`,`updated_at`) VALUES ('1','1','Which keyword declares a constant in JavaScript?','[\"var\",\"let\",\"const\",\"static\"]','2','1','1','2026-11-16 19:33:30','2026-11-16 19:33:30');
INSERT INTO `assessment_questions` (`id`,`assessment_id`,`question`,`options`,`correct_option`,`points`,`position`,`created_at`,`updated_at`) VALUES ('2','1','Which method converts a JSON string into an object?','[\"JSON.stringify()\",\"JSON.parse()\",\"JSON.convert()\",\"JSON.decode()\"]','1','1','2','2026-11-16 19:33:30','2026-11-16 19:33:30');
INSERT INTO `assessment_questions` (`id`,`assessment_id`,`question`,`options`,`correct_option`,`points`,`position`,`created_at`,`updated_at`) VALUES ('3','1','What does the strict equality operator compare?','[\"Value only\",\"Type only\",\"Value and type\",\"Object names\"]','2','1','3','2026-11-16 19:33:30','2026-11-16 19:33:30');
INSERT INTO `assessment_questions` (`id`,`assessment_id`,`question`,`options`,`correct_option`,`points`,`position`,`created_at`,`updated_at`) VALUES ('4','1','What is the scope of a variable declared with let inside a block?','[\"Global\",\"Function only\",\"Block\",\"Module only\"]','2','1','4','2026-11-16 19:33:30','2026-11-16 19:33:30');
INSERT INTO `assessment_questions` (`id`,`assessment_id`,`question`,`options`,`correct_option`,`points`,`position`,`created_at`,`updated_at`) VALUES ('5','1','Which array method creates a new array by transforming each element?','[\"forEach()\",\"map()\",\"filter()\",\"push()\"]','1','1','5','2026-11-16 19:33:30','2026-11-16 19:33:30');
INSERT INTO `assessment_questions` (`id`,`assessment_id`,`question`,`options`,`correct_option`,`points`,`position`,`created_at`,`updated_at`) VALUES ('6','1','What does an async function always return?','[\"A string\",\"A Promise\",\"A callback\",\"An array\"]','1','1','6','2026-11-16 19:33:30','2026-11-16 19:33:30');
INSERT INTO `assessment_questions` (`id`,`assessment_id`,`question`,`options`,`correct_option`,`points`,`position`,`created_at`,`updated_at`) VALUES ('7','1','Which method prevents the default browser action for an event?','[\"stopPropagation()\",\"preventDefault()\",\"removeEventListener()\",\"dispatchEvent()\"]','1','1','7','2026-11-16 19:33:30','2026-11-16 19:33:30');
INSERT INTO `assessment_questions` (`id`,`assessment_id`,`question`,`options`,`correct_option`,`points`,`position`,`created_at`,`updated_at`) VALUES ('8','1','What does optional chaining return when a property before ?. is null or undefined?','[\"An exception\",\"false\",\"undefined\",\"An empty object\"]','2','1','8','2026-11-16 19:33:30','2026-11-16 19:33:30');
INSERT INTO `assessment_questions` (`id`,`assessment_id`,`question`,`options`,`correct_option`,`points`,`position`,`created_at`,`updated_at`) VALUES ('9','1','What happens when one input Promise rejects in Promise.all()?','[\"The combined Promise rejects\",\"The rejected input is ignored\",\"The combined Promise resolves with null\",\"All inputs restart\"]','0','1','9','2026-11-16 19:33:30','2026-11-16 19:33:30');
INSERT INTO `assessment_questions` (`id`,`assessment_id`,`question`,`options`,`correct_option`,`points`,`position`,`created_at`,`updated_at`) VALUES ('10','1','Which statement about arrow functions is correct?','[\"They bind their own this\",\"They inherit this from the surrounding scope\",\"They cannot return values\",\"They must be asynchronous\"]','1','1','10','2026-11-16 19:33:30','2026-11-16 19:33:30');
INSERT INTO `assessments` (`id`,`created_by`,`title`,`skill_names`,`category`,`description`,`time_limit_minutes`,`passing_score`,`is_published`,`created_at`,`updated_at`) VALUES ('2',NULL,'SQL and Database Basics','[\"SQL\"]','databases','Assess SQL queries, joins, grouping, keys, filtering, and transactions.','15','60','1','2026-11-16 19:33:30','2026-11-16 19:33:30');
INSERT INTO `assessment_questions` (`id`,`assessment_id`,`question`,`options`,`correct_option`,`points`,`position`,`created_at`,`updated_at`) VALUES ('11','2','Which statement retrieves rows from a database?','[\"GET\",\"FETCH\",\"SELECT\",\"RETRIEVE\"]','2','1','1','2026-11-16 19:33:30','2026-11-16 19:33:30');
INSERT INTO `assessment_questions` (`id`,`assessment_id`,`question`,`options`,`correct_option`,`points`,`position`,`created_at`,`updated_at`) VALUES ('12','2','What is the purpose of a primary key?','[\"Sort every query\",\"Identify each row uniquely\",\"Encrypt a table\",\"Join every table\"]','1','1','2','2026-11-16 19:33:30','2026-11-16 19:33:30');
INSERT INTO `assessment_questions` (`id`,`assessment_id`,`question`,`options`,`correct_option`,`points`,`position`,`created_at`,`updated_at`) VALUES ('13','2','Which clause filters grouped results?','[\"WHERE\",\"HAVING\",\"ORDER BY\",\"LIMIT\"]','1','1','3','2026-11-16 19:33:30','2026-11-16 19:33:30');
INSERT INTO `assessment_questions` (`id`,`assessment_id`,`question`,`options`,`correct_option`,`points`,`position`,`created_at`,`updated_at`) VALUES ('14','2','Which join keeps every row from the left table even without a match?','[\"INNER JOIN\",\"LEFT JOIN\",\"CROSS JOIN\",\"SELF JOIN\"]','1','1','4','2026-11-16 19:33:30','2026-11-16 19:33:30');
INSERT INTO `assessment_questions` (`id`,`assessment_id`,`question`,`options`,`correct_option`,`points`,`position`,`created_at`,`updated_at`) VALUES ('15','2','Which clause groups rows before aggregate functions are applied?','[\"GROUP BY\",\"ORDER BY\",\"LIMIT\",\"DISTINCT\"]','0','1','5','2026-11-16 19:33:30','2026-11-16 19:33:30');
INSERT INTO `assessment_questions` (`id`,`assessment_id`,`question`,`options`,`correct_option`,`points`,`position`,`created_at`,`updated_at`) VALUES ('16','2','Which function counts rows in a result group?','[\"SUM(*)\",\"COUNT(*)\",\"TOTAL(*)\",\"NUMBER(*)\"]','1','1','6','2026-11-16 19:33:30','2026-11-16 19:33:30');
INSERT INTO `assessment_questions` (`id`,`assessment_id`,`question`,`options`,`correct_option`,`points`,`position`,`created_at`,`updated_at`) VALUES ('17','2','Which clause filters individual rows before grouping?','[\"HAVING\",\"GROUP BY\",\"WHERE\",\"ORDER BY\"]','2','1','7','2026-11-16 19:33:30','2026-11-16 19:33:30');
INSERT INTO `assessment_questions` (`id`,`assessment_id`,`question`,`options`,`correct_option`,`points`,`position`,`created_at`,`updated_at`) VALUES ('18','2','What does a foreign key normally reference?','[\"A table name\",\"A key in another table\",\"A database password\",\"A query result\"]','1','1','8','2026-11-16 19:33:30','2026-11-16 19:33:30');
INSERT INTO `assessment_questions` (`id`,`assessment_id`,`question`,`options`,`correct_option`,`points`,`position`,`created_at`,`updated_at`) VALUES ('19','2','Which expression sorts results from highest to lowest value?','[\"ORDER BY score ASC\",\"ORDER BY score DESC\",\"GROUP BY score\",\"WHERE score DESC\"]','1','1','9','2026-11-16 19:33:30','2026-11-16 19:33:30');
INSERT INTO `assessment_questions` (`id`,`assessment_id`,`question`,`options`,`correct_option`,`points`,`position`,`created_at`,`updated_at`) VALUES ('20','2','What does ROLLBACK do inside a transaction?','[\"Commits pending changes\",\"Undoes uncommitted changes\",\"Deletes the database\",\"Creates a backup\"]','1','1','10','2026-11-16 19:33:30','2026-11-16 19:33:30');

-- FICTIONAL demo student: demo@utem.edu.my / demo123. Development use only.
-- Includes profile, verified demo records, completed assessments and a general CV. NO jobs or matches.
INSERT INTO `users` (`id`,`name`,`email`,`password`,`role`,`is_active`,`programme`,`matric_no`,`email_verified_at`,`created_at`,`updated_at`) VALUES ('1','Aina Rahman (Demo)','demo@utem.edu.my','$2y$10$tvsBs4GnUZRUTtLsQuqaOuan2u7uOCfoKvwpzZSFm0zlL9XXPVDWS','student','1','Bachelor of Computer Science (Software Development)','DEMO2026001','2026-11-16 08:00:00','2026-11-16 08:00:00','2026-11-16 08:00:00');
INSERT INTO `student_profiles` (`id`,`user_id`,`headline`,`bio`,`phone`,`location`,`expected_graduation_date`,`created_at`,`updated_at`) VALUES ('1','1','Junior full-stack developer | Laravel, Vue and SQL','Fictional demonstration profile. Built accessible booking and inventory applications using Laravel, Vue and SQL; looking for a junior developer role in Malaysia.','+(60) - 123456789','Kuala Lumpur, Malaysia','2027-06-30','2026-11-16 08:00:00','2026-11-16 08:00:00');
INSERT INTO `skills` (`id`,`user_id`,`name`,`category`,`proficiency`,`description`,`status`,`reviewed_by`,`reviewed_at`,`admin_remarks`,`created_at`,`updated_at`) VALUES ('1','1','JavaScript','web-development','advanced','Demonstrated in the fictional campus application portfolio. Seeded for development testing.','verified',NULL,'2026-11-16 08:00:00','Fictional development fixture, pre-verified for fit-score testing. Not a real qualification.','2026-11-16 08:00:00','2026-11-16 08:00:00');
INSERT INTO `skills` (`id`,`user_id`,`name`,`category`,`proficiency`,`description`,`status`,`reviewed_by`,`reviewed_at`,`admin_remarks`,`created_at`,`updated_at`) VALUES ('2','1','TypeScript','web-development','advanced','Demonstrated in the fictional campus application portfolio. Seeded for development testing.','verified',NULL,'2026-11-16 08:00:00','Fictional development fixture, pre-verified for fit-score testing. Not a real qualification.','2026-11-16 08:00:00','2026-11-16 08:00:00');
INSERT INTO `skills` (`id`,`user_id`,`name`,`category`,`proficiency`,`description`,`status`,`reviewed_by`,`reviewed_at`,`admin_remarks`,`created_at`,`updated_at`) VALUES ('3','1','PHP','web-development','advanced','Demonstrated in the fictional campus application portfolio. Seeded for development testing.','verified',NULL,'2026-11-16 08:00:00','Fictional development fixture, pre-verified for fit-score testing. Not a real qualification.','2026-11-16 08:00:00','2026-11-16 08:00:00');
INSERT INTO `skills` (`id`,`user_id`,`name`,`category`,`proficiency`,`description`,`status`,`reviewed_by`,`reviewed_at`,`admin_remarks`,`created_at`,`updated_at`) VALUES ('4','1','Laravel','web-development','advanced','Demonstrated in the fictional campus application portfolio. Seeded for development testing.','verified',NULL,'2026-11-16 08:00:00','Fictional development fixture, pre-verified for fit-score testing. Not a real qualification.','2026-11-16 08:00:00','2026-11-16 08:00:00');
INSERT INTO `skills` (`id`,`user_id`,`name`,`category`,`proficiency`,`description`,`status`,`reviewed_by`,`reviewed_at`,`admin_remarks`,`created_at`,`updated_at`) VALUES ('5','1','Vue','web-development','advanced','Demonstrated in the fictional campus application portfolio. Seeded for development testing.','verified',NULL,'2026-11-16 08:00:00','Fictional development fixture, pre-verified for fit-score testing. Not a real qualification.','2026-11-16 08:00:00','2026-11-16 08:00:00');
INSERT INTO `skills` (`id`,`user_id`,`name`,`category`,`proficiency`,`description`,`status`,`reviewed_by`,`reviewed_at`,`admin_remarks`,`created_at`,`updated_at`) VALUES ('6','1','React','web-development','advanced','Demonstrated in the fictional campus application portfolio. Seeded for development testing.','verified',NULL,'2026-11-16 08:00:00','Fictional development fixture, pre-verified for fit-score testing. Not a real qualification.','2026-11-16 08:00:00','2026-11-16 08:00:00');
INSERT INTO `skills` (`id`,`user_id`,`name`,`category`,`proficiency`,`description`,`status`,`reviewed_by`,`reviewed_at`,`admin_remarks`,`created_at`,`updated_at`) VALUES ('7','1','HTML','web-development','advanced','Demonstrated in the fictional campus application portfolio. Seeded for development testing.','verified',NULL,'2026-11-16 08:00:00','Fictional development fixture, pre-verified for fit-score testing. Not a real qualification.','2026-11-16 08:00:00','2026-11-16 08:00:00');
INSERT INTO `skills` (`id`,`user_id`,`name`,`category`,`proficiency`,`description`,`status`,`reviewed_by`,`reviewed_at`,`admin_remarks`,`created_at`,`updated_at`) VALUES ('8','1','CSS','web-development','advanced','Demonstrated in the fictional campus application portfolio. Seeded for development testing.','verified',NULL,'2026-11-16 08:00:00','Fictional development fixture, pre-verified for fit-score testing. Not a real qualification.','2026-11-16 08:00:00','2026-11-16 08:00:00');
INSERT INTO `skills` (`id`,`user_id`,`name`,`category`,`proficiency`,`description`,`status`,`reviewed_by`,`reviewed_at`,`admin_remarks`,`created_at`,`updated_at`) VALUES ('9','1','SQL','databases','advanced','Demonstrated in the fictional campus application portfolio. Seeded for development testing.','verified',NULL,'2026-11-16 08:00:00','Fictional development fixture, pre-verified for fit-score testing. Not a real qualification.','2026-11-16 08:00:00','2026-11-16 08:00:00');
INSERT INTO `skills` (`id`,`user_id`,`name`,`category`,`proficiency`,`description`,`status`,`reviewed_by`,`reviewed_at`,`admin_remarks`,`created_at`,`updated_at`) VALUES ('10','1','MySQL','databases','advanced','Demonstrated in the fictional campus application portfolio. Seeded for development testing.','verified',NULL,'2026-11-16 08:00:00','Fictional development fixture, pre-verified for fit-score testing. Not a real qualification.','2026-11-16 08:00:00','2026-11-16 08:00:00');
INSERT INTO `skills` (`id`,`user_id`,`name`,`category`,`proficiency`,`description`,`status`,`reviewed_by`,`reviewed_at`,`admin_remarks`,`created_at`,`updated_at`) VALUES ('11','1','Git','web-development','advanced','Demonstrated in the fictional campus application portfolio. Seeded for development testing.','verified',NULL,'2026-11-16 08:00:00','Fictional development fixture, pre-verified for fit-score testing. Not a real qualification.','2026-11-16 08:00:00','2026-11-16 08:00:00');
INSERT INTO `skills` (`id`,`user_id`,`name`,`category`,`proficiency`,`description`,`status`,`reviewed_by`,`reviewed_at`,`admin_remarks`,`created_at`,`updated_at`) VALUES ('12','1','REST API','web-development','advanced','Demonstrated in the fictional campus application portfolio. Seeded for development testing.','verified',NULL,'2026-11-16 08:00:00','Fictional development fixture, pre-verified for fit-score testing. Not a real qualification.','2026-11-16 08:00:00','2026-11-16 08:00:00');
INSERT INTO `skills` (`id`,`user_id`,`name`,`category`,`proficiency`,`description`,`status`,`reviewed_by`,`reviewed_at`,`admin_remarks`,`created_at`,`updated_at`) VALUES ('13','1','Python','web-development','advanced','Demonstrated in the fictional campus application portfolio. Seeded for development testing.','verified',NULL,'2026-11-16 08:00:00','Fictional development fixture, pre-verified for fit-score testing. Not a real qualification.','2026-11-16 08:00:00','2026-11-16 08:00:00');
INSERT INTO `skills` (`id`,`user_id`,`name`,`category`,`proficiency`,`description`,`status`,`reviewed_by`,`reviewed_at`,`admin_remarks`,`created_at`,`updated_at`) VALUES ('14','1','Docker','web-development','advanced','Demonstrated in the fictional campus application portfolio. Seeded for development testing.','verified',NULL,'2026-11-16 08:00:00','Fictional development fixture, pre-verified for fit-score testing. Not a real qualification.','2026-11-16 08:00:00','2026-11-16 08:00:00');
INSERT INTO `skills` (`id`,`user_id`,`name`,`category`,`proficiency`,`description`,`status`,`reviewed_by`,`reviewed_at`,`admin_remarks`,`created_at`,`updated_at`) VALUES ('15','1','Communication','soft-skills','advanced','Demonstrated in the fictional campus application portfolio. Seeded for development testing.','verified',NULL,'2026-11-16 08:00:00','Fictional development fixture, pre-verified for fit-score testing. Not a real qualification.','2026-11-16 08:00:00','2026-11-16 08:00:00');
INSERT INTO `skills` (`id`,`user_id`,`name`,`category`,`proficiency`,`description`,`status`,`reviewed_by`,`reviewed_at`,`admin_remarks`,`created_at`,`updated_at`) VALUES ('16','1','Teamwork','soft-skills','advanced','Demonstrated in the fictional campus application portfolio. Seeded for development testing.','verified',NULL,'2026-11-16 08:00:00','Fictional development fixture, pre-verified for fit-score testing. Not a real qualification.','2026-11-16 08:00:00','2026-11-16 08:00:00');
INSERT INTO `projects` (`id`,`user_id`,`title`,`role`,`description`,`technologies`,`completion_date`,`submitted_at`,`submission_revision`,`status`,`reviewed_by`,`reviewed_at`,`admin_remarks`,`created_at`,`updated_at`) VALUES ('1','1','Campus laboratory booking (Demo)','Full-stack developer','Built a responsive laboratory booking application with authentication, role permissions, collision checks, email reminders and automated tests. This is a fictional demonstration project.','[\"Laravel\",\"PHP\",\"Vue\",\"TypeScript\",\"MySQL\",\"SQL\",\"REST API\",\"Git\"]','2026-10-15','2026-11-16 08:00:00','1','verified',NULL,'2026-11-16 08:00:00','Fictional development fixture, pre-verified for fit-score testing. Not a real qualification.','2026-11-16 08:00:00','2026-11-16 08:00:00');
INSERT INTO `projects` (`id`,`user_id`,`title`,`role`,`description`,`technologies`,`completion_date`,`submitted_at`,`submission_revision`,`status`,`reviewed_by`,`reviewed_at`,`admin_remarks`,`created_at`,`updated_at`) VALUES ('2','1','Student society inventory (Demo)','Frontend developer','Created an accessible React inventory dashboard with reusable components, search, keyboard navigation and form validation. Worked with a three-person student team. Fictional demonstration project.','[\"React\",\"JavaScript\",\"HTML\",\"CSS\",\"Communication\",\"Teamwork\"]','2026-10-15','2026-11-16 08:00:00','1','verified',NULL,'2026-11-16 08:00:00','Fictional development fixture, pre-verified for fit-score testing. Not a real qualification.','2026-11-16 08:00:00','2026-11-16 08:00:00');
INSERT INTO `projects` (`id`,`user_id`,`title`,`role`,`description`,`technologies`,`completion_date`,`submitted_at`,`submission_revision`,`status`,`reviewed_by`,`reviewed_at`,`admin_remarks`,`created_at`,`updated_at`) VALUES ('3','1','Inventory data quality checks (Demo)','Developer','Wrote Python scripts to validate inventory records, generated a quality report and packaged the development environment with Docker. Fictional demonstration project.','[\"Python\",\"Docker\",\"SQL\",\"Git\"]','2026-10-15','2026-11-16 08:00:00','1','verified',NULL,'2026-11-16 08:00:00','Fictional development fixture, pre-verified for fit-score testing. Not a real qualification.','2026-11-16 08:00:00','2026-11-16 08:00:00');
INSERT INTO `certifications` (`id`,`user_id`,`title`,`issuing_organization`,`issue_date`,`credential_id`,`submitted_at`,`submission_revision`,`status`,`reviewed_by`,`reviewed_at`,`admin_remarks`,`created_at`,`updated_at`) VALUES ('1','1','Web Development Practice Certificate (Demo)','Skills & Career Demo Lab — fictional issuer','2026-09-01','DEMO-ONLY-1','2026-11-16 08:00:00','1','verified',NULL,'2026-11-16 08:00:00','Fictional development fixture, pre-verified for fit-score testing. Not a real qualification.','2026-11-16 08:00:00','2026-11-16 08:00:00');
INSERT INTO `certifications` (`id`,`user_id`,`title`,`issuing_organization`,`issue_date`,`credential_id`,`submitted_at`,`submission_revision`,`status`,`reviewed_by`,`reviewed_at`,`admin_remarks`,`created_at`,`updated_at`) VALUES ('2','1','Database Design Practice Certificate (Demo)','Skills & Career Demo Lab — fictional issuer','2026-09-01','DEMO-ONLY-2','2026-11-16 08:00:00','1','verified',NULL,'2026-11-16 08:00:00','Fictional development fixture, pre-verified for fit-score testing. Not a real qualification.','2026-11-16 08:00:00','2026-11-16 08:00:00');
INSERT INTO `project_skill` (`project_id`,`skill_id`) VALUES ('1','4');
INSERT INTO `project_skill` (`project_id`,`skill_id`) VALUES ('1','3');
INSERT INTO `project_skill` (`project_id`,`skill_id`) VALUES ('1','5');
INSERT INTO `project_skill` (`project_id`,`skill_id`) VALUES ('1','2');
INSERT INTO `project_skill` (`project_id`,`skill_id`) VALUES ('1','10');
INSERT INTO `project_skill` (`project_id`,`skill_id`) VALUES ('1','9');
INSERT INTO `project_skill` (`project_id`,`skill_id`) VALUES ('1','12');
INSERT INTO `project_skill` (`project_id`,`skill_id`) VALUES ('1','11');
INSERT INTO `project_skill` (`project_id`,`skill_id`) VALUES ('2','6');
INSERT INTO `project_skill` (`project_id`,`skill_id`) VALUES ('2','1');
INSERT INTO `project_skill` (`project_id`,`skill_id`) VALUES ('2','7');
INSERT INTO `project_skill` (`project_id`,`skill_id`) VALUES ('2','8');
INSERT INTO `project_skill` (`project_id`,`skill_id`) VALUES ('2','15');
INSERT INTO `project_skill` (`project_id`,`skill_id`) VALUES ('2','16');
INSERT INTO `project_skill` (`project_id`,`skill_id`) VALUES ('3','13');
INSERT INTO `project_skill` (`project_id`,`skill_id`) VALUES ('3','14');
INSERT INTO `project_skill` (`project_id`,`skill_id`) VALUES ('3','9');
INSERT INTO `project_skill` (`project_id`,`skill_id`) VALUES ('3','11');
INSERT INTO `certification_skill` (`certification_id`,`skill_id`) VALUES ('1','1');
INSERT INTO `certification_skill` (`certification_id`,`skill_id`) VALUES ('1','7');
INSERT INTO `certification_skill` (`certification_id`,`skill_id`) VALUES ('1','8');
INSERT INTO `certification_skill` (`certification_id`,`skill_id`) VALUES ('1','5');
INSERT INTO `certification_skill` (`certification_id`,`skill_id`) VALUES ('2','9');
INSERT INTO `certification_skill` (`certification_id`,`skill_id`) VALUES ('2','10');
INSERT INTO `job_preferences` (`id`,`user_id`,`preferred_fields`,`preferred_locations`,`work_types`,`accepts_remote`,`minimum_salary`,`maximum_salary`,`created_at`,`updated_at`) VALUES ('1','1','[\"developer\"]','[\"Kuala Lumpur\",\"Selangor\",\"Melaka\"]','[\"full-time\"]','1','3000','6000','2026-11-16 08:00:00','2026-11-16 08:00:00');
INSERT INTO `assessment_attempts` (`id`,`assessment_id`,`user_id`,`score`,`maximum_score`,`percentage`,`passed`,`started_at`,`submitted_at`,`assessment_snapshot`,`created_at`,`updated_at`) VALUES ('1','1','1','10','10','100','1','2026-11-16 07:50:00','2026-11-16 08:00:00','{\"title\":\"JavaScript Fundamentals\",\"category\":\"programming\",\"description\":\"Test JavaScript syntax, functions, arrays, asynchronous work, and browser events.\",\"version\":1,\"family_id\":1,\"skill_names\":[\"JavaScript\"],\"time_limit\":15,\"passing_score\":60,\"questions\":[{\"id\":1,\"question\":\"Which keyword declares a constant in JavaScript?\",\"options\":[\"var\",\"let\",\"const\",\"static\"],\"correct_option\":2,\"points\":1},{\"id\":2,\"question\":\"Which method converts a JSON string into an object?\",\"options\":[\"JSON.stringify()\",\"JSON.parse()\",\"JSON.convert()\",\"JSON.decode()\"],\"correct_option\":1,\"points\":1},{\"id\":3,\"question\":\"What does the strict equality operator compare?\",\"options\":[\"Value only\",\"Type only\",\"Value and type\",\"Object names\"],\"correct_option\":2,\"points\":1},{\"id\":4,\"question\":\"What is the scope of a variable declared with let inside a block?\",\"options\":[\"Global\",\"Function only\",\"Block\",\"Module only\"],\"correct_option\":2,\"points\":1},{\"id\":5,\"question\":\"Which array method creates a new array by transforming each element?\",\"options\":[\"forEach()\",\"map()\",\"filter()\",\"push()\"],\"correct_option\":1,\"points\":1},{\"id\":6,\"question\":\"What does an async function always return?\",\"options\":[\"A string\",\"A Promise\",\"A callback\",\"An array\"],\"correct_option\":1,\"points\":1},{\"id\":7,\"question\":\"Which method prevents the default browser action for an event?\",\"options\":[\"stopPropagation()\",\"preventDefault()\",\"removeEventListener()\",\"dispatchEvent()\"],\"correct_option\":1,\"points\":1},{\"id\":8,\"question\":\"What does optional chaining return when a property before ?. is null or undefined?\",\"options\":[\"An exception\",\"false\",\"undefined\",\"An empty object\"],\"correct_option\":2,\"points\":1},{\"id\":9,\"question\":\"What happens when one input Promise rejects in Promise.all()?\",\"options\":[\"The combined Promise rejects\",\"The rejected input is ignored\",\"The combined Promise resolves with null\",\"All inputs restart\"],\"correct_option\":0,\"points\":1},{\"id\":10,\"question\":\"Which statement about arrow functions is correct?\",\"options\":[\"They bind their own this\",\"They inherit this from the surrounding scope\",\"They cannot return values\",\"They must be asynchronous\"],\"correct_option\":1,\"points\":1}]}','2026-11-16 08:00:00','2026-11-16 08:00:00');
INSERT INTO `assessment_attempts` (`id`,`assessment_id`,`user_id`,`score`,`maximum_score`,`percentage`,`passed`,`started_at`,`submitted_at`,`assessment_snapshot`,`created_at`,`updated_at`) VALUES ('2','2','1','10','10','100','1','2026-11-16 07:50:00','2026-11-16 08:00:00','{\"title\":\"SQL and Database Basics\",\"category\":\"databases\",\"description\":\"Assess SQL queries, joins, grouping, keys, filtering, and transactions.\",\"version\":1,\"family_id\":2,\"skill_names\":[\"SQL\"],\"time_limit\":15,\"passing_score\":60,\"questions\":[{\"id\":11,\"question\":\"Which statement retrieves rows from a database?\",\"options\":[\"GET\",\"FETCH\",\"SELECT\",\"RETRIEVE\"],\"correct_option\":2,\"points\":1},{\"id\":12,\"question\":\"What is the purpose of a primary key?\",\"options\":[\"Sort every query\",\"Identify each row uniquely\",\"Encrypt a table\",\"Join every table\"],\"correct_option\":1,\"points\":1},{\"id\":13,\"question\":\"Which clause filters grouped results?\",\"options\":[\"WHERE\",\"HAVING\",\"ORDER BY\",\"LIMIT\"],\"correct_option\":1,\"points\":1},{\"id\":14,\"question\":\"Which join keeps every row from the left table even without a match?\",\"options\":[\"INNER JOIN\",\"LEFT JOIN\",\"CROSS JOIN\",\"SELF JOIN\"],\"correct_option\":1,\"points\":1},{\"id\":15,\"question\":\"Which clause groups rows before aggregate functions are applied?\",\"options\":[\"GROUP BY\",\"ORDER BY\",\"LIMIT\",\"DISTINCT\"],\"correct_option\":0,\"points\":1},{\"id\":16,\"question\":\"Which function counts rows in a result group?\",\"options\":[\"SUM(*)\",\"COUNT(*)\",\"TOTAL(*)\",\"NUMBER(*)\"],\"correct_option\":1,\"points\":1},{\"id\":17,\"question\":\"Which clause filters individual rows before grouping?\",\"options\":[\"HAVING\",\"GROUP BY\",\"WHERE\",\"ORDER BY\"],\"correct_option\":2,\"points\":1},{\"id\":18,\"question\":\"What does a foreign key normally reference?\",\"options\":[\"A table name\",\"A key in another table\",\"A database password\",\"A query result\"],\"correct_option\":1,\"points\":1},{\"id\":19,\"question\":\"Which expression sorts results from highest to lowest value?\",\"options\":[\"ORDER BY score ASC\",\"ORDER BY score DESC\",\"GROUP BY score\",\"WHERE score DESC\"],\"correct_option\":1,\"points\":1},{\"id\":20,\"question\":\"What does ROLLBACK do inside a transaction?\",\"options\":[\"Commits pending changes\",\"Undoes uncommitted changes\",\"Deletes the database\",\"Creates a backup\"],\"correct_option\":1,\"points\":1}]}','2026-11-16 08:00:00','2026-11-16 08:00:00');
INSERT INTO `assessment_answers` (`id`,`attempt_id`,`question_id`,`selected_option`,`is_correct`,`points_awarded`,`created_at`,`updated_at`) VALUES ('1','1','1','2','1','1','2026-11-16 08:00:00','2026-11-16 08:00:00');
INSERT INTO `assessment_answers` (`id`,`attempt_id`,`question_id`,`selected_option`,`is_correct`,`points_awarded`,`created_at`,`updated_at`) VALUES ('2','1','2','1','1','1','2026-11-16 08:00:00','2026-11-16 08:00:00');
INSERT INTO `assessment_answers` (`id`,`attempt_id`,`question_id`,`selected_option`,`is_correct`,`points_awarded`,`created_at`,`updated_at`) VALUES ('3','1','3','2','1','1','2026-11-16 08:00:00','2026-11-16 08:00:00');
INSERT INTO `assessment_answers` (`id`,`attempt_id`,`question_id`,`selected_option`,`is_correct`,`points_awarded`,`created_at`,`updated_at`) VALUES ('4','1','4','2','1','1','2026-11-16 08:00:00','2026-11-16 08:00:00');
INSERT INTO `assessment_answers` (`id`,`attempt_id`,`question_id`,`selected_option`,`is_correct`,`points_awarded`,`created_at`,`updated_at`) VALUES ('5','1','5','1','1','1','2026-11-16 08:00:00','2026-11-16 08:00:00');
INSERT INTO `assessment_answers` (`id`,`attempt_id`,`question_id`,`selected_option`,`is_correct`,`points_awarded`,`created_at`,`updated_at`) VALUES ('6','1','6','1','1','1','2026-11-16 08:00:00','2026-11-16 08:00:00');
INSERT INTO `assessment_answers` (`id`,`attempt_id`,`question_id`,`selected_option`,`is_correct`,`points_awarded`,`created_at`,`updated_at`) VALUES ('7','1','7','1','1','1','2026-11-16 08:00:00','2026-11-16 08:00:00');
INSERT INTO `assessment_answers` (`id`,`attempt_id`,`question_id`,`selected_option`,`is_correct`,`points_awarded`,`created_at`,`updated_at`) VALUES ('8','1','8','2','1','1','2026-11-16 08:00:00','2026-11-16 08:00:00');
INSERT INTO `assessment_answers` (`id`,`attempt_id`,`question_id`,`selected_option`,`is_correct`,`points_awarded`,`created_at`,`updated_at`) VALUES ('9','1','9','0','1','1','2026-11-16 08:00:00','2026-11-16 08:00:00');
INSERT INTO `assessment_answers` (`id`,`attempt_id`,`question_id`,`selected_option`,`is_correct`,`points_awarded`,`created_at`,`updated_at`) VALUES ('10','1','10','1','1','1','2026-11-16 08:00:00','2026-11-16 08:00:00');
INSERT INTO `assessment_answers` (`id`,`attempt_id`,`question_id`,`selected_option`,`is_correct`,`points_awarded`,`created_at`,`updated_at`) VALUES ('11','2','11','2','1','1','2026-11-16 08:00:00','2026-11-16 08:00:00');
INSERT INTO `assessment_answers` (`id`,`attempt_id`,`question_id`,`selected_option`,`is_correct`,`points_awarded`,`created_at`,`updated_at`) VALUES ('12','2','12','1','1','1','2026-11-16 08:00:00','2026-11-16 08:00:00');
INSERT INTO `assessment_answers` (`id`,`attempt_id`,`question_id`,`selected_option`,`is_correct`,`points_awarded`,`created_at`,`updated_at`) VALUES ('13','2','13','1','1','1','2026-11-16 08:00:00','2026-11-16 08:00:00');
INSERT INTO `assessment_answers` (`id`,`attempt_id`,`question_id`,`selected_option`,`is_correct`,`points_awarded`,`created_at`,`updated_at`) VALUES ('14','2','14','1','1','1','2026-11-16 08:00:00','2026-11-16 08:00:00');
INSERT INTO `assessment_answers` (`id`,`attempt_id`,`question_id`,`selected_option`,`is_correct`,`points_awarded`,`created_at`,`updated_at`) VALUES ('15','2','15','0','1','1','2026-11-16 08:00:00','2026-11-16 08:00:00');
INSERT INTO `assessment_answers` (`id`,`attempt_id`,`question_id`,`selected_option`,`is_correct`,`points_awarded`,`created_at`,`updated_at`) VALUES ('16','2','16','1','1','1','2026-11-16 08:00:00','2026-11-16 08:00:00');
INSERT INTO `assessment_answers` (`id`,`attempt_id`,`question_id`,`selected_option`,`is_correct`,`points_awarded`,`created_at`,`updated_at`) VALUES ('17','2','17','2','1','1','2026-11-16 08:00:00','2026-11-16 08:00:00');
INSERT INTO `assessment_answers` (`id`,`attempt_id`,`question_id`,`selected_option`,`is_correct`,`points_awarded`,`created_at`,`updated_at`) VALUES ('18','2','18','1','1','1','2026-11-16 08:00:00','2026-11-16 08:00:00');
INSERT INTO `assessment_answers` (`id`,`attempt_id`,`question_id`,`selected_option`,`is_correct`,`points_awarded`,`created_at`,`updated_at`) VALUES ('19','2','19','1','1','1','2026-11-16 08:00:00','2026-11-16 08:00:00');
INSERT INTO `assessment_answers` (`id`,`attempt_id`,`question_id`,`selected_option`,`is_correct`,`points_awarded`,`created_at`,`updated_at`) VALUES ('20','2','20','1','1','1','2026-11-16 08:00:00','2026-11-16 08:00:00');
INSERT INTO `generated_cvs` (`id`,`user_id`,`title`,`version`,`status`,`source_type`,`model_name`,`content`,`created_at`,`updated_at`) VALUES ('1','1','Aina Rahman — General CV (Demo)','1','draft','profile','authored-demo-fixture','{\"personal\":{\"name\":\"Aina Rahman (Demo)\",\"email\":\"demo@utem.edu.my\",\"phone\":\"+(60) - 123456789\",\"location\":\"Kuala Lumpur, Malaysia\",\"headline\":\"Junior full-stack developer\",\"website\":\"\"},\"summary\":\"Software development student experienced in Laravel, Vue, React and SQL through campus application projects. Seeking a junior developer role in Malaysia. This entire profile is fictional demonstration data.\",\"skills\":[\"JavaScript\",\"TypeScript\",\"PHP\",\"Laravel\",\"Vue\",\"React\",\"HTML\",\"CSS\",\"SQL\",\"MySQL\",\"Git\",\"REST API\",\"Python\",\"Docker\",\"Communication\",\"Teamwork\"],\"education\":[{\"institution\":\"Demo University Malaysia (fictional)\",\"qualification\":\"Bachelor of Computer Science (Software Development)\",\"period\":\"2023–2027\",\"details\":\"CGPA 3.80 (demonstration data)\"}],\"experience\":[{\"role\":\"Web development intern (Demo)\",\"company\":\"Demo Campus Technology Lab (fictional)\",\"period\":\"June–September 2026\",\"description\":\"Implemented booking validation, wrote API tests and documented the deployment process in a supervised practice project.\"}],\"projects\":[{\"title\":\"Campus laboratory booking (Demo)\",\"description\":\"Built a responsive laboratory booking application with authentication, role permissions, collision checks, email reminders and automated tests. This is a fictional demonstration project.\",\"technologies\":[\"Laravel\",\"PHP\",\"Vue\",\"TypeScript\",\"MySQL\",\"SQL\",\"REST API\",\"Git\"]},{\"title\":\"Student society inventory (Demo)\",\"description\":\"Created an accessible React inventory dashboard with reusable components, search, keyboard navigation and form validation. Worked with a three-person student team. Fictional demonstration project.\",\"technologies\":[\"React\",\"JavaScript\",\"HTML\",\"CSS\",\"Communication\",\"Teamwork\"]},{\"title\":\"Inventory data quality checks (Demo)\",\"description\":\"Wrote Python scripts to validate inventory records, generated a quality report and packaged the development environment with Docker. Fictional demonstration project.\",\"technologies\":[\"Python\",\"Docker\",\"SQL\",\"Git\"]}],\"certifications\":[{\"title\":\"Web Development Practice Certificate (Demo)\",\"issuer\":\"Skills & Career Demo Lab — fictional issuer\",\"issueDate\":\"2026-09-01\"},{\"title\":\"Database Design Practice Certificate (Demo)\",\"issuer\":\"Skills & Career Demo Lab — fictional issuer\",\"issueDate\":\"2026-09-01\"}],\"assessments\":[{\"title\":\"JavaScript Fundamentals (demo result)\",\"percentage\":100,\"completedAt\":\"2026-11-16\"},{\"title\":\"SQL and Database Basics (demo result)\",\"percentage\":100,\"completedAt\":\"2026-11-16\"}],\"additional\":\"Languages: Bahasa Melayu, English.\\nAll qualifications and results in this account are fictional fixtures for development testing.\"}','2026-11-16 08:00:00','2026-11-16 08:00:00');

SET FOREIGN_KEY_CHECKS = 1;
