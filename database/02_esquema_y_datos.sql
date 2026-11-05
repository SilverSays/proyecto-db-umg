-- ============================================================================
-- Sistema Académico UMG · Script 02
-- Tablas, datos de prueba y vistas del modelo académico.
--
-- Ejecutar conectado como UMG_ACADEMICO.
-- ============================================================================


-- ============================================================================
-- 2. CREACIÓN DE TABLAS
-- ============================================================================

-- ---- Elimina las tablas si ya existen, para poder re-ejecutar el script ----
BEGIN EXECUTE IMMEDIATE 'DROP TABLE ASIGNACION_CATEDRATICO CASCADE CONSTRAINTS'; EXCEPTION WHEN OTHERS THEN NULL; END;
/
BEGIN EXECUTE IMMEDIATE 'DROP TABLE INSCRIPCION CASCADE CONSTRAINTS'; EXCEPTION WHEN OTHERS THEN NULL; END;
/
BEGIN EXECUTE IMMEDIATE 'DROP TABLE SECCION CASCADE CONSTRAINTS'; EXCEPTION WHEN OTHERS THEN NULL; END;
/
BEGIN EXECUTE IMMEDIATE 'DROP TABLE PRERREQUISITO CASCADE CONSTRAINTS'; EXCEPTION WHEN OTHERS THEN NULL; END;
/
BEGIN EXECUTE IMMEDIATE 'DROP TABLE CURSO CASCADE CONSTRAINTS'; EXCEPTION WHEN OTHERS THEN NULL; END;
/
BEGIN EXECUTE IMMEDIATE 'DROP TABLE ALUMNO CASCADE CONSTRAINTS'; EXCEPTION WHEN OTHERS THEN NULL; END;
/
BEGIN EXECUTE IMMEDIATE 'DROP TABLE CARRERA CASCADE CONSTRAINTS'; EXCEPTION WHEN OTHERS THEN NULL; END;
/
BEGIN EXECUTE IMMEDIATE 'DROP TABLE PERIODO_ACADEMICO CASCADE CONSTRAINTS'; EXCEPTION WHEN OTHERS THEN NULL; END;
/
BEGIN EXECUTE IMMEDIATE 'DROP TABLE CATEDRATICO CASCADE CONSTRAINTS'; EXCEPTION WHEN OTHERS THEN NULL; END;
/
BEGIN EXECUTE IMMEDIATE 'DROP TABLE FACULTAD CASCADE CONSTRAINTS'; EXCEPTION WHEN OTHERS THEN NULL; END;
/


-- ---- FACULTAD (sin dependencias) ----
CREATE TABLE FACULTAD (
    pk_facultadId   NUMBER(5)      NOT NULL,
    nombre          VARCHAR2(100)  NOT NULL,
    decano          VARCHAR2(100)  NOT NULL,
    CONSTRAINT pk_facultad PRIMARY KEY (pk_facultadId)
);

-- ---- CATEDRATICO (sin dependencias) ----
CREATE TABLE CATEDRATICO (
    pk_catedraticoId NUMBER(5)     NOT NULL,
    nombres          VARCHAR2(60)  NOT NULL,
    apellidos        VARCHAR2(60)  NOT NULL,
    email            VARCHAR2(100) NOT NULL,
    CONSTRAINT pk_catedratico PRIMARY KEY (pk_catedraticoId),
    CONSTRAINT uq_catedratico_email UNIQUE (email)
);

-- ---- PERIODO_ACADEMICO (sin dependencias) ----
CREATE TABLE PERIODO_ACADEMICO (
    pk_periodoId    NUMBER(5)     NOT NULL,
    nombre          VARCHAR2(30)  NOT NULL,
    fechaInicio     DATE          NOT NULL,
    fechaFin        DATE          NOT NULL,
    CONSTRAINT pk_periodo PRIMARY KEY (pk_periodoId),
    CONSTRAINT ck_periodo_fechas CHECK (fechaFin > fechaInicio)
);

-- ---- CARRERA (depende de FACULTAD) ----
CREATE TABLE CARRERA (
    pk_carreraId    NUMBER(5)     NOT NULL,
    nombre          VARCHAR2(100) NOT NULL,
    nivel           VARCHAR2(30)  NOT NULL,
    fk_facultadId   NUMBER(5)     NOT NULL,
    CONSTRAINT pk_carrera PRIMARY KEY (pk_carreraId),
    CONSTRAINT fk_carrera_facultad FOREIGN KEY (fk_facultadId)
        REFERENCES FACULTAD (pk_facultadId)
);

