<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class TbRetailService
{
    public function guardarConteosTbRetail($tipo = 'marca', $fecha = null)
    {
        // Si no se envía fecha, usar ayer
        $fecha = $fecha
            ? Carbon::parse($fecha)->format('Y-m-d')
            : Carbon::yesterday()->format('Y-m-d');

        $fecha_inicio = $fecha;
        $fecha_fin    = $fecha;

        /*
        |--------------------------------------------------------------------------
        | 1. OBTENER ACCESS TOKEN (MISMA LÓGICA ORIGINAL)
        |--------------------------------------------------------------------------
        */
        $refresh_token = "eyJjdHkiOiJKV1QiLCJlbmMiOiJBMjU2R0NNIiwiYWxnIjoiUlNBLU9BRVAifQ.RQyb-uYmKuluD1rUZO8fI5XX1UDk0ozOOt1ISLJgSHw_-z-9xOylh3NihpUHbIzp_6FhEFWu9Hv3TAgPGZZT8SnTS05hlZ5QnRgU00Osd67JKeBrrOoEoRybjbbHrS8ZqgDs2Gg_sGSlveWTY2H609Ux2BshK_7bYB0axqbQxTJTcrTwFQjtuXwcdGIJva-dRoraMPpzfUbFbnOmdmjks4ZwX5gJKaQ_Ytui_tShq2vbDutyJt1vWkeRaWyKf34QXXbo7PcjK2UBnrS74QaljimGfNL-fkM6lABVbnFPR9Z0cwh-6iwW6Ygf64dJeEzJR1jlYJCTCD6ZjNr8tCs-og.-zfoI1XlOMeTQBEq.sVr2WTUNMjBq4RzVF0jZgX9Chgj29k3vPSQSLxvY6iuL8hhaW_HxEn_krYvfeHa4Y3u4rgJpSDppSh0-l-Sp8WhcgpbICuVQWiPU6B9aPmp0JDDbCnlL_OKKkWnRP5njFgcLUEZnARiRlhnBXRVjQAizzjzmBrVh5e_-rB2Z5sA7RNHFdNf38VplG-_h0f_pSQrKWXp55uRSfVMiNBWooUUq8VyEbAC-N9iDP92PEYI7-ArfzfVamLIuUqCRYuAIpo56Q7BQT0k9lQwseQJv5CnjY8x4IVPgHCKf1omfoOj00HawUjFDTDTRxywbRs3NpAtCDSxuTAjX23AydlkfmabC0x2aUJspAxq3JqWkZ98OeB03PMEo7ykuxZnuYtz4m--BNK4DEYcjn1kGmGjJildfqY7yi5mQDfP9urlqIWjACEanILaM4RmsuFODRL-4-gMYS-iScwMXW_Ym96q0X39Uou0OTc_PiNFbAL909pORWd9nfyXjMH-kV68W0Vc6hAWtdDDo4LWaR66_flppBGFjaLq5ltOE-qRNlz1Dchiu52EeUH0p28Ds44u3jjvmXGvf1qPPzGhGUWvmA0n6seBUnUzG9LJYg3y4_Akd9DvMzrTwev6MFBY4JtLVM6gGHODQ5DXzhgrK09w2XR0iy-lpZFA6KlhlOqMHc4cksZHsgPBelBUXiPibhFU7ee62ckzxoaOuh3tVft_IHf7EES6dlBpzCl3zaubcT7QHw0MZ7-fKddsbPkU4U9xu7pknjtzm--eMW6UcdxjMNpjERhDS1ZwUgsvgkkU25Y6AfXN_pIMqDa1tE86i-bK1NWzGC_MoQjeKTByOFNLhGAuzCwDMuq2I6oohBM8oxUVce7Maae577yWMBjuRT741dY8qyR8YxQ48E28G9Thoch_DCEvTRXpxnxbJl-8vc64dPsgOQiRmvBipbS8GI5OwZijROdV6REKHq-V8SB1p3_mW7fYgJOIvm4uviYyYEGT3Zxw9LpghRl4u5yUAFRYlqVVf71thT_tv6HPEZJYoohJXdM6zToAlONemmwI-jbLD6P49PPlR0yvdUBf2IRTIM03mVyRYwlSbUsG9AWi6bNlwiFWVe7lc2l65nvsnSo8kLSwziQ3jnoOQP4Sfj4fxW2OJjK6XMgU_ypP3KgqY0fpXK8BrD-zhd1-LTfVKHJbQ9jqvPuiDMtxxxpAMEE_-gA0X-nP-TZa0bMlivV_1OwfpVcLTgES6icu6XlFHb6TAP39b6Ex5Tb-6uMtOjN_L-lOA5eXxX0wGCCgBK4_PNPXfC8vdoy-WvijXYdL5Gy9Zio9LS5QLONM7dWolytzrEgl0VPKw-pwm34ZgoNXmjyfk8gV9JZFMjGCT.gOIef4qk3JCTQJODGInXTQ";
        $client_id = '63h4jubc7qvrg9f65c36vjp3bq';
        $redirect_uri = 'https://login.tbretail.com/mytoken/index.html';

        $token_url = 'https://auth.tbretail.com/oauth2/token';

        $postData = http_build_query([
            'grant_type'    => 'refresh_token',
            'client_id'     => $client_id,
            'redirect_uri'  => $redirect_uri,
            'refresh_token' => $refresh_token,
        ]);

        $options = [
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => $postData,
            ],
        ];

        $context = stream_context_create($options);
        $response = @file_get_contents($token_url, false, $context);

        if ($response === false) {
            $error = error_get_last();

            throw new \Exception(
                'Error obteniendo token de TB Retail: ' .
                ($error['message'] ?? 'Respuesta desconocida')
            );
        }

        $token_data = json_decode($response, true);

        if (!isset($token_data['access_token'])) {
            throw new \Exception('No se pudo obtener access_token');
        }

        $access_token = $token_data['access_token'];

        /*
        |--------------------------------------------------------------------------
        | 2. CONSULTAR API (MISMA LÓGICA ORIGINAL)
        |--------------------------------------------------------------------------
        */
        $api_url = 'https://api.tbretail.com';

        $json_data = [
            'query' => '
            query {
                getData(
                    companies: []
                    brands: []
                    locations: [1566, 1569, 1570, 1571, 1572, 1573, 1575, 1577, 1578, 1579, 1580, 1581, 1586, 1587, 1588, 1617, 1618, 1619, 1620, 1630, 1631, 1632, 1633, 1634, 1635, 1636, 1637, 1638, 1639, 1640, 1643, 1644, 1645, 1646, 1647, 1648, 1651, 1652, 1653, 1655, 1657, 1658, 1661, 1662, 1663, 1665, 1666, 1672, 1674, 1678, 1696, 1697, 1699, 1709]
                    zones: []
                    start_date: "' . $fecha_inicio . '"
                    end_date: "' . $fecha_fin . '"
                    metrics: [{name: ENTERS, operation: SUM}]
                    category: {dimension: MINUTE interval: 15}
                    group: {dimension: LOCATION}
                ) {
                    series {
                        group
                        metric
                        data
                    }
                    categories
                }
            }'
        ];

        $headers = [
            'Authorization: ' . $access_token,
            'Content-Type: application/json',
        ];

        $ch = curl_init($api_url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($json_data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);

        if ($response === false) {
            throw new \Exception('Error consumiendo API TB Retail: ' . curl_error($ch));
        }

        curl_close($ch);

        $data = json_decode($response, true);

        if (!isset($data['data']['getData']['series'])) {
            throw new \Exception('La API no devolvió datos válidos.');
        }

        /*
        |--------------------------------------------------------------------------
        | 3. PROCESAR DATOS
        |--------------------------------------------------------------------------
        */
        $conteos = [];

        foreach ($data['data']['getData']['series'] as $item) {
            $conteos[$item['group']] = array_sum($item['data']);
        }

        /*
        |--------------------------------------------------------------------------
        | 4. MAPEAR LOCATION => MARCA / TIENDA
        |--------------------------------------------------------------------------
        */
        $mapeos = DB::connection('pgsql')
            ->table('location_id_tienda')
            ->get();

        $resultado = [];

        foreach ($mapeos as $mapeo) {
            if (!isset($conteos[$mapeo->location])) {
                continue;
            }

            $entidadId = ($tipo === 'marca')
                ? $mapeo->idmarca
                : $mapeo->idtienda;

            if (!isset($resultado[$entidadId])) {
                $resultado[$entidadId] = 0;
            }

            $resultado[$entidadId] += $conteos[$mapeo->location];
        }

        /*
        |--------------------------------------------------------------------------
        | 5. GUARDAR EN POSTGRESQL
        |--------------------------------------------------------------------------
        */
        $rows = [];

        foreach ($resultado as $entidadId => $conteo) {
            $rows[] = [
                'fecha'      => $fecha,
                'tipo'       => $tipo,
                'entidad_id' => $entidadId,
                'conteo'     => $conteo,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (!empty($rows)) {
            DB::connection('pgsql')
                ->table('tbretail_conteos')
                ->upsert(
                    $rows,
                    ['fecha', 'tipo', 'entidad_id'],
                    ['conteo', 'updated_at']
                );
        }

        return $resultado;
    }
}