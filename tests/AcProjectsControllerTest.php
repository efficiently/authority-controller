<?php

class AcProjectsControllerTest extends AcTestCase
{
    use AuthorityControllerHelpers;

    protected $controllerName;
    protected $modelName;
    protected $modelAttributes;
    protected $resourceName;

    public function setUp()
    {
        parent::setUp();
        View::addLocation(__DIR__.'/fixtures/views');

        $this->controllerName = "AcProjectsController";
        $this->modelName = "AcProject";
        $this->modelAttributes = ['id' => 5, 'name' => 'Test AuthorityController package', 'priority' => 1];
        $this->resourceName = "ac_projects"; // str_plural(snake_case($this->modelName));

        Route::resource($this->resourceName, $this->controllerName);

        $this->userAttributes = ['id' => 1, 'username' => 'tortue', 'firstname' => 'Tortue',
        'lastname' => 'Torche', 'email' => 'tortue.torche@spam.me', 'password' => Hash::make('tortuetorche'),
         'displayname' => 'Tortue Torche'];

        $this->user = $this->getUserWithRole('admin');
        $this->authority = $this->getAuthority($this->user);

        $this->authority->allow('manage', $this->modelName);

    }

    public function testIndexActionAllows()
    {
        $actionName = 'index';

        $model = $this->buildModel();

        $this->assertCan($actionName, $this->modelName);

        $this->action('GET', $this->controllerName."@".$actionName);
        $this->assertViewHas('acProjects', $model->all());
    }

    /**
     * @expectedException Efficiently\AuthorityController\Exceptions\AccessDenied
     */
    public function testIndexActionDenies()
    {
        $actionName = 'index';

        $this->authority->deny($actionName, $this->modelName);

        $model = $this->buildModel();

        $this->assertCannot($actionName, $this->modelName);

        $this->action('GET', $this->controllerName."@".$actionName);
    }

    public function testCreateActionAllows()
    {
        $actionName = 'create';

        $modelAttributes = array_except($this->modelAttributes, 'id');
        $model = $this->buildModel($modelAttributes);

        $this->assertCan($actionName, $this->modelName);

        $this->action('GET', $this->controllerName."@".$actionName);
        $this->assertResponseOk();
    }

    /**
     * @expectedException Efficiently\AuthorityController\Exceptions\AccessDenied
     */
    public function testCreateActionDenies()
    {
        $actionName = 'create';

        $this->authority->deny($actionName, $this->modelName);

        $modelAttributes = array_except($this->modelAttributes, 'id');
        $model = $this->buildModel($modelAttributes);

        $this->assertCannot($actionName, $this->modelName);

        $this->action('GET', $this->controllerName."@".$actionName);
    }

    public function testStoreActionAllows()
    {
        $actionName = 'store';

        $modelAttributes = array_except($this->modelAttributes, 'id');
        $model = $this->buildModel($modelAttributes);

        $this->assertCan($actionName, $this->modelName);

        $this->action('POST', $this->controllerName."@".$actionName, [], $modelAttributes);
        $this->assertRedirectedToRoute($this->resourceName.'.index');
    }

    /**
     * @expectedException Efficiently\AuthorityController\Exceptions\AccessDenied
     */
    public function testStoreActionDenies()
    {
        $actionName = 'store';

        $this->authority->deny($actionName, $this->modelName);

        $modelAttributes = array_except($this->modelAttributes, 'id');
        $model = $this->buildModel($modelAttributes);

        $this->assertCannot($actionName, $this->modelName);

        $this->action('POST', $this->controllerName."@".$actionName, [], $modelAttributes);
    }

    public function testShowActionAllows()
    {
        $actionName = 'show';

        $model = $this->buildModel();

        $this->assertCan($actionName, $this->modelName);

        $response = $this->action('GET', $this->controllerName."@".$actionName, [$model->id]);
        $this->assertViewHas('acProject');
        $view = $response->original;
        $this->assertEquals($view->acProject->id, 5);
    }

    /**
     * @expectedException Efficiently\AuthorityController\Exceptions\AccessDenied
     */
    public function testShowActionDenies()
    {
        $actionName = 'show';

        $this->authority->deny($actionName, $this->modelName);

        $model = $this->buildModel();

        $this->assertCannot($actionName, $this->modelName);

        $this->action('GET', $this->controllerName."@".$actionName, [$model->id]);
    }

    public function testEditActionAllows()
    {
        $actionName = 'edit';

        $model = $this->buildModel();

        $this->assertCan($actionName, $this->modelName);

        $response = $this->action('GET', $this->controllerName."@".$actionName, [$model->id]);
        $this->assertViewHas('acProject');
        $view = $response->original;
        $this->assertEquals($view->acProject->id, 5);
    }

    /**
     * @expectedException Efficiently\AuthorityController\Exceptions\AccessDenied
     */
    public function testEditActionDenies()
    {
        $actionName = 'edit';

        $this->authority->deny($actionName, $this->modelName);

        $model = $this->buildModel();

        $this->assertCannot($actionName, $this->modelName);

        $this->action('GET', $this->controllerName."@".$actionName, [$model->id]);
    }

    public function testUpdateActionAllows()
    {
        $actionName = 'update';

        $modelAttributes = $this->modelAttributes;
        $model = $this->buildModel($modelAttributes);

        $this->assertCan($actionName, $this->modelName);

        $this->action('PATCH', $this->controllerName."@".$actionName, [$model->id], $modelAttributes);
        $this->assertRedirectedToRoute($this->resourceName.'.show', 5);
    }

    /**
     * @expectedException Efficiently\AuthorityController\Exceptions\AccessDenied
     */
    public function testUpdateActionDenies()
    {
        $actionName = 'update';

        $this->authority->deny($actionName, $this->modelName);

        $modelAttributes = $this->modelAttributes;
        $model = $this->buildModel($modelAttributes);

        $this->assertCannot($actionName, $this->modelName);

        $this->action('PATCH', $this->controllerName."@".$actionName, [$model->id], $modelAttributes);
    }

    public function testDestroyActionAllows()
    {
        $actionName = 'destroy';

        $model = $this->buildModel();

        $this->assertCan($actionName, $this->modelName);

        $this->action('DELETE', $this->controllerName."@".$actionName, [$model->id]);
        $this->assertRedirectedToRoute($this->resourceName.'.index');
    }

    /**
     * @expectedException Efficiently\AuthorityController\Exceptions\AccessDenied
     */
    public function testDestroyActionDenies()
    {
        $actionName = 'destroy';

        $this->authority->deny($actionName, $this->modelName);

        $model = $this->buildModel();

        $this->assertCannot($actionName, $this->modelName);

        $this->action('DELETE', $this->controllerName."@".$actionName, [$model->id]);
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
