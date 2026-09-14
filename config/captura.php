<?php

return [
    'correo_alertas' => env('CAPTURA_CORREO_ALERTAS', 'soporte@tuempresa.com'),

    // Gateway SQL Server para acceder a tiendas vía linked servers.
    // Cuando CAPTURA_GATEWAY_HOST está definido, el Motor se conecta a
    // este servidor central y usa sintaxis de 4 partes para cada tienda:
    //   [servidor_host].[bd_nombre].[dbo].[TABLA]
    // Dejar CAPTURA_GATEWAY_HOST vacío para usar conexión directa a cada
    // tienda (modo futuro, cuando se habilite acceso por IP pública).
    'gateway_host'     => env('CAPTURA_GATEWAY_HOST', ''),
    'gateway_port'     => (int) env('CAPTURA_GATEWAY_PORT', 1433),
    'gateway_usuario'  => env('CAPTURA_GATEWAY_USUARIO', ''),
    'gateway_password' => env('CAPTURA_GATEWAY_PASSWORD', ''),
];
