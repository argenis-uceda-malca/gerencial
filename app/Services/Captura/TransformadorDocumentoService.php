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

        $detalleTransformado = $detalle->map(function ($item) use ($conexion, $tipoDocumento, $serieNumero, $emisor, $pfx) {
            $cantidad   = (float) $item->CANTIDAD;
            $cantidad   = $cantidad !== 0.0 ? $cantidad : 1.0;

            $subtotal   = (float) $item->IMPORTE_SUBTOTAL; // sin IGV
            $total      = (float) $item->IMPORTE_TOTAL;    // con IGV
            $igv        = round($total - $subtotal, 2);
            $tasaIgv    = $subtotal > 0 ? '18' : '0';
            // Catálogo 07 SUNAT: '10' Gravado IGV, '20' Exonerado
            $codigoRazonExoneracion = $subtotal > 0 ? '10' : '20';

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
                'importeIgv'                     => (string) $igv,
                'montoBaseIgv'                   => (string) round($subtotal, 2),
                'tasaIgv'                        => $tasaIgv,
                'importeTotalImpuestos'          => (string) $igv,
                // Uso interno para validación de totales de cabecera; descartado en InsercionBizlinksService.
                'importeTotalItem'               => $total,
            ];
        })->values()->all();

        $sumaDetalleTotal = array_sum(array_column($detalleTransformado, 'importeTotalItem'));
        $montoRedondeo = round(((float) $venta->IMPORTE_TOTAL) - $sumaDetalleTotal, 2);

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
            'totalImpuestos'                    => (string) round((float) $venta->IMPORTE_IGV, 2),
            'totalValorVentaNetoOpGravadas'     => (string) round((float) $venta->IMPORTE_SUBTOTAL, 2),
            'totalIgv'                          => (string) round((float) $venta->IMPORTE_IGV, 2),
            'totalVenta'                        => (string) round((float) $venta->IMPORTE_TOTAL, 2),
            'montoRedondeoTotalVenta'           => (string) $montoRedondeo,
            'totalMontoICBPER'                  => (string) round((float) ($venta->IMPORTE_ICBPER ?? 0), 2),
            'tipoOperacion'                     => '0101',
            'bl_estadoRegistro'                 => 'A',
            'bl_origen'                         => 'T',
            'bl_reintento'                      => '0',
        ];

        // totalValorVenta y totalPrecioVenta son nullable en SPE_EINVOICEHEADER:
        // el manual los marca como no aplica ('-') para NC/ND.
        if (! $esNcNd) {
            $cabecera['totalValorVenta']  = (string) round((float) $venta->IMPORTE_SUBTOTAL, 2);
            $cabecera['totalPrecioVenta'] = (string) round((float) $venta->IMPORTE_TOTAL, 2);
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

        return ['cabecera' => $cabecera, 'detalle' => $detalleTransformado];
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
            SELECT IDTRANSACCION_ORIGEN, CODIGO_MOTIVO_SUNAT, MOTIVO_SUNAT, DOCUMENTO_REFERENCIA
            FROM {$pfx}[CABECERA_DOCUMENTO]
            WHERE IDTRANSACCION = ?
        ", [$idTransaccion]);

        $codigoMotivo = $nc->CODIGO_MOTIVO_SUNAT ?? null;
        $motivoTexto  = filled($nc->MOTIVO_SUNAT)
            ? $nc->MOTIVO_SUNAT
            : ($nc->DOCUMENTO_REFERENCIA ?? '');

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
            $persona = $conexion->selectOne("
                SELECT p.NUMERO_IDENTIDAD, p.PERSONA, p.EMAIL1, t.CODIGO_SUNAT
                FROM {$pfx}[M_PERSONAS] p
                LEFT JOIN {$pfx}[M_TIPO_IDENTIDAD] t ON t.TIPO_IDENTIDAD = p.TIPO_IDENTIDAD
                WHERE p.IDPERSONA = ?
            ", [$idPersona]);

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
