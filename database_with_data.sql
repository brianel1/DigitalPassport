-- DigitalPassport Database
-- Generated: 2026-11-03 13:36:13
-- Laravel migrations applied automatically

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS `digitalpassport` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `digitalpassport`;

DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `personal_access_tokens`;
DROP TABLE IF EXISTS `sessions`;
DROP TABLE IF EXISTS `cache`;
DROP TABLE IF EXISTS `cache_locks`;
DROP TABLE IF EXISTS `jobs`;
DROP TABLE IF EXISTS `job_batches`;
DROP TABLE IF EXISTS `failed_jobs`;
DROP TABLE IF EXISTS `student_profiles`;
DROP TABLE IF EXISTS `skills`;
DROP TABLE IF EXISTS `projects`;
DROP TABLE IF EXISTS `certifications`;
DROP TABLE IF EXISTS `project_skill`;
DROP TABLE IF EXISTS `certification_skill`;
DROP TABLE IF EXISTS `evidence_files`;
DROP TABLE IF EXISTS `verification_reviews`;
DROP TABLE IF EXISTS `assessments`;
DROP TABLE IF EXISTS `assessment_questions`;
DROP TABLE IF EXISTS `assessment_attempts`;
DROP TABLE IF EXISTS `assessment_answers`;
DROP TABLE IF EXISTS `passport_exports`;
DROP TABLE IF EXISTS `job_preferences`;
DROP TABLE IF EXISTS `job_listings`;
DROP TABLE IF EXISTS `job_recommendation_runs`;
DROP TABLE IF EXISTS `job_matches`;
DROP TABLE IF EXISTS `generated_cvs`;
DROP TABLE IF EXISTS `cv_exports`;

