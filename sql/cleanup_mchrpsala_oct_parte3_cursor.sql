-- ============================================================
-- MCHRPSALA: resetear cursor para detectar B419-1 a B419-70
-- min IDTRANSACCION = 196 → cursor = 195
-- ============================================================

BEGIN;

UPDATE fe_tiendas SET ultimo_idtransaccion_capturado = 195 WHERE codigo_tienda = 'MCHRPSALA';

SELECT codigo_tienda, ultimo_idtransaccion_capturado FROM fe_tiendas WHERE codigo_tienda = 'MCHRPSALA';

COMMIT;
