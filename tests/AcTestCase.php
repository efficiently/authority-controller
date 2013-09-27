<?php

use Mockery as m;

class AcTestCase extends Orchestra\Testbench\TestCase
{
    // public function setUp()
    // {
    //  parent::setUp();
    // }

    public function tearDown()
    {
        parent::tearDown();
        m::close();
    }

    protected function mock($className)
    {
        $mock = m::mock($className);
        // App::instance($className, $mock);
        App::bind($className, function() use($mock) { return $mock; });

        return $mock;
    }

    protected function fillMock($mock, $attributes)
    {
        $instance = $mock->makePartial();
        foreach ($attributes as $key => $value) {
            $instance->$key = $value;
        }
        return $instance;
    }

    protected function getPackageProviders()
    {
        return [
            'Efficiently\AuthorityController\AuthorityControllerServiceProvider',
        ];
    }

    protected function getPackageAliases()
    {
        return [
            'Authority' => 'Efficiently\AuthorityController\Facades\Authority',
            'Params'    => 'Efficiently\AuthorityController\Facades\Params',
        ];
    }

}
