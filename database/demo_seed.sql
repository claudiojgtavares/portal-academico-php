-- Dados de demonstração totalmente fictícios.
-- Palavra-passe comum dos utilizadores: Demo1234!
SET NAMES utf8mb4;

INSERT INTO roles (id, code, slug, name, description) VALUES
(1, 'admin', 'admin', 'Administrador', 'Gestão técnica do protótipo'),
(2, 'secretaria', 'secretaria', 'Secretaria Académica', 'Processos e documentos'),
(3, 'direcao', 'direcao', 'Direção Académica', 'Supervisão académica'),
(4, 'coordenador', 'coordenador', 'Coordenação de Curso', 'Coordenação de um curso'),
(5, 'professor', 'professor', 'Professor', 'Registo de notas e faltas'),
(6, 'funcionario', 'funcionario', 'Funcionário', 'Operações administrativas'),
(7, 'aluno', 'aluno', 'Aluno', 'Consulta académica');

INSERT INTO users (id, institutional_id, full_name, email, personal_email, phone, password_hash, role, roles, status, is_active, must_change_password) VALUES
(1, 'ADM-DEMO-001', 'Alex Silva', 'admin@example.test', 'admin.personal@example.test', '+238 900 0001', '$2y$10$/gdvWef60UXbyebKU8fFfun8hRNDziOEyUglvPAz9yOkgM5dffJtG', 'admin', '["admin"]', 'active', 1, 0),
(2, 'SEC-DEMO-001', 'Bruna Lopes', 'secretaria@example.test', 'secretaria.personal@example.test', '+238 900 0002', '$2y$10$/gdvWef60UXbyebKU8fFfun8hRNDziOEyUglvPAz9yOkgM5dffJtG', 'secretaria', '["secretaria"]', 'active', 1, 0),
(3, 'DIR-DEMO-001', 'Carlos Monteiro', 'direcao@example.test', 'direcao.personal@example.test', '+238 900 0003', '$2y$10$/gdvWef60UXbyebKU8fFfun8hRNDziOEyUglvPAz9yOkgM5dffJtG', 'direcao', '["direcao"]', 'active', 1, 0),
(4, 'COO-DEMO-001', 'Diana Fortes', 'coordenacao@example.test', 'coordenacao.personal@example.test', '+238 900 0004', '$2y$10$/gdvWef60UXbyebKU8fFfun8hRNDziOEyUglvPAz9yOkgM5dffJtG', 'coordenador', '["coordenador"]', 'active', 1, 0),
(5, 'PRO-DEMO-001', 'Elias Pina', 'professor@example.test', 'professor.personal@example.test', '+238 900 0005', '$2y$10$/gdvWef60UXbyebKU8fFfun8hRNDziOEyUglvPAz9yOkgM5dffJtG', 'professor', '["professor"]', 'active', 1, 0),
(6, 'FUN-DEMO-001', 'Fátima Reis', 'funcionario@example.test', 'funcionario.personal@example.test', '+238 900 0006', '$2y$10$/gdvWef60UXbyebKU8fFfun8hRNDziOEyUglvPAz9yOkgM5dffJtG', 'funcionario', '["funcionario"]', 'active', 1, 0),
(7, 'ALU-DEMO-001', 'Gisela Gomes', 'aluno@example.test', 'aluno.personal@example.test', '+238 900 0007', '$2y$10$/gdvWef60UXbyebKU8fFfun8hRNDziOEyUglvPAz9yOkgM5dffJtG', 'aluno', '["aluno"]', 'active', 1, 0);

INSERT INTO user_roles (user_id, role_id) VALUES
(1, 1), (2, 2), (3, 3), (4, 4), (5, 5), (6, 6), (7, 7);

INSERT INTO academic_years (id, name, start_date, end_date, is_active, status) VALUES
(1, '2026/2027', '2026-09-01', '2027-07-31', 1, 'active');

INSERT INTO courses (id, code, name, level, duration_years, description, status) VALUES
(1, 'ESI', 'Engenharia de Sistemas e Informática', 'Licenciatura', 4, 'Curso fictício para demonstração do portal.', 'active');

INSERT INTO subjects (id, course_id, code, name, semester, credits, workload_hours, description, status) VALUES
(1, 1, 'POO101', 'Programação Orientada a Objetos', 1, 6, 90, 'Fundamentos de modelação e testes.', 'active'),
(2, 1, 'BD102', 'Bases de Dados', 1, 6, 90, 'Modelação relacional e consultas.', 'active'),
(3, 1, 'SEG201', 'Sistemas e Segurança', 2, 5, 75, 'Princípios de sistemas e segurança.', 'active');

INSERT INTO academic_classes (id, course_id, academic_year_id, code, name, year_number, semester, shift, status, created_by) VALUES
(1, 1, 1, 'ESI-1A', 'Engenharia de Sistemas — 1.º ano', 1, 1, 'diurno', 'active', 1);

INSERT INTO class_subjects (class_id, subject_id, academic_year_id, semester, status, created_by) VALUES
(1, 1, 1, 1, 'active', 1), (1, 2, 1, 1, 'active', 1), (1, 3, 1, 2, 'active', 1);

INSERT INTO fees (academic_year_id, course_id, title, description, month_reference, amount_cve, due_date, status) VALUES
(1, 1, 'Propina de demonstração', 'Valor fictício para testar o fluxo de pagamentos.', '2026-10', 12500.00, '2026-10-10', 'pending');
