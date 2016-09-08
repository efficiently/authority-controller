<?php

use Orchestra\Testbench\Exceptions\Handler as OrchestraHandler;

class AcExceptionsHandler extends OrchestraHandler
{
    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Exception  $e
     *
     * @return void
     */
    public function report(Exception $e)
    {
        if (is_a($e, 'Efficiently\AuthorityController\Exceptions\AccessDenied')) {
            throw $e;
        }
        parent::report($e);
    }
}
