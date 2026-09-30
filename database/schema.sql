-- portal_academico_reset_completo.sql
-- Reconstrução limpa da base de dados do protótipo Portal Académico.
-- Apenas o esquema é público; os dados de demonstração ficam em demo_seed.sql.
-- Mantem as configuracoes atuais do projeto: config/database.php usa dbname = 'portal_academico'.
-- ATENCAO: este script apaga e recria a base portal_academico do zero.

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

DROP DATABASE IF EXISTS `portal_academico`;
CREATE DATABASE `portal_academico` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `portal_academico`;

SET FOREIGN_KEY_CHECKS = 0;
START TRANSACTION;

CREATE TABLE `roles` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `code` VARCHAR(50) NOT NULL UNIQUE,
    `slug` VARCHAR(50) NOT NULL UNIQUE,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `institutional_id` VARCHAR(80) NULL UNIQUE,
    `full_name` VARCHAR(180) NOT NULL,
    `email` VARCHAR(180) NOT NULL UNIQUE,
    `personal_email` VARCHAR(180) NULL,
    `phone` VARCHAR(50) NULL,
    `birth_date` DATE NULL,
    `document_type` VARCHAR(80) NULL,
    `document_number` VARCHAR(100) NULL,
    `address` TEXT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `role` VARCHAR(50) NULL,
    `roles` TEXT NULL,
    `status` VARCHAR(30) NOT NULL DEFAULT 'active',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `must_change_password` TINYINT(1) NOT NULL DEFAULT 0,
    `profile_photo` VARCHAR(255) NULL,
    `identity_document_file` VARCHAR(255) NULL,
    `last_login_at` DATETIME NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_users_institutional_id` (`institutional_id`),
    INDEX `idx_users_email` (`email`),
    INDEX `idx_users_role` (`role`),
    INDEX `idx_users_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `user_roles` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `role_id` INT NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_user_role` (`user_id`, `role_id`),
    INDEX `idx_user_roles_user` (`user_id`),
    INDEX `idx_user_roles_role` (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `academic_years` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `start_date` DATE NULL,
    `end_date` DATE NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 0,
    `status` VARCHAR(30) NOT NULL DEFAULT 'active',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_academic_years_status` (`status`),
    INDEX `idx_academic_years_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `courses` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `code` VARCHAR(50) NOT NULL UNIQUE,
    `name` VARCHAR(180) NOT NULL,
    `level` VARCHAR(50) NOT NULL DEFAULT 'licenciatura',
    `duration_years` INT NULL,
    `description` TEXT NULL,
    `status` VARCHAR(20) NOT NULL DEFAULT 'active',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_courses_level` (`level`),
    INDEX `idx_courses_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `subjects` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `course_id` INT NOT NULL,
    `code` VARCHAR(50) NOT NULL,
    `name` VARCHAR(180) NOT NULL,
    `semester` INT NULL,
    `credits` INT NULL,
    `workload_hours` INT NULL,
    `description` TEXT NULL,
    `status` VARCHAR(20) NOT NULL DEFAULT 'active',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_subject_course_code` (`course_id`, `code`),
    INDEX `idx_subjects_course` (`course_id`),
    INDEX `idx_subjects_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `applications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `application_code` VARCHAR(80) NOT NULL UNIQUE,
    `full_name` VARCHAR(180) NOT NULL,
    `birth_date` DATE NULL,
    `document_type` VARCHAR(80) NULL,
    `document_number` VARCHAR(100) NULL,
    `personal_email` VARCHAR(180) NULL,
    `phone` VARCHAR(50) NULL,
    `course_id` INT NULL,
    `academic_year_id` INT NULL,
    `status` VARCHAR(40) NOT NULL DEFAULT 'pending',
    `rejection_reason` TEXT NULL,
    `observations` TEXT NULL,
    `notes` TEXT NULL,
    `created_user_id` INT NULL,
    `reviewed_by` INT NULL,
    `reviewed_at` DATETIME NULL,
    `user_id` INT NULL,
    `student_id` INT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_applications_code` (`application_code`),
    INDEX `idx_applications_course` (`course_id`),
    INDEX `idx_applications_year` (`academic_year_id`),
    INDEX `idx_applications_status` (`status`),
    INDEX `idx_applications_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `application_documents` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `application_id` INT NOT NULL,
    `document_name` VARCHAR(180) NOT NULL,
    `file_path` VARCHAR(255) NOT NULL,
    `status` VARCHAR(30) NOT NULL DEFAULT 'pending',
    `notes` TEXT NULL,
    `uploaded_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_application_documents_application` (`application_id`),
    INDEX `idx_application_documents_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `application_status_history` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `application_id` INT NOT NULL,
    `user_id` INT NULL,
    `old_status` VARCHAR(40) NULL,
    `new_status` VARCHAR(40) NULL,
    `action` VARCHAR(100) NOT NULL,
    `message` TEXT NULL,
    `ip_address` VARCHAR(80) NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_app_history_application` (`application_id`),
    INDEX `idx_app_history_user` (`user_id`),
    INDEX `idx_app_history_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `students` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `student_code` VARCHAR(80) NOT NULL UNIQUE,
    `course_id` INT NULL,
    `academic_year_id` INT NULL,
    `current_year` INT NULL,
    `enrollment_status` VARCHAR(40) NOT NULL DEFAULT 'active',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_students_user` (`user_id`),
    INDEX `idx_students_code` (`student_code`),
    INDEX `idx_students_course` (`course_id`),
    INDEX `idx_students_year` (`academic_year_id`),
    INDEX `idx_students_status` (`enrollment_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `academic_classes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `course_id` INT NOT NULL,
    `academic_year_id` INT NULL,
    `code` VARCHAR(50) NULL,
    `name` VARCHAR(150) NOT NULL,
    `year_number` INT NULL,
    `semester` INT NULL,
    `shift` VARCHAR(30) NULL,
    `status` VARCHAR(20) NOT NULL DEFAULT 'active',
    `created_by` INT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_academic_classes_course` (`course_id`),
    INDEX `idx_academic_classes_year` (`academic_year_id`),
    INDEX `idx_academic_classes_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `class_subjects` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `class_id` INT NOT NULL,
    `subject_id` INT NOT NULL,
    `academic_year_id` INT NULL,
    `semester` INT NULL,
    `status` VARCHAR(20) NOT NULL DEFAULT 'active',
    `created_by` INT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_class_subject_year` (`class_id`, `subject_id`, `academic_year_id`),
    INDEX `idx_class_subjects_class` (`class_id`),
    INDEX `idx_class_subjects_subject` (`subject_id`),
    INDEX `idx_class_subjects_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `class_students` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `class_id` INT NOT NULL,
    `student_id` INT NOT NULL,
    `academic_year_id` INT NULL,
    `status` VARCHAR(20) NOT NULL DEFAULT 'active',
    `created_by` INT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_class_student_year` (`class_id`, `student_id`, `academic_year_id`),
    INDEX `idx_class_students_class` (`class_id`),
    INDEX `idx_class_students_student` (`student_id`),
    INDEX `idx_class_students_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `course_staff` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `course_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `academic_year_id` INT NULL,
    `function_role` VARCHAR(30) NOT NULL DEFAULT 'professor',
    `status` VARCHAR(20) NOT NULL DEFAULT 'active',
    `created_by` INT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_course_staff_year_role` (`course_id`, `user_id`, `academic_year_id`, `function_role`),
    INDEX `idx_course_staff_course` (`course_id`),
    INDEX `idx_course_staff_user` (`user_id`),
    INDEX `idx_course_staff_role` (`function_role`),
    INDEX `idx_course_staff_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `course_coordinators` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `course_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `coordinator_user_id` INT NULL,
    `academic_year_id` INT NULL,
    `status` VARCHAR(20) NOT NULL DEFAULT 'active',
    `created_by` INT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_course_coordinator_year` (`course_id`, `user_id`, `academic_year_id`),
    INDEX `idx_course_coord_course` (`course_id`),
    INDEX `idx_course_coord_user` (`user_id`),
    INDEX `idx_course_coord_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `teacher_subjects` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `teacher_user_id` INT NOT NULL,
    `subject_id` INT NOT NULL,
    `class_id` INT NOT NULL,
    `academic_year_id` INT NOT NULL,
    `status` VARCHAR(20) NOT NULL DEFAULT 'active',
    `created_by` INT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_teacher_subject_class_year` (`teacher_user_id`, `subject_id`, `class_id`, `academic_year_id`),
    INDEX `idx_teacher_subjects_teacher` (`teacher_user_id`),
    INDEX `idx_teacher_subjects_subject` (`subject_id`),
    INDEX `idx_teacher_subjects_class` (`class_id`),
    INDEX `idx_teacher_subjects_year` (`academic_year_id`),
    INDEX `idx_teacher_subjects_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `schedules` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `academic_year_id` INT NULL,
    `course_id` INT NULL,
    `class_id` INT NOT NULL,
    `subject_id` INT NOT NULL,
    `teacher_user_id` INT NULL,
    `weekday` VARCHAR(20) NOT NULL,
    `start_time` TIME NOT NULL,
    `end_time` TIME NOT NULL,
    `room` VARCHAR(100) NULL,
    `notes` TEXT NULL,
    `status` VARCHAR(20) NOT NULL DEFAULT 'draft',
    `created_by` INT NULL,
    `validated_by` INT NULL,
    `validated_at` DATETIME NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_schedules_class_day` (`class_id`, `weekday`, `start_time`),
    INDEX `idx_schedules_subject` (`subject_id`),
    INDEX `idx_schedules_teacher` (`teacher_user_id`),
    INDEX `idx_schedules_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `grades` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_id` INT NOT NULL,
    `subject_id` INT NOT NULL,
    `class_id` INT NOT NULL,
    `teacher_user_id` INT NULL,
    `academic_year_id` INT NULL,
    `assessment_type` VARCHAR(40) NOT NULL DEFAULT 'frequencia',
    `grade_value` DECIMAL(5,2) NULL,
    `status` VARCHAR(30) NOT NULL DEFAULT 'draft',
    `notes` TEXT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_grades_student` (`student_id`),
    INDEX `idx_grades_subject` (`subject_id`),
    INDEX `idx_grades_class` (`class_id`),
    INDEX `idx_grades_teacher` (`teacher_user_id`),
    INDEX `idx_grades_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `attendance_records` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_id` INT NOT NULL,
    `subject_id` INT NOT NULL,
    `class_id` INT NOT NULL,
    `teacher_user_id` INT NULL,
    `academic_year_id` INT NULL,
    `class_date` DATE NOT NULL,
    `status` VARCHAR(30) NOT NULL DEFAULT 'present',
    `notes` TEXT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_attendance_once` (`student_id`, `subject_id`, `class_id`, `teacher_user_id`, `class_date`),
    INDEX `idx_attendance_student` (`student_id`),
    INDEX `idx_attendance_subject` (`subject_id`),
    INDEX `idx_attendance_class` (`class_id`),
    INDEX `idx_attendance_teacher` (`teacher_user_id`),
    INDEX `idx_attendance_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `fees` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `academic_year_id` INT NULL,
    `course_id` INT NULL,
    `title` VARCHAR(180) NOT NULL,
    `description` VARCHAR(255) NULL,
    `month_reference` VARCHAR(30) NULL,
    `amount_cve` DECIMAL(12,2) NOT NULL DEFAULT 0,
    `due_date` DATE NULL,
    `status` VARCHAR(30) NOT NULL DEFAULT 'ativa',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_fees_year` (`academic_year_id`),
    INDEX `idx_fees_course` (`course_id`),
    INDEX `idx_fees_status` (`status`),
    INDEX `idx_fees_month` (`month_reference`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `payments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_id` INT NOT NULL,
    `fee_id` INT NULL,
    `amount_cve` DECIMAL(12,2) NOT NULL DEFAULT 0,
    `payment_method` VARCHAR(80) NULL,
    `bank_reference` VARCHAR(120) NULL,
    `reference` VARCHAR(120) NULL,
    `proof_file` VARCHAR(255) NULL,
    `status` VARCHAR(30) NOT NULL DEFAULT 'em_analise',
    `notes` TEXT NULL,
    `submitted_at` DATETIME NULL,
    `validated_by` INT NULL,
    `validated_at` DATETIME NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_payments_student` (`student_id`),
    INDEX `idx_payments_fee` (`fee_id`),
    INDEX `idx_payments_status` (`status`),
    INDEX `idx_payments_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `document_requests` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_id` INT NOT NULL,
    `document_type` VARCHAR(120) NOT NULL,
    `purpose` TEXT NULL,
    `status` VARCHAR(30) NOT NULL DEFAULT 'pending',
    `file_path` VARCHAR(255) NULL,
    `notes` TEXT NULL,
    `requested_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_document_requests_student` (`student_id`),
    INDEX `idx_document_requests_status` (`status`),
    INDEX `idx_document_requests_requested` (`requested_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `notifications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `title` VARCHAR(180) NOT NULL,
    `message` TEXT NOT NULL,
    `type` VARCHAR(30) NOT NULL DEFAULT 'info',
    `is_read` TINYINT(1) NOT NULL DEFAULT 0,
    `read_at` DATETIME NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_notifications_user` (`user_id`),
    INDEX `idx_notifications_read` (`is_read`),
    INDEX `idx_notifications_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `activity_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NULL,
    `action` VARCHAR(120) NOT NULL,
    `description` TEXT NULL,
    `ip_address` VARCHAR(80) NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_activity_logs_user` (`user_id`),
    INDEX `idx_activity_logs_action` (`action`),
    INDEX `idx_activity_logs_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `login_attempts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `identifier` VARCHAR(180) NOT NULL,
    `success` TINYINT(1) NOT NULL DEFAULT 0,
    `ip_address` VARCHAR(80) NULL,
    `attempted_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_login_attempts_identifier` (`identifier`),
    INDEX `idx_login_attempts_success` (`success`),
    INDEX `idx_login_attempts_attempted` (`attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `academic_reports` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `submitted_by` INT NOT NULL,
    `target_role` VARCHAR(30) NOT NULL DEFAULT 'direcao',
    `title` VARCHAR(180) NOT NULL,
    `description` TEXT NULL,
    `category` VARCHAR(40) NOT NULL DEFAULT 'relatorio_coordenacao',
    `related_course_id` INT NULL,
    `academic_year_id` INT NULL,
    `original_name` VARCHAR(255) NOT NULL,
    `file_path` VARCHAR(255) NOT NULL,
    `mime_type` VARCHAR(120) NULL,
    `file_size` INT NULL,
    `status` VARCHAR(30) NOT NULL DEFAULT 'submitted',
    `reviewed_by` INT NULL,
    `reviewed_at` DATETIME NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_academic_reports_submitted_by` (`submitted_by`),
    INDEX `idx_academic_reports_category` (`category`),
    INDEX `idx_academic_reports_course` (`related_course_id`),
    INDEX `idx_academic_reports_year` (`academic_year_id`),
    INDEX `idx_academic_reports_status` (`status`),
    INDEX `idx_academic_reports_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabelas auxiliares preventivas, para compatibilidade com versoes anteriores/futuras do portal.
CREATE TABLE `academic_subjects` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `academic_year_id` INT NULL,
    `subject_id` INT NULL,
    `class_id` INT NULL,
    `status` VARCHAR(30) NOT NULL DEFAULT 'active',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `classrooms` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `code` VARCHAR(50) NULL,
    `name` VARCHAR(120) NOT NULL,
    `capacity` INT NULL,
    `status` VARCHAR(30) NOT NULL DEFAULT 'active',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `class_schedules` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `class_id` INT NULL,
    `subject_id` INT NULL,
    `teacher_user_id` INT NULL,
    `classroom_id` INT NULL,
    `weekday` VARCHAR(20) NULL,
    `start_time` TIME NULL,
    `end_time` TIME NULL,
    `status` VARCHAR(30) NOT NULL DEFAULT 'active',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `staff_profiles` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `department` VARCHAR(120) NULL,
    `job_title` VARCHAR(120) NULL,
    `status` VARCHAR(30) NOT NULL DEFAULT 'active',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_staff_profiles_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dados base










SET FOREIGN_KEY_CHECKS = 1;
COMMIT;