-- ---- ALUMNO (depende de CARRERA) ----
CREATE TABLE ALUMNO (
    pk_alumnoId     NUMBER(5)     NOT NULL,
    carnet          VARCHAR2(20)  NOT NULL,
    nombres         VARCHAR2(60)  NOT NULL,
    apellidos       VARCHAR2(60)  NOT NULL,
    email           VARCHAR2(100) NOT NULL,
    fk_carreraId    NUMBER(5)     NOT NULL,
    CONSTRAINT pk_alumno PRIMARY KEY (pk_alumnoId),
    CONSTRAINT uq_alumno_carnet UNIQUE (carnet),
    CONSTRAINT uq_alumno_email UNIQUE (email),
    CONSTRAINT fk_alumno_carrera FOREIGN KEY (fk_carreraId)
        REFERENCES CARRERA (pk_carreraId)
);

-- ---- CURSO (depende de CARRERA) ----
CREATE TABLE CURSO (
    pk_cursoId      NUMBER(5)     NOT NULL,
    codigo          VARCHAR2(15)  NOT NULL,
    nombre          VARCHAR2(100) NOT NULL,
    creditos        NUMBER(2)     NOT NULL,
    fk_carreraId    NUMBER(5)     NOT NULL,
    CONSTRAINT pk_curso PRIMARY KEY (pk_cursoId),
    CONSTRAINT uq_curso_codigo UNIQUE (codigo),
    CONSTRAINT fk_curso_carrera FOREIGN KEY (fk_carreraId)
        REFERENCES CARRERA (pk_carreraId)
);

-- ---- PRERREQUISITO (depende de CURSO, autorreferencia) ----
CREATE TABLE PRERREQUISITO (
    fk_cursoId              NUMBER(5) NOT NULL,
    fk_cursoPrerequisitoId  NUMBER(5) NOT NULL,
    CONSTRAINT pk_prerrequisito PRIMARY KEY (fk_cursoId, fk_cursoPrerequisitoId),
    CONSTRAINT fk_prerreq_curso FOREIGN KEY (fk_cursoId)
        REFERENCES CURSO (pk_cursoId),
    CONSTRAINT fk_prerreq_cursoprereq FOREIGN KEY (fk_cursoPrerequisitoId)
        REFERENCES CURSO (pk_cursoId),
    CONSTRAINT ck_prerreq_distinto CHECK (fk_cursoId <> fk_cursoPrerequisitoId)
);

-- ---- SECCION (depende de CURSO, CATEDRATICO, PERIODO_ACADEMICO) ----
CREATE TABLE SECCION (
    pk_seccionId     NUMBER(5)    NOT NULL,
    fk_cursoId       NUMBER(5)    NOT NULL,
    fk_catedraticoId NUMBER(5)    NOT NULL,
    fk_periodoId     NUMBER(5)    NOT NULL,
    horario          VARCHAR2(50) NOT NULL,
    aula             VARCHAR2(20) NOT NULL,
    cupo             NUMBER(3)    NOT NULL,
    CONSTRAINT pk_seccion PRIMARY KEY (pk_seccionId),
    CONSTRAINT fk_seccion_curso FOREIGN KEY (fk_cursoId)
        REFERENCES CURSO (pk_cursoId),
    CONSTRAINT fk_seccion_catedratico FOREIGN KEY (fk_catedraticoId)
        REFERENCES CATEDRATICO (pk_catedraticoId),
    CONSTRAINT fk_seccion_periodo FOREIGN KEY (fk_periodoId)
        REFERENCES PERIODO_ACADEMICO (pk_periodoId),
    CONSTRAINT ck_seccion_cupo CHECK (cupo > 0)
);

