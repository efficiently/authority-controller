<?php

use Mockery as m;

class AcControllerResourceTest extends AcTestCase
{
    use AuthorityControllerHelpers;

    public function setUp()
    {
        parent::setUp();

        $this->params = ['controller' => 'projects'];
        $this->controllerClass = $this->mock('ProjectsController');
        $this->controller = App::make('ProjectsController');

        $this->user = $this->getUserWithRole('admin');
        $this->authority = $this->getAuthority($this->user);

        $this->controller->shouldReceive('getParams')->andReturnUsing(function () {
            return $this->params;
        });
        $this->controller->shouldReceive('getCurrentAuthority')->andReturnUsing(function () {
            return $this->authority;
        });
        // $this->controllerClass->shouldReceive('cancanSkipper')->andReturnUsing( function() { return ['authorize' => [], 'load' => []] });;
    }

    // Should load the resource into an instance variable if $params['id'] is specified
    public function testLoadResourceInstanceWithParamId()
    {
        $projectAttributes = ['id' => 2];
        $project = $this->buildModel('Project', $projectAttributes);
        $this->params = array_merge($this->params, ['action' => 'show', 'id' => $project->id]);

        $resource = new Efficiently\AuthorityController\ControllerResource($this->controller);
        $resource->loadResource();

        $this->assertEquals($this->getProperty($this->controller, 'project'), $project);
    }


    // Should not load resource into an instance variable if already set
    public function testNotLoadResourceInstanceIfAlreadySet()
    {
        $this->params = array_merge($this->params, ['action' => 'show', 'id' => '123']);

        $this->setProperty($this->controller, 'project', 'some_project');

        $resource = new Efficiently\AuthorityController\ControllerResource($this->controller);
        $resource->loadResource();

        $this->assertEquals($this->getProperty($this->controller, 'project'), 'some_project');
    }

    // Should properly load resource for namespaced controller
    public function testLoadResourceForNamespacedController()
    {
        $projectAttributes = ['id' => 2];
        $project = $this->buildModel('Project', $projectAttributes);
        $this->params = array_merge(
            $this->params,
            ['controller' => 'admin/projects', 'action' => 'show', 'id' => $project->id]
        );

        $resource = new Efficiently\AuthorityController\ControllerResource($this->controller);
        $resource->loadResource();

        $this->assertEquals($this->getProperty($this->controller, 'project'), $project);
    }

    // Should attempt to load a resource with the same namespace as the controller when using '\' for namespace
    public function testLoadResourceWithSameNamespaceAsControllerWithBackslashedNamespace()
    {
        $projectAttributes = ['id' => 2];
        $project = $this->buildModel('MyEngine\Project', $projectAttributes);
        $this->params = array_merge(
            $this->params,
            ['controller' => 'MyEngine\ProjectsController', 'action' => 'show', 'id' => $project->id]
        );

        $resource = new Efficiently\AuthorityController\ControllerResource($this->controller);
        $resource->loadResource();

        $this->assertEquals($this->getProperty($this->controller, 'project'), $project);
    }

    // Laravel includes namespace in params, see CanCan issue #349
    // Should store through the namespaced params
    public function testCreateThroughNamespacedParams()
    {
        // namespace MyEngine;
        // class Project extends \Project {}
        $projectAttributes = ['id' => 2];
        $project = $this->buildModel('MyEngine\Project', $projectAttributes);

        $actionParams = ['my_engine_project' => ['name' => 'foobar']];
        $this->params = array_merge(
            $this->params,
            array_merge(['controller' => 'MyEngine\ProjectsController', 'action' => 'store'], $actionParams)
        );

        $resource = new Efficiently\AuthorityController\ControllerResource($this->controller);
        $resource->loadResource();

        $this->assertEquals($this->getProperty($this->controller, 'project')->name, "foobar");
    }

    // Should properly load resource for namespaced controller when using '\' for namespace
    public function testProperlyLoadResourceNamespacedControllerWithBackslashedNamespace()
    {
        $projectAttributes = ['id' => 2];
        $project = $this->buildModel('Project', $projectAttributes);
        $this->params = array_merge(
            $this->params,
            ['controller' => 'Admin\ProjectsController', 'action' => 'show', 'id' => $project->id]
        );

        $resource = new Efficiently\AuthorityController\ControllerResource($this->controller);
        $resource->loadResource();

        $this->assertEquals($this->getProperty($this->controller, 'project'), $project);
    }

