<?php

namespace CtrlV\Providers;

use Config;
use CtrlV\Libraries\CacheManager;
use CtrlV\Libraries\FileManager;
use CtrlV\Libraries\PasswordHasher;
use CtrlV\Libraries\PictureFactory;
use Event;
use Request;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot()
    {
        /**
         * Query logging
         */
        if (Config::get('database.log_queries')) {
            $queryLogger = new \Monolog\Logger('Queries');

            $fileHandler = new \Monolog\Handler\RotatingFileHandler(storage_path() . '/logs/query.log');

            $lineFormatter = new \Monolog\Formatter\LineFormatter("%message% %context% %extra%\n", null, true, true);
            $fileHandler->setFormatter($lineFormatter);

            $queryLogger->pushHandler($fileHandler);

            if (php_sapi_name() !== 'cli') {
                $queryLogger->info(
                    "\n\n=======\n{$_SERVER['REQUEST_METHOD']}\n{$_SERVER['REQUEST_URI']}\n" . Request::server(
                        'HTTP_REFERER'
                    ) . "\n" . date('Y-m-d H:i:s') . "\n========="
                );
            }

            Event::listen(
                "illuminate.query",
                function ($query, $bindings, $time) use ($queryLogger) {
                    $queryLogger->info($query);
                    $queryLogger->info("\t$time seconds", $bindings);
                }
            );

        }
    }

    /**
     * Register any application services.
     */
    public function register()
    {
        $this->app->singleton('CacheManager', function ($app) {
            return new CacheManager();
        });

        $this->app->singleton('FileManager', function ($app) {
            return new FileManager();
        });

        $this->app->singleton('PictureFactory', function ($app) {
            return new PictureFactory();
        });

        $this->app->singleton('PasswordHasher', function ($app) {
            return new PasswordHasher();
        });
    }
}
