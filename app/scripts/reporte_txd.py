
import requests 
import pyodbc
import pandas as pd
import json
import datetime
import csv
import mysql.connector
import psycopg2
from ftplib import FTP
import chardet

from datetime import date #
from datetime import timedelta #

import os
import paramiko 

from cryptography.hazmat.primitives import serialization

import urllib3 #
urllib3.disable_warnings()

import logging #
import mysql.connector #
import sys


import logging

log_filename = 'log_reporte_txd.log'
logging.basicConfig(filename=log_filename, level=logging.INFO,
                    format='%(asctime)s - %(levelname)s - %(message)s')

# Ruta absoluta de este script
SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))

# Asumiendo que el script está en: /var/www/mi-proyecto/app/scripts/
# Retrocedemos 2 niveles para llegar a la raíz del proyecto Laravel
BASE_PROJECT_DIR = os.path.abspath(os.path.join(SCRIPT_DIR, "../../"))

# Carpeta donde Laravel guarda los archivos en storage/app/public
BASE_LOCAL = os.path.join(BASE_PROJECT_DIR, "storage", "app", "public")


def inicio(tipo): 
    #credenciales del ftp
    hostname = "172.16.1.20"
    port = 2323
    username = "userrepo"
    password = ".R3p0rt3txd!"


    # try:
    #     # ssh = paramiko.SSHClient()

    #     # Carga la llave privada

    #     # Establece política de aceptación automática de claves SSH desconocidas 
    #     # ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    #     # Conéctate al servidor SFTP
    #     # ssh.connect(hostname, port, username, password = password)

    #     # Abre una conexión SFTP
    #     # sftp = ssh.open_sftp()
    # except Exception as e:
    #     logging.error("Error al conectar por ssh: %s", str(e))
    #     sys.exit(1)
    
    if(tipo == 1):    
        carpeta = 'reporte_txd/Oechsle'
        ruta_remota = os.path.join(BASE_LOCAL, *carpeta.split('/'))
        ruta_local = os.path.join(BASE_LOCAL, *carpeta.split('/'))
        # ruta_local = os.path.join(BASE_LOCAL, "oechsle")
    if(tipo == 2):   
        carpeta = 'reporte_txd/ripley'
        ruta_remota = os.path.join(BASE_LOCAL, *carpeta.split('/'))
        ruta_local = os.path.join(BASE_LOCAL, *carpeta.split('/'))
        # ruta_local = os.path.join(BASE_LOCAL, "ripley")
    if(tipo == 3):   
        carpeta = 'reporte_txd/saga/stock'
        ruta_remota = os.path.join(BASE_LOCAL, *carpeta.split('/'))
        ruta_local = os.path.join(BASE_LOCAL, *carpeta.split('/'))
        # ruta_local = os.path.join(BASE_LOCAL, "saga", "stock")
    if(tipo == 4):   
        carpeta = 'reporte_txd/saga/ventas'
        ruta_remota = os.path.join(BASE_LOCAL, *carpeta.split('/'))
        ruta_local = os.path.join(BASE_LOCAL, *carpeta.split('/'))
        # ruta_local = os.path.join(BASE_LOCAL, "saga", "ventas")
        
    #nombre_archivo = 'arhivo_prueba.txt'
    archivos_remotos = os.listdir(ruta_remota)
    # archivos_remotos = sftp.listdir(ruta_remota)
    print("Archivos remotos a descargar:", archivos_remotos)
    #descargar_archivo_ftp(hostname, username, password, ruta_remota, ruta_local)
    #validación de los archivos

    ##
    
    # Conexión a la base de datos MySQL
    conn = psycopg2.connect(
        host="172.16.1.23",
        database="smartanalytic",
        user="postgres",
        password="theodenx"
    )

    


    for archivo_remoto in archivos_remotos:
        ruta_completa_remota = f"{ruta_remota}/{archivo_remoto}"
        ruta_completa_local = f"{ruta_local}/{archivo_remoto}"
        # print(ruta_completa_remota)
        # print(ruta_completa_local)
        if(buscarPorNombre(archivo_remoto)):
            # sftp.remove(ruta_completa_remota)
            print("saltas")
        else:
            
            # sftp.get(ruta_completa_remota, ruta_completa_local)
            # print(f"Archivo descargado: {ruta_completa_local}")
            cursor = conn.cursor()
            
            # Extraer el nombre del archivo y los dos primeros caracteres
            result_tipo_archivo = validar_tipo_archivo(archivo_remoto)
            #print(f"Nombre del archivo: {nombre_archivo}, Dos primeros caracteres: {dos_primeros_caracteres}")
            if(result_tipo_archivo):
                
                print("archivo valido")
                control_tablas(tipo,conn, cursor, ruta_completa_local)

    # sftp.close()

   
