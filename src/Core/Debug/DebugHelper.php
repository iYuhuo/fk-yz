<?php

namespace AuthSystem\Core\Debug;

use AuthSystem\Core\Logger\Logger;


class DebugHelper
{
    private static ?Logger $logger = null;


    public static function setLogger(Logger $logger): void
    {
        self::$logger = $logger;
    }


    public static function log(string $message, array $context = []): void
    {
        if (self::$logger) {
            self::$logger->debug($message, $context);
        }

        if (defined('DEBUG') && DEBUG) {
            error_log("[DEBUG] {$message} " . json_encode($context));
        }
    }


    public static function logRoute(string $method, string $uri, string $handler): void
    {
        self::log("Route matched", [
            'method' => $method,
            'uri' => $uri,
            'handler' => $handler
        ]);
    }


    public static function logMiddleware(string $middleware, string $action = 'executing'): void
    {
        self::log("Middleware {$action}", [
            'middleware' => $middleware
        ]);
    }


    public static function logRequest(string $method, string $uri, array $data = []): void
    {
        self::log("Request received", [
            'method' => $method,
            'uri' => $uri,
            'data_keys' => array_keys($data)
        ]);
    }


    public static function logResponse(int $statusCode, string $type = 'html'): void
    {
        self::log("Response sent", [
            'status_code' => $statusCode,
            'type' => $type
        ]);
    }


    public static function logSession(string $action, array $data = []): void
    {
        self::log("Session {$action}", $data);
    }
}