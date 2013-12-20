<?php namespace Efficiently\AuthorityController;

use Illuminate\Support\ServiceProvider;
use Illuminate\Foundation\AliasLoader;
use Efficiently\AuthorityController\Authority;
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

        // Find the default Controller class of the current Laravel application
        $aliasLoader = AliasLoader::getInstance();
        $controllerClass = array_get($aliasLoader->getAliases(), 'Controller', '\Illuminate\Routing\Controller');

        $this->app->resolvingAny(function ($object) use ($controllerClass) {
            if (is_a($object, $controllerClass)) {
                // Fill $params properties of the current controller
                $this->app['parameters']->fillController($object);
            }
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
