<?php

use Mockery as m;

class AcControllerAdditionsClass
{
    use \Efficiently\AuthorityController\ControllerAdditions;
}

class AcControllerAdditionsTest extends AcTestCase
{
    use AuthorityControllerHelpers;

    protected $controller;
    protected $filterPrefix = "router.filter: ";

    public function setUp()
    {
        parent::setUp();
        // Route::enableFilters();

        $this->controllerClass = $this->mock('AcControllerAdditionsClass');

        $this->controller = $this->controllerClass->makePartial();
        $this->controller->shouldReceive('getParams')->andReturn([]);
        $this->controller->shouldReceive('getCurrentUser')->andReturn("currentUser");
    }

    // Authorize should assign $_authorized property and pass args to current authority
    public function testAuthorizeShouldAssignAuthorizedInstanceVariableAndPassArgsToCurrentAuthority()
    {
        $this->controller->shouldReceive('getCurrentAuthority->authorize')->with('foo', 'bar')->once();

        $this->controller->authorize('foo', 'bar');
        $this->assertTrue($this->getProperty($this->controller, '_authorized'));
    }

    // Should have a getCurrentAuthority() method which generates an authority for the current user
    public function testShouldHaveACurrentAuthorityMethodWhichGeneratesAnAuthorityForTheCurrentUser()
    {
        $this->assertInstanceOf("Efficiently\AuthorityController\Authority", $this->controller->getCurrentAuthority());
    }

    // Should provide a can() and cannot() methods which go through the current authority
    public function testShouldProvideACanAndCannotMethodsWhichGoThroughTheCurrentAuthority()
    {
        $this->assertInstanceOf("Efficiently\AuthorityController\Authority", $this->controller->getCurrentAuthority());
        $this->assertFalse($this->controller->can('foo', 'bar'));
        $this->assertTrue($this->controller->cannot('foo', 'bar'));
    }

    // loadAndAuthorizeResource() should setup a before filter which passes call to ControllerResource
    public function testLoadAndAuthorizeResourceShouldSetupABeforeFilterWhichPassesCallToControllerResource()
    {
        $controller = $this->controller;
        $controllerResourceClass = 'Efficiently\AuthorityController\ControllerResource';
        App::offsetUnset($controllerResourceClass);
        App::bind($controllerResourceClass, function ($app, $parameters) use ($controllerResourceClass, $controller) {
            $this->assertEquals($parameters, [$controller, null, ['foo' => 'bar']]);
            $controllerResource = m::mock($controllerResourceClass, $parameters);
            $controllerResource->shouldReceive('loadAndAuthorizeResource')->once();
            return $controllerResource;
        });

        $controller->shouldReceive('beforeFilter')->with(m::type('string'), [])->once()
            ->andReturnUsing(function ($filterName, $options) use ($controller) {
                $this->assertTrue(Event::hasListeners($this->filterPrefix.$filterName));
                return Event::fire($this->filterPrefix.$filterName);
            });

        $controller->loadAndAuthorizeResource(['foo' => 'bar']);
    }

    // loadAndAuthorizeResource() should properly pass first argument as the resource name
    public function testloadAndAuthorizeResourceShouldProperlyPassFirstArgumentAsTheResourceName()
    {
        $controller = $this->controller;
        $controllerResourceClass = 'Efficiently\AuthorityController\ControllerResource';
        App::offsetUnset($controllerResourceClass);
        App::bind($controllerResourceClass, function ($app, $parameters) use ($controllerResourceClass, $controller) {
            $this->assertEquals($parameters, [$controller, 'project', ['foo' => 'bar']]);
            $controllerResource = m::mock($controllerResourceClass, $parameters);
            $controllerResource->shouldReceive('loadAndAuthorizeResource')->once();
            return $controllerResource;
        });

        $controller->shouldReceive('beforeFilter')->with(m::type('string'), [])->once()
            ->andReturnUsing(function ($filterName, $options) use ($controller) {
                $this->assertTrue(Event::hasListeners($this->filterPrefix.$filterName));
                return Event::fire($this->filterPrefix.$filterName);
            });

        $controller->loadAndAuthorizeResource('project', ['foo' => 'bar']);
    }

    // loadAndAuthorizeResource() with 'prepend' should prepend the before filter
    public function testLoadAndAuthorizeResourceWithPrependShouldPrependTheBeforeFilter()
    {
        $this->controller->shouldReceive('prependBeforeFilter')->once();

        $this->controller->loadAndAuthorizeResource(['foo' => 'bar', 'prepend' => true]);
    }

    // authorizeResource() should setup a before filter which passes call to ControllerResource
    public function testAuthorizeResourceShouldSetupABeforeFilterWhichPassesCallToControllerResource()
    {
        $controller = $this->controller;
        $controllerResourceClass = 'Efficiently\AuthorityController\ControllerResource';
        App::offsetUnset($controllerResourceClass);
        App::bind($controllerResourceClass, function ($app, $parameters) use ($controllerResourceClass, $controller) {
            $this->assertEquals($parameters, [$controller, null, ['foo' => 'bar']]);
            $controllerResource = m::mock($controllerResourceClass, $parameters);
            $controllerResource->shouldReceive('authorizeResource')->once();
            return $controllerResource;
        });

        $controller->shouldReceive('beforeFilter')->with(m::type('string'), ['except' => 'show'])->once()
            ->andReturnUsing(function ($filterName, $options) use ($controller) {
                $this->assertTrue(Event::hasListeners($this->filterPrefix.$filterName));
                return Event::fire($this->filterPrefix.$filterName);
            });

        $controller->authorizeResource(['foo' => 'bar', 'except' => 'show']);
    }

    // loadResource() should setup a before filter which passes call to ControllerResource
    public function testLoadResourceShouldSetupABeforeFilterWhichPassesCallToControllerResource()
    {
        $controller = $this->controller;
        $controllerResourceClass = 'Efficiently\AuthorityController\ControllerResource';
        App::offsetUnset($controllerResourceClass);
        App::bind($controllerResourceClass, function ($app, $parameters) use ($controllerResourceClass, $controller) {
            $this->assertEquals($parameters, [$controller, null, ['foo' => 'bar']]);
            $controllerResource = m::mock($controllerResourceClass, $parameters);
            $controllerResource->shouldReceive('loadResource')->once();
            return $controllerResource;
        });

        $controller->shouldReceive('beforeFilter')->with(m::type('string'), ['only' => ['show', 'index']])->once()
            ->andReturnUsing(function ($filterName, $options) use ($controller) {
                $this->assertTrue(Event::hasListeners($this->filterPrefix.$filterName));
                return Event::fire($this->filterPrefix.$filterName);
            });

        $controller->loadResource(['foo' => 'bar', 'only' => ['show', 'index']]);
    }
}
