<?php

use Mockery as m;

class AcParametersTest extends AcTestCase
{
    use AuthorityControllerHelpers;

    public function setUp()
    {
        parent::setUp();

        $this->controllerName = "ProjectsController";
        Route::resource('projects', $this->controllerName);

        $this->parameters = new \Efficiently\AuthorityController\Parameters;
        App::instance('Params', $this->parameters);
    }

    public function testAddParameter()
    {
        Params::add('key', 'value');
        $this->assertEquals(Params::get('key'), 'value');
    }

    public function testAddParameterWithDotKeys()
    {
        Params::add('key.subkey', 'value');
        $this->assertEquals(Params::get('key.subkey'), 'value');
    }

    public function testOnlyParameters()
    {
        Params::add('key1', 'value1');
        Params::add('key2', 'value2');
        $this->assertEquals(Params::only('key1'), ['key1' => 'value1']);
    }

    public function testExceptParameters()
    {
        Params::add('key1', 'value1');
        Params::add('key2', 'value2');
        $this->assertEquals(Params::except('key2'), ['key1' => 'value1']);
    }

    public function testExtractResourceFromInput()
    {
        $input = ['project' => ['name' => 'foo']];
        $parameters = $this->parameters;
        $controller = $this->mockController();

        $this->call('POST', '/projects', $input);// store action

        $this->assertArrayHasKey('project', $this->getProperty($parameters, 'params'));
        $this->assertEquals($this->getProperty($parameters, 'params')['project'], $input['project']);

        $this->assertArrayHasKey('project', $this->getProperty($controller, 'params'));
        $this->assertEquals($this->getProperty($controller, 'params')['project'], $input['project']);
    }

    public function testResolveResourceFromInput()
    {
        $input = ['name' => 'foo'];
        $parameters = $this->parameters;
        $controller = $this->mockController();

        $this->call('POST', '/projects', $input);// store action

        $this->assertArrayHasKey('project', $this->getProperty($parameters, 'params'));
        $this->assertEquals($this->getProperty($parameters, 'params')['project'], $input);

        $this->assertArrayHasKey('project', $this->getProperty($controller, 'params'));
        $this->assertEquals($this->getProperty($controller, 'params')['project'], $input);
    }

    public function testExtractResourceFromInputWithSingularControllerAndRoute()
    {
        $input = ['project' => ['name' => 'foo']];
        $parameters = $this->parameters;

        $controllerName = "ProjectController";
        Route::resource('project', $controllerName);
        $controller = $this->mockController($controllerName);

        $this->call('POST', '/project', $input);// store action

        $this->assertArrayHasKey('project', $this->getProperty($parameters, 'params'));
        $this->assertEquals($this->getProperty($parameters, 'params')['project'], $input['project']);

        $this->assertArrayHasKey('project', $this->getProperty($controller, 'params'));
        $this->assertEquals($this->getProperty($controller, 'params')['project'], $input['project']);
    }

    public function testResolveResourceFromInputWithSingularControllerAndRoute()
    {
        $input = ['name' => 'foo'];
        $parameters = $this->parameters;

        $controllerName = "ProjectController";
        Route::resource('project', $controllerName);
        $controller = $this->mockController($controllerName);

        $this->call('POST', '/project', $input);// store action

        $this->assertArrayHasKey('project', $this->getProperty($parameters, 'params'));
        $this->assertEquals($this->getProperty($parameters, 'params')['project'], $input);

        $this->assertArrayHasKey('project', $this->getProperty($controller, 'params'));
        $this->assertEquals($this->getProperty($controller, 'params')['project'], $input);
    }

    public function testResolveActionAndControllerNamesFromRequest()
    {
        $input = ['project' => ['name' => 'foo']];
        $parameters = $this->parameters;
        $controller = $this->mockController();

        $this->call('POST', '/projects', $input);// store action

        $this->assertArrayHasKey('action', $this->getProperty($parameters, 'params'));
        $this->assertEquals($this->getProperty($parameters, 'params')['action'], 'store');

        $this->assertArrayHasKey('controller', $this->getProperty($parameters, 'params'));
        $this->assertEquals($this->getProperty($parameters, 'params')['controller'], 'ProjectsController');


        $this->assertArrayHasKey('action', $this->getProperty($controller, 'params'));
        $this->assertEquals($this->getProperty($controller, 'params')['action'], 'store');

        $this->assertArrayHasKey('controller', $this->getProperty($controller, 'params'));
        $this->assertEquals($this->getProperty($controller, 'params')['controller'], 'ProjectsController');
    }

    public function testResolveResourceIdFromRequest()
    {
        $parameters = $this->parameters;
        $controller = $this->mockController();

        $this->call('GET', '/projects/5');// show action

        $this->assertArrayHasKey('id', $this->getProperty($parameters, 'params'));
        $this->assertEquals($this->getProperty($parameters, 'params')['id'], '5');

        $this->assertArrayHasKey('id', $this->getProperty($controller, 'params'));
        $this->assertEquals($this->getProperty($controller, 'params')['id'], '5');
    }

