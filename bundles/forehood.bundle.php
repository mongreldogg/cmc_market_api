<?php

namespace Bundle;

use Core\IReverseRoute;

define('REQUEST_HTTP_GET', 'GET');
define('REQUEST_HTTP_POST', 'POST');
define('REQUEST_HTTP_PUT', 'PUT');
define('REQUEST_HTTP_HEAD', 'HEAD');

class Forehood implements IReverseRoute
{
    private static $ownRoutes = [];
    private static $response = null;

    private static $cacheEnabled = false;

    public static function EnableResponseCache()
    {
        self::$cacheEnabled = true;
    }

    public static function own()
    {
        return self::$ownRoutes;
    }

    public static function request($method, $url, $data = [], $headers = [])
    {
        self::$response = null;

        if (self::$cacheEnabled) {
            if (isset($_SESSION['FOREHOOD_RESPONSE_'.$url])) {
                self::$response = json_decode($_SESSION['FOREHOOD_RESPONSE_'.$url], true);
            }
        }

        if (!self::$response) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            if (DEBUG_MODE) {
                curl_setopt($ch, CURLOPT_VERBOSE, 1);
            } else {
                curl_setopt($ch, CURLOPT_VERBOSE, 0);
            }
            curl_setopt($ch, CURLOPT_HEADER, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
            curl_setopt($ch, CURLOPT_CRLF, 1);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, FOREHOOD_DEAFULT_TIMEOUT);
            curl_setopt($ch, CURLOPT_TIMEOUT_MS, FOREHOOD_DEAFULT_TIMEOUT);
            curl_setopt($ch, CURLOPT_COOKIESESSION, 1);
            $_headers = [];
            if (is_array($headers)) {
                foreach ($headers as $key => $value) {
                    $_headers[] = $key.': '.$value;
                }
            }
            if (count($_headers) > 0) {
                curl_setopt($ch, CURLOPT_HTTPHEADER, $_headers);
            }
            switch ($method) {
                case REQUEST_HTTP_GET:
                    curl_setopt($ch, CURLOPT_HTTPGET, 1);
                    break;
                case REQUEST_HTTP_POST:
                    curl_setopt($ch, CURLOPT_POST, 1);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                    break;
                case REQUEST_HTTP_PUT:
                    curl_setopt($ch, CURLOPT_PUT, 1);
                    break;
                case REQUEST_HTTP_HEAD:
                    curl_setopt($ch, CURLOPT_NOBODY, 1);
                    break;
                default:
                    curl_setopt($ch, CURLOPT_HTTPGET, 1);
                    break;
            }

            @$response = curl_exec($ch);

            $headers = substr($response, 0, curl_getinfo($ch, CURLINFO_HEADER_SIZE));
            $body = substr($response, strlen($headers));
            $headers = preg_split('/\\r\\n|\\r|\\n/', $headers);
            $_headers = [];
            foreach ($headers as $id => $header) {
                $header = explode(':', $header, 2);
                if (count($header) == 2) {
                    $_headers[$header[0]] = trim($header[1]);
                }
            }

            self::$response = [];
            self::$response['error'] = curl_error($ch);
            self::$response['target'] = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
            self::$response['status'] = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            self::$response['http_status'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            self::$response['time'] = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
            self::$response['ip'] = curl_getinfo($ch, CURLINFO_PRIMARY_IP);
            self::$response['headers'] = $_headers;
            self::$response['body'] = $body;

            curl_close($ch);
        }

        self::call($url);
    }

    public static function call($pattern)
    {
        @self::$ownRoutes[$pattern](self::$response);
    }

    public static function on($pattern, $callback)
    {
        self::$ownRoutes[$pattern] = $callback;
    }

    public function __construct()
    {
    }
}
