<?php namespace Efficiently\AuthorityController;

use Efficiently\AuthorityController\Authority;
use Illuminate\Support\ServiceProvider;
use Controller;
use Efficiently\AuthorityController\Parameters;

class AuthorityControllerServiceProvider extends ServiceProvider
{

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->package('efficiently/authority-controller');
        // $this->app->resolving(function($object) {
        //     // Fill $params properties of the current controller if it hasn't any filters
        //     if ( is_a($object, 'BaseController') && ! $object->getControllerFilters() ) {
        //         $this->app['parameters']->fillController($object);
        //     }
        // });

    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app['parameters'] = $this->app->share(function ($app) {
            return new Parameters;
        });

        $this->app['authority'] = $this->app->share(function ($app) {
            $user = $app['auth']->user();
            $authority = new Authority($user);
            $fn = $app['config']->get('authority-controller::initialize', null);

            if ($fn) {
                $fn($authority);
            }

            return $authority;
        });

        $this->app->bind('Efficiently\AuthorityController\ControllerResource', function ($app, $parameters) {
            list($controller, $resourceName, $resourceOptions) = $parameters;
            return new ControllerResource($controller, $resourceName, $resourceOptions);
        });

    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['authority'];
    }

}
