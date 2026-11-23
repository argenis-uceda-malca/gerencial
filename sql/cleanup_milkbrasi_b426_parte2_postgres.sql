-- ============================================================
-- MILKBRASI B426: eliminar del motor + resetear cursor
-- Ejecutar INMEDIATAMENTE después de la Parte 1
-- ============================================================

BEGIN;

-- Eliminar solo los 6 documentos stuck (B426-1,2,3,4 + B426-23 + F426-1)
DELETE FROM fe_auditoria_estados
WHERE id_control_registro IN (
  SELECT id FROM fe_control_registros
  WHERE codigo_tienda = 'MILKBRASI'
    AND serie_numero_bizlinks IN (
      'B426-00000001','B426-00000002','B426-00000003','B426-00000004',
      'B426-00000023','F426-00000001'
    )
);

DELETE FROM fe_historial_errores
WHERE id_control_registro IN (
  SELECT id FROM fe_control_registros
  WHERE codigo_tienda = 'MILKBRASI'
    AND serie_numero_bizlinks IN (
      'B426-00000001','B426-00000002','B426-00000003','B426-00000004',
      'B426-00000023','F426-00000001'
    )
);

DELETE FROM fe_notificaciones
WHERE id_control_registro IN (
  SELECT id FROM fe_control_registros
  WHERE codigo_tienda = 'MILKBRASI'
    AND serie_numero_bizlinks IN (
      'B426-00000001','B426-00000002','B426-00000003','B426-00000004',
      'B426-00000023','F426-00000001'
    )
);

DELETE FROM fe_historial_reintentos
WHERE id_control_registro IN (
  SELECT id FROM fe_control_registros
  WHERE codigo_tienda = 'MILKBRASI'
    AND serie_numero_bizlinks IN (
      'B426-00000001','B426-00000002','B426-00000003','B426-00000004',
      'B426-00000023','F426-00000001'
    )
);

DELETE FROM fe_control_registros
WHERE codigo_tienda = 'MILKBRASI'
  AND serie_numero_bizlinks IN (
    'B426-00000001','B426-00000002','B426-00000003','B426-00000004',
    'B426-00000023','F426-00000001'
  );

-- Resetear cursor a 0 (B426-1 tiene IDTRANSACCION=1)
-- Los documentos P (ya aceptados) quedarán intactos por firstOrCreate
UPDATE fe_tiendas SET ultimo_idtransaccion_capturado = 0 WHERE codigo_tienda = 'MILKBRASI';

-- Verificar
SELECT COUNT(*) AS quedaron_stuck FROM fe_control_registros
WHERE codigo_tienda = 'MILKBRASI'
  AND serie_numero_bizlinks IN (
    'B426-00000001','B426-00000002','B426-00000003','B426-00000004',
    'B426-00000023','F426-00000001'
  );

SELECT COUNT(*) AS total_milkbrasi,
  SUM(CASE WHEN estado_bizlinks = 'P' THEN 1 ELSE 0 END) AS p_ok
FROM fe_control_registros WHERE codigo_tienda = 'MILKBRASI';

SELECT ultimo_idtransaccion_capturado FROM fe_tiendas WHERE codigo_tienda = 'MILKBRASI';

COMMIT;
