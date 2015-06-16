<?php

class AcTasksControllerTest extends AcTestCase
{
    use AuthorityControllerHelpers;

    protected $parentControllerName;
    protected $parentModelName;
    protected $parentModelAttributes;
    protected $parentResourceName;

    protected $controllerName;
    protected $modelName;
    protected $modelAttributes;
    protected $resourceName;

    public function setUp()
    {
        parent::setUp();
        View::addLocation(__DIR__.'/fixtures/views');

        $this->parentControllerName = "AcProjectsController";
        $this->parentModelName = "AcProject";
        $this->parentModelAttributes = ['id' => 3, 'name' => 'Test AuthorityController package', 'priority' => 1];
        $this->parentResourceName = "ac_projects";

        $this->controllerName = "AcTasksController";
        $this->modelName = "AcTask";
        $this->modelAttributes = ['id' => 5, 'name' => 'Write more tests!', 'ac_project_id' => 3];
        $this->resourceName = "ac_projects.ac_tasks";

        Route::resource($this->parentResourceName, $this->parentControllerName);
        Route::resource($this->resourceName, $this->controllerName);

        $this->userAttributes = ['id' => 1, 'username' => 'tortue', 'firstname' => 'Tortue',
        'lastname' => 'Torche', 'email' => 'tortue.torche@spam.me', 'password' => Hash::make('tortuetorche'),
         'displayname' => 'Tortue Torche'];

        $this->user = $this->getUserWithRole('admin');
        $this->authority = $this->getAuthority($this->user);

        $this->authority->allow('manage', $this->parentModelName);
        $this->authority->allow('manage', $this->modelName);

    }

    public function testIndexActionAllows()
    {
        $actionName = 'index';

        $parentModel = $this->buildParentModel();

        $model = $this->buildModel();

        $this->assertCan($actionName, $this->modelName);
        $this->action('GET', $this->controllerName."@".$actionName, [$parentModel->id]);

        $this->assertViewHas('acTasks', $model->all());
    }

    /**
     * @expectedException Efficiently\AuthorityController\Exceptions\AccessDenied
     */
    public function testIndexActionDenies()
    {
        $actionName = 'index';

        $this->authority->deny($actionName, $this->modelName);

        $parentModel = $this->buildParentModel();

        $model = $this->buildModel();

        $this->assertCannot($actionName, $this->modelName);
        $this->action('GET', $this->controllerName."@".$actionName, [$parentModel->id]);
    }

    public function testCreateActionAllows()
    {
        $actionName = 'create';

        $parentModel = $this->buildParentModel();

        $modelAttributes = array_except($this->modelAttributes, 'id');
        $model = $this->buildModel($modelAttributes);

        $this->assertCan($actionName, $this->modelName);

        $this->action('GET', $this->controllerName."@".$actionName, [$parentModel->id]);

        $this->assertResponseOk();
    }

    /**
     * @expectedException Efficiently\AuthorityController\Exceptions\AccessDenied
     */
    public function testCreateActionDenies()
    {
        $actionName = 'create';

        $this->authority->deny($actionName, $this->modelName);

        $parentModel = $this->buildParentModel();

        $modelAttributes = array_except($this->modelAttributes, 'id');
        $model = $this->buildModel($modelAttributes);

        $this->assertCannot($actionName, $this->modelName);

        $this->action('GET', $this->controllerName."@".$actionName, [$parentModel->id]);
    }

    public function testStoreActionAllows()
    {
        $actionName = 'store';

        $parentModel = $this->buildParentModel();

        $modelAttributes = array_except($this->modelAttributes, 'id');
        $model = $this->buildModel($modelAttributes);

        $this->assertCan($actionName, $this->modelName);

        $this->action('POST', $this->controllerName."@".$actionName, [$parentModel->id], $modelAttributes);
        $this->assertRedirectedToRoute($this->resourceName.'.index', $parentModel->id);
    }

    /**
     * @expectedException Efficiently\AuthorityController\Exceptions\AccessDenied
     */
    public function testStoreActionDenies()
    {
        $actionName = 'store';

        $this->authority->deny($actionName, $this->modelName);

        $parentModel = $this->buildParentModel();

        $modelAttributes = array_except($this->modelAttributes, 'id');
        $model = $this->buildModel($modelAttributes);

        $this->assertCannot($actionName, $this->modelName);

        $this->action('POST', $this->controllerName."@".$actionName, [$parentModel->id], $modelAttributes);
    }

    public function testShowActionAllows()
    {
        $actionName = 'show';

        $parentModel = $this->buildParentModel();

        $model = $this->buildModel();

        $this->assertCan($actionName, $this->modelName);

        $response = $this->action('GET', $this->controllerName."@".$actionName, [$parentModel->id, $model->id]);
        $this->assertViewHas('acTask');
        $view = $response->original;
        $this->assertEquals($view->acTask->ac_project_id, 3);
        $this->assertEquals($view->acTask->id, 5);
    }

