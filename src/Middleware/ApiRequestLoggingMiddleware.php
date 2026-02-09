<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Model\Entity\ApiToken;
use Cake\Datasource\FactoryLocator;
use Cake\I18n\FrozenTime;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ApiRequestLoggingMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $path = rtrim($request->getUri()->getPath(), '/');
        if ($path === '') {
            $path = '/';
        }

        $method = strtoupper($request->getMethod());
        $isApi = (preg_match('#/api/v1(/|$)#', $path) === 1);
        if (!$isApi || $method === 'OPTIONS' || $method === 'GET') {
            return $handler->handle($request);
        }

        $response = $handler->handle($request);

        $tableLocator = FactoryLocator::get('Table');
        /** @var \Cake\ORM\Table $activityLogs */
        $activityLogs = $tableLocator->get('ActivityLogs');

        $userId = $this->resolveUserId($request, $tableLocator);

        $serverParams = $request->getServerParams();
        $ipAddress = (string)($serverParams['REMOTE_ADDR'] ?? '');

        $entity = $activityLogs->newEntity([
            'user_id' => $userId,
            'action' => $path,
            'ip_address' => $ipAddress,
            'user_agent' => (string)$request->getHeaderLine('User-Agent'),
        ]);
        $activityLogs->save($entity);

        return $response;
    }

    private function resolveUserId(ServerRequestInterface $request, $tableLocator): ?int
    {
        $apiUser = $request->getAttribute('apiUser');
        if (is_object($apiUser) && isset($apiUser->id)) {
            return (int)$apiUser->id;
        }

        $header = trim((string)$request->getHeaderLine('Authorization'));
        if ($header === '' || !preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) {
            return null;
        }

        $rawToken = trim((string)$matches[1]);
        $parts = explode('.', $rawToken, 2);
        if (count($parts) !== 2 || $parts[0] === '' || $parts[1] === '') {
            return null;
        }

        $tokenId = $parts[0];
        $secret = $parts[1];

        /** @var \App\Model\Table\ApiTokensTable $apiTokens */
        $apiTokens = $tableLocator->get('ApiTokens');
        $token = $apiTokens->find()
            ->where([
                'token_id' => $tokenId,
                'token_type' => 'access',
            ])
            ->first();

        if (!$token instanceof ApiToken) {
            return null;
        }

        if (!hash_equals((string)$token->token_hash, hash('sha256', $secret))) {
            return null;
        }

        if ($token->revoked_at !== null || $token->expires_at <= FrozenTime::now()) {
            return null;
        }

        return (int)$token->user_id;
    }
}
