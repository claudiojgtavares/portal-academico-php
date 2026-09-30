-- Relatórios académicos e fotografia de perfil
-- Executar no phpMyAdmin depois de importar/atualizar a base principal.

CREATE TABLE IF NOT EXISTS academic_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    submitted_by INT NOT NULL,
    target_role VARCHAR(30) NOT NULL DEFAULT 'direcao',
    title VARCHAR(180) NOT NULL,
    description TEXT NULL,
    category VARCHAR(40) NOT NULL DEFAULT 'relatorio_coordenacao',
    related_course_id INT NULL,
    academic_year_id INT NULL,
    original_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    mime_type VARCHAR(120) NULL,
    file_size INT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'submitted',
    reviewed_by INT NULL,
    reviewed_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_academic_reports_submitted_by (submitted_by),
    INDEX idx_academic_reports_category (category),
    INDEX idx_academic_reports_course (related_course_id),
    INDEX idx_academic_reports_year (academic_year_id),
    INDEX idx_academic_reports_status (status),
    INDEX idx_academic_reports_created (created_at)
);

-- Adiciona fotografia de perfil em users, se ainda não existir.
SET @db_name := DATABASE();
SET @profile_photo_exists := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @db_name
      AND TABLE_NAME = 'users'
      AND COLUMN_NAME = 'profile_photo'
);

SET @profile_photo_sql := IF(
    @profile_photo_exists = 0,
    'ALTER TABLE users ADD COLUMN profile_photo VARCHAR(255) NULL AFTER identity_document_file',
    'SELECT "A coluna profile_photo já existe."'
);

PREPARE stmt_profile_photo FROM @profile_photo_sql;
EXECUTE stmt_profile_photo;
DEALLOCATE PREPARE stmt_profile_photo;

-- Categorias sugeridas:
-- relatorio_coordenacao  = relatório submetido por coordenador
-- documento_secretaria   = documento/relatório submetido pela secretaria
-- relatorio_direcao      = relatório interno da direção
--
-- Estados sugeridos:
-- submitted = Submetido
-- reviewed  = Revisto
-- archived  = Arquivado
