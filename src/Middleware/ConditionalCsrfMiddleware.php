<?php
declare(strict_types=1);

namespace App\Middleware;

use Cake\Http\Middleware\CsrfProtectionMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ConditionalCsrfMiddleware implements MiddlewareInterface
{
    public function __construct(private CsrfProtectionMiddleware $csrfMiddleware)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $path = $request->getUri()->getPath();
        // App can be hosted in a subdirectory (e.g. /MindForge)
        // so match the API segment anywhere in the path.
        if (str_contains($path, '/api/v1') && preg_match('#/api/v1(/|$)#', $path) === 1) {
            return $handler->handle($request);
        }

        return $this->csrfMiddleware->process($request, $handler);
    }
}
