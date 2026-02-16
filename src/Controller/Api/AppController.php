<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Controller\AppController as BaseController;
use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.2.0',
    title: 'MindForge API',
    description: 'API documentation for MindForge Mobile Application',
)]
#[OA\Server(
    url: 'http://localhost/MindForge',
    description: 'Local Development Server',
)]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'Opaque',
)]
class AppController extends BaseController
{
    /**
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();

        // API authentication is enforced by ApiTokenAuthMiddleware.
        // Allow the current action through CakePHP's Authentication component to avoid
        // duplicate/incorrect 401s when the middleware already validated the token.
        $action = (string)$this->request->getParam('action');
        if ($action !== '') {
            $this->Authentication->allowUnauthenticated([$action]);
        }

        // Load JSON view components by default for API
        $this->viewBuilder()->setClassName('Json');
    }

    /**
     * @param array<string, mixed> $payload
     * @return void
     */
    protected function jsonSuccess(array $payload): void
    {
        if (!array_key_exists('ok', $payload)) {
            $payload = ['ok' => true] + $payload;
        }
        $this->set($payload);
        $this->viewBuilder()->setOption('serialize', array_keys($payload));
    }

    /**
     * @return void
     */
    protected function jsonError(int $status, string $code, string $message): void
    {
        // Safety: an error response must never be HTTP 200.
        if ($status < 400) {
            $status = 400;
        }

        $this->response = $this->response->withStatus($status);
        $this->set([
            'ok' => false,
            'error' => [
            'code' => $code,
            'message' => $message,
            ],
        ]);
        $this->viewBuilder()->setOption('serialize', ['ok', 'error']);
    }
}