    // Should properly detect namespaces for resource and controller when using '\' for namespace
    public function testProperlyDetectNamespacesResourceControllerWithBackslashedNamespace()
    {
        $commentAttributes = ['id' => 3];
        $comment = $this->buildModel('App\Comment', $commentAttributes);
        $this->params = array_merge(
            $this->params,
            ['controller' => 'CommentsController', 'action' => 'show', 'id' => $comment->id]
        );

        $this->mock('App\Http\Controllers\CommentsController');
        $controller = App::make('App\Http\Controllers\CommentsController');
        $controller->shouldReceive('getParams')->andReturnUsing(function () {
            return $this->params;
        });
        $controller->shouldReceive('getCurrentAuthority')->andReturnUsing(function () {
            return $this->authority;
        });

        $resource = new Efficiently\AuthorityController\ControllerResource($controller);
        $resource->loadResource();

        $this->assertEquals($this->getProperty($controller, 'comment'), $comment);
    }

    // Should properly detect root namespace of resource for namespaced controller when using '\' for namespace
    public function testProperlyDetectRootNamespaceResourceNamespacedControllerWithBackslashedNamespace()
    {
        $commentAttributes = ['id' => 3];
        $comment = $this->buildModel('App\Comment', $commentAttributes);
        $this->params = array_merge(
            $this->params,
            ['controller' => 'App\Http\Controllers\CommentsController', 'action' => 'show', 'id' => $comment->id]
        );

        $this->mock('App\Http\Controllers\CommentsController');
        $controller = App::make('App\Http\Controllers\CommentsController');
        $controller->shouldReceive('getParams')->andReturnUsing(function () {
            return $this->params;
        });
        $controller->shouldReceive('getCurrentAuthority')->andReturnUsing(function () {
            return $this->authority;
        });

        $resource = new Efficiently\AuthorityController\ControllerResource($controller);
        $resource->loadResource();

        $this->assertEquals($this->getProperty($controller, 'comment'), $comment);
    }

    // Has the specified nested resource_class when using '/' for namespace
    public function testHasSpecifiedNestedResourceClassWithSlashedNamespace()
    {
        // namespace Admin;
        // class Dashboard {}
        $dashboardAttributes = ['id' => 2];
        $dashboard = $this->buildModel('Admin\Dashboard', $dashboardAttributes);

        $this->authority->allow('index', 'admin/dashboard');

        $this->params = array_merge(
            $this->params,
            ['controller' => 'admin/dashboard', 'action' => 'index']
        );

        $resource = new Efficiently\AuthorityController\ControllerResource($this->controller, ['authorize' => true]);

        $this->assertEquals($this->invokeMethod($resource, 'getResourceClass'), 'Admin\Dashboard');
    }

    // Should build a new resource with hash if params[:id] is not specified
    public function testBuildNewResourceWithAssociativeArrayIfParamIdIsNotSpecified()
    {
        $this->buildModel('Project');
        $this->params = array_merge(
            $this->params,
            ['action' => 'store', 'project' => ['name' => 'foobar']]
        );

        $resource = new Efficiently\AuthorityController\ControllerResource($this->controller);
        $resource->loadResource();

        $this->assertEquals($this->getProperty($this->controller, 'project')->name, "foobar");
    }

    // Should build a new resource for namespaced model with hash if params[:id] is not specified
    public function testBuildNewResourceNamespacedModelWithAssociativeArrayIfParamIdIsNotSpecified()
    {
        $this->buildModel('\Sub\Project');
        $this->params = array_merge(
            $this->params,
            ['action' => 'store', 'sub_project' => ['name' => 'foobar']]
        );

        $resource = new Efficiently\AuthorityController\ControllerResource($this->controller, ['class' => '\Sub\Project']);
        $resource->loadResource();

        $this->assertEquals($this->getProperty($this->controller, 'project')->name, "foobar");
    }

    // Should build a new resource for namespaced controller and namespaced model with hash if params[:id] is not specified
    public function testBuildNewResourceForNamespacedControllerAndNamespacedModelWithAssociativeArrayIfParamIdIsNotSpecified()
    {
        $this->buildModel('Project');
        $this->params = array_merge(
            $this->params,
            ['controller' => 'Admin\SubProjectsController', 'action' => 'store', 'sub_project' => ['name' => 'foobar']]
        );

        $resource = new Efficiently\AuthorityController\ControllerResource($this->controller, ['class' => 'Project']);
        $resource->loadResource();

        $this->assertEquals($this->getProperty($this->controller, 'subProject')->name, "foobar");
    }

    // Should build a collection when on index action
    public function testBuildCollectionWhenOnIndexAction()
    {
        $project = $this->mock('Project');
        $project->shouldReceive('get')->once()->andReturn("found_projects");

        $this->params['action'] = "index";

        $resource = new Efficiently\AuthorityController\ControllerResource($this->controller, 'project');
        $resource->loadResource();

        $this->assertNull($this->getProperty($this->controller, 'project'));
        $this->assertEquals($this->getProperty($this->controller, 'projects'), "found_projects");
    }

