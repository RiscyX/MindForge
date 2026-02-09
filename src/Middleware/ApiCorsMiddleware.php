<?php
declare(strict_types=1);

namespace App\Middleware;

use Cake\Http\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use function Cake\Core\env;

class ApiCorsMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $origin = (string)$request->getHeaderLine('Origin');
        $allowedOrigin = $this->resolveAllowedOrigin($origin);

        if (strtoupper($request->getMethod()) === 'OPTIONS') {
            $response = new Response();

            return $this->withCorsHeaders($response, $allowedOrigin);
        }

        $response = $handler->handle($request);

        return $this->withCorsHeaders($response, $allowedOrigin);
    }

    private function withCorsHeaders(ResponseInterface $response, string $allowedOrigin): ResponseInterface
    {
        return $response
            ->withHeader('Access-Control-Allow-Origin', $allowedOrigin)
            ->withHeader('Vary', 'Origin')
            ->withHeader('Access-Control-Allow-Methods', 'GET,POST,PUT,PATCH,DELETE,OPTIONS')
            ->withHeader('Access-Control-Allow-Headers', 'Authorization,Content-Type,Accept,Origin,X-Requested-With')
            ->withHeader('Access-Control-Max-Age', '600');
    }

    private function resolveAllowedOrigin(string $origin): string
    {
        $configured = trim((string)env('API_CORS_ORIGINS', '*'));
        if ($configured === '*' || $configured === '') {
            return '*';
        }

        $allowed = array_filter(array_map('trim', explode(',', $configured)));
        if ($origin !== '' && in_array($origin, $allowed, true)) {
            return $origin;
        }

        return (string)($allowed[0] ?? '*');
    }
}
