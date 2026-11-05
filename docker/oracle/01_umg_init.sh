#!/bin/bash
# ---------------------------------------------------------------------------
# Inicialización de la base de datos (se ejecuta solo en el primer arranque
# del contenedor gvenzl/oracle-free, cuando el volumen está vacío).
#   01 -> como SYS en la PDB: crea el usuario UMG_ACADEMICO
#   02 -> como UMG_ACADEMICO: tablas, datos de prueba y vistas
#   03 -> como UMG_ACADEMICO: usuarios del portal, bitácora y secuencias
# Nota: este archivo es "sourced" por el entrypoint de la imagen; no usar exit.
# ---------------------------------------------------------------------------

export NLS_LANG=AMERICAN_AMERICA.AL32UTF8
UMG_PDB="${ORACLE_DATABASE:-FREEPDB1}"
UMG_SQL_DIR=/opt/umg/sql
UMG_CONN='umg_academico/"Umg_Acad_2026#"@//localhost:1521/'"${UMG_PDB}"

echo "[UMG] Creando el esquema UMG_ACADEMICO en ${UMG_PDB}..."
sqlplus -s -L / as sysdba <<SQL
WHENEVER SQLERROR EXIT SQL.SQLCODE
ALTER SESSION SET CONTAINER = ${UMG_PDB};
@${UMG_SQL_DIR}/01_crear_usuario.sql
EXIT
SQL

for script in 02_esquema_y_datos.sql 03_seguridad_aplicacion.sql; do
    echo "[UMG] Ejecutando ${script}..."
    sqlplus -s -L "${UMG_CONN}" <<SQL
SET DEFINE OFF
@${UMG_SQL_DIR}/${script}
EXIT
SQL
done

echo "[UMG] Base de datos lista."