    // Should not use load collection when defining Authority rules through a Closure
    public function testShouldLoadCollectionResourceWhenDefiningAuthorityRulesThroughClosure()
    {
        $project = $this->mock('Project');
        $project->shouldReceive('get')->never()->andReturn("found_projects");

        $this->params['action'] = "index";

        $this->authority->allow('read', 'Project', function ($self, $p) {
            return false;
        });

        $resource = new Efficiently\AuthorityController\ControllerResource($this->controller);
        $resource->loadResource();

        $this->assertNull($this->getProperty($this->controller, 'project'));
        $this->assertFalse(property_exists($this->controller, 'projects'));
    }

    // Should not call Closure when only class name is passed, only return true
    public function testShouldNotCallClosureWhenOnlyClassNameIsPassedOnlyReturnTrue()
    {
        $blockCalled = false;
        $this->authority->allow('preview', 'all', function ($self, $object) use (&$blockCalled) {
            return $blockCalled = true;
        });
        $this->assertTrue($this->authority->can('preview', 'Project'));
        $this->assertFalse($blockCalled);
    }

    // Should call Closure when an instance variable is passed
    public function testShouldCallClosureWhenAnInstanceVariableIsPassed()
    {
        $blockCalled = false;
        $this->authority->allow('preview', 'all', function ($self, $object) use (&$blockCalled) {
            $this->assertInstanceOf('stdClass', $object);
            return $blockCalled = true;
        });
        $this->assertTrue($this->authority->can('preview', new stdClass));
        $this->assertTrue($blockCalled);
    }

    /**
     * Should not authorize single resource in collection action
     *
     * @expectedException Efficiently\AuthorityController\Exceptions\AccessDenied
     */
    public function testShouldNotAuthorizeSingleResourceInCollectionAction()
    {
        $this->params['action'] = "index";
        $this->setProperty($this->controller, 'project', 'some_project');

        $this->controller->shouldReceive('authorize')->once()->with('index', 'Project')
            ->andThrow('Efficiently\AuthorityController\Exceptions\AccessDenied');

        $resource = new Efficiently\AuthorityController\ControllerResource($this->controller);
        $resource->authorizeResource();
    }

    /**
     * Should authorize parent resource in collection action
     * @expectedException Efficiently\AuthorityController\Exceptions\AccessDenied
     */
    public function testShouldAuthorizeParentResourceInCollectionAction()
    {
        $this->params['action'] = "index";
        $this->setProperty($this->controller, 'category', 'some_category');

        $this->controller->shouldReceive('authorize')->once()->with('show', 'some_category')
            ->andThrow('Efficiently\AuthorityController\Exceptions\AccessDenied');

        $resource = new Efficiently\AuthorityController\ControllerResource($this->controller, 'category', ['parent' => true]);
        $resource->authorizeResource();
    }

    /**
     * Should perform authorization using controller action and loaded model
     * @expectedException Efficiently\AuthorityController\Exceptions\AccessDenied
     */
    public function testShouldPerformAuthorizationUsingControllerActionAndLoadedModel()
    {
        $this->params = array_merge($this->params, array_merge(['action' => 'show', 'id' => '123']));
        $this->setProperty($this->controller, 'project', 'some_project');

        $this->controller->shouldReceive('authorize')->once()->with('show', 'some_project')
            ->andThrow('Efficiently\AuthorityController\Exceptions\AccessDenied');

        $resource = new Efficiently\AuthorityController\ControllerResource($this->controller);
        $resource->authorizeResource();
    }

    /**
     * Should perform authorization using controller action and non loaded model
     * @expectedException Efficiently\AuthorityController\Exceptions\AccessDenied
     */
    public function testShouldPerformAuthorizationUsingControllerActionAndNonLoadedModel()
    {
        $this->params = array_merge($this->params, array_merge(['action' => 'show', 'id' => '123']));

        $this->controller->shouldReceive('authorize')->once()->with('show', 'Project')
            ->andThrow('Efficiently\AuthorityController\Exceptions\AccessDenied');

        $resource = new Efficiently\AuthorityController\ControllerResource($this->controller);
        $resource->authorizeResource();
    }

    // Should call loadResource and authorizeResource for loadAndAuthorizeResource
    public function testShouldCallLoadResourceAndAuthorizeResourceForLoadAndAuthorizeResource()
    {
        $this->params = array_merge($this->params, array_merge(['action' => 'show', 'id' => '123']));

        $className = "Efficiently\\AuthorityController\\ControllerResource";
        $mock = m::mock($className)->makePartial();
        App::instance($className, $mock);
        $resource = App::make($className, [$this->controller]);

        $resource->shouldReceive('loadResource')->once();
        $resource->shouldReceive('authorizeResource')->once();
        $resource->loadAndAuthorizeResource();
    }

