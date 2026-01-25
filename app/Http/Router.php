<?php

declare(strict_types=1);

final class Router
{
    /** @var Route[] */
    private array $routes = [];

    public function get(string $path, callable $handler): Route
    {
        return $this->add('GET', $path, $handler);
    }

    public function post(string $path, callable $handler): Route
    {
        return $this->add('POST', $path, $handler);
    }

    public function dispatch(Request $request): Response
    {
        foreach ($this->routes as $route) {
            if (!$route->matches($request->method(), $request->path())) {
                continue;
            }

            $params = $route->extractParams($request->path());
            $request->setParams($params);

            $handler = $route->handler();
            $pipeline = $this->buildMiddlewarePipeline($route, $handler);
            $result = $pipeline($request);

            return $this->normalizeResponse($result);
        }

        $body = View::render('404', ['title' => 'Not Found']);
        return Response::notFound($body);
    }

    public function url(string $name, array $params = []): ?string
    {
        foreach ($this->routes as $route) {
            if ($route->name() !== $name) {
                continue;
            }
            return $route->buildUrl($params);
        }

        return null;
    }

    private function add(string $method, string $path, callable $handler): Route
    {
        $route = new Route($method, $path, $handler);
        $this->routes[] = $route;

        return $route;
    }

    private function buildMiddlewarePipeline(Route $route, callable $handler): callable
    {
        $middleware = $route->middlewareList();
        $pipeline = $handler;

        foreach (array_reverse($middleware) as $layer) {
            $pipeline = static function (Request $request) use ($layer, $pipeline) {
                return $layer->handle($request, $pipeline);
            };
        }

        return $pipeline;
    }

    private function normalizeResponse($result): Response
    {
        if ($result instanceof Response) {
            return $result;
        }

        return Response::html((string)$result);
    }
}

final class Route
{
    private string $method;
    private string $path;
    /** @var callable */
    private $handler;
    private ?string $name = null;
    private array $middleware = [];

    public function __construct(string $method, string $path, callable $handler)
    {
        $this->method = strtoupper($method);
        $this->path = rtrim($path, '/') ?: '/';
        $this->handler = $handler;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function middleware(object $middleware): self
    {
        $this->middleware[] = $middleware;
        return $this;
    }

    public function handler(): callable
    {
        return $this->handler;
    }

    public function name(): ?string
    {
        return $this->name;
    }

    public function middlewareList(): array
    {
        return $this->middleware;
    }

    public function matches(string $method, string $path): bool
    {
        if ($this->method !== strtoupper($method)) {
            return false;
        }

        return (bool)preg_match($this->toRegex(), $path);
    }

    public function extractParams(string $path): array
    {
        $params = [];
        if (preg_match($this->toRegex(), $path, $matches)) {
            foreach ($matches as $key => $value) {
                if (!is_string($key)) {
                    continue;
                }
                $params[$key] = $value;
            }
        }
        return $params;
    }

    public function buildUrl(array $params): string
    {
        $url = $this->path;
        foreach ($params as $key => $value) {
            $url = str_replace('{' . $key . '}', (string)$value, $url);
        }
        return $url;
    }

    private function toRegex(): string
    {
        $pattern = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_-]*)\}#', '(?P<$1>[^/]+)', $this->path);
        return '#^' . $pattern . '$#';
    }
}