def control_tablas(tipo, conn, cursor, ruta_completa_local):
    print("archivo valido")
    # Leer el archivo Excel descargado en la ruta local
    # df = pd.read_excel('ruta/al/archivo.xlsx', sheet_name='nombre_de_hoja')
    print(ruta_completa_local)
    
    with open(ruta_completa_local, 'rb') as f:
        result = chardet.detect(f.read(10000))  # Leer los primeros 10,000 bytes
        encoding = result['encoding']
        
    if(tipo == 1): ## Oechsle tiene dos archivos csv por eso validamos los delimitadores
        with open(ruta_completa_local, 'r', encoding=encoding) as csvfile:
            sample = csvfile.read(2000)  # Leer las primeras 2000 bytes para la detección del delimitador
            dialect = csv.Sniffer().sniff(sample)
            delimiter = dialect.delimiter
    
    try:
        if(tipo == 1):
            df = pd.read_csv(ruta_completa_local, encoding=encoding, delimiter=delimiter)##COMENTTADO
        if(tipo == 2):
            df = pd.read_excel(ruta_completa_local, sheet_name='TD1')##COMENTTADO
        if(tipo == 3 ):
            df = pd.read_excel(ruta_completa_local, sheet_name='Product details')##
        if(tipo == 4 ):
            df = pd.read_excel(ruta_completa_local, sheet_name='Sheet 1')##
        
    except UnicodeDecodeError:
        df = pd.read_csv(ruta_completa_local, encoding='latin1', delimiter=',')##COMENTADO
        # df = pd.read_excel(ruta_completa_local, sheet_name='TD1')##COMENTADO
                
    
    #print(df)                
    
    
    # Seleccionar las columnas específicas para insertar
    #print(df.info())
    if(tipo == 1):
        fecha_actual = datetime.datetime.now().strftime("%Y-%m-%d")
        #fecha_actual = '2024-05-11'
        #df.iloc[:, 0] = fecha_actual
        df.iloc[:, 0] = df.iloc[:, 0].str.split(' al ').str[0]

        columnas_seleccionadas = ['PERIODO','COD_OECHSLE','DESCRIPCION_PRODUCTO','MARCA','COD_LOCAL','DESCRIPCION_LOCAL','VTA_PERIODO_S', 'VTA_PERIODO_UNID','INVENTARIO_S', 'INVENTARIO_UNID']
        
        for col in columnas_seleccionadas:
            if col not in df.columns:
                df[col] = pd.NA  # Rellenar con NaN (Not a Number)
        # Filtrar las columnas que existen en el DataFrame
        #columnas_seleccionadas = [col for col in columnas_deseadas if col in df.columns]
        
    if (tipo == 2):
        df = pd.read_excel(
            ruta_completa_local,
            sheet_name='TD1',
            dtype={
                'Codigo Modelo': str,
                'Codigo Variacion': str
            }
        )

        fecha_actual = datetime.datetime.now().strftime("%Y-%m-%d")
        #fecha_actual = '2024-05-11'
        #df.iloc[:, 0] = fecha_actual
        # Convertir la primera columna de fechas al formato deseado
        df.iloc[:, 0] = pd.to_datetime(df.iloc[:, 0], format='%d-%m-%Y').dt.strftime('%Y-%m-%d')
        # df['Codigo Modelo'] = df['Codigo Modelo'].astype(str)
        
        columnas_seleccionadas = [
            'Fecha',
            'Marca',
            'Temporada',
            'Sucursal',
            'Suma de Costo Venta Actual',
            'Codigo Modelo',
            'Nombre Modelo',
            'Suma de Rebates Actual',
            'Codigo Variacion',
            'Nombre Variacion',
            'Suma de Venta S/.',
            'Suma de Venta Unid.',
            'Suma de Contr. S/.',
            'Suma de Stock S/.',
            'Suma de Stock Und.'
            ] 
    if (tipo == 3):###
        #tabla pla_stock_txd (STOCK)
        columnas_seleccionadas = ['SKU GSC','Descripción','TEMPORADA','Stock Disponible','Marca']
        # Agrupar por 'SKU GSC' y sumar las columnas de días
        df_seleccionado = df[columnas_seleccionadas]
        df_seleccionado = df_seleccionado.groupby(['SKU GSC', 'Descripción'], as_index=False).sum()
    if (tipo == 4):
        #(Ventas)
        columnas_seleccionadas = ['Falabella SKU','Marca','Descripción','Paid Price','Created at']
    
    df_seleccionado = df[columnas_seleccionadas]
    
    if(tipo == 4):
        # Extraer el día de 'Created at'
        df['Day'] = pd.to_datetime(df['Created at'], format='%b %d, %Y %H:%M').dt.day
        
        # Obtener la lista de días únicos presentes en los datos
        days = df['Day'].unique()
        days.sort()
        
        # Obtener el rango de 7 días consecutivos basado en el primer día en los datos
        min_day = min(days)
        max_day = min_day + 6
        full_week_days = list(range(min_day, max_day + 1))

        # Encontrar los días faltantes y agregar columnas para ellos llenas con 0
        missing_days = set(full_week_days) - set(days)
        print(missing_days)
        for day in missing_days:
            df[str(day)] = 0
        
        # Crear las nuevas columnas de días y llenar con 0 inicialmente
        for day in full_week_days:
            if day not in df.columns:
                df[str(day)] = 0
            
        # Llenar las columnas de días con 1 si el día coincide
        for day in days:
            df.loc[df['Day'] == day, str(day)] = 1

        # Crear la columna 'Total general'
        df['Total general'] = df[[str(day) for day in days]].sum(axis=1)
        
        # Seleccionar y reordenar las columnas finales
        final_columns = ['Falabella SKU', 'Descripción'] + [str(day) for day in full_week_days] + ['Total general'] + ['Paid Price']
        df_seleccionado = df[final_columns]
        
        # Agrupar por 'Falabella SKU' y sumar las columnas de días
        df_seleccionado = df_seleccionado.groupby(['Falabella SKU', 'Descripción', 'Paid Price'], as_index=False).sum()
    #agregar columna si el archivo viene de saga stock
    if(tipo == 3 or tipo == 4):
        df_seleccionado.insert(df_seleccionado.columns.get_loc('Descripción') + 1, 'sucursal', 'Tienda Virtual')
    #Reordena las columnas 
    if(tipo == 4):
        # Agregar las columnas 'nro_local' y 'skip' con valores NULL
        df_seleccionado['nro_local'] = None
        df_seleccionado['marca'] = None
        df_seleccionado['skip'] = None
        
        #final_columns_order = ['Falabella SKU', 'Descripción', 'sucursal'] + [str(day) for day in full_week_days] + ['Total general', 'Paid Price']
        final_columns_order = ['Falabella SKU', 'Descripción', 'sucursal'] + [str(day) for day in full_week_days] + ['Total general', 'Paid Price', 'nro_local', 'marca','skip']
        df_seleccionado = df_seleccionado[final_columns_order]

        
    # print("Seleccionado");                    
    print(df_seleccionado)
    #exit()
    temp_filepath = 'temp.csv'
    df_seleccionado.to_csv(temp_filepath, index=False, sep=';')### comentado
    #df_seleccionado.read_excel(temp_filepath, sheet_name='TD1'
    #insertar archivos en la base de datos Mysql. 
    #insert_data(cursor, df_seleccionado, conn)
    if(tipo == 1):
        #exit()
        #print("insertando")
        load_data_infile(conn, temp_filepath, 'pla_temp_oechsle_txd')##
    if(tipo == 2):
        load_data_infile(conn, temp_filepath, 'pla_temp_ripley_txd')##
    if(tipo == 3): #stock
        #exit()
        load_data_infile(conn, temp_filepath, 'pla_stock_txd')##
    if(tipo == 4): #ventas
        #exit()
        load_data_infile(conn, temp_filepath, 'pla_temp_saga_txd')##
    
    # load_data_infile(conn, temp_filepath, 'pla_temp_ripley_txd')
    # Confirmar los cambios y cerrar la conexión
    conn.commit()
    # cursor.close()
    # conn.close()
    return True


