-- Gestão de cursos e disciplinas
-- Execute este ficheiro no phpMyAdmin depois de aplicar o ZIP v15.
-- Objetivo: garantir que cursos e disciplinas possuem os campos usados pelas novas páginas de criação/edição.

CREATE TABLE IF NOT EXISTS courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(180) NOT NULL,
    level VARCHAR(50) NOT NULL DEFAULT 'licenciatura',
    duration_years INT NULL,
    description TEXT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_courses_level (level),
    INDEX idx_courses_status (status)
);

CREATE TABLE IF NOT EXISTS subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    code VARCHAR(50) NOT NULL,
    name VARCHAR(180) NOT NULL,
    semester INT NULL,
    credits INT NULL,
    workload_hours INT NULL,
    description TEXT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_subject_course_code (course_id, code),
    INDEX idx_subjects_course (course_id),
    INDEX idx_subjects_status (status)
);

ALTER TABLE courses ADD COLUMN IF NOT EXISTS description TEXT NULL;
ALTER TABLE courses ADD COLUMN IF NOT EXISTS created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE courses ADD COLUMN IF NOT EXISTS updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE subjects ADD COLUMN IF NOT EXISTS workload_hours INT NULL;
ALTER TABLE subjects ADD COLUMN IF NOT EXISTS description TEXT NULL;
ALTER TABLE subjects ADD COLUMN IF NOT EXISTS created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE subjects ADD COLUMN IF NOT EXISTS updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP;

-- Regra de negócio recomendada:
-- 1. Admin ou Secretaria criam cursos e disciplinas.
-- 2. Direção e Coordenação consultam, mas não fazem manutenção operacional.
-- 3. Depois da disciplina existir, a Secretaria/Admin associa essa disciplina a uma turma.
-- 4. Depois a Secretaria/Admin atribui essa turma+disciplina a um professor ou coordenador.
