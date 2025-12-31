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

use Cake\Routing\Route\DashedRoute;
use Cake\Routing\RouteBuilder;

/*
 * This file is loaded in the context of the `Application` class.
 * So you can use `$this` to reference the application class instance
 * if required.
 */
return function (RouteBuilder $routes): void {
    $routes->setRouteClass(DashedRoute::class);

    // Root redirect to default language
    $routes->scope('/', function (RouteBuilder $builder): void {
        $builder->connect('/', ['controller' => 'Pages', 'action' => 'redirectToDefaultLanguage']);
    });

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

        $builder->connect('/dashboard', ['controller' => 'Dashboard', 'action' => 'index'])
            ->setPatterns(['lang' => 'en|hu']);

        $builder->connect('/pages/*', 'Pages::display')
            ->setPatterns(['lang' => 'en|hu']);

        $builder->fallbacks();
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
            $builder->fallbacks();
        },
    );

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
