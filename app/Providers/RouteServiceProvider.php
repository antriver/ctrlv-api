<?php

namespace CtrlV\Providers;

use Illuminate\Routing\Router;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * This namespace is applied to the controller routes in your routes file.
     * In addition, it is set as the URL generator's root namespace.
     *
     * @var string
     */
    protected $namespace = 'CtrlV\Http\Controllers';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @param \Illuminate\Routing\Router $router
     */
    public function boot(Router $router)
    {
        //$router->pattern('image', '\n+');

        $router->model('album', '\CtrlV\Models\Album');
        $router->model('image', '\CtrlV\Models\Image');
        $router->model('session', '\CtrlV\Models\UserSession');

        $router->bind(
            'user',
            function ($username) {
                return \CtrlV\Models\User::whereUsername($username)->first();
            }
        );

        //$router->model('user', '\CtrlV\Models\User');

        parent::boot($router);
    }

    /**
     * Define the routes for the application.
     *
     * @param \Illuminate\Routing\Router $router
     */
    public function map(Router $router)
    {
        $router->group(
            ['namespace' => $this->namespace],
            function ($router) {
                // $router is used in the required file though :(
                require dirname(__DIR__).'/Http/routes.php';
            }
        );
    }
}
