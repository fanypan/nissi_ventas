#!/usr/bin/env python3
"""
Script para restaurar el backup de la base de datos ventas_bd
Uso: python3 restore_db.py [host] [usuario] [contraseña] [base_datos]
Ejemplo: python3 restore_db.py localhost root "" ventas_bd_new
"""

import subprocess
import sys
import os

def restore_backup(host, user, password, database, backup_file):
    """
    Restaura el backup SQL en la base de datos especificada
    """
    print(f"Iniciando restauración del backup...")
    print(f"Host: {host}")
    print(f"Usuario: {user}")
    print(f"Base de datos: {database}")
    print(f"Archivo: {backup_file}")
    print("-" * 50)
    
    if not os.path.exists(backup_file):
        print(f"❌ Error: El archivo {backup_file} no existe")
        return False
    
    try:
        # Construir comando mysql
        if password:
            cmd = f'mysql -h {host} -u {user} -p{password} {database}'
        else:
            cmd = f'mysql -h {host} -u {user} {database}'
        
        # Ejecutar restauración
        with open(backup_file, 'r', encoding='utf-8') as f:
            process = subprocess.Popen(cmd, stdin=f, stdout=subprocess.PIPE, 
                                     stderr=subprocess.PIPE, shell=True)
            stdout, stderr = process.communicate()
            
            if process.returncode == 0:
                print("✅ Backup restaurado exitosamente!")
                return True
            else:
                print(f"❌ Error durante la restauración:")
                print(stderr.decode('utf-8'))
                return False
                
    except Exception as e:
        print(f"❌ Error: {str(e)}")
        return False

if __name__ == "__main__":
    # Parámetros por defecto
    host = "localhost"
    user = "root"
    password = ""
    database = "ventas_bd"
    backup_file = "backup_completo_ventas_bd.sql"
    
    # Procesar argumentos de línea de comandos
    if len(sys.argv) > 1:
        host = sys.argv[1]
    if len(sys.argv) > 2:
        user = sys.argv[2]
    if len(sys.argv) > 3:
        password = sys.argv[3]
    if len(sys.argv) > 4:
        database = sys.argv[4]
    if len(sys.argv) > 5:
        backup_file = sys.argv[5]
    
    # Ejecutar restauración
    success = restore_backup(host, user, password, database, backup_file)
    sys.exit(0 if success else 1)