-- ---- INSCRIPCION (depende de ALUMNO, SECCION) ----
CREATE TABLE INSCRIPCION (
    pk_inscripcionId NUMBER(6)     NOT NULL,
    fk_alumnoId      NUMBER(5)     NOT NULL,
    fk_seccionId     NUMBER(5)     NOT NULL,
    fecha            DATE          NOT NULL,
    estado           VARCHAR2(20)  DEFAULT 'INSCRITO' NOT NULL,
    nota             NUMBER(5,2),
    CONSTRAINT pk_inscripcion PRIMARY KEY (pk_inscripcionId),
    CONSTRAINT fk_inscripcion_alumno FOREIGN KEY (fk_alumnoId)
        REFERENCES ALUMNO (pk_alumnoId),
    CONSTRAINT fk_inscripcion_seccion FOREIGN KEY (fk_seccionId)
        REFERENCES SECCION (pk_seccionId),
    CONSTRAINT uq_inscripcion_alumno_seccion UNIQUE (fk_alumnoId, fk_seccionId),
    CONSTRAINT ck_inscripcion_estado CHECK (estado IN ('INSCRITO','CONFIRMADO','RETIRADO','FINALIZADO')),
    CONSTRAINT ck_inscripcion_nota CHECK (nota BETWEEN 0 AND 100)
);

-- ---- ASIGNACION_CATEDRATICO (depende de CATEDRATICO, CURSO, FACULTAD, PERIODO_ACADEMICO) ----
CREATE TABLE ASIGNACION_CATEDRATICO (
    pk_asignacionId  NUMBER(6) NOT NULL,
    fk_catedraticoId NUMBER(5) NOT NULL,
    fk_cursoId       NUMBER(5) NOT NULL,
    fk_facultadId    NUMBER(5) NOT NULL,
    fk_periodoId     NUMBER(5) NOT NULL,
    fechaInicio      DATE      NOT NULL,
    fechaFin         DATE      NOT NULL,
    CONSTRAINT pk_asignacion PRIMARY KEY (pk_asignacionId),
    CONSTRAINT fk_asignacion_catedratico FOREIGN KEY (fk_catedraticoId)
        REFERENCES CATEDRATICO (pk_catedraticoId),
    CONSTRAINT fk_asignacion_curso FOREIGN KEY (fk_cursoId)
        REFERENCES CURSO (pk_cursoId),
    CONSTRAINT fk_asignacion_facultad FOREIGN KEY (fk_facultadId)
        REFERENCES FACULTAD (pk_facultadId),
    CONSTRAINT fk_asignacion_periodo FOREIGN KEY (fk_periodoId)
        REFERENCES PERIODO_ACADEMICO (pk_periodoId),
    CONSTRAINT ck_asignacion_fechas CHECK (fechaFin > fechaInicio)
);


-- ============================================================================
-- 3. INSERCIÓN DE DATOS DE PRUEBA
-- ============================================================================

-- ---- FACULTAD ----
INSERT INTO FACULTAD VALUES (1, 'Facultad de Ingeniería', 'Dr. Carlos Méndez');
INSERT INTO FACULTAD VALUES (2, 'Facultad de Ciencias Económicas', 'Dra. Ana Lucía Pérez');
INSERT INTO FACULTAD VALUES (3, 'Facultad de Humanidades', 'Lic. Jorge Ramírez');
INSERT INTO FACULTAD VALUES (4, 'Facultad de Ciencias Jurídicas', 'Dr. Mario Solís');
INSERT INTO FACULTAD VALUES (5, 'Facultad de Odontología', 'Dra. Patricia Gómez');

-- ---- CATEDRATICO ----
INSERT INTO CATEDRATICO VALUES (1, 'Luis Fernando', 'García López', 'lgarcia@umg.edu.gt');
INSERT INTO CATEDRATICO VALUES (2, 'María José', 'Hernández Ruiz', 'mhernandez@umg.edu.gt');
INSERT INTO CATEDRATICO VALUES (3, 'Pedro Antonio', 'Morales Cruz', 'pmorales@umg.edu.gt');
INSERT INTO CATEDRATICO VALUES (4, 'Ana Sofía', 'Castillo Reyes', 'acastillo@umg.edu.gt');
INSERT INTO CATEDRATICO VALUES (5, 'Roberto Carlos', 'Vásquez Ortiz', 'rvasquez@umg.edu.gt');
INSERT INTO CATEDRATICO VALUES (6, 'Claudia Beatriz', 'Rodríguez Paz', 'crodriguez@umg.edu.gt');
INSERT INTO CATEDRATICO VALUES (7, 'Jorge Mario', 'Estrada Luna', 'jestrada@umg.edu.gt');
INSERT INTO CATEDRATICO VALUES (8, 'Silvia Patricia', 'López Aguilar', 'slopez@umg.edu.gt');