    // Should not build a single resource when on custom collection action even with id
    public function testShouldNotBuildASingleResourceWhenOnCustomCollectionActionEvenWithId()
    {
        $project = $this->mock('Project');
        $project->shouldReceive('get')->once()->andReturn(new Illuminate\Database\Eloquent\Collection);

        $this->params = array_merge($this->params, array_merge(['action' => 'sort', 'id' => '123']));

        $resource = new Efficiently\AuthorityController\ControllerResource($this->controller, ['collection' => ['sort', 'list']]);
        $resource->loadResource();

        $this->assertNull($this->getProperty($this->controller, 'project'));
    }

    // // Should load a collection resource when on custom action with no id param
    public function testShouldLoadACollectionResourceWhenOnCustomActionWithNoIdParam()
    {
        $project = $this->mock('Project');
        $project->shouldReceive('get')->once()->andReturn("found_projects");

        $this->params['action'] = "sort";

        $resource = new Efficiently\AuthorityController\ControllerResource($this->controller);
        $resource->loadResource();

        $this->assertNull($this->getProperty($this->controller, 'project'));
        $this->assertEquals($this->getProperty($this->controller, 'projects'), 'found_projects');
    }

    // Should build a resource when on custom create action even when $this->params['id'] exists
    public function testShouldBuildAResourceWhenOnCustomCreateActionEvenWhenParamsIdExists()
    {
        $this->params = array_merge($this->params, array_merge(['action' => 'build', 'id' => '123']));

        $project = $this->mock('Project');
        $project->shouldReceive('__toString')->once()->andReturn("some_project");

        $resource = new Efficiently\AuthorityController\ControllerResource($this->controller, ['create' => 'build']);
        $resource->loadResource();

        $this->assertEquals($this->getProperty($this->controller, 'project'), 'some_project');
    }

    // Should not try to load resource for other action if $this->params['id'] is undefined
    public function testShouldNotTryToLoadResourceForOtherActionIfParamsIdIsUndefined()
    {
        $project = $this->mock('Project');
        $project->shouldReceive('get')->once()->andReturn(new Illuminate\Database\Eloquent\Collection);

        $this->params['action'] = "list";

        $resource = new Efficiently\AuthorityController\ControllerResource($this->controller);
        $resource->loadResource();

        $this->assertNull($this->getProperty($this->controller, 'project'));
    }

    // Should be a parent resource when name is provided which doesn't match controller
    public function testShouldBeAParentResourceWhenNameIsProvidedWhichDoesntMatchController()
    {
        $resource = new Efficiently\AuthorityController\ControllerResource($this->controller, 'category');
        $this->assertTrue($resource->isParent());
    }

    // // Should not be a parent resource when name is provided which matches controller
    public function testShouldNotBeAParentResourceWhenNameIsProvidedWhichMatchesController()
    {
        $resource = new Efficiently\AuthorityController\ControllerResource($this->controller, 'project');
        $this->assertFalse($resource->isParent());
    }

    // // Should be parent if specified in options
    public function testShouldBeParentIfSpecifiedInOptions()
    {
        $resource = new Efficiently\AuthorityController\ControllerResource($this->controller, 'project', ['parent' => true]);
        $this->assertTrue($resource->isParent());
    }

    // // Should not be parent if specified in options
    public function testShouldNotBeParentIfSpecifiedInOptions()
    {
        $resource = new Efficiently\AuthorityController\ControllerResource($this->controller, 'category', ['parent' => false]);
        $this->assertFalse($resource->isParent());
    }

    // Should have the specified resource_class if 'name' is passed to loadResource()
    public function testShouldHaveTheSpecifiedResourceClassIfNameIsPassedToLoadResource()
    {
        // class Section {}
        $this->mock("Section");

        $resource = new Efficiently\AuthorityController\ControllerResource($this->controller, 'section');
        $this->assertEquals($this->invokeMethod($resource, 'getResourceClass'), 'Section');
    }

    // Should load parent resource through proper id parameter
    public function testShouldLoadParentResourceThroughProperIdParameter()
    {
        $projectAttributes = ['id' => 2];
        $project = $this->buildModel('Project', $projectAttributes);

        $this->params = array_merge(
            $this->params,
            ['controller' => 'categories', 'action' => 'index', 'project_id' => $project->id]
        );

        $resource = new Efficiently\AuthorityController\ControllerResource($this->controller, 'project');
        $resource->loadResource();

        $this->assertEquals($this->getProperty($this->controller, 'project'), $project);
    }

