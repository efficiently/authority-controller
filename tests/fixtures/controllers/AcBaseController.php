<?php

use Illuminate\Routing\Controller;

class AcBaseController extends Controller
{
    use Efficiently\AuthorityController\ControllerAdditions;

    /**
     * Setup the layout used by the controller.
     *
     * @return void
     */
    protected function setupLayout()
    {
        if (! is_null($this->layout))
        {
            $this->layout = View::make($this->layout);
        }
    }

}
