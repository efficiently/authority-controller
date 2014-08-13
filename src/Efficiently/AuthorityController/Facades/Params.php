<?php namespace Efficiently\AuthorityController\Facades;

use Illuminate\Support\Facades\Facade;

class Params extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'parameters';
    }
}
