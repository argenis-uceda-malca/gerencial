<?php

namespace App\Services\Captura;

use App\Models\FeTienda;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Aplica el mapeo de campos documentado en
 * "Mapeo de Campos - Soluflex a Bizlinks" (v1.2) para armar el arreglo
 * que InsercionBizlinksService usara para poblar SPE_EINVOICEHEADER y
 * SPE_EINVOICEDETAIL.
 *
 * $pfx es el prefijo de 4 partes para modo gateway, ej.:
 *   "[10.20.0.15].[SOLUFLEX_FARO].[dbo]."
 * En modo directo es cadena vacia.
 *
 * PENDIENTE DE NEGOCIO (no implementado todavia, ver documento de
 * mapeo seccion 7):
 *  - Pago mixto (mas de una forma de pago por venta).
 *  - Tratamiento de la linea de cargo por bolsa (ICBPER) en el detalle:
 *    hoy se envia tal cual viene en DETALLE_DOCUMENTO, sin excluirla
 *    ni conciliarla contra CABECERA_DOCUMENTO.IMPORTE_ICBPER.
 */
class TransformadorDocumentoService
{
    public function transformar(ConnectionInterface $conexion, FeTienda $tienda, object $venta, Collection $detalle, string $pfx): array
    {
        $tipoDocumento = $this->obtenerTipoDocumentoSunat($conexion, $venta->CODIGO_DOCUMENTO, $pfx);
        $emisor = $this->obtenerDatosEmisor($conexion, (int) $venta->IDEMPRESA, $tienda->idsucursal_soluflex, $pfx);
        $ubicacion = $this->obtenerUbicacion($conexion, $emisor->ubigeo, $pfx);
        $monedaSunat = $this->obtenerCodigoSunatMoneda($conexion, $venta->CODIGO_MONEDA, $pfx);
        $cliente = $this->obtenerDatosCliente($conexion, $venta->IDPERSONA ?? null, $venta->IDENTIDAD ?? null, $venta->NOMBRE ?? null, $pfx);

        $codigoSunat = $tipoDocumento['codigoSunat'];
        $esNcNd = in_array($codigoSunat, ['07', '08'], true);

        $datosReferencia = null;
        if ($esNcNd) {
            $datosReferencia = $this->obtenerDatosReferenciaNcNd(
                $conexion, (int) $venta->IDTRANSACCION, $codigoSunat, $pfx
            );
            $prefijo = $datosReferencia['prefijoNc'];
        } else {
            $prefijo = $codigoSunat === '01' ? 'F' : 'B';
        }

        // NC/ND: SUNAT limita el serie a 2 dígitos (FC01..FC99) porque el prefijo ya ocupa 2 letras.
        // Se usa numero_serie_nc de fe_tiendas en lugar del NUMERO_SERIE interno del Soluflex.
        $serieParaArmar = $esNcNd
            ? ($tienda->numero_serie_nc ?? 1)
            : $venta->NUMERO_SERIE;
        $serieNumero = $this->armarSerieNumero($prefijo, $serieParaArmar, $venta->NUMERO_DOCUMENTO, $esNcNd);

        $detalleTransformado = $detalle->map(function ($item) use ($conexion, $tipoDocumento, $serieNumero, $emisor, $esNcNd, $pfx) {
            $cantidad   = (float) $item->CANTIDAD;
            $cantidad   = $cantidad !== 0.0 ? abs($cantidad) : 1.0;

            // NC/ND: Soluflex guarda importes negativos; Bizlinks exige >= 0 en todos
            // los campos de importe del detalle (error 7779 si son negativos).
            // SUNAT ya sabe que es crédito/débito por el tipoDocumento (07/08).
            $subtotal = abs((float)$item->IMPORTE_SUBTOTAL);
            $total    = abs((float)$item->IMPORTE_TOTAL);
            // max(0.0,...) previene IGV negativo por redondeo en Soluflex (error 2033).
            $igv      = max(0.0, round($total - $subtotal, 2));
            // Líneas de ICBPER (bolsa) tienen subtotal>0 pero igv=0: tratarlas como
            // exoneradas (catálogo 07 = '20') con tasa 0, no como gravadas con IGV=0.
            // Si mandamos tasaIgv='18' pero importeIgv=0 SUNAT devuelve error 2033.
            $tieneIgv = $subtotal > 0 && $igv > 0;
            $tasaIgv  = $tieneIgv ? '18' : '0';
            // Catálogo 07 SUNAT: '10' Gravado IGV, '20' Exonerado
            $codigoRazonExoneracion = $tieneIgv ? '10' : '20';

            return [
                'tipoDocumentoEmisor'            => '6',
                'numeroDocumentoEmisor'          => $emisor->ruc,
                'tipoDocumento'                  => $tipoDocumento['codigoSunat'],
                'serieNumero'                    => $serieNumero,
                'numeroOrdenItem'                => (string) max(1, (int) $item->SECUENCIA),
                'cantidad'                       => (string) $cantidad,
                'unidadMedida'                   => $this->obtenerUnidadMedidaSunat($conexion, $item->CODIGO_UNIMED ?? null, $pfx),
                'codigoProducto'                 => $item->CODIGO_PRODUCTO ?? '-',
                'descripcion'                    => $this->obtenerDescripcionProducto($conexion, $item->CODIGO_PRODUCTO, $pfx) ?? '-',
                'importeUnitarioSinImpuesto'     => (string) round($subtotal / $cantidad, 6),
                'importeUnitarioConImpuesto'     => (string) round($total / $cantidad, 6),
                'codigoImporteUnitarioConImpues' => '01',
                // Totales por línea — requeridos por Bizlinks para armar el XML SUNAT
                'codigoRazonExoneracion'         => $codigoRazonExoneracion,
                'importeTotalSinImpuesto'        => (string) round($subtotal, 2),
                'importeIgv'                     => number_format($igv, 2, '.', ''),
                // Exonerados: montoBaseIgv = precio de la línea (no 0) para que Bizlinks
                // genere el TaxSubtotal de exoneración en el UBL. Si se envía 0.00,
                // Bizlinks omite el tributo y SUNAT rechaza con error 3105.
                'montoBaseIgv'                   => number_format($subtotal, 2, '.', ''),
                'tasaIgv'                        => $tasaIgv,
                'importeTotalImpuestos'          => number_format($igv, 2, '.', ''),
                // Uso interno para validación de totales de cabecera; descartado en InsercionBizlinksService.
                'importeTotalItem'               => $total,
            ];
        })->values()->all();

        $sumaDetalleTotal = array_sum(array_column($detalleTransformado, 'importeTotalItem'));

        // Descuento global en cabecera (Soluflex aplica el descuento al header pero no a las líneas).
        // Si existe, escalamos los importes de cada línea proporcionalmente para que sumen al total
        // de cabecera — SUNAT exige coherencia entre líneas y totales.
        $descuentoGlobal = abs((float) ($venta->IMPORTE_DESCUENTO ?? 0));
        if ($descuentoGlobal > 0.005 && $sumaDetalleTotal > 0) {
            $totalCabecera = abs((float) $venta->IMPORTE_TOTAL);
            $factor = $totalCabecera / $sumaDetalleTotal;
            $acumulado = 0.0;
            $ultimoIdx  = count($detalleTransformado) - 1;
            foreach ($detalleTransformado as $idx => &$linea) {
                if ($idx === $ultimoIdx) {
                    // La última línea absorbe el residuo de redondeo
                    $totalLinea    = round($totalCabecera - $acumulado, 2);
                    $subtotalLinea = round($totalLinea / 1.18, 2);
                } else {
                    $totalLinea    = round((float) $linea['importeTotalItem'] * $factor, 2);
                    $subtotalLinea = round($totalLinea / 1.18, 2);
                    $acumulado    += $totalLinea;
                }
                $igvLinea = round($totalLinea - $subtotalLinea, 2);
                $cantidad  = max((float) $linea['cantidad'], 1.0);
                $linea['importeTotalItem']          = $totalLinea;
                $linea['importeTotalSinImpuesto']   = (string) $subtotalLinea;
                $linea['importeIgv']                = (string) $igvLinea;
                $linea['montoBaseIgv']              = (string) $subtotalLinea;
                $linea['importeTotalImpuestos']     = (string) $igvLinea;
                $linea['importeUnitarioSinImpuesto']= (string) round($subtotalLinea / $cantidad, 6);
                $linea['importeUnitarioConImpuesto']= (string) round($totalLinea    / $cantidad, 6);
            }
            unset($linea);
            $sumaDetalleTotal = array_sum(array_column($detalleTransformado, 'importeTotalItem'));
        }

        // Subtotales por tipo de afectación para declarar en el cabecero.
        // SUNAT error 2638 si hay líneas exoneradas y no se declara totalValorVentaNetoOpExonerada.
        $subGravadas   = 0.0;
        $subExoneradas = 0.0;
        foreach ($detalleTransformado as $linea) {
            if (($linea['codigoRazonExoneracion'] ?? '10') === '10') {
                $subGravadas   += (float) $linea['importeTotalSinImpuesto'];
            } else {
                $subExoneradas += (float) $linea['importeTotalSinImpuesto'];
            }
        }
        $subGravadas   = round($subGravadas, 2);
        $subExoneradas = round($subExoneradas, 2);

        // Bizlinks exige todos los campos de importe >= 0 (error 7779 si negativos).
        // Soluflex guarda NC/ND con importes negativos — siempre aplicar abs().
        // SUNAT ya sabe que es NC/ND por tipoDocumento (07/08).
        $igvCab = abs((float)$venta->IMPORTE_IGV);
        $subCab = abs((float)$venta->IMPORTE_SUBTOTAL);
        $totCab = abs((float)$venta->IMPORTE_TOTAL);

        $montoRedondeo = round($totCab - $sumaDetalleTotal, 2);

        // Formateamos la fecha como varchar YYYY-MM-DD (10 chars exactos que requiere Bizlinks).
        // Usamos date_create para evitar ambigüedad cuando SQL Server devuelve formatos regionales.
        $fechaEmisionStr = Carbon::parse($venta->FECHA_DOCUMENTO)->format('Y-m-d');

        $cabecera = [
            'serieNumero'                       => $serieNumero,
            'fechaEmision'                      => $fechaEmisionStr,
            'tipoDocumento'                     => $codigoSunat,
            'tipoMoneda'                        => $monedaSunat ?? 'PEN',
            'numeroDocumentoEmisor'             => $emisor->ruc ?? '-',
            'tipoDocumentoEmisor'               => '6',
            'nombreComercialEmisor'             => filled($emisor->nombreTienda) ? $emisor->nombreTienda : ($emisor->razonSocial ?? '-'),
            'razonSocialEmisor'                 => $emisor->razonSocial ?? '-',
            'ubigeoEmisor'                      => $emisor->ubigeo ?? '-',
            'direccionEmisor'                   => filled($emisor->direccion) ? $emisor->direccion : '-',
            'provinciaEmisor'                   => filled($ubicacion['provincia']) ? $ubicacion['provincia'] : '-',
            'departamentoEmisor'                => filled($ubicacion['departamento']) ? $ubicacion['departamento'] : '-',
            'distritoEmisor'                    => filled($ubicacion['distrito']) ? $ubicacion['distrito'] : '-',
            'paisEmisor'                        => 'PE',
            'correoEmisor'                      => '-',
            'codigoLocalAnexoEmisor'            => filled($emisor->codigoAnexo) ? $emisor->codigoAnexo : '0000',
            'tipoDocumentoAdquiriente'          => $cliente['tipoDocumentoSunat'] ?? '1',
            'numeroDocumentoAdquiriente'        => filled($cliente['numeroDocumento']) ? $cliente['numeroDocumento'] : '00000000',
            'razonSocialAdquiriente'            => filled($cliente['razonSocial']) ? $cliente['razonSocial'] : '-',
            'correoAdquiriente'                 => filled($cliente['correo'] ?? null) ? $cliente['correo'] : '-',
            'totalImpuestos'                    => (string) round($igvCab, 2),
            'totalValorVentaNetoOpGravadas'     => (string) $subGravadas,
            'totalIgv'                          => (string) round($igvCab, 2),
            'totalVenta'                        => (string) round($totCab, 2),
            'montoRedondeoTotalVenta'           => (string) $montoRedondeo,
            'tipoOperacion'                     => '0101',
            'bl_estadoRegistro'                 => 'A',
            'bl_origen'                         => 'T',
            'bl_reintento'                      => '0',
        ];

        // Operaciones exoneradas: solo declarar si existen en el documento.
        if ($subExoneradas > 0.0) {
            $cabecera['totalValorVentaNetoOpExonerada'] = (string) $subExoneradas;
        }

        // ICBPER: solo incluir si el importe es mayor a cero.
        // Bizlinks genera un TaxSubtotal ICBPER en el UBL aunque el valor sea 0,
        // y SUNAT rechaza ese TaxSubtotal vacío con error 2048.
        $icbper = round((float) ($venta->IMPORTE_ICBPER ?? 0), 2);
        if ($icbper > 0) {
            $cabecera['totalMontoICBPER'] = (string) $icbper;
        }

        // totalValorVenta y totalPrecioVenta son nullable en SPE_EINVOICEHEADER:
        // el manual los marca como no aplica ('-') para NC/ND.
        if (! $esNcNd) {
            $cabecera['totalValorVenta']  = (string) round($subCab, 2);
            $cabecera['totalPrecioVenta'] = (string) round($totCab, 2);
        }

        // Campos exclusivos de NC (07) y ND (08) — columnas NULL en la tabla.
        if ($esNcNd && $datosReferencia) {
            $cabecera['codigoSerieNumeroAfectado']      = $datosReferencia['codigoMotivo'];
            $cabecera['motivoDocumento']               = $datosReferencia['motivoTexto'];
            // Nombre truncado a 30 chars por el DDL de Bizlinks:
            // tipoDocumentoReferenciaPrincipal (32) → tipoDocumentoReferenciaPrincip (30)
            $cabecera['tipoDocumentoReferenciaPrincip'] = $datosReferencia['tipoDocOriginalSunat'];
            // numeroDocumentoReferenciaPrincipal (34) → numeroDocumentoReferenciaPrinc (30)
            $cabecera['numeroDocumentoReferenciaPrinc'] = $datosReferencia['serieNumeroOriginal'];
        }

        // Para facturas (tipo 01), SUNAT exige declarar la forma de pago.
        // Bizlinks lo lee desde SPE_EINVOICEHEADER_ADD con clave 'formaPagoNegociable'.
        // 0 = Contado (no negociable), 1 = Crédito (factura negociable Ley 29623).
        $headerAdd = [];
        if ($codigoSunat === '01') {
            $headerAdd[] = [
                'tipoDocumentoEmisor'   => '6',
                'numeroDocumentoEmisor' => $emisor->ruc,
                'serieNumero'           => $serieNumero,
                'tipoDocumento'         => $codigoSunat,
                'clave'                 => 'formaPagoNegociable',
                'valor'                 => '0',
            ];
        }

        return ['cabecera' => $cabecera, 'detalle' => $detalleTransformado, 'headerAdd' => $headerAdd];
    }

