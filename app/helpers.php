<?php

use Illuminate\Support\Str;

if (!function_exists('rest_api')) {
    function rest_api($array_data, $url, $method, $timeout = 300, $http_header = [])
    {
        //dd($http_header);
        $curl = curl_init();

        $array_data['env'] = isset($array_data["env"]) ? $array_data["env"] : config('app.env');
        $json_data = is_array($array_data) ? json_encode($array_data, true) : $array_data;
        // $json_data = $array_data;

        $http_header = empty($http_header) ? array_merge($http_header, [
            "Content-Type: application/json",
            "Cache-Control: no-cache",
        ]) : array_merge($http_header, ["Cache-Control: no-cache"]);

        $page = 1; // Número de página
        $pageSize = 4000; // Tamaño de página (cantidad de resultados por página)

        if (empty($http_header)) {
            //dd($url);
            // Loop para recuperar todas las páginas de resultados
            while (true) {
                // Construir la URL de la solicitud con los parámetros de paginación
                $urlWithPagination = $url . "?page=" . $page . "&pageSize=" . $pageSize;

                curl_setopt_array($curl, array(
                    CURLOPT_URL => $urlWithPagination,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => "",
                    CURLOPT_MAXREDIRS => -1,
                    CURLOPT_TIMEOUT => 600,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_SSL_VERIFYHOST => false,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_CUSTOMREQUEST => $method,
                    CURLOPT_POSTFIELDS => $json_data,
                    CURLOPT_HTTPHEADER => $http_header,
                ));


                // Ejecutar la solicitud CURL
                $response = curl_exec($curl);
                $err = curl_error($curl);
                //dd($response);
                //var_dump($response);
                // Verificar si hubo algún error en la solicitud
                if ($err) {
                    echo "Error en la solicitud: " . $err;
                    break; // Salir del bucle en caso de error
                }

                // Procesar la respuesta aquí...

                // Incrementar el número de página para la próxima solicitud
                $page++;
                //print("pagina : " . $page);
                // Decidir si continuar recuperando más páginas o no
                if (!$response) {
                    // Si no hay más datos, salir del bucle
                    break;
                }
                //var_dump(collect(json_decode(json_encode(json_decode($response, true)))));
            }

            //dd($method, $json_data, $http_header);

            // $response = curl_exec($curl); //comentado 2024-02-14
            // $err = curl_error($curl); //comentado 2024-02-14

            curl_close($curl);

            //dd($response, $err, $json_data, $url);
            if ($err) {
                //dd($err);
                return "cURL Error #:" . $err; //comentado 2024-02-14
            }

            //dd(collect(json_decode(json_encode(json_decode($response, true)))));
            //dd($response);
            return $response;
        }

        //Si http_header esta vacio sigue el flojo normal 

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => -1,
            CURLOPT_TIMEOUT => 600,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => $json_data,
            CURLOPT_HTTPHEADER => $http_header,
        ));


        // Ejecutar la solicitud CURL
        $response = curl_exec($curl);
        $err = curl_error($curl);
        //dd($response);
        //var_dump($response);
        // Verificar si hubo algún error en la solicitud
        if ($err) {
            echo "Error en la solicitud: " . $err;
        }
        //dd($method, $json_data, $http_header);

        $response = curl_exec($curl); //comentado 2024-02-14
        $err = curl_error($curl); //comentado 2024-02-14

        curl_close($curl);

        //dd($response, $err, $json_data, $url);
        if ($err) {
            //dd($err);
            return "cURL Error #:" . $err; //comentado 2024-02-14
        }

        //dd(collect(json_decode(json_encode(json_decode($response, true)))));
        //dd($response);
        return $response;
    }
}

if (!function_exists('rest_api_token')) {
    function rest_api_token($url = "", $credencials = [])
    {
        if (empty($url)) {
            $url = "https://apirest.sbperu.com/oauth/token";
        }
        if (empty($credencials)) {
            $credencials = [
                "grant_type" => "client_credentials",
                "client_id" => 7,
                "client_secret" => "bZtI2r4liDPMoSo1MLow3LrDEVjDYBBzUCSpNVOt",
                "scope" => "",
            ];
        }

        $token = json_decode(rest_api($credencials, $url, "POST"), true);

        return $token;
    }
}

if (!function_exists('get_url_api_rest')) {
    function get_url_api_rest()
    {
        return "https://apirest.sbperu.com/v2/smartapp/net";
    }
}

if (!function_exists('get_url_api_rest_reporte')) {
    function get_url_api_rest_reporte()
    {
        return "https://apirest.sbperu.com/v2/smartapp/reporte";
    }
}

if (!function_exists('get_url_api_rest_reporte_rfm')) {
    function get_url_api_rest_reporte_rfm()
    {
        return "https://apirest.sbperu.com/v2/smartapp/reporte_rfm";
    }
}

if (!function_exists('setActive')) {
    function setActive($routeName = '', $className = 'active')
    {
        if (is_array($routeName)) {
            for ($i = 0; $i < count($routeName); $i++) {
                if (request()->routeIs($routeName[$i])) {
                    return $className;
                }
            }
            return '';
        } else {
            return request()->routeIs($routeName) ? $className : '';
        }
    }
}

if (!function_exists('prueba')) {
    function prueba($prueba)
    {
        return $prueba;
    }
}