    // Should load resource through the association of another parent resource using instance variable
    public function testShouldLoadResourceThroughTheAssociationOfAnotherParentResourceUsingInstanceVariable()
    {
        $this->params = array_merge($this->params, array_merge(['action' => 'show', 'id' => '123']));

        $category = $this->mock('Category');
        $this->setProperty($this->controller, 'category', $category);

        $project = $this->mock('Project');
        $category->shouldReceive('projects->getModel')->once()->andReturn($project);
        $project->shouldReceive('where')->with('id', '123')->once()->andReturn($queryBuilder = m::mock());
        $queryBuilder->shouldReceive('firstOrFail')->once()->andReturn('some_project');

        $resource = new Efficiently\AuthorityController\ControllerResource($this->controller, ['through' => 'category']);
        $resource->loadResource();

        $this->assertEquals($this->getProperty($this->controller, 'project'), 'some_project');
    }

    // Should load resource through the custom association name
    public function testShouldLoadResourceThroughTheCustomAssociationName()
    {
        $this->params = array_merge($this->params, array_merge(['action' => 'show', 'id' => '123']));

        $category = $this->mock('Category');
        $this->setProperty($this->controller, 'category', $category);

        $project = $this->mock('Project');
        $category->shouldReceive('customProjects->getModel')->once()->andReturn($project);
        $project->shouldReceive('where')->with('id', '123')->once()->andReturn($queryBuilder = m::mock());
        $queryBuilder->shouldReceive('firstOrFail')->once()->andReturn('some_project');

        $resource = new Efficiently\AuthorityController\ControllerResource($this->controller, [
            'through' => 'category', 'throughAssociation' => 'customProjects'
        ]);
        $resource->loadResource();

        $this->assertEquals($this->getProperty($this->controller, 'project'), 'some_project');
    }

    // Should load resource through the association of another parent resource using getter method
    public function testShouldLoadResourceThroughTheAssociationOfAnotherParentResourceUsingGetterMethod()
    {
        $this->params = array_merge($this->params, array_merge(['action' => 'show', 'id' => '123']));

        $category = $this->mock('Category');
        $this->controller->shouldReceive('getCategory')->atLeast(1)->andReturn($category);

        $project = $this->mock('Project');
        $category->shouldReceive('projects->getModel')->once()->andReturn($project);
        $project->shouldReceive('where')->with('id', '123')->once()->andReturn($queryBuilder = m::mock());
        $queryBuilder->shouldReceive('firstOrFail')->once()->andReturn('some_project');

        $resource = new Efficiently\AuthorityController\ControllerResource($this->controller, ['through' => 'category']);
        $resource->loadResource();

        $this->assertEquals($this->getProperty($this->controller, 'project'), 'some_project');
    }

    // Should load resource through the association of another parent resource using method
    public function testShouldLoadResourceThroughTheAssociationOfAnotherParentResourceUsingMethod()
    {
        $this->params = array_merge($this->params, array_merge(['action' => 'show', 'id' => '123']));

        $category = $this->mock('Category');
        $this->controller->shouldReceive('category')->atLeast(1)->andReturn($category);

        $project = $this->mock('Project');
        $category->shouldReceive('projects->getModel')->once()->andReturn($project);
        $project->shouldReceive('where')->with('id', '123')->once()->andReturn($queryBuilder = m::mock());
        $queryBuilder->shouldReceive('firstOrFail')->once()->andReturn('some_project');

        $resource = new Efficiently\AuthorityController\ControllerResource($this->controller, ['through' => 'category']);
        $resource->loadResource();

        $this->assertEquals($this->getProperty($this->controller, 'project'), 'some_project');
    }

    // Should load resource through a custom method of the controller
    public function testShouldLoadResourceThroughCustomMethodOfTheController()
    {
        $this->params = array_merge($this->params, array_merge(['action' => 'show', 'id' => '123']));

        $this->controller->shouldReceive('getCurrentUser')->atLeast(1)->andReturn($this->user);

        $project = $this->mock('Project');
        $this->user->shouldReceive('projects->getModel')->once()->andReturn($project);
        $project->shouldReceive('where')->with('id', '123')->once()->andReturn($queryBuilder = m::mock());
        $queryBuilder->shouldReceive('firstOrFail')->once()->andReturn('some_project');

        $resource = new Efficiently\AuthorityController\ControllerResource($this->controller, ['through' => 'getCurrentUser']);
        $resource->loadResource();

        $this->assertEquals($this->getProperty($this->controller, 'project'), 'some_project');
    }

