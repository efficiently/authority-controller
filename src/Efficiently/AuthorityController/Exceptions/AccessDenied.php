<?php namespace Efficiently\AuthorityController\Exceptions;

/**
 * This error is raised when a user isn't allowed to access a given controller action.
 * This usually happens within a call to ControllerAdditions::authorize() but can be
 * raised manually.
 *
 *   throw new Efficiently\AuthorityController\Exceptions\AccessDenied('Not authorized!', 'read', 'Product');
 *
 *   $exception->getMessage(); //-> "Not authorized!"
 *   $exception->action; //-> 'read'
 *   $exception->subject; //-> 'Product'
 */
class AccessDenied extends \Exception
{
    public $action;
    public $subject;
    public $defaultMessage;

    public function __construct($message = null, $action = null, $subject = null, $code = 0, \Exception $previous = null)
    {
        $this->action = $action;
        $this->subject = $subject;
        $this->defaultMessage = ac_trans("messages.unauthorized.default");
        $this->message = $message ?: $this->defaultMessage;

        parent::__construct($this->message, $code, $previous);
    }

    public function __toString()
    {
        return $this->message ?: $this->defaultMessage;
    }

    public function setDefaultMessage($value = null)
    {
        $this->defaultMessage = $value;
        $this->message = $this->message ?: $this->defaultMessage;
    }

    public function getDefaultMessage()
    {
        return $this->defaultMessage;
    }

    public function getAction()
    {
        return $this->action;
    }

    public function getSubject()
    {
        return $this->subject;
    }
}
