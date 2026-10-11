<?php
/**
 * Registrar nueva tienda en el motor de captura.
 * Uso: php scripts/registrar_tienda.php
 *
 * Llenar las variables de la sección CONFIGURACIÓN y ejecutar.
 */

// =============================================================================
// CONFIGURACIÓN — editar aquí antes de ejecutar
// =============================================================================

$CODIGO_TIENDA   = 'MCHRPSALA';          // Código único, máx 10 chars (ej. LIM01, TRU01)
$NOMBRE_TIENDA   = 'MCH RP SALAVERRY'; // Nombre completo de la tienda
$IP_SERVIDOR     = '10.20.0.132';    // IP del servidor SQL Server de la tienda
$BD_BIZLINKS     = 'BIZLINKS_PROD';  // Nombre de la BD Bizlinks en ese servidor
$USUARIO_SQL     = 'sa';             // Usuario SQL Server
$PASSWORD_SQL    = '123456789';      // Contraseña SQL Server (se cifra automáticamente)
$DIAS_HISTORICO  = 2;                // Cuántos días hacia atrás capturar (0 = desde el inicio)

$IDSUCURSAL      = 177;                // ID de sucursal en automatizacion_dm_sucursales_activas

// =============================================================================
// EJECUCIÓN — no modificar
// =============================================================================

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use App\Models\FeTienda;
use App\Services\Captura\ConexionTiendaService;

echo "=== Registrar Tienda ===" . PHP_EOL . PHP_EOL;

// Validaciones básicas
if (in_array($CODIGO_TIENDA, ['XXX01', ''], true)) {
    echo "ERROR: Debes cambiar el CODIGO_TIENDA antes de ejecutar." . PHP_EOL;
    exit(1);
}
if (str_contains($IP_SERVIDOR, 'XXX')) {
    echo "ERROR: Debes cambiar la IP_SERVIDOR antes de ejecutar." . PHP_EOL;
    exit(1);
}
if (FeTienda::find($CODIGO_TIENDA)) {
    echo "ERROR: Ya existe una tienda con código '{$CODIGO_TIENDA}'." . PHP_EOL;
    exit(1);
}

if ($IDSUCURSAL <= 0) {
    echo "ERROR: Debes especificar el IDSUCURSAL antes de ejecutar." . PHP_EOL;
    exit(1);
}

// Crear la tienda
$passCifrada = Crypt::encryptString($PASSWORD_SQL);

$tienda = FeTienda::create([
    'codigo_tienda'                  => $CODIGO_TIENDA,
    'nombre_tienda'                  => $NOMBRE_TIENDA,
    'idempresa_soluflex'             => 1,
    'idsucursal_soluflex'            => $IDSUCURSAL,
    'servidor_host'                  => $IP_SERVIDOR,
    'servidor_puerto'                => 1433,
    'bd_soluflex_nombre'             => 'SOLUFLEX_FARO',
    'bd_bizlinks_nombre'             => $BD_BIZLINKS,
    'usuario_conexion'               => $USUARIO_SQL,
    'password_conexion_cifrado'      => $passCifrada,
    'estado'                         => 'ACTIVA',
    'tipo_fuente'                    => 'TIENDA',
    'numero_serie_nc'                => 1,
    'ultimo_idtransaccion_capturado' => 0,
    'fecha_inicio_captura'           => null,
]);

echo "Tienda creada: {$tienda->codigo_tienda} — {$tienda->nombre_tienda}" . PHP_EOL;
echo "  IP:          {$tienda->servidor_host}" . PHP_EOL;
echo "  Sucursal ID: {$tienda->idsucursal_soluflex}" . PHP_EOL;
echo "  Bizlinks BD: {$tienda->bd_bizlinks_nombre}" . PHP_EOL;

// Ajustar cursor al último IDTRANSACCION antes del rango deseado
if ($DIAS_HISTORICO > 0) {
    $svc = app(ConexionTiendaService::class);
    echo PHP_EOL . "Conectando a Soluflex ({$IP_SERVIDOR}) para ajustar cursor..." . PHP_EOL;

    try {
        $con = $svc->conexionSoluflex($tienda);
        $pfx = $svc->prefijoSoluflex($tienda);
        $fechaCorte = date('Ymd', strtotime("-{$DIAS_HISTORICO} days"));

        $row = $con->selectOne(
            "SELECT MAX(IDTRANSACCION) AS ultimo FROM {$pfx}[CABECERA_DOCUMENTO] WHERE FECHA_DOCUMENTO < ?",
            [$fechaCorte]
        );
        $cursor = $row && $row->ultimo ? (int)$row->ultimo : 0;

        $tienda->ultimo_idtransaccion_capturado = $cursor;
        $tienda->save();

        echo "Cursor ajustado a {$cursor} (capturará desde hace {$DIAS_HISTORICO} días)." . PHP_EOL;
    } catch (Throwable $e) {
        echo "AVISO: No se pudo ajustar el cursor automáticamente: " . $e->getMessage() . PHP_EOL;
        echo "El cursor queda en 0 (capturará todo el histórico disponible)." . PHP_EOL;
    }
} else {
    echo "Cursor en 0 — capturará todo el histórico disponible." . PHP_EOL;
}

echo PHP_EOL . "✓ Tienda lista. Ya aparecerá en el motor de captura." . PHP_EOL;
