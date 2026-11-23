-- ============================================================
-- PARTE 3: Resetear cursores en fe_tiendas
-- Para que el motor re-detecte los documentos del 28 oct
-- (cuyos registros en fe_control_registros ya fueron borrados)
--
-- El motor usa: WHERE IDTRANSACCION > ultimo_idtransaccion_capturado
-- Se setea cada cursor al IDTRANSACCION mínimo del 28 oct menos 1
-- firstOrCreate garantiza que no se duplicarán los ya CAPTURADO
-- ============================================================

BEGIN;

UPDATE fe_tiendas SET ultimo_idtransaccion_capturado = 1080 WHERE codigo_tienda = 'FINAJP';
-- (B401-79 tiene IDTRANSACCION=1081; cursor actual era 1145)

UPDATE fe_tiendas SET ultimo_idtransaccion_capturado = 1105 WHERE codigo_tienda = 'JKY01';
-- (B402-115 tiene IDTRANSACCION=1106; cursor actual era 1208)

UPDATE fe_tiendas SET ultimo_idtransaccion_capturado = 1148 WHERE codigo_tienda = 'MCHJP';
-- (B403-153 tiene IDTRANSACCION=1149; cursor actual era 1297)

UPDATE fe_tiendas SET ultimo_idtransaccion_capturado = 37 WHERE codigo_tienda = 'MILKJP';
-- (B404-37 tiene IDTRANSACCION=38; cursor actual era 84)

UPDATE fe_tiendas SET ultimo_idtransaccion_capturado = 64 WHERE codigo_tienda = 'EXITCHI';
-- (B405-63 tiene IDTRANSACCION=65; cursor actual era 1101)

UPDATE fe_tiendas SET ultimo_idtransaccion_capturado = 50 WHERE codigo_tienda = 'MCHPIURA';
-- (B409-41 tiene IDTRANSACCION=51; cursor actual era 1109)

UPDATE fe_tiendas SET ultimo_idtransaccion_capturado = 92 WHERE codigo_tienda = 'MCHMPTRUJ';
-- (B411-89 tiene IDTRANSACCION=93; cursor actual era 2164)

UPDATE fe_tiendas SET ultimo_idtransaccion_capturado = 83 WHERE codigo_tienda = 'EXITPN';
-- (B412-7 tiene IDTRANSACCION=84; cursor actual era 196)

UPDATE fe_tiendas SET ultimo_idtransaccion_capturado = 1041 WHERE codigo_tienda = 'MLKMALSUR';
-- (B417-58 tiene IDTRANSACCION=1042; cursor actual era 3080)

UPDATE fe_tiendas SET ultimo_idtransaccion_capturado = 8 WHERE codigo_tienda = 'EXITSALAV';
-- (B418-9 tiene IDTRANSACCION=9; cursor actual era 96)

UPDATE fe_tiendas SET ultimo_idtransaccion_capturado = 46 WHERE codigo_tienda = 'EXITLR';
-- (B420-7 tiene IDTRANSACCION=47; cursor actual era 140)

UPDATE fe_tiendas SET ultimo_idtransaccion_capturado = 0 WHERE codigo_tienda = 'MCHCRONO';
-- (B431-1 tiene IDTRANSACCION=1; cursor actual era 50)

-- EXITMALSUR: NO incluido — la consulta SQL Server no devolvió filas para esta tienda.
-- Verifica manualmente con:
-- SELECT IDTRANSACCION, NUMERO_DOCUMENTO FROM [10.20.0.145].[soluflex_faro].[dbo].[CABECERA_DOCUMENTO]
-- WHERE NUMERO_SERIE = 416 AND NUMERO_DOCUMENTO IN (69,70,71,72,73)
-- Luego corre: UPDATE fe_tiendas SET ultimo_idtransaccion_capturado = <min-1> WHERE codigo_tienda = 'EXITMALSUR';

-- Verificar resultado
SELECT codigo_tienda, ultimo_idtransaccion_capturado, fecha_ultima_captura
FROM fe_tiendas
WHERE codigo_tienda IN (
  'FINAJP','JKY01','MCHJP','MILKJP','EXITCHI',
  'MCHPIURA','MCHMPTRUJ','EXITPN','EXITMALSUR',
  'MLKMALSUR','EXITSALAV','EXITLR','MCHCRONO'
)
ORDER BY codigo_tienda;

COMMIT;
