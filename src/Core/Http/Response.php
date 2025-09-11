<?php

namespace AuthSystem\Core\Http;


class Response
{
    private int $statusCode = 200;
    private array $headers = [];
    private string $body = '';
    private array $cookies = [];

    public function __construct(string $body = '', int $statusCode = 200, array $headers = [])
    {
        $this->body = $body;
        $this->statusCode = $statusCode;
        $this->headers = $headers;
    }


    public function setStatusCode(int $statusCode): self
    {
        $this->statusCode = $statusCode;
        return $this;
    }


    public function getStatusCode(): int
    {
        return $this->statusCode;
    }


    public function setHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }


    public function getHeader(string $name): ?string
    {
        return $this->headers[$name] ?? null;
    }


    public function setHeaders(array $headers): self
    {
        $this->headers = $headers;
        return $this;
    }


    public function getHeaders(): array
    {
        return $this->headers;
    }


    public function setBody(string $body): self
    {
        $this->body = $body;
        return $this;
    }


    public function getBody(): string
    {
        return $this->body;
    }


    public function setCookie(string $name, string $value, int $expire = 0, string $path = '/', string $domain = '', bool $secure = false, bool $httpOnly = true): self
    {
        $this->cookies[] = [
            'name' => $name,
            'value' => $value,
            'expire' => $expire,
            'path' => $path,
            'domain' => $domain,
            'secure' => $secure,
            'httpOnly' => $httpOnly,
        ];
        return $this;
    }


    public function send(): void
    {

        http_response_code($this->statusCode);


        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}");
        }


        foreach ($this->cookies as $cookie) {
            setcookie(
                $cookie['name'],
                $cookie['value'],
                $cookie['expire'],
                $cookie['path'],
                $cookie['domain'],
                $cookie['secure'],
                $cookie['httpOnly']
            );
        }


        echo $this->body;
    }


    public static function json(array $data, int $statusCode = 200, array $headers = []): self
    {
        $headers['Content-Type'] = 'application/json; charset=utf-8';
        return new self(json_encode($data, JSON_UNESCAPED_UNICODE), $statusCode, $headers);
    }


    public static function html(string $html, int $statusCode = 200, array $headers = []): self
    {
        $headers['Content-Type'] = 'text/html; charset=utf-8';
        return new self($html, $statusCode, $headers);
    }


    public static function redirect(string $url, int $statusCode = 302): self
    {
        return new self('', $statusCode, ['Location' => $url]);
    }


    public static function error(string $message, int $statusCode = 500): self
    {
        return self::json(['success' => false, 'message' => $message], $statusCode);
    }


    public static function success(array $data = [], string $message = null): self
    {
        $response = ['success' => true];

        if ($message !== null) {
            $response['message'] = $message;
        }

        if (!empty($data)) {
            $response['data'] = $data;
        }

        return self::json($response);
    }


    public static function validationError(array $errors): self
    {
        return self::json(['success' => false, 'message' => 'Validation failed', 'errors' => $errors], 422);
    }


    public static function unauthorized(string $message = 'Unauthorized'): self
    {
        return self::json(['success' => false, 'message' => $message], 401);
    }


    public static function forbidden(string $message = 'Forbidden'): self
    {
        return self::json(['success' => false, 'message' => $message], 403);
    }


    public static function notFound(string $message = 'Not Found'): self
    {

        if (!isset($_SERVER['HTTP_ACCEPT']) || strpos($_SERVER['HTTP_ACCEPT'], 'application/json') === false) {
            $html = self::render404Page($message);
            return self::html($html, 404);
        }

        return self::json(['success' => false, 'message' => $message], 404);
    }


    private static function render404Page(string $message): string
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - 页面未找到</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .error-page {
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .error-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 3rem;
            text-align: center;
            max-width: 500px;
        }
        .error-code {
            font-size: 6rem;
            font-weight: bold;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="error-page">
        <div class="error-card">
            <div class="error-code">404</div>
            <h2 class="mb-3">页面未找到</h2>
            <p class="mb-4">{$message}</p>
            <div>
                <a href="/" class="btn btn-light me-2">
                    <i class="bi bi-house"></i> 返回首页
                </a>
                <a href="/login" class="btn btn-outline-light">
                    <i class="bi bi-box-arrow-in-right"></i> 登录
                </a>
            </div>
        </div>
    </div>
</body>
</html>
HTML;
    }


    public static function methodNotAllowed(string $message = 'Method Not Allowed'): self
    {
        return self::json(['success' => false, 'message' => $message], 405);
    }


    public static function tooManyRequests(string $message = 'Too Many Requests'): self
    {
        return self::json(['success' => false, 'message' => $message], 429);
    }
}