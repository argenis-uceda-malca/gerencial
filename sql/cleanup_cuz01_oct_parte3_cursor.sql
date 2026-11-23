-- ============================================================
-- CUZ01: resetear cursor para detectar B410-1 a B410-93
-- min IDTRANSACCION = 1 → cursor = 0
-- Los B410-94+ ya están CAPTURADO con estado_bizlinks=P (firstOrCreate los saltea)
-- ============================================================

BEGIN;

UPDATE fe_tiendas SET ultimo_idtransaccion_capturado = 0 WHERE codigo_tienda = 'CUZ01';

SELECT codigo_tienda, ultimo_idtransaccion_capturado FROM fe_tiendas WHERE codigo_tienda = 'CUZ01';

COMMIT;
