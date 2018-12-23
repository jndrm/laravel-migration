<?php

namespace Drmer\Laravel\Migration\Routing;

use App;

class UrlGeneratorMigration
{
    public static function run()
    {
        App::extend('url', function ($oldUrl, $app) {
            $routes = $app['router']->getRoutes();

            $url = new UrlGenerator(
                $routes,
                $app->rebinding(
                    'request',
                    static::requestRebinder()
                ),
                $app['config']['app.asset_url']
            );

            // Next we will set a few service resolvers on the URL generator so it can
            // get the information it needs to function. This just provides some of
            // the convenience features to this URL generator like "signed" URLs.
            $url->setSessionResolver(function () use ($app) {
                return $app['session'];
            });

            $url->setKeyResolver(function () use ($app) {
                return $app->make('config')->get('app.key');
            });

            // If the route collection is "rebound", for example, when the routes stay
            // cached for the application, we will need to rebind the routes on the
            // URL generator instance so it has the latest version of the routes.
            $app->rebinding('routes', function ($app, $routes) {
                $app['url']->setRoutes($routes);
            });

            return $url;
        });
    }

    /**
     * Get the URL generator request rebinder.
     *
     * @return \Closure
     */
    protected static function requestRebinder()
    {
        return function ($app, $request) {
            $app['url']->setRequest($request);
        };
    }
}