-- ---- PERIODO_ACADEMICO ----
INSERT INTO PERIODO_ACADEMICO VALUES (1, '2026 - Primer Semestre', DATE '2026-01-12', DATE '2026-05-15');
INSERT INTO PERIODO_ACADEMICO VALUES (2, '2026 - Segundo Semestre', DATE '2026-07-06', DATE '2026-11-13');
INSERT INTO PERIODO_ACADEMICO VALUES (3, '2026 - Vacaciones', DATE '2026-11-16', DATE '2026-12-18');
INSERT INTO PERIODO_ACADEMICO VALUES (4, '2027 - Primer Semestre', DATE '2027-01-11', DATE '2027-05-14');

-- ---- CARRERA (fk_facultadId) ----
INSERT INTO CARRERA VALUES (1, 'Ingeniería en Sistemas', 'Licenciatura', 1);
INSERT INTO CARRERA VALUES (2, 'Ingeniería Civil', 'Licenciatura', 1);
INSERT INTO CARRERA VALUES (3, 'Administración de Empresas', 'Licenciatura', 2);
INSERT INTO CARRERA VALUES (4, 'Contaduría Pública y Auditoría', 'Licenciatura', 2);
INSERT INTO CARRERA VALUES (5, 'Profesorado de Enseñanza Media', 'Técnico', 3);
INSERT INTO CARRERA VALUES (6, 'Derecho', 'Licenciatura', 4);

-- ---- ALUMNO (fk_carreraId) ----
INSERT INTO ALUMNO VALUES (1, '20231001', 'Silvia', 'Morales Gómez', 'silvia.morales@umg.edu.gt', 1);
INSERT INTO ALUMNO VALUES (2, '20231002', 'Diego', 'Ramírez Solís', 'diego.ramirez@umg.edu.gt', 1);
INSERT INTO ALUMNO VALUES (3, '20231003', 'Karla', 'Fuentes López', 'karla.fuentes@umg.edu.gt', 2);
INSERT INTO ALUMNO VALUES (4, '20231004', 'Estuardo', 'Pérez Castillo', 'estuardo.perez@umg.edu.gt', 3);
INSERT INTO ALUMNO VALUES (5, '20231005', 'Andrea', 'Chávez Ruiz', 'andrea.chavez@umg.edu.gt', 3);
INSERT INTO ALUMNO VALUES (6, '20231006', 'Byron', 'Ortiz Vásquez', 'byron.ortiz@umg.edu.gt', 4);
INSERT INTO ALUMNO VALUES (7, '20231007', 'Melissa', 'Aguilar Paz', 'melissa.aguilar@umg.edu.gt', 4);
INSERT INTO ALUMNO VALUES (8, '20231008', 'Josué', 'Luna Estrada', 'josue.luna@umg.edu.gt', 5);
INSERT INTO ALUMNO VALUES (9, '20231009', 'Wendy', 'García Reyes', 'wendy.garcia@umg.edu.gt', 6);
INSERT INTO ALUMNO VALUES (10, '20231010', 'Alejandro', 'Cruz Hernández', 'alejandro.cruz@umg.edu.gt', 6);

-- ---- CURSO (fk_carreraId) ----
INSERT INTO CURSO VALUES (1, 'ISW-101', 'Introducción a la Programación', 5, 1);
INSERT INTO CURSO VALUES (2, 'ISW-102', 'Estructuras de Datos', 5, 1);
INSERT INTO CURSO VALUES (3, 'ISW-201', 'Bases de Datos I', 4, 1);
INSERT INTO CURSO VALUES (4, 'ISW-202', 'Bases de Datos II', 4, 1);
INSERT INTO CURSO VALUES (5, 'ICV-101', 'Dibujo Técnico', 3, 2);
INSERT INTO CURSO VALUES (6, 'ICV-102', 'Mecánica de Suelos', 5, 2);
INSERT INTO CURSO VALUES (7, 'ADE-101', 'Fundamentos de Administración', 4, 3);
INSERT INTO CURSO VALUES (8, 'ADE-102', 'Contabilidad General', 4, 4);
INSERT INTO CURSO VALUES (9, 'PEM-101', 'Pedagogía General', 3, 5);
INSERT INTO CURSO VALUES (10, 'DER-101', 'Introducción al Derecho', 4, 6);

