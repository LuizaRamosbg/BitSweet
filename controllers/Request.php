<?php

class Request {
    private string $method;
    private array $query;
    private array $body;

    public function __construct() {
        $this->method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $this->query = $_GET;

        $input = file_get_contents('php://input');
        $decoded = json_decode($input, true);
        $this->body = is_array($decoded) ? $decoded : [];
    }

    public function getMethod(): string {
        return $this->method;
    }

    public function getQuery(string $key, $default = null) {
        return $this->query[$key] ?? $default;
    }

    public function getBody(string $key, $default = null) {
        return $this->body[$key] ?? $default;
    }

    public function hasBody(string $key): bool {
        return array_key_exists($key, $this->body);
    }

    public function allBody(): array {
        return $this->body;
    }
}
