<?php

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as Controller;
use Illuminate\Foundation\Validation\ValidatesRequests;

class AcBaseController extends Controller
{
    use DispatchesJobs, ValidatesRequests;
    use Efficiently\AuthorityController\ControllerAdditions;

    /**
     * Setup the layout used by the controller.
     *
     * @return void
     */
    protected function setupLayout()
    {
        if (! is_null($this->layout)) {
            $this->layout = view($this->layout);
        }
    }
}
