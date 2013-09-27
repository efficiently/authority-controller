<?php namespace Efficiently\AuthorityController;

use App;
use Input;
use ReflectionProperty;

// TODO: Move this class in its own Laravel package
class Parameters
{
    protected $params = [];

    public function fillController($controller)
    {
        $router = App::make('router');
        $controllerClass = \Authority::getClass($controller);
        $paramsFilterName = "controller.parameters.".$controllerClass;

        if (! $router->getFilter($paramsFilterName)) {
            $router->filter($paramsFilterName, function() use($controller, $router) {
                $currentRoute = $router->getCurrentRoute();
                $resourceParams = [];
                list($resourceParams['controller'], $resourceParams['action']) = explode('@', $currentRoute->getAction());
                $resourceController = $resourceParams['controller'];
                $resourceId = ControllerResource::getNameByController($resourceController);
                $params = [$resourceId => Input::except('_method', '_token') ];
                $routeParams = $currentRoute->getParametersWithoutDefaults();

                // In Laravel, unlike Rails, by default 'id' parameter of a 'Product' resource is 'products'
                // And 'shop_id' parameter of a 'Shop' parent resource is 'shops'
                // So we need to reaffect correct parameter name at ControllerResource initialization.
                // TODO: Handle the situation when Laravel Router doesn't provide 'products' or 'shops' params
                $routeParamsParsed = [];
                $keysToRemove = [];
                if ( str_plural($resourceId) === last(array_keys($routeParams)) ) {
                    $routeParamsParsed['id'] = array_pop($routeParams);
                }
                foreach ($routeParams as $key => $routeParam) {
                    $routeParamsParsed[str_singular($key).'_id'] = $routeParam;
                    $keysToRemove[] = $key;
                }
                $routeParams = array_except($routeParams, $keysToRemove);

                // TODO: Escape or sanitize these params. Maybe an external filter/listener(event) can do the job.
                $this->params = array_filter( array_merge( $params, $routeParams, $routeParamsParsed, $resourceParams ) );

                $reflection = new ReflectionProperty($controller, 'params');
                $reflection->setAccessible(true);
                $reflection->setValue($controller, $this->params);
            });

            $controller->paramsBeforeFilter($paramsFilterName);
        }
    }

    protected function add($key, $value)
    {
        return $this->params[$key] = $value;
    }

    public function all()
    {
        return $this->params;
    }

    public function get($key, $default = null)
    {
        return $this->has($key) ? $this->params[$key] : $default;
    }

    public function has($key)
    {
        return array_key_exists($key, $this->params);
    }

    public function only($args = null)
    {
        $args = is_array( $args ) ? $args : func_get_args();
        return array_only($this->params, $args);
    }

    public function except($args = null)
    {
        $args = is_array( $args ) ? $args : func_get_args();
        return array_except($this->params, $args);
    }
}
