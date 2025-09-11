<?php

namespace AuthSystem\Core\Http;


class Request
{
    private array $get;
    private array $post;
    private array $files;
    private array $server;
    private array $headers;
    private string $method;
    private string $uri;
    private string $body;

    public function __construct()
    {
        $this->get = $_GET;
        $this->post = $_POST;
        $this->files = $_FILES;
        $this->server = $_SERVER;
        $this->method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $this->uri = $_SERVER['REQUEST_URI'] ?? '/';
        $this->body = file_get_contents('php://input');
        $this->headers = $this->parseHeaders();
    }


    public static function createFromGlobals(): self
    {
        return new self();
    }


    public function getMethod(): string
    {
        return $this->method;
    }


    public function getUri(): string
    {
        return $this->uri;
    }


    public function get(string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->get;
        }
        return $this->get[$key] ?? $default;
    }


    public function post(string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->post;
        }
        return $this->post[$key] ?? $default;
    }


    public function all(): array
    {
        return array_merge($this->get, $this->post);
    }


    public function files(string $key = null)
    {
        if ($key === null) {
            return $this->files;
        }
        return $this->files[$key] ?? null;
    }


    public function server(string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->server;
        }
        return $this->server[$key] ?? $default;
    }


    public function header(string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->headers;
        }
        return $this->headers[$key] ?? $default;
    }


    public function getBody(): string
    {
        return $this->body;
    }


    public function setBody(string $body): void
    {
        $this->body = $body;
    }


    public function getHeader(string $name): ?string
    {
        $name = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        return $_SERVER[$name] ?? null;
    }


    public function json(): ?array
    {
        $data = json_decode($this->body, true);
        return $data ?: null;
    }


    public function getClientIp(): string
    {
        $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];

        foreach ($ipKeys as $key) {
            if (!empty($this->server[$key])) {
                $ip = $this->server[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        return $this->server['REMOTE_ADDR'] ?? '0.0.0.0';
    }


    public function getUserAgent(): string
    {
        return $this->server['HTTP_USER_AGENT'] ?? '';
    }


    public function isAjax(): bool
    {
        return $this->header('X-Requested-With') === 'XMLHttpRequest';
    }


    public function isJson(): bool
    {
        return strpos($this->header('Content-Type', ''), 'application/json') !== false;
    }


    public function isPost(): bool
    {
        return $this->method === 'POST';
    }


    public function isGet(): bool
    {
        return $this->method === 'GET';
    }


    public function isPut(): bool
    {
        return $this->method === 'PUT';
    }


    public function isDelete(): bool
    {
        return $this->method === 'DELETE';
    }


    private function parseHeaders(): array
    {
        $headers = [];

        foreach ($this->server as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $header = str_replace('_', '-', substr($key, 5));
                $header = ucwords(strtolower($header), '-');
                $headers[$header] = $value;
            }
        }

        return $headers;
    }
}