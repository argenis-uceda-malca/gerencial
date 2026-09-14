# Proyecto: Módulo de Captura y Gestión de Facturación Electrónica

## Contexto general

Este proyecto es un módulo dentro de la web Laravel existente
(`C:\laragon\www\gerencial`). Su función:

1. **Capturar** en tiempo real las ventas de cada tienda (leídas desde
   la base de datos del ERP, `SOLUFLEX_FARO`, SQL Server) y transformarlas
   al formato que exige Bizlinks (proveedor de facturación electrónica),
   insertándolas en la base de datos intermedia local de cada tienda
   (`BIZLINKS_TST21`, también SQL Server).
2. **Gestionar** todo el ciclo de vida de esos documentos: monitoreo de
   respuestas SUNAT, errores, reintentos, notificaciones, reportes,
   auditoría — desde un dashboard central, sin que nadie necesite tocar
   las bases de datos directamente.

No existía integración previa: el ERP nunca declaraba nada a SUNAT.
Este módulo reemplaza esa función.

## Documentos de referencia (leer antes de tocar código)

Ubicar estos archivos en una carpeta `docs/` del proyecto y leerlos
antes de cualquier cambio relacionado con este módulo:

- **Plan_de_Accion_Modulo_Facturacion_Electronica.docx** — arquitectura
  general de 2 niveles (servidor central + conexión directa a cada
  tienda por IP pública/firewall), lista de pantallas, jobs en segundo
  plano, tablas `FE_*`.
- **Mapeo_Campos_Soluflex_a_Bizlinks.docx** — mapeo campo a campo de
  `CABECERA_DOCUMENTO`/`DETALLE_DOCUMENTO` (Soluflex) hacia
  `SPE_EINVOICEHEADER`/`SPE_EINVOICEDETAIL` (Bizlinks), con los
  catálogos ya resueltos y los riesgos pendientes.
- **Diseno_Flujo_Motor_Captura.docx** — algoritmo exacto del Motor de
  Captura: disparo por scheduler, idempotencia, reanudación ante
  fallos, manejo de errores de validación.
- **Esquema_BD_Central_FE_PostgreSQL.sql** — DDL de las 12 tablas
  `FE_*` (ya ejecutado en la base central PostgreSQL).

## Arquitectura de datos (3 bases distintas, no confundir)

| Base | Motor | Ubicación | Rol |
|---|---|---|---|
| `SOLUFLEX_FARO` | SQL Server | Servidor de cada tienda | ERP, fuente de las ventas. Solo lectura. |
| `BIZLINKS_TST21` | SQL Server | Servidor de cada tienda (misma IP, otra BD) | Base intermedia del proveedor Bizlinks. Se inserta y se lee, nunca se altera su esquema. |
| BD central `FE_*` | PostgreSQL | Servidor central (infra existente) | Propia del módulo. Resume/consolida el estado de todas las tiendas. NO copia PDFs (se consultan en vivo desde `BIZLINKS_TST21`). |

## Stack de implementación

- **Laravel** (PHP), sobre la web ya existente en este repo.
- El Motor de Captura es un **Artisan Command** (`captura:ejecutar`)
  programado en el Scheduler — no un daemon.
- Conexión a cada tienda: **dinámica en runtime** (no en
  `config/database.php`), armada desde la tabla `FE_TIENDAS` con
  `Config::set(...)` + `DB::connection(...)`.
- Requiere la extensión PHP `sqlsrv` / `pdo_sqlsrv` para conectarse a
  las bases SQL Server de las tiendas.

## Código ya generado (ver `Motor_Captura_Laravel.zip` en docs/, o ya integrado si este README aparece actualizado)

- `app/Models/Fe*.php` — 9 modelos Eloquent sobre la conexión `central`.
- `app/Services/Captura/*.php` — Motor de Captura completo (conexión,
  detección de ventas, transformación, validación, inserción,
  orquestador).
