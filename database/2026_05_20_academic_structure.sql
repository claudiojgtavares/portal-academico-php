-- Estrutura académica complementar
-- Execute este ficheiro no phpMyAdmin depois de aplicar o ZIP v14.
-- Objetivo: permitir criar turmas, associar disciplinas às turmas,
-- associar alunos às turmas e associar docentes/coordenadores aos cursos.

CREATE TABLE IF NOT EXISTS academic_classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    academic_year_id INT NULL,
    code VARCHAR(50) NULL,
    name VARCHAR(150) NOT NULL,
    year_number INT NULL,
    semester INT NULL,
    shift VARCHAR(30) NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    created_by INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_academic_classes_course (course_id),
    INDEX idx_academic_classes_year (academic_year_id),
    INDEX idx_academic_classes_status (status)
);

CREATE TABLE IF NOT EXISTS class_subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class_id INT NOT NULL,
    subject_id INT NOT NULL,
    academic_year_id INT NULL,
    semester INT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    created_by INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_class_subject_year (class_id, subject_id, academic_year_id),
    INDEX idx_class_subjects_class (class_id),
    INDEX idx_class_subjects_subject (subject_id),
    INDEX idx_class_subjects_status (status)
);

CREATE TABLE IF NOT EXISTS class_students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class_id INT NOT NULL,
    student_id INT NOT NULL,
    academic_year_id INT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    created_by INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_class_student_year (class_id, student_id, academic_year_id),
    INDEX idx_class_students_class (class_id),
    INDEX idx_class_students_student (student_id),
    INDEX idx_class_students_status (status)
);

CREATE TABLE IF NOT EXISTS course_staff (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    user_id INT NOT NULL,
    academic_year_id INT NULL,
    function_role VARCHAR(30) NOT NULL DEFAULT 'professor',
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    created_by INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_course_staff_year_role (course_id, user_id, academic_year_id, function_role),
    INDEX idx_course_staff_course (course_id),
    INDEX idx_course_staff_user (user_id),
    INDEX idx_course_staff_role (function_role),
    INDEX idx_course_staff_status (status)
);

CREATE TABLE IF NOT EXISTS course_coordinators (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    user_id INT NOT NULL,
    coordinator_user_id INT NULL,
    academic_year_id INT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    created_by INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_course_coordinator_year (course_id, user_id, academic_year_id),
    INDEX idx_course_coord_course (course_id),
    INDEX idx_course_coord_user (user_id),
    INDEX idx_course_coord_status (status)
);

-- Regra de negócio recomendada:
-- 1. Admin cria e corrige perfis de utilizador.
-- 2. Admin ou Secretaria criam turmas.
-- 3. Admin ou Secretaria associam disciplinas às turmas.
-- 4. Admin ou Secretaria associam professores/coordenadores aos cursos.
-- 5. Admin ou Secretaria atribuem disciplina + turma + ano letivo ao professor/coordenador.
-- 6. Coordenador valida a coerência pedagógica do curso e também pode lecionar quando tiver atribuição ativa.
-- 7. Alunos são colocados em turmas pela secretaria após matrícula ativa.
