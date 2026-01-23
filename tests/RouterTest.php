<?php

declare(strict_types=1);

require __DIR__ . '/../app/Http/Request.php';
require __DIR__ . '/../app/Http/Response.php';
require __DIR__ . '/../app/Http/Router.php';
require __DIR__ . '/../app/View/View.php';

final class RouterTest extends TestCase
{
    public function run(): void
    {
        $router = new Router();
        $router->get('/users/{id}', static function (Request $request) {
            return 'user:' . $request->param('id');
        });

        $request = new Request('GET', '/users/42', [], []);
        $response = $router->dispatch($request);

        ob_start();
        $response->send();
        $body = ob_get_clean();

        $this->assertEquals('user:42', $body, 'Route params not captured');
    }
}
