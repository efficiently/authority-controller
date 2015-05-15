<?php namespace Efficiently\AuthorityController;

use Illuminate\Support\ServiceProvider;
use SuperClosure\Serializer;
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
        // Publish config
        $this->publishes([
            __DIR__ . '/../../config/config.php' => config_path('authority-controller.php')
        ], 'config');

        // Publish migrations
        $this->publishes([
            __DIR__ . '/../../migrations/' => base_path('database/migrations')
        ], 'migrations');

        // Load translations
        $this->loadTranslationsFrom(__DIR__ . '/../../lang', 'authority-controller');

        // Publish translations
        $this->publishes([
            __DIR__ . '/../../translations' => base_path('resources/lang')
        ], 'translations');
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
        $controllerClass = $this->app['config']->get(
            'authority-controller.controllerClass',
            'Illuminate\Routing\Controller'
        );

        $this->app->resolving(function ($object) use ($controllerClass) {
            // Check if the current $object class is a Controller class and if it responds to paramsBeforeFilter method
            if (is_a($object, $controllerClass) && respond_to($object, 'paramsBeforeFilter')) {
                // Fill $params properties of the current controller
                $this->app['parameters']->fillController($object);
            }
        });

        $this->app['authority'] = $this->app->share(function ($app) {
            $user = $app['auth']->user();
            $authority = new Authority($user);

            $fn = $app['config']->get('authority-controller.initialize');

            $serializer = new Serializer;
            if (is_string($fn)) {
                $fn = $serializer->unserialize($fn);
            }

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