-- ---- PRERREQUISITO (fk_cursoId, fk_cursoPrerequisitoId) ----
INSERT INTO PRERREQUISITO VALUES (2, 1);   -- Estructuras de Datos requiere Introducción a la Programación
INSERT INTO PRERREQUISITO VALUES (3, 2);   -- Bases de Datos I requiere Estructuras de Datos
INSERT INTO PRERREQUISITO VALUES (4, 3);   -- Bases de Datos II requiere Bases de Datos I
INSERT INTO PRERREQUISITO VALUES (6, 5);   -- Mecánica de Suelos requiere Dibujo Técnico
INSERT INTO PRERREQUISITO VALUES (8, 7);   -- Contabilidad General requiere Fundamentos de Administración

-- ---- SECCION (fk_cursoId, fk_catedraticoId, fk_periodoId) ----
INSERT INTO SECCION VALUES (1, 1, 1, 1, 'Lunes y Miércoles 07:00-08:30', 'A-101', 30);
INSERT INTO SECCION VALUES (2, 2, 1, 1, 'Martes y Jueves 07:00-08:30', 'A-102', 30);
INSERT INTO SECCION VALUES (3, 3, 2, 1, 'Lunes y Miércoles 08:30-10:00', 'A-201', 25);
INSERT INTO SECCION VALUES (4, 4, 2, 2, 'Martes y Jueves 08:30-10:00', 'A-202', 25);
INSERT INTO SECCION VALUES (5, 5, 3, 1, 'Viernes 07:00-10:00', 'B-101', 20);
INSERT INTO SECCION VALUES (6, 6, 3, 2, 'Sábado 07:00-10:00', 'B-102', 20);
INSERT INTO SECCION VALUES (7, 7, 4, 1, 'Lunes y Miércoles 18:00-19:30', 'C-101', 35);
INSERT INTO SECCION VALUES (8, 8, 5, 1, 'Martes y Jueves 18:00-19:30', 'C-102', 35);
INSERT INTO SECCION VALUES (9, 9, 6, 2, 'Sábado 08:00-11:00', 'D-101', 40);
INSERT INTO SECCION VALUES (10, 10, 7, 1, 'Viernes 18:00-21:00', 'E-101', 30);

-- ---- INSCRIPCION (fk_alumnoId, fk_seccionId) ----
INSERT INTO INSCRIPCION VALUES (1, 1, 1, DATE '2026-01-15', 'CONFIRMADO', 90);
INSERT INTO INSCRIPCION VALUES (2, 1, 3, DATE '2026-01-15', 'CONFIRMADO', 85);
INSERT INTO INSCRIPCION VALUES (3, 2, 1, DATE '2026-01-16', 'CONFIRMADO', 78);
INSERT INTO INSCRIPCION VALUES (4, 2, 2, DATE '2026-01-16', 'CONFIRMADO', 88);
INSERT INTO INSCRIPCION VALUES (5, 3, 5, DATE '2026-01-17', 'CONFIRMADO', 92);
INSERT INTO INSCRIPCION VALUES (6, 4, 7, DATE '2026-01-18', 'CONFIRMADO', 75);
INSERT INTO INSCRIPCION VALUES (7, 5, 7, DATE '2026-01-18', 'RETIRADO', NULL);
INSERT INTO INSCRIPCION VALUES (8, 6, 8, DATE '2026-01-19', 'CONFIRMADO', 81);
INSERT INTO INSCRIPCION VALUES (9, 7, 8, DATE '2026-01-19', 'CONFIRMADO', 95);
INSERT INTO INSCRIPCION VALUES (10, 8, 9, DATE '2026-01-20', 'CONFIRMADO', 89);
INSERT INTO INSCRIPCION VALUES (11, 9, 10, DATE '2026-01-20', 'CONFIRMADO', 70);
INSERT INTO INSCRIPCION VALUES (12, 10, 10, DATE '2026-01-21', 'INSCRITO', NULL);

