-- ============================================================
-- FACTURAS oct28-30: resetear cursores en fe_tiendas
-- Cursor = min(IDTRANSACCION) de los documentos a recuperar - 1
-- ============================================================

BEGIN;

-- EXITPN: min IDTRANSACCION=77 (F412-1); cursor actual=198
UPDATE fe_tiendas SET ultimo_idtransaccion_capturado = 76 WHERE codigo_tienda = 'EXITPN';

-- EXITSALAV: min IDTRANSACCION=1 (F418-1); cursor actual=97
UPDATE fe_tiendas SET ultimo_idtransaccion_capturado = 0 WHERE codigo_tienda = 'EXITSALAV';

-- FINAPOLO: min IDTRANSACCION=43 (F413-1); cursor actual=1060
UPDATE fe_tiendas SET ultimo_idtransaccion_capturado = 42 WHERE codigo_tienda = 'FINAPOLO';

-- JKY01: min IDTRANSACCION=2 (F402-2); cursor actual=1209
UPDATE fe_tiendas SET ultimo_idtransaccion_capturado = 1 WHERE codigo_tienda = 'JKY01';

-- MCHCRONO: min IDTRANSACCION=1 (F431-1); cursor actual=50
UPDATE fe_tiendas SET ultimo_idtransaccion_capturado = 0 WHERE codigo_tienda = 'MCHCRONO';

-- MCHLR: min IDTRANSACCION=95 (F421-1); cursor actual=170
UPDATE fe_tiendas SET ultimo_idtransaccion_capturado = 94 WHERE codigo_tienda = 'MCHLR';

-- MCHMPTRUJ: min IDTRANSACCION=5 (F411-3); cursor actual=2164
UPDATE fe_tiendas SET ultimo_idtransaccion_capturado = 4 WHERE codigo_tienda = 'MCHMPTRUJ';

-- MCHPIURA: min IDTRANSACCION=7 (F409-6); cursor actual=1110
UPDATE fe_tiendas SET ultimo_idtransaccion_capturado = 6 WHERE codigo_tienda = 'MCHPIURA';

-- Verificar
SELECT codigo_tienda, ultimo_idtransaccion_capturado
FROM fe_tiendas
WHERE codigo_tienda IN ('EXITPN','EXITSALAV','FINAPOLO','JKY01','MCHCRONO','MCHLR','MCHMPTRUJ','MCHPIURA')
ORDER BY codigo_tienda;

COMMIT;
