#!/bin/bash

cd /opt/docker/services/movil/movil || exit

LOG_PATH="./log_laravel_python.txt"

{
    echo ""
    echo "======= EJECUCIÓN ======="
    echo "Fecha: $(date)"
    echo "Usuario: $(whoami)"
    echo "PWD: $(pwd)"
    echo "Python path: $(which python3)"
    echo "Activando entorno virtual..."
} >> "$LOG_PATH"

# Activar entorno virtual
source .venv/bin/activate

{
    echo "Entorno activado."

    # echo "▶️ Probando imports de librerías..."
    # python3 -c "
    # import importlib
    # import sys

    # log_path = '$LOG_PATH'
    # librerias = ['pandas', 'psycopg2', 'requests', 'paramiko', 'chardet', 'mysql.connector']

    # with open(log_path, 'a') as f:
    #     for lib in librerias:
    #         try:
    #             importlib.import_module(lib)
    #             f.write(f'✅ Librería {lib} importada correctamente\\n')
    #         except Exception as e:
    #             f.write(f'❌ Error importando {lib}: {str(e)}\\n')
    #             sys.exit(1)
    #     f.write('✅ Todas las librerías se importaron con éxito\\n')
    # "

    echo "▶️ Ejecutando script Python principal..."
    python3 app/scripts/reporte_txd.py

    echo "✅ Script finalizado correctamente."
} >> "$LOG_PATH" 2>&1