-- ---- ASIGNACION_CATEDRATICO (fk_catedraticoId, fk_cursoId, fk_facultadId, fk_periodoId) ----
INSERT INTO ASIGNACION_CATEDRATICO VALUES (1, 1, 1, 1, 1, DATE '2026-01-12', DATE '2026-05-15');
INSERT INTO ASIGNACION_CATEDRATICO VALUES (2, 1, 2, 1, 1, DATE '2026-01-12', DATE '2026-05-15');
INSERT INTO ASIGNACION_CATEDRATICO VALUES (3, 2, 3, 1, 1, DATE '2026-01-12', DATE '2026-05-15');
INSERT INTO ASIGNACION_CATEDRATICO VALUES (4, 2, 4, 1, 2, DATE '2026-07-06', DATE '2026-11-13');
INSERT INTO ASIGNACION_CATEDRATICO VALUES (5, 3, 5, 1, 1, DATE '2026-01-12', DATE '2026-05-15');
INSERT INTO ASIGNACION_CATEDRATICO VALUES (6, 3, 6, 1, 2, DATE '2026-07-06', DATE '2026-11-13');
INSERT INTO ASIGNACION_CATEDRATICO VALUES (7, 4, 7, 2, 1, DATE '2026-01-12', DATE '2026-05-15');
INSERT INTO ASIGNACION_CATEDRATICO VALUES (8, 5, 8, 2, 1, DATE '2026-01-12', DATE '2026-05-15');

COMMIT;


-- ============================================================================
-- 4. CREACIÓN DE VISTAS
-- ============================================================================

-- ---- Vista 1: Alumnos con su carrera y facultad ----
CREATE OR REPLACE VIEW VW_ALUMNOS_CARRERA AS
SELECT
    a.pk_alumnoId,
    a.carnet,
    a.nombres || ' ' || a.apellidos AS nombre_completo,
    a.email,
    c.nombre  AS carrera,
    c.nivel,
    f.nombre  AS facultad
FROM ALUMNO a, CARRERA c, FACULTAD f
WHERE a.fk_carreraId  = c.pk_carreraId
  AND c.fk_facultadId = f.pk_facultadId;

-- ---- Vista 2: Secciones con catedrático, curso, periodo y cupo disponible ----
CREATE OR REPLACE VIEW VW_SECCIONES_DISPONIBLES AS
SELECT
    s.pk_seccionId,
    cur.codigo AS codigo_curso,
    cur.nombre AS curso,
    cat.nombres || ' ' || cat.apellidos AS catedratico,
    per.nombre AS periodo,
    s.horario,
    s.aula,
    s.cupo,
    COUNT(ins.pk_inscripcionId) AS inscritos,
    s.cupo - COUNT(ins.pk_inscripcionId) AS cupo_disponible
FROM SECCION s, CURSO cur, CATEDRATICO cat, PERIODO_ACADEMICO per, INSCRIPCION ins
WHERE s.fk_cursoId       = cur.pk_cursoId
  AND s.fk_catedraticoId = cat.pk_catedraticoId
  AND s.fk_periodoId     = per.pk_periodoId
  AND s.pk_seccionId     = ins.fk_seccionId (+)
  AND ins.estado (+) IN ('INSCRITO','CONFIRMADO')
GROUP BY s.pk_seccionId, cur.codigo, cur.nombre, cat.nombres, cat.apellidos,
         per.nombre, s.horario, s.aula, s.cupo;

-- ---- Vista 3: Historial académico (inscripciones con detalle completo) ----
CREATE OR REPLACE VIEW VW_HISTORIAL_ACADEMICO AS
SELECT
    al.pk_alumnoId,
    al.carnet,
    al.nombres || ' ' || al.apellidos AS alumno,
    cur.codigo AS codigo_curso,
    cur.nombre AS curso,
    per.nombre AS periodo,
    ins.fecha,
    ins.estado,
    ins.nota
FROM INSCRIPCION ins, ALUMNO al, SECCION sec, CURSO cur, PERIODO_ACADEMICO per
WHERE ins.fk_alumnoId  = al.pk_alumnoId
  AND ins.fk_seccionId = sec.pk_seccionId
  AND sec.fk_cursoId   = cur.pk_cursoId
  AND sec.fk_periodoId = per.pk_periodoId;