    /**
     * @expectedException Efficiently\AuthorityController\Exceptions\AccessDenied
     */
    public function testShowActionDenies()
    {
        $actionName = 'show';

        $this->authority->deny($actionName, $this->modelName);

        $parentModel = $this->buildParentModel();

        $model = $this->buildModel();

        $this->assertCannot($actionName, $this->modelName);

        $this->action('GET', $this->controllerName."@".$actionName, [$parentModel->id, $model->id]);
    }

    public function testEditActionAllows()
    {
        $actionName = 'edit';

        $parentModel = $this->buildParentModel();

        $model = $this->buildModel();

        $this->assertCan($actionName, $this->modelName);

        $response = $this->action('GET', $this->controllerName."@".$actionName, [$parentModel->id, $model->id]);
        $this->assertViewHas('acTask');
        $view = $response->original;
        $this->assertEquals($view->acTask->ac_project_id, 3);
        $this->assertEquals($view->acTask->id, 5);
    }

    /**
     * @expectedException Efficiently\AuthorityController\Exceptions\AccessDenied
     */
    public function testEditActionDenies()
    {
        $actionName = 'edit';

        $this->authority->deny($actionName, $this->modelName);

        $parentModel = $this->buildParentModel();

        $model = $this->buildModel();

        $this->assertCannot($actionName, $this->modelName);

        $this->action('GET', $this->controllerName."@".$actionName, [$parentModel->id, $model->id]);
    }

    public function testUpdateActionAllows()
    {
        $actionName = 'update';

        $parentModel = $this->buildParentModel();

        $modelAttributes = $this->modelAttributes;
        $model = $this->buildModel($modelAttributes);

        $this->assertCan($actionName, $this->modelName);

        $this->action('PATCH', $this->controllerName."@".$actionName, [$parentModel->id, $model->id], $modelAttributes);
        $this->assertRedirectedToRoute($this->resourceName.'.show', [3, 5]);
    }

    /**
     * @expectedException Efficiently\AuthorityController\Exceptions\AccessDenied
     */
    public function testUpdateActionDenies()
    {
        $actionName = 'update';

        $this->authority->deny($actionName, $this->modelName);

        $parentModel = $this->buildParentModel();

        $modelAttributes = $this->modelAttributes;
        $model = $this->buildModel($modelAttributes);

        $this->assertCannot($actionName, $this->modelName);

        $this->action('PATCH', $this->controllerName."@".$actionName, [$parentModel->id, $model->id], $modelAttributes);
    }

    public function testDestroyActionAllows()
    {
        $actionName = 'destroy';

        $parentModel = $this->buildParentModel();

        $model = $this->buildModel();

        $this->assertCan($actionName, $this->modelName);

        $this->action('DELETE', $this->controllerName."@".$actionName, [$parentModel->id, $model->id]);
        $this->assertRedirectedToRoute($this->resourceName.'.index', $parentModel->id);
    }

    /**
     * @expectedException Efficiently\AuthorityController\Exceptions\AccessDenied
     */
    public function testDestroyActionDenies()
    {
        $actionName = 'destroy';

        $this->authority->deny($actionName, $this->modelName);

        $parentModel = $this->buildParentModel();

        $model = $this->buildModel();

        $this->assertCannot($actionName, $this->modelName);

        $this->action('DELETE', $this->controllerName."@".$actionName, [$parentModel->id, $model->id]);
    }

    protected function buildParentModel($parentModelAttributes = null)
    {
        $parentModelAttributes = $parentModelAttributes ?: $this->parentModelAttributes;
        $parentMock = $this->mock($this->parentModelName);
        $parentModel = $this->fillMock($parentMock, $parentModelAttributes);

        $parentMock->shouldReceive('where->firstOrFail')->once()->andReturn($parentModel);
        $associationName = str_plural(camel_case($this->modelName));
        $parentMock->shouldReceive($associationName.'->getModel')->/*once()->*/andReturn(App::make($this->modelName));

        return $parentModel;
    }

    protected function buildModel($modelAttributes = null)
    {
        $modelAttributes = $modelAttributes ?: $this->modelAttributes;
        $mock = $this->mock($this->modelName);
        $model = $this->fillMock($mock, $modelAttributes);

        $mock->shouldReceive('where->firstOrFail')->/*once()->*/andReturn($model);
        $mock->shouldReceive('save')->/*once()->*/andReturn(true);
        $models = new Illuminate\Database\Eloquent\Collection();
        $models->add($model);
        $mock->shouldReceive('get')->andReturn($models);
        $mock->shouldReceive('all')->andReturn($models);

        return $model;
    }
}
