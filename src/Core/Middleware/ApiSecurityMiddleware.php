<?php

namespace AuthSystem\Core\Middleware;

use AuthSystem\Core\Http\Request;
use AuthSystem\Core\Http\Response;
use AuthSystem\Core\Config\Config;
use AuthSystem\Core\Logger\Logger;


class ApiSecurityMiddleware implements MiddlewareInterface
{
    private Config $config;
    private Logger $logger;

    public function __construct(Config $config, Logger $logger)
    {
        $this->config = $config;
        $this->logger = $logger;
    }


    public function handle(Request $request, callable $next): Response
    {
        try {

            if ($this->isClientAuthRequired()) {
                if (!$this->validateClientAuth($request)) {
                    $this->logger->warning('API Client authentication failed', [
                        'ip' => $request->getClientIp(),
                        'user_agent' => $request->getUserAgent(),
                        'path' => $request->getUri()
                    ]);

                    return Response::unauthorized('需要客户端身份验证');
                }
            }


            if ($this->isApiKeyRequired()) {
                $apiSecret = $this->config->get('security.api_secret_key', '');
                if (!empty($apiSecret)) {
                    if (!$this->validateApiKey($request, $apiSecret)) {
                        $this->logger->warning('API Key validation failed', [
                            'ip' => $request->getClientIp(),
                            'user_agent' => $request->getUserAgent(),
                            'path' => $request->getUri()
                        ]);

                        return Response::unauthorized('API密钥无效');
                    }
                } else {

                    $this->logger->warning('API Key validation enabled but key is empty', [
                        'ip' => $request->getClientIp(),
                        'path' => $request->getUri()
                    ]);

                    return Response::unauthorized('API密钥验证已启用但未配置密钥');
                }
            }


            $request = $this->handleEncryption($request);


            $response = $next($request);


            return $this->handleResponseEncryption($response);

        } catch (\Exception $e) {
            $this->logger->error('API Security middleware error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return Response::error('安全验证失败', 500);
        }
    }


    private function isClientAuthRequired(): bool
    {
        $required = $_ENV['CLIENT_AUTH_REQUIRED'] ?? 'false';
        return filter_var($required, FILTER_VALIDATE_BOOLEAN);
    }


    private function isApiKeyRequired(): bool
    {
        $required = $_ENV['API_KEY_REQUIRED'] ?? 'false';
        return filter_var($required, FILTER_VALIDATE_BOOLEAN);
    }


    private function validateClientAuth(Request $request): bool
    {

        $clientId = $request->getHeader('X-Client-ID');
        $clientSignature = $request->getHeader('X-Client-Signature');
        $timestamp = $request->getHeader('X-Timestamp');

        if (empty($clientId) || empty($clientSignature) || empty($timestamp)) {
            return false;
        }


        $currentTime = time();
        $requestTime = (int)$timestamp;
        if (abs($currentTime - $requestTime) > 300) {
            return false;
        }



        return true;
    }


    private function validateApiKey(Request $request, string $expectedKey): bool
    {

        $apiKey = $request->getHeader('X-API-Key')
               ?? $request->getHeader('Authorization')
               ?? $request->get('api_key');


        if ($apiKey && strpos($apiKey, 'Bearer ') === 0) {
            $apiKey = substr($apiKey, 7);
        }

        return hash_equals($expectedKey, $apiKey ?? '');
    }


    private function handleEncryption(Request $request): Request
    {
        $encryptMethod = $_ENV['API_ENCRYPT_METHOD'] ?? '';
        if (empty($encryptMethod) || $encryptMethod === 'none') {
            return $request;
        }


        $encryptedData = $request->getHeader('X-Encrypted-Data');
        if (empty($encryptedData)) {
            return $request;
        }

        try {

            $decryptedData = $this->decryptData($encryptedData, $encryptMethod);
            if ($decryptedData) {

                $request->setBody($decryptedData);
            }
        } catch (\Exception $e) {
            $this->logger->error('Request decryption failed', [
                'error' => $e->getMessage(),
                'method' => $encryptMethod
            ]);
        }

        return $request;
    }


    private function handleResponseEncryption(Response $response): Response
    {
        $encryptMethod = $_ENV['API_ENCRYPT_METHOD'] ?? '';
        if (empty($encryptMethod) || $encryptMethod === 'none') {
            return $response;
        }

        try {
            $body = $response->getBody();
            $encryptedBody = $this->encryptData($body, $encryptMethod);

            if ($encryptedBody) {
                $response->setBody($encryptedBody);
                $response->setHeader('X-Encrypted-Response', 'true');
                $response->setHeader('X-Encryption-Method', $encryptMethod);
            }
        } catch (\Exception $e) {
            $this->logger->error('Response encryption failed', [
                'error' => $e->getMessage(),
                'method' => $encryptMethod
            ]);
        }

        return $response;
    }


    private function encryptData(string $data, string $method): ?string
    {
        $apiSecret = $_ENV['API_SECRET_KEY'] ?? '';
        if (empty($apiSecret)) {
            return null;
        }

        try {
            switch ($method) {
                case 'AES-256-CBC':
                    $iv = openssl_random_pseudo_bytes(16);
                    $encrypted = openssl_encrypt($data, 'aes-256-cbc', $apiSecret, 0, $iv);
                    return base64_encode($iv . $encrypted);

                case 'AES-128-CBC':
                    $iv = openssl_random_pseudo_bytes(16);
                    $encrypted = openssl_encrypt($data, 'aes-128-cbc', substr($apiSecret, 0, 16), 0, $iv);
                    return base64_encode($iv . $encrypted);

                case 'DES-EDE3-CBC':
                    $iv = openssl_random_pseudo_bytes(8);
                    $encrypted = openssl_encrypt($data, 'des-ede3-cbc', substr($apiSecret, 0, 24), 0, $iv);
                    return base64_encode($iv . $encrypted);

                default:
                    return null;
            }
        } catch (\Exception $e) {
            $this->logger->error('Encryption failed', [
                'error' => $e->getMessage(),
                'method' => $method
            ]);
            return null;
        }
    }


    private function decryptData(string $encryptedData, string $method): ?string
    {
        $apiSecret = $_ENV['API_SECRET_KEY'] ?? '';
        if (empty($apiSecret)) {
            return null;
        }

        try {
            $data = base64_decode($encryptedData);

            switch ($method) {
                case 'AES-256-CBC':
                    $iv = substr($data, 0, 16);
                    $encrypted = substr($data, 16);
                    return openssl_decrypt($encrypted, 'aes-256-cbc', $apiSecret, 0, $iv);

                case 'AES-128-CBC':
                    $iv = substr($data, 0, 16);
                    $encrypted = substr($data, 16);
                    return openssl_decrypt($encrypted, 'aes-128-cbc', substr($apiSecret, 0, 16), 0, $iv);

                case 'DES-EDE3-CBC':
                    $iv = substr($data, 0, 8);
                    $encrypted = substr($data, 8);
                    return openssl_decrypt($encrypted, 'des-ede3-cbc', substr($apiSecret, 0, 24), 0, $iv);

                default:
                    return null;
            }
        } catch (\Exception $e) {
            $this->logger->error('Decryption failed', [
                'error' => $e->getMessage(),
                'method' => $method
            ]);
            return null;
        }
    }
}