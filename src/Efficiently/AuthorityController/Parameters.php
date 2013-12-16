<?php namespace Efficiently\AuthorityController;

use App;
use Input;

// TODO: Move this class in its own Laravel package
class Parameters
{
    protected $params = [];

    /**
     * Fill the $params property of the given Controller
     *
     * @param  \Efficiently\AuthorityController\ControllerDispatcher $controller
     */
    public function fillController($controller)
    {
        $router = App::make('router');
        $controllerClass = get_classname($controller);
        $paramsFilterPrefix = "router.filter: ";
        $paramsFilterName = "controller.parameters.".$controllerClass;

        $events = get_property($router, 'events');
        if (! $events->hasListeners($paramsFilterPrefix.$paramsFilterName)) {
            $router->filter($paramsFilterName, function() use($controller, $router) {
                $currentRoute = $router->current();
                $resourceParams = [];
                list($resourceParams['controller'], $resourceParams['action']) = explode('@', $router->currentRouteAction());
                $resourceParams['controller'] = $this->normalizeControllerName($resourceParams['controller']);

                $resourceId = str_singular($resourceParams['controller']);
                if (Input::has($resourceId)) {
                    $params = Input::all();
                } else {
                    $specialInputKeys = $this->specialInputKeys();
                    $params = [$resourceId => Input::except($specialInputKeys)] + Input::only($specialInputKeys);
                }
                $routeParams = $currentRoute->parametersWithoutNulls();

                // In Laravel, unlike Rails, by default 'id' parameter of a 'Product' resource is 'products'
                // And 'shop_id' parameter of a 'Shop' parent resource is 'shops'
                // So we need to reaffect correct parameter name before any controller's actions or filters.
                $routeParamsParsed = [];
                $keysToRemove = [];
                if ($resourceId === str_singular(last(array_keys($routeParams)))) {
                    $id = last($routeParams);
                    if(is_string($id) || is_numeric($id)) {
                        $routeParamsParsed['id'] = array_pop($routeParams);
                    }
                }

                foreach ($routeParams as $key => $parentId) {
                    if(is_string($parentId) || is_numeric($parentId)) {
                        $routeParamsParsed[str_singular($key).'_id'] = $parentId;
                        $keysToRemove[] = $key;
                    }
                }
                $routeParams = array_except($routeParams, $keysToRemove);

                /**
                 * You can escape or purify these parameters. For example:
                 *
                 *   class ProductsController extends \BaseController
                 *   {
                 *       public function __construct()
                 *       {
                 *           $self = $this;
                 *           $this->beforeFilter(function () use($self) {
                 *               if(array_get($self->params, 'product')) {
                 *                   $productParams = $this->yourPurifyOrEscapeMethod('product');
                 *                   $self->params['product'] = $productParams;
                 *               }
                 *           });
                 *       }
                 *   }
                 *
                 */
                $this->params = array_filter(array_merge($params, $routeParams, $routeParamsParsed, $resourceParams));

                if (property_exists($controller, 'params')) {
                    set_property($controller, 'params', $this->params);
                } else {
                    $controller->params = $this->params;
                }
            });

            $controller->paramsBeforeFilter($paramsFilterName);
        }
    }

    /**
     * Get an item from the parameters.
     *
     * @param  string $key
     * @param  mixed  $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        return array_get($this->params, $key, $default);
    }

    /**
     * Determine if the request contains a given parameter item.
     *
     * @param  string|array  $key
     * @return bool
     */
    public function has($key)
    {
        return !!array_get($this->params, $key);
    }

    /**
     * Get all of the parameters for the request.
     *
     * @return array
     */
    public function all()
    {
        return $this->params;
    }

    /**
     * Get a subset of the items from the parameters.
     *
     * @param  array  $keys
     * @return array
     */
    public function only($keys = null)
    {
        $keys = is_array( $keys ) ? $keys : func_get_args();
        return array_only($this->params, $keys);
    }

    /**
     * Get all of the input except for a specified array of items.
     *
     * @param  array  $keys
     * @return array
     */
    public function except($keys = null)
    {
        $keys = is_array( $keys ) ? $keys : func_get_args();
        return array_except($this->params, $keys);
    }

    /**
     * Adds an item to the parameters.
     *
     * @param string $key    Key to add value to.
     * @param mixed  $value  New data.
     *
     * @return mixed
     */
    public function add($key, $value)
    {
        return array_set($this->params, $key, $value);
    }

    /**
     * Returns all inputs keys who starts with an underscore character (<code>_</code>).
     * For exmaple '_method' and '_token' inputs
     *
     * @param  array $inputKeyss
     * @return array
     */
    protected function specialInputKeys($inputKeys = [])
    {
        $inputKeys = $inputKeys ?: array_keys(Input::all());
        return array_filter($inputKeys, function($value) { return is_string($value) ? starts_with($value, '_') : false; });
    }

    /**
     * @param  string $controller
     * @return string
     */
    protected function normalizeControllerName($controller)
    {
        $name = preg_replace("/^(.+)Controller$/", "$1", $controller);
        return str_plural(snake_case(class_basename($name)));
    }

}
