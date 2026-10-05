<?php

namespace App\Services\Captura;

use App\Models\FeTienda;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

/**
 * Arma conexiones hacia SOLUFLEX_FARO y BIZLINKS_TST21 de cada tienda.
 *
 * Modo gateway (CAPTURA_GATEWAY_HOST configurado):
 *   Ambas conexiones apuntan al servidor central (172.16.1.3). Las queries
 *   usan sintaxis de 4 partes: [servidor_tienda].[base].[dbo].[TABLA].
 *   Usar prefijoSoluflex() / prefijoBizlinks() para obtener ese prefijo.
 *
 * Modo directo (CAPTURA_GATEWAY_HOST vacío):
 *   Conexión directa a la IP de cada tienda. El prefijo devuelto es vacío;
 *   las queries usan nombres simples de tabla.
 */
class ConexionTiendaService
{
    public function usaGateway(): bool
    {
        return config('captura.gateway_host') !== '';
    }

    /**
     * Prefijo para tablas de SOLUFLEX_FARO de esta tienda.
     * Tienda normal (gateway): "[host].[db].[dbo]."  (4 partes)
     * CENTRAL (BD local del gateway): "[db].[dbo]."   (2 partes, sin IP)
     * Modo directo: cadena vacía.
     */
    public function prefijoSoluflex(FeTienda $tienda): string
    {
        if ($this->usaGateway()) {
            if ($tienda->tipo_fuente === 'CENTRAL') {
                // SOLUFLEX_FARO está en el mismo servidor gateway, no necesita IP.
                return "[{$tienda->bd_soluflex_nombre}].[dbo].";
            }

            return "[{$tienda->servidor_host}].[{$tienda->bd_soluflex_nombre}].[dbo].";
        }

        return '';
    }

    /**
     * Prefijo para tablas de Bizlinks de esta tienda.
     * Tienda normal (gateway): "[host].[db].[dbo]."
     * CENTRAL: cadena vacía — usa conexión directa a 10.20.0.134 (sin linked server).
     * Modo directo: cadena vacía.
     */
    public function prefijoBizlinks(FeTienda $tienda): string
    {
        if ($this->usaGateway() && $tienda->tipo_fuente !== 'CENTRAL') {
            return "[{$tienda->servidor_host}].[{$tienda->bd_bizlinks_nombre}].[dbo].";
        }

        return '';
    }

    public function conexionSoluflex(FeTienda $tienda): ConnectionInterface
    {
        if ($this->usaGateway()) {
            return $this->conexionGateway($tienda);
        }

        return $this->registrarDirecta($tienda, $tienda->bd_soluflex_nombre, 'soluflex');
    }

    public function conexionBizlinks(FeTienda $tienda): ConnectionInterface
    {
        // CENTRAL: conexión directa a la máquina dedicada Bizlinks (sin pasar por linked server del gateway).
        if ($tienda->tipo_fuente === 'CENTRAL' || !$this->usaGateway()) {
            return $this->registrarDirecta($tienda, $tienda->bd_bizlinks_nombre, 'bizlinks');
        }

        return $this->conexionGateway($tienda);
    }

    /**
     * Cierra la(s) conexion(es) de la tienda al terminar el ciclo.
     * En modo gateway ambas llamadas desde MotorCapturaService purgan
     * el mismo nombre; la segunda es un no-op seguro (Laravel hace unset).
     */
    public function cerrar(FeTienda $tienda, string $sufijo): void
    {
        if ($this->usaGateway()) {
            DB::purge("tienda_{$tienda->codigo_tienda}_gateway");

            return;
        }

        DB::purge("tienda_{$tienda->codigo_tienda}_{$sufijo}");
    }

    private function conexionGateway(FeTienda $tienda): ConnectionInterface
    {
        $nombre = "tienda_{$tienda->codigo_tienda}_gateway";

        Config::set("database.connections.{$nombre}", [
            'driver'                   => 'sqlsrv',
            'host'                     => config('captura.gateway_host'),
            'port'                     => config('captura.gateway_port'),
            'database'                 => 'master',
            'username'                 => config('captura.gateway_usuario'),
            'password'                 => config('captura.gateway_password'),
            'charset'                  => 'utf8',
            'prefix'                   => '',
            'trust_server_certificate' => true,
        ]);

        return DB::connection($nombre);
    }

    private function registrarDirecta(FeTienda $tienda, string $bd, string $sufijo): ConnectionInterface
    {
        $nombre = "tienda_{$tienda->codigo_tienda}_{$sufijo}";

        Config::set("database.connections.{$nombre}", [
            'driver'                   => 'sqlsrv',
            'host'                     => $tienda->servidor_host,
            'port'                     => $tienda->servidor_puerto,
            'database'                 => $bd,
            'username'                 => $tienda->usuario_conexion,
            'password'                 => Crypt::decryptString($tienda->password_conexion_cifrado),
            'charset'                  => 'utf8',
            'prefix'                   => '',
            'trust_server_certificate' => true,
        ]);

        return DB::connection($nombre);
    }
}
