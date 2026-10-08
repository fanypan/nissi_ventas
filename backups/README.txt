=============================================================================
BACKUP COMPLETO GENERADO - SISTEMA DE VENTAS
=============================================================================

📁 UBICACIÓN: C:\xampp\htdocs\Sistemaventas\backups\

📊 ARCHIVOS GENERADOS:

1. backup_completo_ventas_bd.sql (515 KB)
   - Dump SQL completo con estructura y datos
   - 31 tablas incluidas
   - Compatible con MySQL 5.7+ y MariaDB 10.0+

2. backup_completo_ventas_bd_LEEME.txt
   - Documentación completa del backup
   - Instrucciones detalladas para restaurar
   - Información de todas las tablas incluidas

3. restore_db.bat (Windows)
   - Script automático para restaurar en Windows
   - Uso: restore_db.bat [host] [usuario] [contraseña] [base_datos]
   - Ejemplo: restore_db.bat localhost root "" ventas_bd_new

4. restore_db.py (Multiplataforma)
   - Script en Python para restaurar en cualquier OS
   - Uso: python3 restore_db.py [host] [usuario] [contraseña] [base_datos]
   - Requiere Python 3.6+

=============================================================================
INSTALACIÓN EN OTRO SISTEMA
=============================================================================

OPCIÓN 1: USAR EL SCRIPT BATCH (Windows) ✅ RECOMENDADO
────────────────────────────────────────────────────────
1. Copiar la carpeta "backups" al nuevo servidor
2. Abrir comando/PowerShell en la carpeta
3. Ejecutar: restore_db.bat
4. O con parámetros: restore_db.bat localhost root "" ventas_bd

OPCIÓN 2: USAR EL SCRIPT PYTHON (Multiplataforma)
────────────────────────────────────────────────────────
1. Copiar la carpeta "backups" al nuevo servidor
2. Abrir terminal en la carpeta
3. Ejecutar: python3 restore_db.py
4. O con parámetros: python3 restore_db.py localhost root "" ventas_bd

OPCIÓN 3: COMANDO DIRECTO
────────────────────────────────────────────────────────
mysql -u root -h localhost < backup_completo_ventas_bd.sql

OPCIÓN 4: INTERFAZ GRÁFICA (phpMyAdmin)
────────────────────────────────────────────────────────
1. Abrir phpMyAdmin (http://localhost/phpmyadmin)
2. Ir a "Importar"
3. Seleccionar archivo "backup_completo_ventas_bd.sql"
4. Hacer clic en "Aceptar"

=============================================================================
CONTENIDO DEL BACKUP
=============================================================================

TOTAL DE TABLAS: 31

✓ ajuste_stock              ✓ marca_tamano
✓ bancos                    ✓ motivos_ajuste
✓ caja                      ✓ movimientos_lotes
✓ categoria                 ✓ pagos_qr
✓ categoria_marca           ✓ pagos_tarjeta
✓ cliente                   ✓ producto
✓ compras                   ✓ proveedor
✓ configuracion             ✓ rol
✓ consumo_insumos           ✓ tamano
✓ detalle_recibo            ✓ transferencias
✓ detalle_recibo_compra     ✓ usuario
✓ detalle_temp              ✓ venta
✓ detalle_temp_compra
✓ detalleventa
✓ egresos
✓ entradas
✓ insumos
✓ lotes
✓ marca

=============================================================================
REQUERIMIENTOS PARA RESTAURAR
=============================================================================

Servidor Destino:
  ✓ MySQL 5.7+ o MariaDB 10.0+
  ✓ Usuario con privilegios: CREATE, DROP, SELECT, INSERT, UPDATE, DELETE
  ✓ ~2 MB de espacio libre en disco
  ✓ Acceso a la línea de comandos MySQL

Sistema Operativo:
  ✓ Windows (usar restore_db.bat)
  ✓ Linux/Mac (usar restore_db.py o comando directo)

=============================================================================
VERIFICACIÓN DESPUÉS DE RESTAURAR
=============================================================================

Ejecutar estos comandos para verificar:

mysql -u root -h localhost

USE ventas_bd;
SHOW TABLES;
SELECT COUNT(*) as total_tablas FROM information_schema.tables 
  WHERE table_schema='ventas_bd';
SELECT COUNT(*) as productos FROM producto;
SELECT COUNT(*) as clientes FROM cliente;
SELECT COUNT(*) as ventas FROM venta;

=============================================================================
NOTAS IMPORTANTES
=============================================================================

⚠️  El backup sobrescribirá la base de datos si ya existe
⚠️  Los datos están en UTF-8
⚠️  Las relaciones (foreign keys) están incluidas
⚠️  Los IDs auto-incrementales se preservarán
✅ Hecho: 26 de Julio de 2026
✅ Usuario: root
✅ Codificación: UTF-8 / UTF-8mb4
✅ Backup íntegro: SÍ

=============================================================================
SOPORTE
=============================================================================

Si encuentras errores:

1. Verificar que MySQL esté corriendo
2. Verificar credenciales (usuario, contraseña, host)
3. Verificar que la base de datos está vacía (si es nueva)
4. Revisar permisos del usuario MySQL
5. Ejecutar manualmente para ver mensajes de error:
   mysql -u root -h localhost < backup_completo_ventas_bd.sql

=============================================================================
