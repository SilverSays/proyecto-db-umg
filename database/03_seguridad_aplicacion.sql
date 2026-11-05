-- ============================================================================
-- Sistema Académico UMG · Script 03
-- Objetos de soporte para la aplicación web:
--   * USUARIO_APP : cuentas del portal (contraseñas con hash bcrypt)
--   * BITACORA    : auditoría de accesos y operaciones CRUD
--   * Secuencias  : generación de llaves primarias para todas las tablas
--
-- Ejecutar conectado como UMG_ACADEMICO, DESPUÉS del script 02.
-- ============================================================================

BEGIN EXECUTE IMMEDIATE 'DROP TABLE BITACORA CASCADE CONSTRAINTS'; EXCEPTION WHEN OTHERS THEN NULL; END;
/
BEGIN EXECUTE IMMEDIATE 'DROP TABLE USUARIO_APP CASCADE CONSTRAINTS'; EXCEPTION WHEN OTHERS THEN NULL; END;
/

-- ---- USUARIO_APP ----
CREATE TABLE USUARIO_APP (
    pk_usuarioId       NUMBER(5)      NOT NULL,
    username           VARCHAR2(30)   NOT NULL,
    nombre_completo    VARCHAR2(120)  NOT NULL,
    email              VARCHAR2(100)  NOT NULL,
    password_hash      VARCHAR2(255)  NOT NULL,
    rol                VARCHAR2(20)   DEFAULT 'CONSULTA' NOT NULL,
    activo             NUMBER(1)      DEFAULT 1 NOT NULL,
    intentos_fallidos  NUMBER(3)      DEFAULT 0 NOT NULL,
    bloqueado_hasta    TIMESTAMP,
    ultimo_acceso      TIMESTAMP,
    fecha_creacion     TIMESTAMP      DEFAULT SYSTIMESTAMP NOT NULL,
    CONSTRAINT pk_usuario_app      PRIMARY KEY (pk_usuarioId),
    CONSTRAINT uq_usuario_username UNIQUE (username),
    CONSTRAINT uq_usuario_email    UNIQUE (email),
    CONSTRAINT ck_usuario_rol      CHECK (rol IN ('ADMIN','CONSULTA')),
    CONSTRAINT ck_usuario_activo   CHECK (activo IN (0,1))
);

COMMENT ON TABLE  USUARIO_APP               IS 'Cuentas de acceso al portal web del Sistema Académico';
COMMENT ON COLUMN USUARIO_APP.password_hash IS 'Hash bcrypt generado con password_hash() de PHP';
COMMENT ON COLUMN USUARIO_APP.rol           IS 'ADMIN: lectura y escritura | CONSULTA: solo lectura';

-- ---- BITACORA ----
CREATE TABLE BITACORA (
    pk_bitacoraId  NUMBER(10)      NOT NULL,
    fk_usuarioId   NUMBER(5),
    username       VARCHAR2(30)    NOT NULL,
    accion         VARCHAR2(20)    NOT NULL,
    tabla          VARCHAR2(40),
    registro       VARCHAR2(100),
    detalle        VARCHAR2(4000),
    ip             VARCHAR2(45),
    fecha          TIMESTAMP       DEFAULT SYSTIMESTAMP NOT NULL,
    CONSTRAINT pk_bitacora PRIMARY KEY (pk_bitacoraId),
    CONSTRAINT fk_bitacora_usuario FOREIGN KEY (fk_usuarioId)
        REFERENCES USUARIO_APP (pk_usuarioId) ON DELETE SET NULL,
    CONSTRAINT ck_bitacora_accion CHECK (accion IN
        ('LOGIN','LOGIN_FALLIDO','LOGOUT','BLOQUEO','INSERT','UPDATE','DELETE','PASSWORD'))
);

CREATE INDEX ix_bitacora_fecha   ON BITACORA (fecha);
CREATE INDEX ix_bitacora_usuario ON BITACORA (fk_usuarioId);

COMMENT ON TABLE BITACORA IS 'Registro de auditoría de accesos y cambios realizados desde el portal';

-- ---- Usuarios iniciales de demostración ----
--   admin    / Admin#2026     (rol ADMIN: CRUD completo)
--   consulta / Consulta#2026  (rol CONSULTA: solo lectura)
-- Cambiar contraseñas antes de publicar proyecto
INSERT INTO USUARIO_APP (pk_usuarioId, username, nombre_completo, email, password_hash, rol)
VALUES (1, 'admin', 'Administrador del Sistema', 'admin@umg.edu.gt',
        '$2y$10$08/4xPKuWAInHUE43R.m7.jH6zfDQ4s3tgPKeH3SivVwsg8C4Jqd2', 'ADMIN');

INSERT INTO USUARIO_APP (pk_usuarioId, username, nombre_completo, email, password_hash, rol)
VALUES (2, 'consulta', 'Usuario de Consulta', 'consulta@umg.edu.gt',
        '$2y$10$/LQaKkZs1FaWS67boEk.Qu.G7Kt32VDmiycANTOT65QwAn3rZCM.2', 'CONSULTA');

COMMIT;

-- ---- Secuencias ----
DECLARE
    PROCEDURE crear_secuencia(p_secuencia VARCHAR2, p_tabla VARCHAR2, p_pk VARCHAR2) IS
        v_inicio NUMBER;
    BEGIN
        BEGIN
            EXECUTE IMMEDIATE 'DROP SEQUENCE ' || p_secuencia;
        EXCEPTION WHEN OTHERS THEN NULL;
        END;
        EXECUTE IMMEDIATE 'SELECT NVL(MAX(' || p_pk || '), 0) + 1 FROM ' || p_tabla INTO v_inicio;
        EXECUTE IMMEDIATE 'CREATE SEQUENCE ' || p_secuencia ||
                          ' START WITH ' || v_inicio || ' INCREMENT BY 1 NOCACHE NOCYCLE';
    END;
BEGIN
    crear_secuencia('SEQ_FACULTAD',               'FACULTAD',               'pk_facultadId');
    crear_secuencia('SEQ_CATEDRATICO',            'CATEDRATICO',            'pk_catedraticoId');
    crear_secuencia('SEQ_PERIODO_ACADEMICO',      'PERIODO_ACADEMICO',      'pk_periodoId');
    crear_secuencia('SEQ_CARRERA',                'CARRERA',                'pk_carreraId');
    crear_secuencia('SEQ_ALUMNO',                 'ALUMNO',                 'pk_alumnoId');
    crear_secuencia('SEQ_CURSO',                  'CURSO',                  'pk_cursoId');
    crear_secuencia('SEQ_SECCION',                'SECCION',                'pk_seccionId');
    crear_secuencia('SEQ_INSCRIPCION',            'INSCRIPCION',            'pk_inscripcionId');
    crear_secuencia('SEQ_ASIGNACION_CATEDRATICO', 'ASIGNACION_CATEDRATICO', 'pk_asignacionId');
    crear_secuencia('SEQ_USUARIO_APP',            'USUARIO_APP',            'pk_usuarioId');
    crear_secuencia('SEQ_BITACORA',               'BITACORA',               'pk_bitacoraId');
END;
/