DROP TABLE IF EXISTS `migrations`;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('1', '0001_01_01_000000_create_users_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('2', '0001_01_01_000001_create_cache_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('3', '0001_01_01_000002_create_jobs_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('4', '2026_10_27_131708_create_personal_access_tokens_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('5', '2026_11_03_000000_add_role_and_student_fields_to_users_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('6', '2026_11_03_010000_create_portfolio_and_assessment_tables', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('7', '2026_11_03_020000_create_job_and_cv_tables', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('8', '2026_11_04_000000_add_active_status_to_users_table', '1');

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
  KEY `users_role_index` (`role`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  PRIMARY KEY (`id`),
  KEY `projects_user_id_foreign` (`user_id`),
  KEY `projects_reviewed_by_foreign` (`reviewed_by`),
  KEY `projects_status_index` (`status`),
  CONSTRAINT `projects_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `projects_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  PRIMARY KEY (`id`),
  KEY `certifications_user_id_foreign` (`user_id`),
  KEY `certifications_reviewed_by_foreign` (`reviewed_by`),
  KEY `certifications_status_index` (`status`),
  CONSTRAINT `certifications_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `certifications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `project_skill` (
  `project_id` bigint(20) unsigned NOT NULL,
  `skill_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`project_id`,`skill_id`),
  KEY `project_skill_skill_id_foreign` (`skill_id`),
  CONSTRAINT `project_skill_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `project_skill_skill_id_foreign` FOREIGN KEY (`skill_id`) REFERENCES `skills` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `certification_skill` (
  `certification_id` bigint(20) unsigned NOT NULL,
  `skill_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`certification_id`,`skill_id`),
  KEY `certification_skill_skill_id_foreign` (`skill_id`),
  CONSTRAINT `certification_skill_certification_id_foreign` FOREIGN KEY (`certification_id`) REFERENCES `certifications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `certification_skill_skill_id_foreign` FOREIGN KEY (`skill_id`) REFERENCES `skills` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

CREATE TABLE `verification_reviews` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `reviewer_id` bigint(20) unsigned NOT NULL,
  `reviewable_type` varchar(255) NOT NULL,
  `reviewable_id` bigint(20) unsigned NOT NULL,
  `decision` varchar(20) NOT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `verification_reviews_reviewer_id_foreign` (`reviewer_id`),
  KEY `verification_reviews_reviewable_type_reviewable_id_index` (`reviewable_type`,`reviewable_id`),
  CONSTRAINT `verification_reviews_reviewer_id_foreign` FOREIGN KEY (`reviewer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  PRIMARY KEY (`id`),
  KEY `assessments_created_by_foreign` (`created_by`),
  KEY `assessments_is_published_index` (`is_published`),
  CONSTRAINT `assessments_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  PRIMARY KEY (`id`),
  KEY `assessment_attempts_assessment_id_foreign` (`assessment_id`),
  KEY `assessment_attempts_user_id_assessment_id_index` (`user_id`,`assessment_id`),
  CONSTRAINT `assessment_attempts_assessment_id_foreign` FOREIGN KEY (`assessment_id`) REFERENCES `assessments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `assessment_attempts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

CREATE TABLE `job_preferences` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `preferred_fields` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`preferred_fields`)),
  `preferred_locations` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`preferred_locations`)),
  `work_types` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`work_types`)),
  `accepts_remote` tinyint(1) NOT NULL DEFAULT 1,
  `minimum_salary` int(10) unsigned DEFAULT NULL,
  `maximum_salary` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `job_preferences_user_id_unique` (`user_id`),
  CONSTRAINT `job_preferences_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  PRIMARY KEY (`id`),
  UNIQUE KEY `job_listings_source_external_id_unique` (`source`,`external_id`),
  KEY `job_listings_title_location_index` (`title`,`location`),
  KEY `job_listings_source_index` (`source`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  PRIMARY KEY (`id`),
  KEY `job_recommendation_runs_user_id_foreign` (`user_id`),
  CONSTRAINT `job_recommendation_runs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  PRIMARY KEY (`id`),
  UNIQUE KEY `job_matches_recommendation_run_id_job_listing_id_unique` (`recommendation_run_id`,`job_listing_id`),
  KEY `job_matches_job_listing_id_foreign` (`job_listing_id`),
  KEY `job_matches_recommendation_run_id_rank_index` (`recommendation_run_id`,`rank`),
  CONSTRAINT `job_matches_job_listing_id_foreign` FOREIGN KEY (`job_listing_id`) REFERENCES `job_listings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `job_matches_recommendation_run_id_foreign` FOREIGN KEY (`recommendation_run_id`) REFERENCES `job_recommendation_runs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  PRIMARY KEY (`id`),
  KEY `generated_cvs_target_job_listing_id_foreign` (`target_job_listing_id`),
  KEY `generated_cvs_user_id_updated_at_index` (`user_id`,`updated_at`),
  CONSTRAINT `generated_cvs_target_job_listing_id_foreign` FOREIGN KEY (`target_job_listing_id`) REFERENCES `job_listings` (`id`) ON DELETE SET NULL,
  CONSTRAINT `generated_cvs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

-- Data for users
INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `role`, `is_active`, `programme`, `matric_no`, `remember_token`, `created_at`, `updated_at`) VALUES ('1', 'Elber Anselmus', 'user@student.utem.edu.my', NULL, '$2y$12$TSARXkAH8wyBg1S3VDT/ZO95uBPp9xcQDU64H17VUtNIIh0.ovfJC', 'student', 1, 'BITS', 'B032510581', NULL, '2026-11-03 13:31:42', '2026-11-03 13:31:42');
INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `role`, `is_active`, `programme`, `matric_no`, `remember_token`, `created_at`, `updated_at`) VALUES ('2', 'Admin DigitalPassport', 'admin@utem.edu.my', NULL, '$2y$12$XYuhcpU3OB9enfpA79irw.lETkHmkBpEdarAW.pJQkFMWK75uyVty', 'admin', 1, NULL, NULL, NULL, '2026-11-03 13:31:42', '2026-11-03 13:31:42');

-- Data for skills
INSERT INTO `skills` (`id`, `user_id`, `name`, `category`, `proficiency`, `description`, `status`, `reviewed_by`, `reviewed_at`, `admin_remarks`, `created_at`, `updated_at`) VALUES ('1', '1', 'PHP', 'programming', 'advanced', NULL, 'verified', NULL, NULL, NULL, '2026-11-03 13:31:42', '2026-11-03 13:31:42');
INSERT INTO `skills` (`id`, `user_id`, `name`, `category`, `proficiency`, `description`, `status`, `reviewed_by`, `reviewed_at`, `admin_remarks`, `created_at`, `updated_at`) VALUES ('2', '1', 'Laravel', 'web-development', 'intermediate', NULL, 'verified', NULL, NULL, NULL, '2026-11-03 13:31:42', '2026-11-03 13:31:42');
INSERT INTO `skills` (`id`, `user_id`, `name`, `category`, `proficiency`, `description`, `status`, `reviewed_by`, `reviewed_at`, `admin_remarks`, `created_at`, `updated_at`) VALUES ('3', '1', 'MySQL', 'databases', 'intermediate', NULL, 'verified', NULL, NULL, NULL, '2026-11-03 13:31:42', '2026-11-03 13:31:42');
INSERT INTO `skills` (`id`, `user_id`, `name`, `category`, `proficiency`, `description`, `status`, `reviewed_by`, `reviewed_at`, `admin_remarks`, `created_at`, `updated_at`) VALUES ('4', '1', 'JavaScript', 'programming', 'intermediate', NULL, 'verified', NULL, NULL, NULL, '2026-11-03 13:31:42', '2026-11-03 13:31:42');
INSERT INTO `skills` (`id`, `user_id`, `name`, `category`, `proficiency`, `description`, `status`, `reviewed_by`, `reviewed_at`, `admin_remarks`, `created_at`, `updated_at`) VALUES ('5', '1', 'Vue.js', 'web-development', 'intermediate', NULL, 'verified', NULL, NULL, NULL, '2026-11-03 13:31:42', '2026-11-03 13:31:42');

-- Data for assessments
INSERT INTO `assessments` (`id`, `created_by`, `title`, `category`, `description`, `time_limit_minutes`, `passing_score`, `is_published`, `created_at`, `updated_at`) VALUES ('1', NULL, 'JavaScript Fundamentals', 'programming', 'Test core JavaScript knowledge including variables, functions, the DOM, and JSON.', '10', '60', '1', '2026-11-03 13:31:42', '2026-11-03 13:31:42');
INSERT INTO `assessments` (`id`, `created_by`, `title`, `category`, `description`, `time_limit_minutes`, `passing_score`, `is_published`, `created_at`, `updated_at`) VALUES ('2', NULL, 'SQL and Database Basics', 'databases', 'Assess knowledge of SQL queries, table relationships, keys, and data filtering.', '10', '60', '1', '2026-11-03 13:31:42', '2026-11-03 13:31:42');

-- Data for assessment_questions
INSERT INTO `assessment_questions` (`id`, `assessment_id`, `question`, `options`, `correct_option`, `points`, `position`, `created_at`, `updated_at`) VALUES ('1', '1', 'Which keyword declares a constant in JavaScript?', '[\"var\",\"let\",\"const\",\"static\"]', '2', '1', '1', '2026-11-03 13:31:42', '2026-11-03 13:31:42');
INSERT INTO `assessment_questions` (`id`, `assessment_id`, `question`, `options`, `correct_option`, `points`, `position`, `created_at`, `updated_at`) VALUES ('2', '1', 'Which method converts a JSON string into an object?', '[\"JSON.stringify()\",\"JSON.parse()\",\"JSON.convert()\",\"JSON.decode()\"]', '1', '1', '2', '2026-11-03 13:31:42', '2026-11-03 13:31:42');
INSERT INTO `assessment_questions` (`id`, `assessment_id`, `question`, `options`, `correct_option`, `points`, `position`, `created_at`, `updated_at`) VALUES ('3', '1', 'What does the strict equality operator compare?', '[\"Value only\",\"Type only\",\"Value and type\",\"Object names\"]', '2', '1', '3', '2026-11-03 13:31:42', '2026-11-03 13:31:42');
INSERT INTO `assessment_questions` (`id`, `assessment_id`, `question`, `options`, `correct_option`, `points`, `position`, `created_at`, `updated_at`) VALUES ('4', '2', 'Which statement retrieves rows from a database?', '[\"GET\",\"FETCH\",\"SELECT\",\"RETRIEVE\"]', '2', '1', '1', '2026-11-03 13:31:42', '2026-11-03 13:31:42');
INSERT INTO `assessment_questions` (`id`, `assessment_id`, `question`, `options`, `correct_option`, `points`, `position`, `created_at`, `updated_at`) VALUES ('5', '2', 'What is the purpose of a primary key?', '[\"Sort every query\",\"Identify each row uniquely\",\"Encrypt a table\",\"Join every table\"]', '1', '1', '2', '2026-11-03 13:31:42', '2026-11-03 13:31:42');
INSERT INTO `assessment_questions` (`id`, `assessment_id`, `question`, `options`, `correct_option`, `points`, `position`, `created_at`, `updated_at`) VALUES ('6', '2', 'Which clause filters grouped results?', '[\"WHERE\",\"HAVING\",\"ORDER BY\",\"LIMIT\"]', '1', '1', '3', '2026-11-03 13:31:42', '2026-11-03 13:31:42');

-- Data for student_profiles
INSERT INTO `student_profiles` (`id`, `user_id`, `headline`, `bio`, `phone`, `location`, `linkedin_url`, `github_url`, `expected_graduation_date`, `created_at`, `updated_at`) VALUES ('1', '1', 'Information Technology Student | PHP and Laravel Developer', 'Information Technology student at Universiti Teknikal Malaysia Melaka with verified experience in PHP, Laravel, MySQL, Vue.js, REST APIs, and full stack web development. Interested in backend engineering, API integration, and practical software products.', '+60 12-345 6789', 'Melaka, Malaysia', NULL, 'https://github.com/brianel1', '2028-02-29', '2026-11-04 09:00:00', '2026-11-04 09:00:00');

-- Data for additional skills
INSERT INTO `skills` (`id`, `user_id`, `name`, `category`, `proficiency`, `description`, `status`, `reviewed_by`, `reviewed_at`, `admin_remarks`, `created_at`, `updated_at`) VALUES ('6', '1', 'TypeScript', 'programming', 'intermediate', 'Builds typed frontend features and API integrations.', 'verified', '2', '2026-11-04 09:10:00', 'Reviewed against project source code.', '2026-11-04 09:00:00', '2026-11-04 09:10:00');
INSERT INTO `skills` (`id`, `user_id`, `name`, `category`, `proficiency`, `description`, `status`, `reviewed_by`, `reviewed_at`, `admin_remarks`, `created_at`, `updated_at`) VALUES ('7', '1', 'REST APIs', 'backend', 'intermediate', 'Designs JSON endpoints with validation and authentication.', 'verified', '2', '2026-11-04 09:10:00', 'Reviewed against project source code.', '2026-11-04 09:00:00', '2026-11-04 09:10:00');
INSERT INTO `skills` (`id`, `user_id`, `name`, `category`, `proficiency`, `description`, `status`, `reviewed_by`, `reviewed_at`, `admin_remarks`, `created_at`, `updated_at`) VALUES ('8', '1', 'Git and GitHub', 'tools', 'intermediate', 'Uses feature branches, pull requests, and review based workflow.', 'verified', '2', '2026-11-04 09:10:00', 'Reviewed against repository history.', '2026-11-04 09:00:00', '2026-11-04 09:10:00');
INSERT INTO `skills` (`id`, `user_id`, `name`, `category`, `proficiency`, `description`, `status`, `reviewed_by`, `reviewed_at`, `admin_remarks`, `created_at`, `updated_at`) VALUES ('9', '1', 'Tailwind CSS', 'frontend', 'intermediate', 'Creates responsive layouts with utility based styling.', 'verified', '2', '2026-11-04 09:10:00', 'Reviewed against project source code.', '2026-11-04 09:00:00', '2026-11-04 09:10:00');
INSERT INTO `skills` (`id`, `user_id`, `name`, `category`, `proficiency`, `description`, `status`, `reviewed_by`, `reviewed_at`, `admin_remarks`, `created_at`, `updated_at`) VALUES ('10', '1', 'Docker', 'devops', 'beginner', 'Understands local container based development workflows.', 'pending', NULL, NULL, NULL, '2026-11-04 09:00:00', '2026-11-04 09:00:00');
INSERT INTO `skills` (`id`, `user_id`, `name`, `category`, `proficiency`, `description`, `status`, `reviewed_by`, `reviewed_at`, `admin_remarks`, `created_at`, `updated_at`) VALUES ('11', '1', 'Figma', 'design', 'intermediate', 'Creates interface wireframes and visual layout references.', 'verified', '2', '2026-11-04 09:10:00', 'Reviewed against submitted interface work.', '2026-11-04 09:00:00', '2026-11-04 09:10:00');
INSERT INTO `skills` (`id`, `user_id`, `name`, `category`, `proficiency`, `description`, `status`, `reviewed_by`, `reviewed_at`, `admin_remarks`, `created_at`, `updated_at`) VALUES ('12', '1', 'Python', 'programming', 'beginner', 'Uses Python for basic data processing and scripting.', 'verified', '2', '2026-11-04 09:10:00', 'Reviewed from assessment record.', '2026-11-04 09:00:00', '2026-11-04 09:10:00');
INSERT INTO `skills` (`id`, `user_id`, `name`, `category`, `proficiency`, `description`, `status`, `reviewed_by`, `reviewed_at`, `admin_remarks`, `created_at`, `updated_at`) VALUES ('13', '1', 'HTML and CSS', 'frontend', 'advanced', 'Builds accessible responsive page structures and layouts.', 'verified', '2', '2026-11-04 09:10:00', 'Reviewed against project source code.', '2026-11-04 09:00:00', '2026-11-04 09:10:00');
INSERT INTO `skills` (`id`, `user_id`, `name`, `category`, `proficiency`, `description`, `status`, `reviewed_by`, `reviewed_at`, `admin_remarks`, `created_at`, `updated_at`) VALUES ('14', '1', 'PHPUnit', 'testing', 'intermediate', 'Writes focused backend tests for services and API behaviour.', 'pending', NULL, NULL, NULL, '2026-11-04 09:00:00', '2026-11-04 09:00:00');

-- Data for projects
INSERT INTO `projects` (`id`, `user_id`, `title`, `description`, `role`, `technologies`, `project_url`, `repository_url`, `completion_date`, `status`, `reviewed_by`, `reviewed_at`, `admin_remarks`, `created_at`, `updated_at`) VALUES ('1', '1', 'DigitalPassport Student Portfolio', 'Built a verified student portfolio platform with profile management, skill review, assessments, AI job recommendations, and ATS CV export.', 'Backend and integration developer', '["PHP","Laravel","MySQL","REST APIs","Vue.js"]', NULL, 'https://github.com/brianel1/DigitalPassport', '2026-11-04', 'verified', '2', '2026-11-04 09:20:00', 'Core project record reviewed.', '2026-11-04 09:00:00', '2026-11-04 09:20:00');
INSERT INTO `projects` (`id`, `user_id`, `title`, `description`, `role`, `technologies`, `project_url`, `repository_url`, `completion_date`, `status`, `reviewed_by`, `reviewed_at`, `admin_remarks`, `created_at`, `updated_at`) VALUES ('2', '1', 'Campus Event Registration System', 'Created a registration workflow for university events with attendance tracking, validation, and downloadable participant reports.', 'Full stack developer', '["PHP","Laravel","MySQL","Blade","Tailwind CSS"]', NULL, NULL, '2026-08-18', 'verified', '2', '2026-11-04 09:20:00', 'Project scope and contribution reviewed.', '2026-11-04 09:00:00', '2026-11-04 09:20:00');
INSERT INTO `projects` (`id`, `user_id`, `title`, `description`, `role`, `technologies`, `project_url`, `repository_url`, `completion_date`, `status`, `reviewed_by`, `reviewed_at`, `admin_remarks`, `created_at`, `updated_at`) VALUES ('3', '1', 'Inventory Insights Dashboard', 'Designed a dashboard for stock movement, low inventory alerts, and monthly reporting for a small retail operation.', 'Frontend and data visualisation developer', '["Vue.js","TypeScript","MySQL","Chart.js"]', NULL, NULL, '2026-09-30', 'pending', NULL, NULL, NULL, '2026-11-04 09:00:00', '2026-11-04 09:00:00');
INSERT INTO `projects` (`id`, `user_id`, `title`, `description`, `role`, `technologies`, `project_url`, `repository_url`, `completion_date`, `status`, `reviewed_by`, `reviewed_at`, `admin_remarks`, `created_at`, `updated_at`) VALUES ('4', '1', 'Student Study Planner', 'Planned a weekly study workflow with task priorities, reminders, and progress summaries for university students.', 'Product and interface developer', '["Figma","Vue.js","TypeScript"]', NULL, NULL, '2026-06-12', 'rejected', '2', '2026-11-04 09:20:00', 'Please add a working deployment link or screenshots before resubmission.', '2026-11-04 09:00:00', '2026-11-04 09:20:00');

-- Data for certifications
INSERT INTO `certifications` (`id`, `user_id`, `title`, `issuing_organization`, `issue_date`, `expiry_date`, `credential_id`, `credential_url`, `status`, `reviewed_by`, `reviewed_at`, `admin_remarks`, `created_at`, `updated_at`) VALUES ('1', '1', 'Responsive Web Design', 'freeCodeCamp', '2026-05-14', NULL, 'FCC-RWD-2026-0514', 'https://www.freecodecamp.org/certification/elber-anselmus/responsive-web-design', 'verified', '2', '2026-11-04 09:30:00', 'Credential details reviewed.', '2026-11-04 09:00:00', '2026-11-04 09:30:00');
INSERT INTO `certifications` (`id`, `user_id`, `title`, `issuing_organization`, `issue_date`, `expiry_date`, `credential_id`, `credential_url`, `status`, `reviewed_by`, `reviewed_at`, `admin_remarks`, `created_at`, `updated_at`) VALUES ('2', '1', 'SQL Intermediate Assessment', 'HackerRank', '2026-07-22', NULL, 'HR-SQL-2026-0722', 'https://www.hackerrank.com/certificates/hr-sql-2026-0722', 'verified', '2', '2026-11-04 09:30:00', 'Credential details reviewed.', '2026-11-04 09:00:00', '2026-11-04 09:30:00');
INSERT INTO `certifications` (`id`, `user_id`, `title`, `issuing_organization`, `issue_date`, `expiry_date`, `credential_id`, `credential_url`, `status`, `reviewed_by`, `reviewed_at`, `admin_remarks`, `created_at`, `updated_at`) VALUES ('3', '1', 'Laravel Essentials', 'Laracasts', '2026-09-05', NULL, 'LARA-ESS-2026-0905', 'https://laracasts.com', 'pending', NULL, NULL, NULL, '2026-11-04 09:00:00', '2026-11-04 09:00:00');
INSERT INTO `certifications` (`id`, `user_id`, `title`, `issuing_organization`, `issue_date`, `expiry_date`, `credential_id`, `credential_url`, `status`, `reviewed_by`, `reviewed_at`, `admin_remarks`, `created_at`, `updated_at`) VALUES ('4', '1', 'GitHub Foundations', 'GitHub', '2026-10-02', NULL, 'GH-FND-2026-1002', 'https://examregistration.github.com/certification', 'verified', '2', '2026-11-04 09:30:00', 'Credential details reviewed.', '2026-11-04 09:00:00', '2026-11-04 09:30:00');

-- Data for project and certification skills
INSERT INTO `project_skill` (`project_id`, `skill_id`) VALUES ('1', '1'), ('1', '2'), ('1', '3'), ('1', '5'), ('1', '7');
INSERT INTO `project_skill` (`project_id`, `skill_id`) VALUES ('2', '1'), ('2', '2'), ('2', '3'), ('2', '9'), ('2', '13');
INSERT INTO `project_skill` (`project_id`, `skill_id`) VALUES ('3', '5'), ('3', '6'), ('3', '3');
INSERT INTO `project_skill` (`project_id`, `skill_id`) VALUES ('4', '6'), ('4', '11');
INSERT INTO `certification_skill` (`certification_id`, `skill_id`) VALUES ('1', '13'), ('1', '11');
INSERT INTO `certification_skill` (`certification_id`, `skill_id`) VALUES ('2', '3');
INSERT INTO `certification_skill` (`certification_id`, `skill_id`) VALUES ('3', '2');
INSERT INTO `certification_skill` (`certification_id`, `skill_id`) VALUES ('4', '8');

-- Data for verification reviews
INSERT INTO `verification_reviews` (`id`, `reviewer_id`, `reviewable_type`, `reviewable_id`, `decision`, `remarks`, `created_at`, `updated_at`) VALUES ('1', '2', 'App\\Models\\Project', '1', 'verified', 'Project contribution and technology list reviewed.', '2026-11-04 09:20:00', '2026-11-04 09:20:00');
INSERT INTO `verification_reviews` (`id`, `reviewer_id`, `reviewable_type`, `reviewable_id`, `decision`, `remarks`, `created_at`, `updated_at`) VALUES ('2', '2', 'App\\Models\\Project', '2', 'verified', 'Project scope and contribution reviewed.', '2026-11-04 09:20:00', '2026-11-04 09:20:00');
INSERT INTO `verification_reviews` (`id`, `reviewer_id`, `reviewable_type`, `reviewable_id`, `decision`, `remarks`, `created_at`, `updated_at`) VALUES ('3', '2', 'App\\Models\\Project', '4', 'rejected', 'Please add a working deployment link or screenshots before resubmission.', '2026-11-04 09:20:00', '2026-11-04 09:20:00');
INSERT INTO `verification_reviews` (`id`, `reviewer_id`, `reviewable_type`, `reviewable_id`, `decision`, `remarks`, `created_at`, `updated_at`) VALUES ('4', '2', 'App\\Models\\Certification', '1', 'verified', 'Credential details reviewed.', '2026-11-04 09:30:00', '2026-11-04 09:30:00');
INSERT INTO `verification_reviews` (`id`, `reviewer_id`, `reviewable_type`, `reviewable_id`, `decision`, `remarks`, `created_at`, `updated_at`) VALUES ('5', '2', 'App\\Models\\Certification', '2', 'verified', 'Credential details reviewed.', '2026-11-04 09:30:00', '2026-11-04 09:30:00');

-- Data for assessment attempts and answers
INSERT INTO `assessment_attempts` (`id`, `assessment_id`, `user_id`, `score`, `maximum_score`, `percentage`, `passed`, `started_at`, `submitted_at`, `created_at`, `updated_at`) VALUES ('1', '1', '1', '3', '3', '100.00', '1', '2026-10-28 09:00:00', '2026-10-28 09:08:00', '2026-10-28 09:00:00', '2026-10-28 09:08:00');
INSERT INTO `assessment_attempts` (`id`, `assessment_id`, `user_id`, `score`, `maximum_score`, `percentage`, `passed`, `started_at`, `submitted_at`, `created_at`, `updated_at`) VALUES ('2', '2', '1', '2', '3', '66.67', '1', '2026-10-29 10:00:00', '2026-10-29 10:07:00', '2026-10-29 10:00:00', '2026-10-29 10:07:00');
INSERT INTO `assessment_attempts` (`id`, `assessment_id`, `user_id`, `score`, `maximum_score`, `percentage`, `passed`, `started_at`, `submitted_at`, `created_at`, `updated_at`) VALUES ('3', '1', '1', '1', '3', '33.33', '0', '2026-10-15 14:00:00', '2026-10-15 14:06:00', '2026-10-15 14:00:00', '2026-10-15 14:06:00');
INSERT INTO `assessment_answers` (`id`, `attempt_id`, `question_id`, `selected_option`, `is_correct`, `points_awarded`, `created_at`, `updated_at`) VALUES ('1', '1', '1', '2', '1', '1', '2026-10-28 09:08:00', '2026-10-28 09:08:00'), ('2', '1', '2', '1', '1', '1', '2026-10-28 09:08:00', '2026-10-28 09:08:00'), ('3', '1', '3', '2', '1', '1', '2026-10-28 09:08:00', '2026-10-28 09:08:00');
INSERT INTO `assessment_answers` (`id`, `attempt_id`, `question_id`, `selected_option`, `is_correct`, `points_awarded`, `created_at`, `updated_at`) VALUES ('4', '2', '4', '2', '1', '1', '2026-10-29 10:07:00', '2026-10-29 10:07:00'), ('5', '2', '5', '1', '1', '1', '2026-10-29 10:07:00', '2026-10-29 10:07:00'), ('6', '2', '6', '1', '0', '0', '2026-10-29 10:07:00', '2026-10-29 10:07:00');

-- Data for job_preferences
INSERT INTO `job_preferences` (`id`, `user_id`, `preferred_fields`, `preferred_locations`, `work_types`, `accepts_remote`, `minimum_salary`, `maximum_salary`, `created_at`, `updated_at`) VALUES ('1', '1', '["backend developer","software developer","full stack developer","PHP","Laravel"]', '["Kuala Lumpur","Selangor","Melaka"]', '["full time","hybrid","remote"]', '1', '3000', '7000', '2026-11-04 09:40:00', '2026-11-04 09:40:00');

-- Data for job_listings
INSERT INTO `job_listings` (`id`, `source`, `external_id`, `title`, `company`, `location`, `work_type`, `salary_min`, `salary_max`, `salary_currency`, `description`, `required_skills`, `application_url`, `published_at`, `expires_at`, `fetched_at`, `raw_payload`, `created_at`, `updated_at`) VALUES ('1', 'jobstreet', '95486787', 'Back End Developer (PHP Laravel)', 'MHA Consultancy Services Sdn Bhd', 'Maluri, Kuala Lumpur', 'hybrid', '5000', '7000', 'MYR', 'Develop backend features with PHP Laravel, optimise MySQL queries, build RESTful APIs, and support integrations for business systems.', '["PHP","Laravel","MySQL","REST APIs","Git and GitHub"]', 'https://my.jobstreet.com/job/95486787?origin=cardTitle&ref=search-standalone&type=standard', '2026-10-27 00:00:00', '2026-11-26 00:00:00', '2026-11-04 09:45:00', '{"provider":"JobStreet Malaysia","captured_on":"2026-11-04"}', '2026-11-04 09:45:00', '2026-11-04 09:45:00');
INSERT INTO `job_listings` (`id`, `source`, `external_id`, `title`, `company`, `location`, `work_type`, `salary_min`, `salary_max`, `salary_currency`, `description`, `required_skills`, `application_url`, `published_at`, `expires_at`, `fetched_at`, `raw_payload`, `created_at`, `updated_at`) VALUES ('2', 'jobstreet', '95236128', 'Backend PHP Developer (Laravel) Mid Senior Level', 'INNOVATECOREX SDN. BHD.', 'Cheras, Kuala Lumpur', 'full time', '5500', '8000', 'MYR', 'Build and maintain Laravel backend systems, RESTful APIs, database structures, and integrations for CRM and gaming platforms.', '["PHP","Laravel","MySQL","REST APIs","JavaScript"]', 'https://my.jobstreet.com/job/95236128?origin=cardTitle&ref=search-standalone&type=standard', '2026-10-15 00:00:00', '2026-11-14 00:00:00', '2026-11-04 09:45:00', '{"provider":"JobStreet Malaysia","captured_on":"2026-11-04"}', '2026-11-04 09:45:00', '2026-11-04 09:45:00');
INSERT INTO `job_listings` (`id`, `source`, `external_id`, `title`, `company`, `location`, `work_type`, `salary_min`, `salary_max`, `salary_currency`, `description`, `required_skills`, `application_url`, `published_at`, `expires_at`, `fetched_at`, `raw_payload`, `created_at`, `updated_at`) VALUES ('3', 'jobstreet', '95321672', 'Full Stack Developer PHP Laravel and Node.js', 'Ultranet Technologies Sdn Bhd', 'Kuala Lumpur', 'full time', '5500', '8000', 'MYR', 'Develop enterprise web applications with PHP Laravel, Node.js, RESTful APIs, MySQL, caching, and modern frontend frameworks.', '["PHP","Laravel","Node.js","MySQL","REST APIs","Vue.js"]', 'https://my.jobstreet.com/job/95321672?origin=cardTitle&ref=search-standalone&type=standard', '2026-10-17 00:00:00', '2026-11-16 00:00:00', '2026-11-04 09:45:00', '{"provider":"JobStreet Malaysia","captured_on":"2026-11-04"}', '2026-11-04 09:45:00', '2026-11-04 09:45:00');
INSERT INTO `job_listings` (`id`, `source`, `external_id`, `title`, `company`, `location`, `work_type`, `salary_min`, `salary_max`, `salary_currency`, `description`, `required_skills`, `application_url`, `published_at`, `expires_at`, `fetched_at`, `raw_payload`, `created_at`, `updated_at`) VALUES ('4', 'jobstreet', '95340297', 'PHP Web Developer Laravel', 'ONL Biz Solutions Sdn Bhd', 'Kuala Lumpur', 'full time', '7000', '10000', 'MYR', 'Develop and maintain PHP Laravel web applications with a focus on reliable delivery, maintainable code, and practical business features.', '["PHP","Laravel","MySQL","JavaScript"]', 'https://my.jobstreet.com/job/95340297?origin=cardTitle&ref=search-standalone&type=standard', '2026-10-30 00:00:00', '2026-11-29 00:00:00', '2026-11-04 09:45:00', '{"provider":"JobStreet Malaysia","captured_on":"2026-11-04"}', '2026-11-04 09:45:00', '2026-11-04 09:45:00');
INSERT INTO `job_listings` (`id`, `source`, `external_id`, `title`, `company`, `location`, `work_type`, `salary_min`, `salary_max`, `salary_currency`, `description`, `required_skills`, `application_url`, `published_at`, `expires_at`, `fetched_at`, `raw_payload`, `created_at`, `updated_at`) VALUES ('5', 'jobstreet', '95316595', 'Software Developer Junior Mid', 'Aiodin Tech Sdn Bhd', 'Kuala Lumpur City Centre, Kuala Lumpur', 'remote', '4000', '6000', 'MYR', 'Build and maintain backend integrations with partner APIs using PHP and Laravel in a remote friendly engineering team.', '["PHP","Laravel","REST APIs","MySQL","Git and GitHub"]', 'https://my.jobstreet.com/job/95316595?origin=cardTitle&ref=search-standalone&type=standard', '2026-10-23 00:00:00', '2026-11-22 00:00:00', '2026-11-04 09:45:00', '{"provider":"JobStreet Malaysia","captured_on":"2026-11-04"}', '2026-11-04 09:45:00', '2026-11-04 09:45:00');
INSERT INTO `job_listings` (`id`, `source`, `external_id`, `title`, `company`, `location`, `work_type`, `salary_min`, `salary_max`, `salary_currency`, `description`, `required_skills`, `application_url`, `published_at`, `expires_at`, `fetched_at`, `raw_payload`, `created_at`, `updated_at`) VALUES ('6', 'jobstreet', '95527416', 'Senior Backend Developer PHP Laravel', 'techstreet', 'Cheras, Kuala Lumpur', 'full time', '7000', '9000', 'MYR', 'Design scalable RESTful APIs and backend services with Laravel, MySQL, MongoDB, Docker, and secure coding practices.', '["PHP","Laravel","MySQL","REST APIs","Docker","Git and GitHub"]', 'https://my.jobstreet.com/job/95527416?origin=cardTitle&ref=search-standalone&type=standard', '2026-11-01 00:00:00', '2026-11-30 00:00:00', '2026-11-04 09:45:00', '{"provider":"JobStreet Malaysia","captured_on":"2026-11-04"}', '2026-11-04 09:45:00', '2026-11-04 09:45:00');
INSERT INTO `job_listings` (`id`, `source`, `external_id`, `title`, `company`, `location`, `work_type`, `salary_min`, `salary_max`, `salary_currency`, `description`, `required_skills`, `application_url`, `published_at`, `expires_at`, `fetched_at`, `raw_payload`, `created_at`, `updated_at`) VALUES ('7', 'jobstreet', '95520586', 'Software Programmer', 'SmartHire by SEEK', 'Kuala Lumpur', 'full time', NULL, NULL, NULL, 'Develop software through the full lifecycle, solve technical issues, and work with cross functional teams on practical products.', '["PHP","JavaScript","MySQL","Git and GitHub"]', 'https://my.jobstreet.com/job/95520586?origin=cardTitle&ref=search-standalone&type=standard', '2026-11-03 00:00:00', '2026-12-02 00:00:00', '2026-11-04 09:45:00', '{"provider":"JobStreet Malaysia","captured_on":"2026-11-04"}', '2026-11-04 09:45:00', '2026-11-04 09:45:00');
INSERT INTO `job_listings` (`id`, `source`, `external_id`, `title`, `company`, `location`, `work_type`, `salary_min`, `salary_max`, `salary_currency`, `description`, `required_skills`, `application_url`, `published_at`, `expires_at`, `fetched_at`, `raw_payload`, `created_at`, `updated_at`) VALUES ('8', 'jobstreet', '95555143', 'Web Developer', 'Triple Y Studio', 'KL Eco City, Kuala Lumpur', 'full time', '3000', '4500', 'MYR', 'Develop and maintain responsive websites with strong frontend fundamentals, API integration, and reliable delivery.', '["PHP","HTML and CSS","JavaScript","Vue.js"]', 'https://my.jobstreet.com/job/95555143?origin=cardTitle&ref=search-standalone&type=standard', '2026-10-21 00:00:00', '2026-11-20 00:00:00', '2026-11-04 09:45:00', '{"provider":"JobStreet Malaysia","captured_on":"2026-11-04"}', '2026-11-04 09:45:00', '2026-11-04 09:45:00');
INSERT INTO `job_listings` (`id`, `source`, `external_id`, `title`, `company`, `location`, `work_type`, `salary_min`, `salary_max`, `salary_currency`, `description`, `required_skills`, `application_url`, `published_at`, `expires_at`, `fetched_at`, `raw_payload`, `created_at`, `updated_at`) VALUES ('9', 'jobstreet', '95354813', 'Junior PHP Backend Developer Laravel', 'APPLIFY TECHNOLOGIES SDN. BHD.', 'Puchong, Selangor', 'full time', '2500', '3500', 'MYR', 'Entry level PHP Laravel role focused on backend features, practical development, training, and continuous improvement.', '["PHP","Laravel","MySQL","Git and GitHub"]', 'https://my.jobstreet.com/job/95354813?origin=cardTitle&ref=search-standalone&type=standard', '2026-10-26 00:00:00', '2026-11-25 00:00:00', '2026-11-04 09:45:00', '{"provider":"JobStreet Malaysia","captured_on":"2026-11-04"}', '2026-11-04 09:45:00', '2026-11-04 09:45:00');
INSERT INTO `job_listings` (`id`, `source`, `external_id`, `title`, `company`, `location`, `work_type`, `salary_min`, `salary_max`, `salary_currency`, `description`, `required_skills`, `application_url`, `published_at`, `expires_at`, `fetched_at`, `raw_payload`, `created_at`, `updated_at`) VALUES ('10', 'jobstreet', '95494989', 'Junior Front End Developer', 'MHA Consultancy Services Sdn Bhd', 'Maluri, Kuala Lumpur', 'hybrid', '4000', '6000', 'MYR', 'Develop responsive web applications using Vue, integrate APIs, and collaborate with backend developers and designers.', '["Vue.js","JavaScript","TypeScript","HTML and CSS","REST APIs"]', 'https://my.jobstreet.com/job/95494989?origin=cardTitle&ref=search-standalone&type=standard', '2026-10-31 00:00:00', '2026-11-30 00:00:00', '2026-11-04 09:45:00', '{"provider":"JobStreet Malaysia","captured_on":"2026-11-04"}', '2026-11-04 09:45:00', '2026-11-04 09:45:00');
INSERT INTO `job_listings` (`id`, `source`, `external_id`, `title`, `company`, `location`, `work_type`, `salary_min`, `salary_max`, `salary_currency`, `description`, `required_skills`, `application_url`, `published_at`, `expires_at`, `fetched_at`, `raw_payload`, `created_at`, `updated_at`) VALUES ('11', 'jobstreet', '95377450', 'Senior Backend Developer', 'MHA Consultancy Services Sdn Bhd', 'Cheras, Kuala Lumpur', 'full time', '7000', '9000', 'MYR', 'Design scalable Laravel services and RESTful APIs with MySQL, MongoDB, Docker, Redis, and secure coding practices.', '["PHP","Laravel","MySQL","REST APIs","Docker","Git and GitHub"]', 'https://my.jobstreet.com/job/95377450?origin=cardTitle&ref=search-standalone&type=standard', '2026-10-22 00:00:00', '2026-11-21 00:00:00', '2026-11-04 09:45:00', '{"provider":"JobStreet Malaysia","captured_on":"2026-11-04"}', '2026-11-04 09:45:00', '2026-11-04 09:45:00');

-- Data for job recommendation results
INSERT INTO `job_recommendation_runs` (`id`, `user_id`, `profile_snapshot`, `preference_snapshot`, `model_name`, `status`, `failure_reason`, `created_at`, `updated_at`) VALUES ('1', '1', '{"verified_skills":["PHP","Laravel","MySQL","JavaScript","Vue.js","TypeScript","REST APIs","Git and GitHub","Tailwind CSS","Figma","Python","HTML and CSS"],"other_skills":["Docker","PHPUnit"],"project_technologies":["PHP","Laravel","MySQL","REST APIs","Vue.js","Blade","Tailwind CSS","HTML and CSS"],"assessment_results":[{"title":"JavaScript Fundamentals","percentage":100},{"title":"SQL and Database Basics","percentage":66.67}]}', '{"preferred_fields":["backend developer","software developer","full stack developer","PHP","Laravel"],"preferred_locations":["Kuala Lumpur","Selangor","Melaka"],"work_types":["full time","hybrid","remote"],"accepts_remote":true,"minimum_salary":3000,"maximum_salary":7000}', 'explainable-rules-v1', 'completed', NULL, '2026-11-04 09:50:00', '2026-11-04 09:50:00');
INSERT INTO `job_matches` (`id`, `recommendation_run_id`, `job_listing_id`, `match_score`, `matched_skills`, `missing_skills`, `explanation`, `rank`, `created_at`, `updated_at`) VALUES ('1', '1', '1', '93.00', '["PHP","Laravel","MySQL","REST APIs","Git and GitHub"]', '[]', 'Strong match for your verified PHP, Laravel, MySQL, REST API, and Git skills. The hybrid location also fits your preferences.', '1', '2026-11-04 09:50:00', '2026-11-04 09:50:00');
INSERT INTO `job_matches` (`id`, `recommendation_run_id`, `job_listing_id`, `match_score`, `matched_skills`, `missing_skills`, `explanation`, `rank`, `created_at`, `updated_at`) VALUES ('2', '1', '2', '82.00', '["PHP","Laravel","MySQL","REST APIs","JavaScript"]', '[]', 'Matches your backend profile and Laravel experience. The CRM and API work align with your verified project record.', '2', '2026-11-04 09:50:00', '2026-11-04 09:50:00');
INSERT INTO `job_matches` (`id`, `recommendation_run_id`, `job_listing_id`, `match_score`, `matched_skills`, `missing_skills`, `explanation`, `rank`, `created_at`, `updated_at`) VALUES ('3', '1', '3', '78.00', '["PHP","Laravel","MySQL","REST APIs","Vue.js"]', '["Node.js"]', 'Good full stack match with a Node.js gap to review. Your Laravel, Vue, and database experience is relevant to the role.', '3', '2026-11-04 09:50:00', '2026-11-04 09:50:00');
INSERT INTO `job_matches` (`id`, `recommendation_run_id`, `job_listing_id`, `match_score`, `matched_skills`, `missing_skills`, `explanation`, `rank`, `created_at`, `updated_at`) VALUES ('4', '1', '9', '77.00', '["PHP","Laravel","MySQL","Git and GitHub"]', '[]', 'A practical entry level role that aligns closely with your verified backend skills and current career preferences.', '4', '2026-11-04 09:50:00', '2026-11-04 09:50:00');
INSERT INTO `job_matches` (`id`, `recommendation_run_id`, `job_listing_id`, `match_score`, `matched_skills`, `missing_skills`, `explanation`, `rank`, `created_at`, `updated_at`) VALUES ('5', '1', '5', '74.00', '["PHP","Laravel","REST APIs","MySQL","Git and GitHub"]', '[]', 'Your profile covers the main backend and API requirements, with a remote work arrangement that fits your preferences.', '5', '2026-11-04 09:50:00', '2026-11-04 09:50:00');
INSERT INTO `job_matches` (`id`, `recommendation_run_id`, `job_listing_id`, `match_score`, `matched_skills`, `missing_skills`, `explanation`, `rank`, `created_at`, `updated_at`) VALUES ('6', '1', '8', '70.00', '["PHP","HTML and CSS","JavaScript","Vue.js"]', '[]', 'Your verified frontend and PHP skills align with this web development role.', '6', '2026-11-04 09:50:00', '2026-11-04 09:50:00');

-- Data for generated CV
INSERT INTO `generated_cvs` (`id`, `user_id`, `target_job_listing_id`, `title`, `content`, `version`, `status`, `model_name`, `created_at`, `updated_at`) VALUES ('1', '1', '1', 'Elber Anselmus CV', '{"personal":{"name":"Elber Anselmus","email":"user@student.utem.edu.my","phone":"+60 12-345 6789","location":"Melaka, Malaysia","headline":"Information Technology Student | PHP and Laravel Developer"},"summary":"Information Technology student at Universiti Teknikal Malaysia Melaka with verified experience in PHP, Laravel, MySQL, Vue.js, REST APIs, and full stack web development. Interested in backend engineering, API integration, and practical software products.","skills":["PHP","Laravel","MySQL","JavaScript","Vue.js","TypeScript","REST APIs","Git and GitHub","Tailwind CSS","HTML and CSS"],"education":[{"institution":"Universiti Teknikal Malaysia Melaka","qualification":"Bachelor of Information Technology with Honours","period":"Expected graduation 2028"}],"projects":[{"title":"DigitalPassport Student Portfolio","description":"Built a verified student portfolio platform with profile management, skill review, assessments, AI job recommendations, and ATS CV export.","technologies":["PHP","Laravel","MySQL","REST APIs","Vue.js"]},{"title":"Campus Event Registration System","description":"Created a registration workflow for university events with attendance tracking, validation, and downloadable participant reports.","technologies":["PHP","Laravel","MySQL","Blade","Tailwind CSS"]}],"certifications":[{"title":"Responsive Web Design","issuer":"freeCodeCamp","issueDate":"2026-05-14"},{"title":"SQL Intermediate Assessment","issuer":"HackerRank","issueDate":"2026-07-22"},{"title":"GitHub Foundations","issuer":"GitHub","issueDate":"2026-10-02"}],"assessments":[{"title":"JavaScript Fundamentals","percentage":100,"completedAt":"2026-10-28"},{"title":"SQL and Database Basics","percentage":66.67,"completedAt":"2026-10-29"}]}', '1', 'draft', 'trusted-profile-template-v1', '2026-11-04 09:55:00', '2026-11-04 09:55:00');

SET FOREIGN_KEY_CHECKS = 1;