    public function testResolveResourceAndParentResourceIdsFromRequest()
    {
        $parameters = $this->parameters;

        $controllerName = "TasksController";
        Route::resource('projects.tasks', $controllerName);
        $controller = $this->mockController($controllerName);

        $this->call('GET', '/projects/5/tasks/2');// show action of task resource

        $this->assertArrayHasKey('project_id', $this->getProperty($parameters, 'params'));
        $this->assertEquals($this->getProperty($parameters, 'params')['project_id'], '5');

        $this->assertArrayHasKey('project_id', $this->getProperty($controller, 'params'));
        $this->assertEquals($this->getProperty($controller, 'params')['project_id'], '5');


        $this->assertArrayHasKey('id', $this->getProperty($parameters, 'params'));
        $this->assertEquals($this->getProperty($parameters, 'params')['id'], '2');

        $this->assertArrayHasKey('id', $this->getProperty($controller, 'params'));
        $this->assertEquals($this->getProperty($controller, 'params')['id'], '2');
    }

    public function testResolveResourceIdFromRequestWithSingularController()
    {
        $parameters = $this->parameters;

        $controllerName = "ProjectController";
        Route::resource('projects', $controllerName);
        $controller = $this->mockController($controllerName);

        $this->call('GET', '/projects/6');// show action

        $this->assertArrayHasKey('id', $this->getProperty($parameters, 'params'));
        $this->assertEquals($this->getProperty($parameters, 'params')['id'], '6');

        $this->assertArrayHasKey('id', $this->getProperty($controller, 'params'));
        $this->assertEquals($this->getProperty($controller, 'params')['id'], '6');
    }

    public function testResolveResourceIdFromRequestWithSingularRoute()
    {
        $parameters = $this->parameters;

        Route::resource('project', $this->controllerName);
        $controller = $this->mockController();

        $this->call('GET', '/projects/7');// show action

        $this->assertArrayHasKey('id', $this->getProperty($parameters, 'params'));
        $this->assertEquals($this->getProperty($parameters, 'params')['id'], '7');

        $this->assertArrayHasKey('id', $this->getProperty($controller, 'params'));
        $this->assertEquals($this->getProperty($controller, 'params')['id'], '7');
    }

    public function testResolveResourceIdFromRequestWithSingularControllerAndRoute()
    {
        $parameters = $this->parameters;

        $controllerName = "ProjectController";
        Route::resource('project', $controllerName);
        $controller = $this->mockController($controllerName);

        $this->call('GET', '/project/8');// show action

        $this->assertArrayHasKey('id', $this->getProperty($parameters, 'params'));
        $this->assertEquals($this->getProperty($parameters, 'params')['id'], '8');

        $this->assertArrayHasKey('id', $this->getProperty($controller, 'params'));
        $this->assertEquals($this->getProperty($controller, 'params')['id'], '8');
    }

    public function testResolveResourceAndParentResourceIdsFromRequestWithSingularControllerAndRoute()
    {
        $parameters = $this->parameters;

        $controllerName = "TaskController";
        Route::resource('project.task', $controllerName);
        $controller = $this->mockController($controllerName);

        $this->call('GET', '/project/5/task/2');// show action of task resource

        $this->assertArrayHasKey('project_id', $this->getProperty($parameters, 'params'));
        $this->assertEquals($this->getProperty($parameters, 'params')['project_id'], '5');

        $this->assertArrayHasKey('project_id', $this->getProperty($controller, 'params'));
        $this->assertEquals($this->getProperty($controller, 'params')['project_id'], '5');


        $this->assertArrayHasKey('id', $this->getProperty($parameters, 'params'));
        $this->assertEquals($this->getProperty($parameters, 'params')['id'], '2');

        $this->assertArrayHasKey('id', $this->getProperty($controller, 'params'));
        $this->assertEquals($this->getProperty($controller, 'params')['id'], '2');
    }

    protected function mockController($controllerName = null)
    {
        $controllerName = $controllerName ?: $this->controllerName;

        $this->mock($controllerName);
        $controller = App::make($controllerName);

        $controller->shouldReceive('paramsBeforeFilter')->with(m::type('string'))->once();
        $controller->shouldReceive('callAction')
          ->with(m::type('\Illuminate\Container\Container'), m::type('\Illuminate\Routing\Router'), m::type('string'), m::type('array'))
            ->once()->andReturnUsing(function ($container, $router, $method, $parameters) use($controller) {

                App::make('Params')->fillController($controller);
                $filter = $router->getFilter("controller.parameters.".get_classname($controller));
                $filter($controller, $router);// Call the Parameters filter to fill the params into the Controller's $params property

                return new \Symfony\Component\HttpFoundation\Response;
        });

        $this->mock('\Efficiently\AuthorityController\ControllerResource');
        $this->controllerResource = App::make('\Efficiently\AuthorityController\ControllerResource');
        $this->controllerResource->shouldReceive('getNameByController')->with('ProjectsController')->andReturn('project');

        return $controller;
    }

}
