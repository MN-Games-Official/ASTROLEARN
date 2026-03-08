-- ============================================================
-- ASTROLEARN – Database Schema
-- AI-Supported Educational Writing Workspace
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET NAMES utf8mb4;

-- -----------------------------------------------------------
-- 1. Schools
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `schools` (
  `id`              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `name`            VARCHAR(255)    NOT NULL,
  `domain`          VARCHAR(255)    DEFAULT NULL,
  `address`         TEXT            DEFAULT NULL,
  `settings`        JSON            DEFAULT NULL,
  `created_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_schools_domain` (`domain`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- 2. Users
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id`              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `school_id`       INT UNSIGNED    DEFAULT NULL,
  `role`            ENUM('student','teacher','admin') NOT NULL DEFAULT 'student',
  `first_name`      VARCHAR(100)    NOT NULL,
  `last_name`       VARCHAR(100)    NOT NULL,
  `email`           VARCHAR(255)    NOT NULL,
  `password_hash`   VARCHAR(255)    NOT NULL,
  `profile`         JSON            DEFAULT NULL COMMENT 'Adaptive-learning skill profile signals',
  `settings`        JSON            DEFAULT NULL,
  `last_login_at`   DATETIME        DEFAULT NULL,
  `created_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`),
  KEY `idx_users_school` (`school_id`),
  CONSTRAINT `fk_users_school` FOREIGN KEY (`school_id`) REFERENCES `schools`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- 3. Classes
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `classes` (
  `id`              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `school_id`       INT UNSIGNED    NOT NULL,
  `teacher_id`      INT UNSIGNED    NOT NULL,
  `name`            VARCHAR(255)    NOT NULL,
  `description`     TEXT            DEFAULT NULL,
  `ai_policy`       JSON            DEFAULT NULL COMMENT 'Class-level AI behaviour rules',
  `created_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_classes_school` (`school_id`),
  KEY `idx_classes_teacher` (`teacher_id`),
  CONSTRAINT `fk_classes_school`  FOREIGN KEY (`school_id`)  REFERENCES `schools`(`id`)  ON DELETE CASCADE,
  CONSTRAINT `fk_classes_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `users`(`id`)    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- 4. Enrollments (class <-> student)
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `enrollments` (
  `id`              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `class_id`        INT UNSIGNED    NOT NULL,
  `student_id`      INT UNSIGNED    NOT NULL,
  `enrolled_at`     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_enrollment` (`class_id`, `student_id`),
  KEY `idx_enrollments_student` (`student_id`),
  CONSTRAINT `fk_enrollments_class`   FOREIGN KEY (`class_id`)   REFERENCES `classes`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_enrollments_student` FOREIGN KEY (`student_id`) REFERENCES `users`(`id`)   ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- 5. Assignments
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `assignments` (
  `id`              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `class_id`        INT UNSIGNED    NOT NULL,
  `teacher_id`      INT UNSIGNED    NOT NULL,
  `title`           VARCHAR(255)    NOT NULL,
  `instructions`    TEXT            NOT NULL,
  `ai_policy`       JSON            DEFAULT NULL COMMENT 'Assignment-specific AI rules',
  `due_date`        DATETIME        DEFAULT NULL,
  `created_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_assignments_class`   (`class_id`),
  KEY `idx_assignments_teacher` (`teacher_id`),
  CONSTRAINT `fk_assignments_class`   FOREIGN KEY (`class_id`)   REFERENCES `classes`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_assignments_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `users`(`id`)   ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- 6. Documents
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `documents` (
  `id`              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `user_id`         INT UNSIGNED    NOT NULL,
  `assignment_id`   INT UNSIGNED    DEFAULT NULL,
  `title`           VARCHAR(255)    NOT NULL DEFAULT 'Untitled Document',
  `content`         LONGTEXT        DEFAULT NULL,
  `word_count`      INT UNSIGNED    NOT NULL DEFAULT 0,
  `status`          ENUM('draft','submitted','reviewed') NOT NULL DEFAULT 'draft',
  `created_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_documents_user`       (`user_id`),
  KEY `idx_documents_assignment` (`assignment_id`),
  CONSTRAINT `fk_documents_user`       FOREIGN KEY (`user_id`)       REFERENCES `users`(`id`)       ON DELETE CASCADE,
  CONSTRAINT `fk_documents_assignment` FOREIGN KEY (`assignment_id`) REFERENCES `assignments`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- 7. Document Versions (revision history)
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `document_versions` (
  `id`              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `document_id`     INT UNSIGNED    NOT NULL,
  `content`         LONGTEXT        NOT NULL,
  `word_count`      INT UNSIGNED    NOT NULL DEFAULT 0,
  `saved_at`        DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_docver_document` (`document_id`),
  CONSTRAINT `fk_docver_document` FOREIGN KEY (`document_id`) REFERENCES `documents`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- 8. Assignment Submissions
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `assignment_submissions` (
  `id`              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `assignment_id`   INT UNSIGNED    NOT NULL,
  `student_id`      INT UNSIGNED    NOT NULL,
  `document_id`     INT UNSIGNED    NOT NULL,
  `submitted_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `grade`           VARCHAR(10)     DEFAULT NULL,
  `feedback`        TEXT            DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_submissions_assignment` (`assignment_id`),
  KEY `idx_submissions_student`    (`student_id`),
  KEY `idx_submissions_document`   (`document_id`),
  CONSTRAINT `fk_sub_assignment` FOREIGN KEY (`assignment_id`) REFERENCES `assignments`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_sub_student`    FOREIGN KEY (`student_id`)    REFERENCES `users`(`id`)        ON DELETE CASCADE,
  CONSTRAINT `fk_sub_document`   FOREIGN KEY (`document_id`)   REFERENCES `documents`(`id`)    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- 9. AI Conversations
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ai_conversations` (
  `id`              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `document_id`     INT UNSIGNED    NOT NULL,
  `user_id`         INT UNSIGNED    NOT NULL,
  `mode`            VARCHAR(50)     NOT NULL COMMENT 'interpreter, planner, brainstorm, outline, draft_coach, reasoning, reflection',
  `created_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_aiconv_document` (`document_id`),
  KEY `idx_aiconv_user`     (`user_id`),
  CONSTRAINT `fk_aiconv_document` FOREIGN KEY (`document_id`) REFERENCES `documents`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_aiconv_user`     FOREIGN KEY (`user_id`)     REFERENCES `users`(`id`)     ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- 10. AI Events (individual messages / actions)
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ai_events` (
  `id`              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `conversation_id` INT UNSIGNED    DEFAULT NULL,
  `document_id`     INT UNSIGNED    NOT NULL,
  `user_id`         INT UNSIGNED    NOT NULL,
  `event_type`      VARCHAR(50)     NOT NULL COMMENT 'request, response, refusal, flag',
  `mode`            VARCHAR(50)     DEFAULT NULL,
  `request_text`    TEXT            DEFAULT NULL,
  `response_text`   TEXT            DEFAULT NULL,
  `model_used`      VARCHAR(100)    DEFAULT NULL,
  `tokens_used`     INT UNSIGNED    DEFAULT 0,
  `created_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_aievt_conversation` (`conversation_id`),
  KEY `idx_aievt_document`     (`document_id`),
  KEY `idx_aievt_user`         (`user_id`),
  CONSTRAINT `fk_aievt_conversation` FOREIGN KEY (`conversation_id`) REFERENCES `ai_conversations`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_aievt_document`     FOREIGN KEY (`document_id`)     REFERENCES `documents`(`id`)        ON DELETE CASCADE,
  CONSTRAINT `fk_aievt_user`         FOREIGN KEY (`user_id`)         REFERENCES `users`(`id`)             ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- 11. Policy Rules
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `policy_rules` (
  `id`              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `scope`           ENUM('global','school','class','assignment') NOT NULL DEFAULT 'global',
  `scope_id`        INT UNSIGNED    DEFAULT NULL COMMENT 'ID of school/class/assignment depending on scope',
  `rule_key`        VARCHAR(100)    NOT NULL,
  `rule_value`      VARCHAR(255)    NOT NULL,
  `description`     TEXT            DEFAULT NULL,
  `created_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_policy_scope` (`scope`, `scope_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- 12. Policy Violations
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `policy_violations` (
  `id`              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `user_id`         INT UNSIGNED    NOT NULL,
  `document_id`     INT UNSIGNED    DEFAULT NULL,
  `ai_event_id`     INT UNSIGNED    DEFAULT NULL,
  `rule_id`         INT UNSIGNED    DEFAULT NULL,
  `severity`        ENUM('low','medium','high') NOT NULL DEFAULT 'medium',
  `flagged_text`    TEXT            DEFAULT NULL,
  `status`          ENUM('pending','reviewed','dismissed') NOT NULL DEFAULT 'pending',
  `teacher_notified` TINYINT(1)    NOT NULL DEFAULT 0,
  `resolution`      TEXT            DEFAULT NULL,
  `created_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_violation_user`     (`user_id`),
  KEY `idx_violation_document` (`document_id`),
  KEY `idx_violation_event`    (`ai_event_id`),
  CONSTRAINT `fk_violation_user`     FOREIGN KEY (`user_id`)     REFERENCES `users`(`id`)     ON DELETE CASCADE,
  CONSTRAINT `fk_violation_document` FOREIGN KEY (`document_id`) REFERENCES `documents`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_violation_event`    FOREIGN KEY (`ai_event_id`) REFERENCES `ai_events`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- 13. Comments (teacher comments on documents)
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `comments` (
  `id`              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `document_id`     INT UNSIGNED    NOT NULL,
  `user_id`         INT UNSIGNED    NOT NULL,
  `content`         TEXT            NOT NULL,
  `selection_start` INT UNSIGNED    DEFAULT NULL,
  `selection_end`   INT UNSIGNED    DEFAULT NULL,
  `resolved`        TINYINT(1)      NOT NULL DEFAULT 0,
  `created_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_comments_document` (`document_id`),
  KEY `idx_comments_user`     (`user_id`),
  CONSTRAINT `fk_comments_document` FOREIGN KEY (`document_id`) REFERENCES `documents`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_comments_user`     FOREIGN KEY (`user_id`)     REFERENCES `users`(`id`)     ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- 14. Notifications
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `notifications` (
  `id`              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `user_id`         INT UNSIGNED    NOT NULL,
  `type`            VARCHAR(50)     NOT NULL,
  `title`           VARCHAR(255)    NOT NULL,
  `body`            TEXT            DEFAULT NULL,
  `link`            VARCHAR(512)    DEFAULT NULL,
  `is_read`         TINYINT(1)      NOT NULL DEFAULT 0,
  `created_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_notifications_user` (`user_id`),
  CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- 15. Integrations (future: Google Docs, LMS, etc.)
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `integrations` (
  `id`              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `school_id`       INT UNSIGNED    DEFAULT NULL,
  `provider`        VARCHAR(50)     NOT NULL,
  `config`          JSON            DEFAULT NULL,
  `enabled`         TINYINT(1)      NOT NULL DEFAULT 1,
  `created_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_integrations_school` (`school_id`),
  CONSTRAINT `fk_integrations_school` FOREIGN KEY (`school_id`) REFERENCES `schools`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- 16. Exports
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `exports` (
  `id`              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `user_id`         INT UNSIGNED    NOT NULL,
  `document_id`     INT UNSIGNED    DEFAULT NULL,
  `format`          VARCHAR(10)     NOT NULL DEFAULT 'pdf',
  `file_path`       VARCHAR(512)    DEFAULT NULL,
  `created_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_exports_user`     (`user_id`),
  KEY `idx_exports_document` (`document_id`),
  CONSTRAINT `fk_exports_user`     FOREIGN KEY (`user_id`)     REFERENCES `users`(`id`)     ON DELETE CASCADE,
  CONSTRAINT `fk_exports_document` FOREIGN KEY (`document_id`) REFERENCES `documents`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- Seed: default global policy rules
-- -----------------------------------------------------------
INSERT INTO `policy_rules` (`scope`, `rule_key`, `rule_value`, `description`) VALUES
  ('global', 'enforcement_level',      'balanced',  'Default enforcement: strict | balanced | supportive'),
  ('global', 'allow_thesis_generation', '0',        'Whether AI may generate thesis statements'),
  ('global', 'allow_paragraph_rewrite', '0',        'Whether AI may rewrite full paragraphs'),
  ('global', 'allow_outline_help',      '1',        'Whether AI may help build outlines'),
  ('global', 'allow_brainstorming',     '1',        'Whether AI may assist with brainstorming'),
  ('global', 'allow_grammar_help',      '1',        'Whether AI may provide grammar suggestions'),
  ('global', 'block_during_tests',      '1',        'Block all AI assistance during test-mode assignments');
