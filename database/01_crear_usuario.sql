-- ============================================================================
-- Sistema Académico UMG · Script 01
-- Creación del usuario/esquema de la aplicación.
--
-- Ejecutar conectado como SYSTEM (o SYS AS SYSDBA) en la PDB
-- (XEPDB1 en Oracle XE 21c / FREEPDB1 en Oracle 23ai Free).
-- ============================================================================

-- ============================================================================
-- 1. CREACIÓN DE USUARIO Y CONTRASEÑA
--    Ejecutar esta sección conectado como SYSTEM o un usuario con privilegios
--    de administración (SYSDBA).
-- ============================================================================

CREATE USER umg_academico IDENTIFIED BY "Umg_Acad_2026#";

GRANT CONNECT, RESOURCE TO umg_academico;
GRANT CREATE VIEW TO umg_academico;
GRANT UNLIMITED TABLESPACE TO umg_academico;