    // Should not load through parent resource if instance isn't loaded when shallow
    public function testShouldNotLoadThroughParentResourceIfInstanceIsntLoadedWhenShallow()
    {
        $projectAttributes = ['id' => 2];
        $project = $this->buildModel('Project', $projectAttributes);

        $this->params = array_merge($this->params, array_merge(['action' => 'show', 'id' => $project->id]));

        $resource = new Efficiently\AuthorityController\ControllerResource($this->controller, ['through' => 'category', 'shallow' => true]);
        $resource->loadResource();

        $this->assertEquals($this->getProperty($this->controller, 'project'), $project);
    }

    // Should raise AccessDenied when attempting to load resource through null
    public function testShouldRaiseAccessDeniedWhenAttemptingToLoadResourceThroughNull()
    {
        $projectAttributes = ['id' => 2];
        $project = $this->buildModel('Project', $projectAttributes);

        $this->params = array_merge($this->params, array_merge(['action' => 'show', 'id' => $project->id]));

        $resource = new Efficiently\AuthorityController\ControllerResource($this->controller, ['through' => 'category']);

        try {
            $resource->loadResource();
        } catch (Efficiently\AuthorityController\Exceptions\AccessDenied $exception) {
            $this->assertEquals($exception->action, 'show');
            $this->assertEquals($exception->subject, 'Project');

            $this->assertNull($this->getProperty($this->controller, 'project'));
            return; // see http://phpunit.de/manual/3.7/en/writing-tests-for-phpunit.html#writing-tests-for-phpunit.exceptions.examples.ExceptionTest4.php
        }
        $this->fail('An expected exception has not been raised.');
    }

    // Should authorize nested resource through parent association on index action
    public function testShouldAuthorizeNestedResourceThroughParentAssociationOnIndexAction()
    {
        $this->params = array_merge($this->params, array_merge(['action' => 'index']));

        $category = $this->mock('Category');
        $this->setProperty($this->controller, 'category', $category);

        $this->controller->shouldReceive('authorize')->once()->with('index', ['Project' => $category])->once()
            ->andThrow("Efficiently\AuthorityController\Exceptions\AccessDenied");

        $resource = new Efficiently\AuthorityController\ControllerResource($this->controller, ['through' => 'category']);

        $this->setExpectedException("Efficiently\AuthorityController\Exceptions\AccessDenied");
        $resource->authorizeResource();
    }

    // Should load through first matching if multiple are given
    public function testShouldLoadThroughFirstMatchingIfMultipleAreGiven()
    {
        $this->params = array_merge($this->params, array_merge(['action' => 'show', 'id' => '123']));

        $category = $this->mock('Category');
        $this->setProperty($this->controller, 'category', $category);

        $project = $this->mock('Project');
        $category->shouldReceive('projects->getModel')->once()->andReturn($project);
        $project->shouldReceive('where')->with('id', '123')->once()->andReturn($queryBuilder = m::mock());
        $queryBuilder->shouldReceive('firstOrFail')->once()->andReturn('some_project');

        $resource = new Efficiently\AuthorityController\ControllerResource($this->controller, ['through' => ['category', 'user']]);
        $resource->loadResource();

        $this->assertEquals($this->getProperty($this->controller, 'project'), 'some_project');
    }

    // Should find record through hasOne association with 'singleton' option without id param
    public function testShouldFindRecordThroughHasOneAssociationWithSingletonOptionWithoutIdParam()
    {
        $this->params = array_merge($this->params, array_merge(['action' => 'show', 'id' => null]));

        $category = $this->mock('Category');
        $this->setProperty($this->controller, 'category', $category);

        $category->shouldReceive('project')->once()->andReturn('some_project');

        $resource = new Efficiently\AuthorityController\ControllerResource($this->controller, ['through' => 'category', 'singleton' => true]);
        $resource->loadResource();

        $this->assertEquals($this->getProperty($this->controller, 'project'), 'some_project');
    }

    // Should not build record through hasOne association with 'singleton' option because it can cause it to delete it in the database
    public function testShouldNotBuildRecordThroughHasOneAssociationWithSingletonOptionBecauseItCanCauseItToDeleteItInTheDatabase()
    {
        $this->params = array_merge($this->params, array_merge(['action' => 'store', 'project' => ['name' => 'foobar']]));

        $category = $this->mock('Category');
        $this->setProperty($this->controller, 'category', $category);

        $project = $this->buildModel('Project');
        $project->shouldReceive('category->associate')->with($category)->once()->andReturnUsing(function () use ($category, $project) {
            $project->category = $category;
            return $project;
        });

        $resource = new Efficiently\AuthorityController\ControllerResource($this->controller, ['through' => 'category', 'singleton' => true]);
        $resource->loadResource();

        $this->assertEquals($this->getProperty($this->controller, 'project')->name, 'foobar');
        $this->assertEquals($this->getProperty($this->controller, 'project')->category, $category);
    }