    /**
     * FAC/BOL: F001-00000001 o B001-00000001 (prefijo 1 char + 3 dígitos + - + 8 dígitos = 13 chars).
     * NC/ND:   FC01-00000001 (prefijo 2 chars + 2 dígitos + - + 8 dígitos = 13 chars).
     * Para NC/ND el serie recibido debe ser 1-99 (viene de fe_tiendas.numero_serie_nc).
     */
    private function armarSerieNumero(string $prefijo, $numeroSerie, $numeroDocumento, bool $esNcNd = false): string
    {
        if ($esNcNd) {
            return sprintf('%s%02d-%08d', $prefijo, (int) $numeroSerie, (int) $numeroDocumento);
        }

        return sprintf('%s%03d-%08d', $prefijo, (int) $numeroSerie, (int) $numeroDocumento);
    }

    /**
     * Recupera los campos de referencia de una NC/ND desde CABECERA_DOCUMENTO y,
     * via IDTRANSACCION_ORIGEN, del documento original para armar el prefijo de serie
     * (FC=NC de Factura, BC=NC de Boleta, FD=ND de Factura, BD=ND de Boleta).
     */
    private function obtenerDatosReferenciaNcNd(
        ConnectionInterface $conexion,
        int $idTransaccion,
        string $codigoSunatNcNd,
        string $pfx
    ): array {
        $nc = $conexion->selectOne("
            SELECT IDTRANSACCION_ORIGEN, MOTIVO_SUNAT, DOCUMENTO_REFERENCIA, GLOSA, TIPO_NOTACREDITO
            FROM {$pfx}[CABECERA_DOCUMENTO]
            WHERE IDTRANSACCION = ?
        ", [$idTransaccion]);

        // Código de motivo SUNAT (Catálogo 09) mapeado desde TIPO_NOTACREDITO de Soluflex.
        // 1=Cambio prenda, 2=Dev.dinero, 3=Acred.bancaria → '06' Devolución parcial
        // 4=Cambio talla/color → '03' Corrección por error en descripción
        // NULL (NCs de CENTRAL) → '06' por default
        $mapaTipoNc = ['1' => '06', '2' => '06', '3' => '06', '4' => '03'];
        $codigoMotivo = $mapaTipoNc[(string) $nc->TIPO_NOTACREDITO] ?? '06';

        // Texto del motivo: MOTIVO_SUNAT > GLOSA > DOCUMENTO_REFERENCIA > default por código
        // SUNAT exige cac:DiscrepancyResponse/cbc:Description no vacío (error 2136 si llega vacío).
        $motivoTexto = filled($nc->MOTIVO_SUNAT)
            ? $nc->MOTIVO_SUNAT
            : (filled($nc->GLOSA) ? $nc->GLOSA : ($nc->DOCUMENTO_REFERENCIA ?? ''));

        if (! filled($motivoTexto)) {
            $textosPorCodigo = [
                '01' => 'Anulacion de la operacion',
                '02' => 'Anulacion por error en el RUC',
                '03' => 'Correccion por error en la descripcion',
                '04' => 'Descuento global',
                '05' => 'Descuento por item',
                '06' => 'Devolucion total',
                '07' => 'Devolucion por item',
                '08' => 'Bonificacion',
                '09' => 'Disminucion en el valor',
                '10' => 'Otros conceptos',
                '11' => 'Ajustes de operaciones de exportacion',
                '12' => 'Ajustes afectos al IVAP',
                '13' => 'Correccion del periodo tributario',
            ];
            $motivoTexto = $textosPorCodigo[$codigoMotivo] ?? 'Devolucion total';
        }

        $idOrigen = (int) ($nc->IDTRANSACCION_ORIGEN ?? 0);
        $tipoDocOriginalSunat = '01'; // Factura como default si no se puede resolver
        $serieNumeroOriginal  = null;

        if ($idOrigen > 0) {
            $original = $conexion->selectOne("
                SELECT c.NUMERO_SERIE, c.NUMERO_DOCUMENTO, d.CODIGO_SUNAT
                FROM {$pfx}[CABECERA_DOCUMENTO] c
                INNER JOIN {$pfx}[DOCUMENTOS] d ON d.CODIGO_DOCUMENTO = c.CODIGO_DOCUMENTO
                WHERE c.IDTRANSACCION = ?
            ", [$idOrigen]);

            if ($original) {
                $tipoDocOriginalSunat = $original->CODIGO_SUNAT ?? '01';
                $prefijoOriginal      = $tipoDocOriginalSunat === '01' ? 'F' : 'B';
                $serieNumeroOriginal  = $this->armarSerieNumero(
                    $prefijoOriginal,
                    $original->NUMERO_SERIE,
                    $original->NUMERO_DOCUMENTO,
                    false
                );
            }
        }

        // Fallback: usar el texto de DOCUMENTO_REFERENCIA si no hay IDTRANSACCION_ORIGEN
        if (! $serieNumeroOriginal) {
            $serieNumeroOriginal = $nc->DOCUMENTO_REFERENCIA ?? '';
        }

        // Prefijo NC/ND: primera letra = del doc original (F=Factura, B=Boleta);
        //                segunda letra = C (NC, tipo 07) o D (ND, tipo 08)
        $primeraLetra = $tipoDocOriginalSunat === '01' ? 'F' : 'B';
        $segundaLetra = $codigoSunatNcNd === '07' ? 'C' : 'D';

        return [
            'codigoMotivo'          => $codigoMotivo,
            'motivoTexto'           => $motivoTexto,
            'tipoDocOriginalSunat'  => $tipoDocOriginalSunat,
            'serieNumeroOriginal'   => $serieNumeroOriginal,
            'prefijoNc'             => $primeraLetra . $segundaLetra,
        ];
    }

    private function obtenerTipoDocumentoSunat(ConnectionInterface $conexion, string $codigoDocumento, string $pfx): array
    {
        $doc = $conexion->selectOne("SELECT CODIGO_SUNAT FROM {$pfx}[DOCUMENTOS] WHERE CODIGO_DOCUMENTO = ?", [$codigoDocumento]);
        $codigoSunat = $doc->CODIGO_SUNAT ?? null;

        return ['codigoSunat' => $codigoSunat];
    }

    private function obtenerDatosEmisor(ConnectionInterface $conexion, int $idEmpresa, ?int $idSucursal, string $pfx): object
    {
        $empresa = $conexion->selectOne("SELECT RUC, EMPRESA, DIRECCION FROM {$pfx}[EMPRESAS] WHERE IDEMPRESA = ?", [$idEmpresa]);

        $sucursal = $idSucursal
            ? $conexion->selectOne("SELECT NOMBRE_TIENDA, DIRECCION1, CODIGO_ANEXO FROM {$pfx}[M_SUCURSALES] WHERE IDSUCURSAL = ?", [$idSucursal])
            : null;

        $ubicacion = $idSucursal
            ? $conexion->selectOne("SELECT UBIGEO FROM {$pfx}[M_SUCURSALES_UBICACION] WHERE IDSUCURSAL = ?", [$idSucursal])
            : null;

        return (object) [
            'ruc'         => $empresa->RUC ?? null,
            'razonSocial' => $empresa->EMPRESA ?? null,
            'direccion'   => $sucursal->DIRECCION1 ?? ($empresa->DIRECCION ?? null),
            'nombreTienda' => $sucursal->NOMBRE_TIENDA ?? null,
            'codigoAnexo' => $sucursal->CODIGO_ANEXO ?? null,
            'ubigeo'      => $ubicacion->UBIGEO ?? null,
        ];
    }

    private function obtenerUbicacion(ConnectionInterface $conexion, ?string $ubigeo, string $pfx): array
    {
        if (! $ubigeo) {
            return ['departamento' => null, 'provincia' => null, 'distrito' => null];
        }

        $departamento = substr($ubigeo, 0, 2).'0000';
        $provincia    = substr($ubigeo, 0, 4).'00';

        $filas = $conexion->select("
            SELECT TIPO_UBIGEO, UBICACION_GEOGRAFICA
            FROM {$pfx}[M_UBICACION_GEOGRAFICA]
            WHERE CODIGO_UBIGEO_REAL IN (?, ?, ?)
              AND CODIGO_PAIS = 167
        ", [$ubigeo, $provincia, $departamento]);

        $porTipo = collect($filas)->keyBy('TIPO_UBIGEO');

        return [
            'distrito'     => $porTipo[3]->UBICACION_GEOGRAFICA ?? null,
            'provincia'    => $porTipo[2]->UBICACION_GEOGRAFICA ?? null,
            'departamento' => $porTipo[1]->UBICACION_GEOGRAFICA ?? null,
        ];
    }

    private function obtenerCodigoSunatMoneda(ConnectionInterface $conexion, string $codigoMoneda, string $pfx): ?string
    {
        $fila = $conexion->selectOne("SELECT codigo_sunat FROM {$pfx}[M_MONEDA] WHERE codigo_moneda = ?", [$codigoMoneda]);

        return $fila->codigo_sunat ?? null;
    }

    private function obtenerUnidadMedidaSunat(ConnectionInterface $conexion, ?string $codigoUnimed, string $pfx): string
    {
        if (! $codigoUnimed) {
            return 'NIU';
        }

        $fila = $conexion->selectOne("SELECT CODIGO_SUNAT FROM {$pfx}[M_UNIDADMEDIDA] WHERE CODIGO_UNIMED = ?", [$codigoUnimed]);

        return $fila->CODIGO_SUNAT ?? 'NIU';
    }

    private function obtenerDescripcionProducto(ConnectionInterface $conexion, string $codigoProducto, string $pfx): ?string
    {
        $fila = $conexion->selectOne("SELECT PRODUCTO FROM {$pfx}[PRODUCTOS] WHERE CODIGO_PRODUCTO = ?", [$codigoProducto]);

        return $fila->PRODUCTO ?? null;
    }

    private function obtenerDatosCliente(ConnectionInterface $conexion, ?int $idPersona, ?string $identidadFallback, ?string $nombreFallback, string $pfx): array
    {
        if ($idPersona) {
            // Algunos servidores usan M_TIPOIDENTIDAD (sin guión bajo) en lugar
            // de M_TIPO_IDENTIDAD. Intentar ambas antes de rendirse.
            $persona = null;
            foreach (['M_TIPO_IDENTIDAD', 'M_TIPOIDENTIDAD'] as $tablaIdentidad) {
                try {
                    $persona = $conexion->selectOne("
                        SELECT p.NUMERO_IDENTIDAD, p.PERSONA, p.EMAIL1, t.CODIGO_SUNAT
                        FROM {$pfx}[M_PERSONAS] p
                        LEFT JOIN {$pfx}[{$tablaIdentidad}] t ON t.TIPO_IDENTIDAD = p.TIPO_IDENTIDAD
                        WHERE p.IDPERSONA = ?
                    ", [$idPersona]);
                    break;
                } catch (\Throwable $e) {
                    if ($tablaIdentidad === 'M_TIPOIDENTIDAD') {
                        throw $e;
                    }
                }
            }

            if ($persona) {
                // M_TIPO_IDENTIDAD puede estar vacía en algunos ERPs: inferir
                // el tipo SUNAT por longitud del número de identidad.
                $tipoSunat = filled($persona->CODIGO_SUNAT)
                    ? $persona->CODIGO_SUNAT
                    : $this->inferirTipoDocumentoSunat($persona->NUMERO_IDENTIDAD);

                return [
                    'tipoDocumentoSunat' => $tipoSunat,
                    'numeroDocumento'    => $persona->NUMERO_IDENTIDAD,
                    'razonSocial'        => $persona->PERSONA,
                    'correo'             => $persona->EMAIL1,
                ];
            }
        }

        return [
            'tipoDocumentoSunat' => $this->inferirTipoDocumentoSunat($identidadFallback),
            'numeroDocumento'    => $identidadFallback,
            'razonSocial'        => $nombreFallback,
            'correo'             => null,
        ];
    }

    /**
     * Infiere el codigo SUNAT de tipo de documento por la longitud del numero.
     * Fallback cuando M_TIPO_IDENTIDAD esta vacia o no tiene CODIGO_SUNAT.
     *   8 digitos → '1' (DNI)
     *  11 digitos → '6' (RUC)
     *  8 ceros    → '1' (consumidor final, DNI generico)
     *  Resto      → '1' (DNI por defecto para boletas de consumidor)
     */
    private function inferirTipoDocumentoSunat(?string $numero): string
    {
        if (blank($numero)) {
            return '1'; // consumidor final sin documento → DNI genérico
        }

        $numero   = trim($numero);
        $longitud = strlen($numero);

        if ($longitud === 11 && ctype_digit($numero)) {
            return '6'; // RUC
        }

        return '1'; // DNI o consumidor final
    }
}
