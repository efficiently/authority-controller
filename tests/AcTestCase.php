<?php

use Mockery as m;

abstract class AcTestCase extends Orchestra\Testbench\TestCase
{
    protected $app;
    protected $router;

    public function tearDown()
    {
        parent::tearDown();
        m::close();
    }

    protected function mock($className)
    {
        $mock = m::mock($className);
        App::bind($className, function ($app, $parameters = []) use ($mock) {
            if (is_array($parameters) && is_array($attributes = array_get($parameters, 0, [])) && respond_to($mock, "fill")) {
                $mock = $this->fillMock($mock, $attributes);
            }

            return $mock;
        });

        return $mock;
    }

    protected function fillMock($mock, $attributes = [])
    {
        $instance = $mock->makePartial();
        foreach ($attributes as $key => $value) {
            $instance->$key = $value;
        }

        return $instance;
    }

    protected function getPackageProviders($app)
    {
        return [
            Collective\Html\HtmlServiceProvider::class,
            Efficiently\AuthorityController\AuthorityControllerServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app)
    {
        return [
            'Input' => Illuminate\Support\Facades\Input::class,
            'Form' => Collective\Html\FormFacade::class,
            'HTML' => Collective\Html\HtmlFacade::class,
            'Authority' => Efficiently\AuthorityController\Facades\Authority::class,
            'Params'    => Efficiently\AuthorityController\Facades\Params::class,
        ];
    }

    /**
     * Resolve application HTTP exception handler.
     *
     * @param  \Illuminate\Foundation\Application  $app
     *
     * @return void
     */
    protected function resolveApplicationExceptionHandler($app)
    {
        $app->singleton('Illuminate\Contracts\Debug\ExceptionHandler', 'AcExceptionsHandler');
    }
}