    // Should find record through hasOne association with 'singleton' and 'shallow' options
    public function testShouldFindRecordThroughHasOneAssociationWithSingletonAndShallowOptions()
    {
        $projectAttributes = ['id' => 2];
        $project = $this->buildModel('Project', $projectAttributes);
        $this->params = array_merge($this->params, array_merge(['action' => 'show', 'id' => $project->id]));

        $resource = new Efficiently\AuthorityController\ControllerResource($this->controller, [
            'through' => 'category', 'singleton' => true, 'shallow' => true
        ]);
        $resource->loadResource();

        $this->assertEquals($this->getProperty($this->controller, 'project'), $project);
    }

    // Should build record through hasOne association with 'singleton' and 'shallow' options
    public function testShouldBuildRecordThroughHasOneAssociationWithSingletonAndShallowOptions()
    {
        $this->buildModel('Project');
        $this->params = array_merge($this->params, array_merge(['action' => 'store', 'project' => ['name' => 'foobar']]));

        $resource = new Efficiently\AuthorityController\ControllerResource($this->controller, [
            'through' => 'category', 'singleton' => true, 'shallow' => true
        ]);
        $resource->loadResource();

        $this->assertEquals($this->getProperty($this->controller, 'project')->name, 'foobar');
    }

    // Should only authorize 'show' action on parent resource
    public function testShouldOnlyAuthorizeShowActionOnParentResource()
    {
        $projectAttributes = ['id' => 2];
        $project = $this->buildModel('Project', $projectAttributes);

        $this->params = array_merge($this->params, array_merge(['action' => 'create', 'project_id' => $project->id]));

        $this->controller->shouldReceive('authorize')->with('show', $project)->once()
            ->andThrow('Efficiently\AuthorityController\Exceptions\AccessDenied');

        $resource = new Efficiently\AuthorityController\ControllerResource($this->controller, 'project', ['parent' => true]);

        $this->setExpectedException("Efficiently\AuthorityController\Exceptions\AccessDenied");
        $resource->loadAndAuthorizeResource();
    }

    // Should load the model using a custom class
    public function testShouldLoadTheModelUsingACustomClass()
    {
        $projectAttributes = ['id' => 2];
        $project = $this->buildModel('Project', $projectAttributes);

        $this->params = array_merge($this->params, array_merge(['action' => 'show', 'id' => $project->id]));

        $resource = new Efficiently\AuthorityController\ControllerResource($this->controller, ['class' => 'Project']);
        $resource->loadResource();

        $this->assertEquals($this->getProperty($this->controller, 'project'), $project);
    }

    // Should load the model using a custom namespaced class
    public function testShouldLoadTheModelUsingACustomNamespacedClass()
    {
        $projectAttributes = ['id' => 2];
        $project = $this->buildModel('\Sub\Project', $projectAttributes);

        $this->params = array_merge($this->params, array_merge(['action' => 'show', 'id' => $project->id]));

        $resource = new Efficiently\AuthorityController\ControllerResource($this->controller, ['class' => '\Sub\Project']);
        $resource->loadResource();

        $this->assertEquals($this->getProperty($this->controller, 'project'), $project);
    }

    // Should authorize based on resource name if class is false
    public function testShouldAuthorizeBasedOnResourceNameIfClassIsFalse()
    {
        $this->params = array_merge($this->params, ['action' => 'show', 'id' => '123']);

        $this->controller->shouldReceive('authorize')->once()->with('show', 'project')
            ->andThrow('Efficiently\AuthorityController\Exceptions\AccessDenied');

        $resource = new Efficiently\AuthorityController\ControllerResource($this->controller, ['class' =>  false]);

        $this->setExpectedException("Efficiently\AuthorityController\Exceptions\AccessDenied");
        $resource->authorizeResource();
    }

    // Should load and authorize using custom instance name
    public function testShouldLoadAndAuthorizeUsingCustomInstanceName()
    {
        $projectAttributes = ['id' => 2];
        $project = $this->buildModel('Project', $projectAttributes);

        $this->params = array_merge($this->params, array_merge(['action' => 'show', 'id' => $project->id]));

        $this->controller->shouldReceive('authorize')->once()->with('show', $project)
            ->andThrow('Efficiently\AuthorityController\Exceptions\AccessDenied');

        $resource = new Efficiently\AuthorityController\ControllerResource($this->controller, ['instanceName' => 'customProject']);

        try {
            $resource->loadAndAuthorizeResource();
        } catch (Efficiently\AuthorityController\Exceptions\AccessDenied $e) {
            $this->assertEquals($this->getProperty($this->controller, 'customProject'), $project);
            return; // see http://phpunit.de/manual/3.7/en/writing-tests-for-phpunit.html#writing-tests-for-phpunit.exceptions.examples.ExceptionTest4.php
        }
        $this->fail('An expected exception has not been raised.');
    }