def validar_tipo_archivo(nombre_archivo):
    # Extraer el nombre del archivo
    #nombre_archivo = os.path.basename(nombre_archivo)

    # Verificar si el archivo es .txt
    if nombre_archivo.endswith('.csv'):
        # Extraer los dos primeros caracteres
        return 1 
    if nombre_archivo.endswith('.xlsx'):
        # Extraer los dos primeros caracteres
        return 2 
    return None  # Si no es un archivo .txt, retorna None


def buscarPorNombre(archivo_remoto):
    #validar que sea el achivo correcto 
    return False


def insert_data(cursor, df, conn):
    # Suponiendo que la tabla ya existe y tiene las columnas correspondientes
    cols = ",".join([str(i) for i in df.columns.tolist()])
    #print(cols)
    # Insertar fila por fila
    for i, row in df.iterrows():
        sql = f"INSERT INTO pla_temp_oechsle_txd (fecha,sku_txd,desc_sku,marca,cod_local,desc_local,vta_act,vta_unds,stk_soles,stk_unds) VALUES ({'%s, ' * (len(row) - 1)}%s)"
        cursor.execute(sql, tuple(row))
        conn.commit()
        #print(sql)
        #exit()
        print(i, row)

def descargar_archivo_ftp(ftp_host, ftp_user, ftp_passwd, remote_filepath, local_filepath):
    with FTP(ftp_host) as ftp:
        ftp.login(user=ftp_user, passwd=ftp_passwd)
        with open(local_filepath, 'wb') as local_file:
            ftp.retrbinary('RETR ' + remote_filepath, local_file.write)


def load_data_infile(conn, temp_filepath, table_name):
    with conn.cursor() as cursor:
        with open(temp_filepath, 'r', encoding='utf-8') as f:
            cursor.copy_expert(f"COPY {table_name} FROM STDIN WITH CSV HEADER DELIMITER ';'", f)


for i in range(1,5):
    inicio(i)

# import datetime

# with open("log_reporte_txd_py.txt", "a") as f:
#     f.write(f"\n✅ Script Python iniciado: {datetime.datetime.now()}\n")





