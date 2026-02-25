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
    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @param \Psr\Http\Server\RequestHandlerInterface $handler The handler.
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $origin = (string)$request->getHeaderLine('Origin');
        $allowedOrigin = $this->resolveAllowedOrigin($origin);

        if (strtoupper($request->getMethod()) === 'OPTIONS') {
            if ($origin !== '' && $allowedOrigin === '') {
                return (new Response())->withStatus(403);
            }

            $response = new Response();

            return $this->withCorsHeaders($response, $allowedOrigin);
        }

        $response = $handler->handle($request);

        return $this->withCorsHeaders($response, $allowedOrigin);
    }

    /**
     * @param \Psr\Http\Message\ResponseInterface $response The response.
     * @param string $allowedOrigin The allowed origin header value.
     * @return \Psr\Http\Message\ResponseInterface
     */
    private function withCorsHeaders(ResponseInterface $response, string $allowedOrigin): ResponseInterface
    {
        $response = $response
            ->withHeader('Vary', 'Origin')
            ->withHeader('Access-Control-Allow-Methods', 'GET,POST,PUT,PATCH,DELETE,OPTIONS')
            ->withHeader('Access-Control-Allow-Headers', 'Authorization,Content-Type,Accept,Origin,X-Requested-With')
            ->withHeader('Access-Control-Max-Age', '600');

        if ($allowedOrigin !== '') {
            $response = $response->withHeader('Access-Control-Allow-Origin', $allowedOrigin);
        }

        return $response;
    }

    /**
     * @param string $origin The Origin header from the request.
     * @return string The allowed origin, or empty string if not allowed.
     */
    private function resolveAllowedOrigin(string $origin): string
    {
        $configured = trim((string)env('API_CORS_ORIGINS', ''));
        if ($configured === '') {
            return '';
        }

        $allowed = array_filter(array_map('trim', explode(',', $configured)));
        if ($origin !== '' && in_array($origin, $allowed, true)) {
            return $origin;
        }

        return '';
    }
}
