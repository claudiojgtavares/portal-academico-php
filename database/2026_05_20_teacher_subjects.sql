-- Atribuição de disciplinas a professores e coordenadores
-- Executar no phpMyAdmin se a tabela teacher_subjects ainda não existir
-- ou para garantir colunas necessárias ao módulo de notas/faltas.

CREATE TABLE IF NOT EXISTS teacher_subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_user_id INT NOT NULL,
    subject_id INT NOT NULL,
    class_id INT NOT NULL,
    academic_year_id INT NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    created_by INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_teacher_subject_class_year (teacher_user_id, subject_id, class_id, academic_year_id),
    INDEX idx_teacher_subjects_teacher (teacher_user_id),
    INDEX idx_teacher_subjects_subject (subject_id),
    INDEX idx_teacher_subjects_class (class_id),
    INDEX idx_teacher_subjects_year (academic_year_id),
    INDEX idx_teacher_subjects_status (status)
);

-- Regra de negócio:
-- 1. Secretaria académica ou administração atribui disciplinas/turmas aos professores e coordenadores.
-- 2. Coordenador também pode lecionar como professor quando tiver atribuição ativa nesta tabela.
-- 3. Lançamento de notas e registo de faltas dependem desta atribuição.
