<?php

use Cake\Core\Plugin;
use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;
use Cake\Routing\Route\DashedRoute;

/**
 * The default class to use for all routes
 *
 * The following route classes are supplied with CakePHP and are appropriate
 * to set as the default:
 *
 * - Route
 * - InflectedRoute
 * - DashedRoute
 *
 * If no call is made to `Router::defaultRouteClass()`, the class used is
 * `Route` (`Cake\Routing\Route\Route`)
 *
 * Note that `Route` does not do any inflections on URLs which will result in
 * inconsistently cased URLs when used with `:plugin`, `:controller` and
 * `:action` markers.
 *
 */
// Router::defaultRouteClass(DashedRoute::class);

Router::defaultRouteClass(InflectedRoute::class); // this is our old way of doing things

Router::scope('/', function (RouteBuilder $routes) {
    $multiDomainRoutes = [
        'site1.tv' => [
            'connect' => [
                '/' => ['controller' => 'Site1', 'action' => 'index'],
            ],
            'redirect' => [
                '/register/' => ['controller' => 'Site1', 'action' => 'register'],
            ]
        ],
        'site2.com' => [
            'connect' => [
                '/' => ['controller' => 'Site2', 'action' => 'index'],
            ],
        ]
    ];

    foreach ($multiDomainRoutes as $site => $siteRoutes) {
        if (isset($_SERVER['SERVER_NAME']) && $_SERVER['SERVER_NAME'] == $site) {
            foreach ($siteRoutes as $method => $methodRoutes) {
                foreach ($methodRoutes as $url => $connection) {
                    if ($method == 'forbidden') {
                        $routes->redirect($connection, '/');
                    } else {
                        $routes->$method($url, $connection);
                    }
                }
            }
            break;
        }
    }

    // $routes->parseExtensions('rss');
    $routes->addExtensions('json');

    // $routes->connect('/', ['controller' => 'Home', 'action' => 'homepage']);

    /**
     * ...and connect the rest of 'Pages' controller's URLs.
     */
    // $routes->connect('/pages/*', ['controller' => 'Pages', 'action' => 'display']);

    /**
     * Connect catchall routes for all controllers.
     *
     * Using the argument `DashedRoute`, the `fallbacks` method is a shortcut for
     *    `$routes->connect('/:controller', ['action' => 'index'], ['routeClass' => 'DashedRoute']);`
     *    `$routes->connect('/:controller/:action/*', [], ['routeClass' => 'DashedRoute']);`
     *
     * Any route class can be used with this method, such as:
     * - DashedRoute
     * - InflectedRoute
     * - Route
     * - Or your own route class
     *
     * You can remove these routes once you've connected the
     * routes you want in your application.
     */
    $routes->fallbacks(InflectedRoute::class); // this is our old way of doing things, TODO SEO dashed routes are better?
    $routes->fallbacks('DashedRoute');
});

/**
 * Load all plugin routes.  See the Plugin documentation on
 * how to customize the loading of plugin routes.
 */
Plugin::routes();
