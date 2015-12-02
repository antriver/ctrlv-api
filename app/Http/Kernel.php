<?php

namespace CtrlV\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * @var array
     */
    protected $middleware = [
        \Illuminate\Foundation\Http\Middleware\CheckForMaintenanceMode::class,
        /**
         * On Nginx add_header only works for 200, 204, 301, 302 and 304 status codes
         * unless we recompile with the headers_more module.
         * So set the CORS headers here instead.
         * (This middleware only sets the headers as necessary. It does not set them for non-CORS requests)
         */
        \Barryvdh\Cors\HandleCors::class,
        // Sessions and cookies are not used
        //\CtrlV\Http\Middleware\EncryptCookies::class,
        //\Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
        //\Illuminate\Session\Middleware\StartSession::class,
        //\Illuminate\View\Middleware\ShareErrorsFromSession::class,
        //\CtrlV\Http\Middleware\VerifyCsrfToken::class,
    ];

    /**
     * The application's route middleware.
     *
     * @var array
     */
    protected $routeMiddleware = [
        'auth' => \CtrlV\Http\Middleware\Authenticate::class,
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'guest' => \CtrlV\Http\Middleware\RedirectIfAuthenticated::class,
    ];
}
