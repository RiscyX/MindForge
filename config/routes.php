<?php
/**
 * Routes configuration.
 *
 * In this file, you set up routes to your controllers and their actions.
 * Routes are very important mechanism that allows you to freely connect
 * different URLs to chosen controllers and their actions (functions).
 *
 * It's loaded within the context of `Application::routes()` method which
 * receives a `RouteBuilder` instance `$routes` as method argument.
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

use App\Middleware\ApiCorsMiddleware;
use App\Middleware\ApiRequestLoggingMiddleware;
use App\Middleware\ApiTokenAuthMiddleware;
use Cake\Http\ServerRequest;
use Cake\Routing\Route\DashedRoute;
use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;

/*
 * This file is loaded in the context of the `Application` class.
 * So you can use `$this` to reference the application class instance
 * if required.
 */
return function (RouteBuilder $routes): void {
    $routes->setRouteClass(DashedRoute::class);

    // Persist the current language in generated URLs by default.
    Router::addUrlFilter(static function (array $params, ServerRequest $request): array {
        if (isset($params['lang'])) {
            return $params;
        }

        $lang = (string)$request->getParam('lang');
        if ($lang === '' || !in_array($lang, ['en', 'hu'], true)) {
            return $params;
        }

        // Never inject language into API routes.
        if (($params['prefix'] ?? null) === 'Api') {
            return $params;
        }

        // Root redirects use query param lang, not path lang.
        if (($params['controller'] ?? null) === 'Pages') {
            $action = (string)($params['action'] ?? '');
            if (in_array($action, ['redirectToAdmin', 'redirectToQuizCreator', 'redirectToDefaultLanguage'], true)) {
                return $params;
            }
        }

        // Swagger scope is not language-prefixed.
        if (($params['controller'] ?? null) === 'Swagger') {
            return $params;
        }

        $params['lang'] = $lang;

        return $params;
    });

    // Root redirect to default language
    $routes->scope('/', function (RouteBuilder $builder): void {
        $builder->connect('/', ['controller' => 'Pages', 'action' => 'redirectToDefaultLanguage']);
        $builder->connect('/admin', ['controller' => 'Pages', 'action' => 'redirectToAdmin']);
        $builder->connect('/admin/dashboard', ['controller' => 'Pages', 'action' => 'redirectToAdmin']);
        $builder->connect('/quiz-creator', ['controller' => 'Pages', 'action' => 'redirectToQuizCreator']);
    });

    // Swagger UI Routes
    $routes->scope('/swagger', function (RouteBuilder $builder): void {
        $builder->connect('/', ['controller' => 'Swagger', 'action' => 'ui']);
        $builder->connect('/json', ['controller' => 'Swagger', 'action' => 'json']);
    });

    // API Routes
    $routes->scope('/api/v1', ['prefix' => 'Api'], function (RouteBuilder $builder): void {
        $builder->registerMiddleware('apiCors', new ApiCorsMiddleware());
        $builder->registerMiddleware('apiRequestLog', new ApiRequestLoggingMiddleware());
        $builder->registerMiddleware('apiAuth', new ApiTokenAuthMiddleware());
        $builder->applyMiddleware('apiCors', 'apiRequestLog', 'apiAuth');

        $builder->setExtensions(['json']);
        $builder->connect('/auth/register', ['controller' => 'Auth', 'action' => 'register'])->setMethods(['POST']);
        $builder->connect('/auth/login', ['controller' => 'Auth', 'action' => 'login'])->setMethods(['POST']);
        $builder->connect('/auth/refresh', ['controller' => 'Auth', 'action' => 'refresh'])->setMethods(['POST']);
        $builder->connect('/auth/logout', ['controller' => 'Auth', 'action' => 'logout'])->setMethods(['POST']);
        $builder->connect('/auth/me', ['controller' => 'Auth', 'action' => 'me'])->setMethods(['GET']);
        $builder->connect('/auth/me', ['controller' => 'Auth', 'action' => 'updateMe'])->setMethods(['PATCH', 'PUT', 'POST']);

        // Attempts / quiz taking flow
        $builder->connect('/tests/{id}/start', ['controller' => 'Tests', 'action' => 'start'])
            ->setPatterns(['id' => '\\d+'])
            ->setPass(['id'])
            ->setMethods(['POST']);
        $builder->connect('/attempts/{id}', ['controller' => 'Attempts', 'action' => 'view'])
            ->setPatterns(['id' => '\\d+'])
            ->setPass(['id'])
            ->setMethods(['GET']);
        $builder->connect('/attempts/{id}/submit', ['controller' => 'Attempts', 'action' => 'submit'])
            ->setPatterns(['id' => '\\d+'])
            ->setPass(['id'])
            ->setMethods(['POST']);
        $builder->connect('/attempts/{id}/review', ['controller' => 'Attempts', 'action' => 'review'])
            ->setPatterns(['id' => '\\d+'])
            ->setPass(['id'])
            ->setMethods(['GET']);

        // Stats for the authenticated user
        $builder->connect('/me/stats/quizzes', ['controller' => 'Stats', 'action' => 'quizzes'])
            ->setMethods(['GET']);

        // Creator: async AI quiz generation (prompt + optional images)
        $builder->connect('/creator/tests/metadata', ['controller' => 'CreatorTests', 'action' => 'metadata'])
            ->setMethods(['GET']);
        $builder->connect('/creator/ai/test-generation', ['controller' => 'CreatorAi', 'action' => 'createTestGeneration'])
            ->setMethods(['POST']);
        $builder->connect('/creator/ai/requests/{id}', ['controller' => 'CreatorAi', 'action' => 'view'])
            ->setPatterns(['id' => '\\d+'])
            ->setPass(['id'])
            ->setMethods(['GET']);
        $builder->connect('/creator/ai/requests/{id}/apply', ['controller' => 'CreatorAi', 'action' => 'apply'])
            ->setPatterns(['id' => '\\d+'])
            ->setPass(['id'])
            ->setMethods(['POST']);

        $builder->resources('Tests');
        $builder->fallbacks(DashedRoute::class);
    });

    // Admin prefix: /{lang}/admin/*
    $routes->scope(
        '/{lang}/admin',
        ['prefix' => 'Admin', 'lang' => 'en|hu'],
        function (RouteBuilder $builder): void {
            $builder->connect('/', ['controller' => 'Dashboard', 'action' => 'index'])
                ->setPatterns(['lang' => 'en|hu']);
            $builder->connect('/dashboard', ['controller' => 'Dashboard', 'action' => 'index'])
                ->setPatterns(['lang' => 'en|hu']);

            $builder->connect('/my-profile', ['controller' => 'Users', 'action' => 'myProfile'])
                ->setPatterns(['lang' => 'en|hu']);
            $builder->fallbacks();
        },
    );

    // Quiz creator prefix: /{lang}/quiz-creator/*
    $routes->scope(
        '/{lang}/quiz-creator',
        ['prefix' => 'QuizCreator', 'lang' => 'en|hu'],
        function (RouteBuilder $builder): void {
            $builder->connect('/', ['controller' => 'Dashboard', 'action' => 'index'])
                ->setPatterns(['lang' => 'en|hu']);
            $builder->connect('/dashboard', ['controller' => 'Dashboard', 'action' => 'index'])
                ->setPatterns(['lang' => 'en|hu']);

            $builder->fallbacks();
        },
    );

    // Language-prefixed routes: /en/* and /hu/*
    $routes->scope('/{lang}', function (RouteBuilder $builder): void {

        $builder->connect('/', ['controller' => 'Pages', 'action' => 'display', 'home'])
            ->setPatterns(['lang' => 'en|hu']);

        $builder->connect('/login', ['controller' => 'Users', 'action' => 'login'])
            ->setPatterns(['lang' => 'en|hu']);

        $builder->connect('/register', ['controller' => 'Users', 'action' => 'register'])
            ->setPatterns(['lang' => 'en|hu']);

        $builder->connect('/logout', ['controller' => 'Users', 'action' => 'logout'])
            ->setPatterns(['lang' => 'en|hu']);

        $builder->connect('/forgot-password', ['controller' => 'Users', 'action' => 'forgotPassword'])
            ->setPatterns(['lang' => 'en|hu']);

        $builder->connect('/reset-password', ['controller' => 'Users', 'action' => 'resetPassword'])
            ->setPatterns(['lang' => 'en|hu']);

        $builder->connect('/dashboard', ['controller' => 'Dashboard', 'action' => 'index'])
            ->setPatterns(['lang' => 'en|hu']);

        $builder->connect('/categories', ['controller' => 'Categories', 'action' => 'index'])
            ->setPatterns(['lang' => 'en|hu']);
        $builder->connect('/categories/add', ['controller' => 'Categories', 'action' => 'add'])
            ->setPatterns(['lang' => 'en|hu']);
        $builder->connect('/categories/view/*', ['controller' => 'Categories', 'action' => 'view'])
            ->setPatterns(['lang' => 'en|hu']);
        $builder->connect('/categories/edit/*', ['controller' => 'Categories', 'action' => 'edit'])
            ->setPatterns(['lang' => 'en|hu']);
        $builder->connect('/categories/delete/*', ['controller' => 'Categories', 'action' => 'delete'])
            ->setPatterns(['lang' => 'en|hu']);

        // Public/user-facing test details (friendly URL)
        $builder->connect('/tests/{id}/details', ['controller' => 'Tests', 'action' => 'details'])
            ->setPatterns(['lang' => 'en|hu', 'id' => '\\d+'])
            ->setPass(['id']);

        $builder->connect('/tests/ai-request-status/{id}', ['controller' => 'Tests', 'action' => 'aiRequestStatus'])
            ->setPatterns(['lang' => 'en|hu', 'id' => '\\d+'])
            ->setPass(['id'])
            ->setMethods(['GET']);

        $builder->connect('/tests/{attemptId}/review/{questionId}/explain', ['controller' => 'Tests', 'action' => 'explainAnswer'])
            ->setPatterns(['lang' => 'en|hu', 'attemptId' => '\\d+', 'questionId' => '\\d+'])
            ->setPass(['attemptId', 'questionId'])
            ->setMethods(['POST']);

        $builder->connect('/pages/*', 'Pages::display')
            ->setPatterns(['lang' => 'en|hu']);

        $builder->connect('/confirm', ['controller' => 'Users', 'action' => 'confirm'])
            ->setPatterns(['lang' => 'en|hu']);

        $builder->connect('/profile', ['controller' => 'Users', 'action' => 'profile'])
            ->setPatterns(['lang' => 'en|hu']);

        $builder->connect('/profile-edit', ['controller' => 'Users', 'action' => 'profileEdit'])
            ->setPatterns(['lang' => 'en|hu']);

        $builder->connect('/my-stats', ['controller' => 'Users', 'action' => 'stats'])
            ->setPatterns(['lang' => 'en|hu']);

        $builder->connect('/users', ['controller' => 'Users', 'action' => 'index'])
            ->setPatterns(['lang' => 'en|hu']);

        $builder->connect('/users/view/*', ['controller' => 'Users', 'action' => 'view'])
            ->setPatterns(['lang' => 'en|hu']);

        $builder->connect('/users/edit/*', ['controller' => 'Users', 'action' => 'edit'])
            ->setPatterns(['lang' => 'en|hu']);

        $builder->connect('/users/add', ['controller' => 'Users', 'action' => 'add'])
            ->setPatterns(['lang' => 'en|hu']);

        $builder->connect('/users/delete/*', ['controller' => 'Users', 'action' => 'delete'])
            ->setPatterns(['lang' => 'en|hu']);

        $builder->fallbacks();
    });

    /*
     * If you need a different set of middleware or none at all,
     * open new scope and define routes there.
     *
     * ```
     * $routes->scope('/api', function (RouteBuilder $builder): void {
     *     // No $builder->applyMiddleware() here.
     *
     *     // Parse specified extensions from URLs
     *     // $builder->setExtensions(['json', 'xml']);
     *
     *     // Connect API actions here.
     * });
     * ```
     */
};
