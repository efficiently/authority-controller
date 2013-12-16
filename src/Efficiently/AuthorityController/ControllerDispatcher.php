<?php namespace Efficiently\AuthorityController;

use Illuminate\Routing\ControllerDispatcher as IllumninateControllerDispatcher;
use Illuminate\Routing\Route as IlluminateRoute;
use Illuminate\Http\Request as IlluminateRequest;
use Params;

class ControllerDispatcher extends IllumninateControllerDispatcher
{
    /**
     * Dispatch a request to a given controller and method.
     *
     * @param  \Illuminate\Routing\Route  $route
     * @param  \Illuminate\Http\Request   $request
     * @param  string  $controller
     * @param  string  $method
     * @return mixed
     */
    public function dispatch(IlluminateRoute $route, IlluminateRequest $request, $controller, $method)
    {
        // First we will make an instance of this controller via the IoC container instance
        // so that we can call the methods on it. We will also apply any "after" filters
        // to the route so that they will be run by the routers after this processing.
        $instance = $this->makeController($controller);
        Params::fillController($instance);

        $this->assignAfter($instance, $route, $request, $method);

        $response = $this->before($instance, $route, $request, $method);

        // If no before filters returned a response we'll call the method on the controller
        // to get the response to be returned to the router. We will then return it back
        // out for processing by this router and the after filters can be called then.
        if (is_null($response)) {
            $response = $this->call($instance, $route, $method);
        }

        return $response;
        // return parent::dispatch($route, $request, $controller, $method);
    }
}
