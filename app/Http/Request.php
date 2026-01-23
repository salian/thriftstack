<?php

declare(strict_types=1);

final class Request
{
    private string $method;
    private string $path;
    private array $query;
    private array $post;
    private array $params = [];

    public function __construct(string $method, string $path, array $query, array $post)
    {
        $this->method = strtoupper($method);
        $this->path = $path;
        $this->query = $query;
        $this->post = $post;
    }

    public static function capture(): self
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $path = rtrim($path, '/') ?: '/';

        return new self(
            $_SERVER['REQUEST_METHOD'] ?? 'GET',
            $path,
            $_GET,
            $_POST
        );
    }

    public function method(): string
    {
        return $this->method;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function query(string $key, $default = null)
    {
        return $this->query[$key] ?? $default;
    }

    public function input(string $key, $default = null)
    {
        return $this->post[$key] ?? $default;
    }

    public function all(): array
    {
        return $this->post;
    }

    public function params(): array
    {
        return $this->params;
    }

    public function param(string $key, $default = null)
    {
        return $this->params[$key] ?? $default;
    }

    public function setParams(array $params): void
    {
        $this->params = $params;
    }

    public function session(string $key, $default = null)
    {
        return $_SESSION[$key] ?? $default;
    }
}
