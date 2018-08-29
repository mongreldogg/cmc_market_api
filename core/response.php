<?php

namespace Core;

class Response
{
    public static function BaseURI($secure = false)
    {
        $uri = $secure ? 'https://' : 'http://';
        $uri .= HOSTNAME;

        return $uri;
    }

    public static function ExecutionTime()
    {
        return microtime(true) - EXECUTION_START_TIME;
    }

    public static function NotFound()
    {
        @header('HTTP/1.1 404 Not Found');
    }

    public static function AllowOrigin($pattern = '*')
    {
        @header("Access-Control-Allow-Origin: $pattern");
    }

    public static function Text($text, $continue = false)
    {
        @header('Content-Type: text/plain');
        echo $text;
        if (!$continue) {
            exit;
        }
    }

    public static function JSON($array, $continue = false)
    {
        @header('Content-Type: application/json');
        echo json_encode($array, true);
        if (!$continue) {
            exit;
        }
    }

    public static function HTML($content, $continue = false)
    {
        @header('Content-Type: text/html');
        echo $content;
        if (!$continue) {
            exit;
        }
    }

    public static function Custom($content, $contentType, $continue = false)
    {
        @header("Content-Type: $contentType");
        echo $content;
        if (!$continue) {
            exit;
        }
    }

    public static function Redirect($url, $temporary = false)
    {
        if ($temporary) {
            header('HTTP/1.1 302 Moved Temporarily');
        } else {
            header('HTTP/1.1 301 Moved Permanently');
        }
        header('Location: '.$url);
        exit;
    }
}
