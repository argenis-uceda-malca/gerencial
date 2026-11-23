-- JKY01: resetear cursor para detectar B402-3 a B402-14 (IDTRANSACCION mín=3)
BEGIN;
UPDATE fe_tiendas SET ultimo_idtransaccion_capturado = 2 WHERE codigo_tienda = 'JKY01';
SELECT codigo_tienda, ultimo_idtransaccion_capturado FROM fe_tiendas WHERE codigo_tienda = 'JKY01';
COMMIT;
