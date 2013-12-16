<?php namespace Efficiently\AuthorityController;

use Illuminate\Routing\Router as IlluminateRouter;

class Router  extends IlluminateRouter
{
    /**
     * Get the controller dispatcher instance.
     *
     * @return \Efficiently\AuthorityController\ControllerDispatcher
     */
    public function getControllerDispatcher()
    {
        if (is_null($this->controllerDispatcher)) {
            $this->controllerDispatcher = new ControllerDispatcher($this, $this->container);
        }

        return $this->controllerDispatcher;
    }

}