- `app/Console/Commands/EjecutarCapturaCommand.php`.
- `routes/console.php` — scheduler (asume Laravel 11+; si el proyecto
  usa Kernel.php, mover el bloque ahí).
- `config/captura.php`.
- Ver `README_INTEGRACION.md` dentro del zip para los pasos manuales
  pendientes (extensión sqlsrv, tipo de columna de password cifrado,
  confirmar nombres reales de columnas en Bizlinks).

## Pendiente de construir (no empezado aún)

- **Motor de Monitoreo**: lee `SPE_EINVOICE_RESPONSE` / `SPE_JOB_DOWNLOAD`
  de cada tienda, actualiza `FE_CONTROL_REGISTROS` y `FE_AUDITORIA_ESTADOS`,
  dispara notificaciones.
- **Capa web** (pantallas): Dashboard, Monitor de Captura, Listado/Detalle
  de Documentos, Gestión de Errores, Reintentos, Notificaciones, Reportes,
  Configuración, Auditoría, Usuarios.

## Decisiones de negocio pendientes de confirmar (no implementadas a propósito)

Marcadas con comentarios explícitos en el código:

1. **Pago mixto**: qué hacer cuando una venta tiene más de una forma de
   pago real (excluyendo el vuelto, código `000` en `M_FORMA_PAGOVENTA`).
2. **Línea de cargo por bolsa (ICBPER)** en el detalle: si se envía como
   ítem normal, se excluye, o se concilia contra el campo de cabecera
   `IMPORTE_ICBPER` / `totalMontoICBPER`.

## Catálogos y reglas de negocio ya confirmados (no volver a preguntar)

- Venta lista para capturar: `CABECERA_DOCUMENTO.CODIGO_ESTADO = '12'`
  (CANCELADO), tipo de documento con `DOCUMENTOS.FLAG_FACT_ELECTRONICA='S'`,
  y `DOCUMENTOS_SERIES.FLAG_ELECTRONICO='S'` para esa serie/empresa.
- `DOCUMENTOS.CODIGO_SUNAT` ya trae el tipo de documento SUNAT (01
  Factura, 03 Boleta, etc.) — no hay que construir ese catálogo.
- Tipo de documento del cliente: viene de `M_PERSONAS.TIPO_IDENTIDAD`
  vía `IDPERSONA` → `M_TIPO_IDENTIDAD.CODIGO_SUNAT`. El campo
  `CABECERA_DOCUMENTO.TIPO_AUXILIAR` NO es esto (descartado).
- Formato de `serieNumero` en Bizlinks: fijo, `F###-NNNNNNNN` /
  `B###-NNNNNNNN` (3 dígitos de serie + 8 de número).
- `correoEmisor` no es responsabilidad de este módulo: es configuración
  propia del portal de Bizlinks.
- Precio unitario del detalle: `IMPORTE_SUBTOTAL / CANTIDAD` (sin
  impuesto) e `IMPORTE_TOTAL / CANTIDAD` (con impuesto) — nunca
  `PRECIO_UNITARIO` ni `PRECIO_UNITARIO_REAL`, que son precio de lista
  antes de descuento.
- Diferencia de redondeo entre cabecera y suma del detalle (~S/0.01) se
  envía en `montoRedondeoTotalVenta`, no se trata como error.

## Cómo trabajar en este proyecto

- Antes de cambiar el Motor de Captura, releer
  `Diseno_Flujo_Motor_Captura.docx`, en especial la sección de
  idempotencia (la reserva en `FE_CONTROL_REGISTROS` ocurre ANTES de
  insertar en Bizlinks; nunca cambiar ese orden).
- Antes de cambiar el mapeo de campos, releer
  `Mapeo_Campos_Soluflex_a_Bizlinks.docx` — casi todo ya fue validado
  contra datos reales de una venta de prueba.
- Tienda de prueba actual: IP `10.20.0.15`, `IDEMPRESA=1`,
  `IDSUCURSAL=172` (MCH MP AREQUIPA).