    // Should load resource using custom ID param
    public function testShouldLoadResourceUsingCustomIDParam()
    {
        $projectAttributes = ['id' => 2];
        $project = $this->buildModel('Project', $projectAttributes);

        $project->shouldReceive('where')->with('the_project', $project->id)->once()->andReturn($queryBuilder = m::mock());
        $queryBuilder->shouldReceive('firstOrFail')->once()->andReturn($project);

        $this->params = array_merge($this->params, array_merge(['action' => 'show', 'the_project' => $project->id]));

        $resource = new Efficiently\AuthorityController\ControllerResource($this->controller, ['idParam' => 'the_project']);
        $resource->loadResource();

        $this->assertEquals($this->getProperty($this->controller, 'project'), $project);
    }

    // CVE-2012-5664
    // Should always convert id param to string
    public function testShouldAlwaysConvertIdParamToString()
    {
        $this->buildModel('Project');
        $this->params = array_merge($this->params, array_merge(['action' => 'show', 'the_project' => ['malicious' => 'I am']]));

        $resource = new Efficiently\AuthorityController\ControllerResource($this->controller, ['idParam' => 'the_project']);

        $this->assertInternalType('string', $this->invokeMethod($resource, 'getIdParam'));
    }

    // Should load resource using custom query where('attribute_name', $attribute);
    public function testShouldLoadResourceUsingCustomQuery()
    {
        $projectAttributes = ['id' => 2, 'name' => 'foo'];
        $project = $this->buildModel('Project', $projectAttributes);

        $project->shouldReceive('where')->with('id', $project->name)->once()->andReturn($queryBuilder = m::mock());
        $queryBuilder->shouldReceive('firstOrFail')->once()->andReturn($project);

        $this->params = array_merge($this->params, array_merge(['action' => 'show', 'id' => 'foo']));

        $resource = new Efficiently\AuthorityController\ControllerResource($this->controller, ['findBy' => 'name']);
        $resource->loadResource();

        $this->assertEquals($this->getProperty($this->controller, 'project'), $project);
    }

    // Should load resource using custom findByAttribute
    public function testShouldLoadResourceUsingCustomFindByAttribute()
    {
        $projectAttributes = ['id' => 2, 'name' => 'foo'];
        $project = $this->buildModel('Project', $projectAttributes);

        $project->shouldReceive('findByName')->with($project->name)->once()->andReturn($project);

        $this->params = array_merge($this->params, array_merge(['action' => 'show', 'id' => 'foo']));

        $resource = new Efficiently\AuthorityController\ControllerResource($this->controller, ['findBy' => 'name']);
        $resource->loadResource();

        $this->assertEquals($this->getProperty($this->controller, 'project'), $project);
    }

    // Should allow full find method to be passed into findBy option
    public function testShouldAllowFullFindMethodToBePassedIntoFindByOption()
    {
        $projectAttributes = ['id' => 2, 'name' => 'foo'];
        $project = $this->buildModel('Project', $projectAttributes);

        $project->shouldReceive('findByName')->with($project->name)->once()->andReturn($project);

        $this->params = array_merge($this->params, array_merge(['action' => 'show', 'id' => 'foo']));

        $resource = new Efficiently\AuthorityController\ControllerResource($this->controller, ['findBy' => 'findByName']);
        $resource->loadResource();

        $this->assertEquals($this->getProperty($this->controller, 'project'), $project);
    }

    protected function buildModel($modelName, $modelAttributes = [])
    {
        $modelAttributes = $modelAttributes;
        $mock = $this->mock($modelName);
        $model = $this->fillMock($mock, $modelAttributes);

        // $mock->shouldReceive('where->firstOrFail')->andReturn($model);
        $mock->shouldReceive('where')->with('id', array_get($modelAttributes, 'id'))->andReturn($queryBuilder = m::mock());
        $queryBuilder->shouldReceive('firstOrFail')->andReturn($model);

        $mock->shouldReceive('save')->andReturn(true);
        $mock->shouldReceive('fill')->with(m::type('array'))->andReturnUsing(function ($attributes) use ($mock) {
            $this->fillMock($mock, $attributes);
            return $mock;
        });

        $models = new Illuminate\Database\Eloquent\Collection();
        $models->add($model);
        $mock->shouldReceive('get')->andReturn($models);
        $mock->shouldReceive('all')->andReturn($models);

        return $model;
    }
}
