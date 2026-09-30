-- Tabela de horários académicos
-- Executar no phpMyAdmin ou na consola MySQL depois de importar a base principal.

CREATE TABLE IF NOT EXISTS schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    academic_year_id INT NULL,
    course_id INT NULL,
    class_id INT NOT NULL,
    subject_id INT NOT NULL,
    teacher_user_id INT NULL,
    weekday VARCHAR(20) NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    room VARCHAR(100) NULL,
    notes TEXT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'draft',
    created_by INT NULL,
    validated_by INT NULL,
    validated_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_schedules_class_day (class_id, weekday, start_time),
    INDEX idx_schedules_subject (subject_id),
    INDEX idx_schedules_teacher (teacher_user_id),
    INDEX idx_schedules_status (status)
);

-- Valores esperados em weekday:
-- segunda, terca, quarta, quinta, sexta, sabado, domingo
--
-- Valores esperados em status:
-- draft      = Rascunho
-- published  = Publicado pela secretaria
-- validated  = Validado pela coordenação/direção
-- cancelled  = Cancelado
