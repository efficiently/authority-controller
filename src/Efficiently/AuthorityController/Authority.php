<?php namespace Efficiently\AuthorityController;

use \Authority\Authority as OriginalAuthority;

class Authority extends OriginalAuthority
{
    protected $aliasedActions = [];

    /**
     * Authority constructor
     *
     * @param mixed $currentUser Current user in the application
     * @param mixed $dispatcher  Dispatcher used for firing events
     */
    public function __construct($currentUser, $dispatcher = null)
    {
        $this->initDefaultAliases();
        parent::__construct($currentUser, $dispatcher);
    }

    // Removes previously aliased actions including the defaults.
    public function clearAliasedActions()
    {
        $this->aliasedActions = [];
    }

    /**
     * Determine if current user can access the given action and resource
     *
     * @return boolean
     */
    public function can($action, $resource, $resourceValue = null)
    {
        if (is_object($resource)) {
            $resourceValue = $resource;
            $resource = get_classname($resourceValue);
        } elseif (is_array($resource)) {
            // Nested resources can be passed through an associative array, this way conditions which are
            // dependent upon the association will work when using a class.
            $resourceValue = head(array_values($resource));
            $resource = head(array_keys($resource));
        }

        // The conditional callback (Closure) is only evaluated when an actual instance object is present.
        // It is not evaluated when checking permissions on the class name (such as in the 'index' action).
        $skipConditions = false;
        if (is_string($resource) && ! is_object($resourceValue) && $this->hasCondition($action, $resource)) {
            $skipConditions = true;
        }

        $self = $this;
        $rules = $this->getRulesFor($action, $resource);

        if (! $rules->isEmpty()) {
            $allowed = array_reduce($rules->all(), function ($result, $rule) use ($self, $resourceValue, $skipConditions) {
                if ($skipConditions) {
                    return $rule->getBehavior(); // Short circuit
                } else {
                    if ($rule->isRestriction()) {
                        // 'deny' rules override prior rules.
                        $result = $result && $rule->isAllowed($self, $resourceValue);
                    } else {
                        // 'allow' rules do not override prior rules but instead are logically or'ed.
                        // Unlike Authority default behavior.
                        $result =  $result || $rule->isAllowed($self, $resourceValue);
                    }
                }

                return $result;
            }, false);
        } else {
            $allowed = false;
        }

        return $allowed;
    }

    public function authorize($action, $resource, $args = null)
    {
        $args = is_array($args) ? $args : array_slice(func_get_args(), 2);

        $message = null;
        $options = array_extract_options($args);
        if (is_array($options) && array_key_exists('message', $options)) {
            $message = $options['message'];
        } elseif (is_array($args) && array_key_exists(0, $args)) {
            list($message) = $args;
            unset($args[0]);
        }

        if ($this->cannot($action, $resource, $args)) {
            $resourceClass = $resource;
            if (is_object($resourceClass)) {
                $resourceClass = get_classname($resourceClass);
            } elseif (is_array($resourceClass)) {
                $resourceClass = head(array_values($resourceClass));
                if (is_object($resourceClass)) {
                    $resourceClass = get_classname($resourceClass);
                }
            }
            $message = $message ?: $this->getUnauthorizedMessage($action, $resourceClass);
            throw new Exceptions\AccessDenied($message, $action, $resourceClass);
        }

        return $resource;
    }

    /**
     * Define rule(s) for a given action(s) and resource(s)
     *
     * @param boolean       $allow True if privilege, false if restriction
     * @param string|array  $actions Action(s) for the rule(s)
     * @param mixed         $resources Resource(s) for the rule(s)
     * @param Closure|null  $condition Optional condition for the rule
     * @return array
     */
    public function addRule($allow, $actions, $resources, $condition = null)
    {
        $actions = (array) $actions;
        $resources = (array) $resources;
        $rules = [];
        foreach ($actions as $action) {
            foreach ($resources as $resource) {
                $rule = new Rule($allow, $action, $resource, $condition);
                $this->rules->add($rules[] = $rule);
            }
        }

        return $rules;
    }

    // alias of addRule()
    public function addRules($allow, $actions, $resources, $condition = null)
    {
        return $this->addRule($allow, $actions, $resources, $condition);
    }

    /**
     * Define new alias for an action
     *
     *   $this->$authority->addAlias('read', ['index', 'show']);
     *   $this->$authority->addAlias('create', 'new');
     *   $this->$authority->addAlias('update', 'edit');
     *
     * This way one can use $params['action'] in the controller to determine the permission.
     *
     * @param string $name Name of action
     * @param string|array $actions Action(s) that $name aliases
     * @return RuleAlias
     */
    public function addAlias($name, $actions)
    {
        $actions = (array) $actions;
        $this->addAliasAction($name, $actions);
        parent::addAlias($name, $this->getExpandActions($actions));
    }

    /**
     * Returns all rules relevant to the given action and resource
     *
     * @return RuleRepository
     */
    public function getRulesFor($action, $resource)
    {
        $aliases = array_merge((array) $action, $this->getAliasesForAction($action));
        return $this->rules->getRelevantRules($aliases, $resource);
    }

    /**
     * @param  string|array $action Name of action(s)
     * @param  string|object $resource Resource for the rule
     * @return boolean
     */
    public function hasCondition($action, $resource)
    {
        $relevantConditions = $this->getRelevantConditions($action, $resource);

        return ! empty($relevantConditions);
    }

    /**
     * @param  string|array $action Name of action(s)
     * @param  string|object $resource Resource for the rule
     * @return array
     */
    public function getRelevantConditions($action, $resource)
    {
        $rules = $this->getRulesFor($action, $resource)->getIterator()->getArrayCopy();

        $relevantConditions = array_filter($rules, function ($rule) {
            return $rule->onlyCondition();
        });

        return $relevantConditions;
    }

    /**
     * Returns an associative array of aliases.
     * The key is the target and the value is an array of actions aliasing the key.
     *
     * @return array
     */
    public function getAliases()
    {
        if (! $this->aliases) {
            $this->initDefaultAliases();
        }
        return parent::getAliases();
    }

    protected function addAliasAction($target, $actions)
    {
        $this->validateTarget($target);
        if (! array_key_exists($target, $this->getAliasedActions())) {
            $this->aliasedActions[$target] = [];
        }
        $this->aliasedActions[$target] = array_unique(array_merge($this->getAliasedActions()[$target], $actions));
    }

    // User shouldn't specify targets with names of real actions or it will cause Seg fault
    protected function validateTarget($target)
    {
        if (in_array($target, array_flatten(array_values($this->getAliasedActions())))) {
            throw new \Exception("You can't specify target ($target) as alias because it is real action name", 1);
        }
    }

    // Returns an associative array of aliased actions.
    // The key is the target and the value is an array of actions aliasing the key.
    protected function getAliasedActions()
    {
        if (! $this->aliasedActions) {
            $this->aliasedActions = $this->getDefaultAliasActions();
        }
        return $this->aliasedActions;
    }


    public function getUnauthorizedMessage($action, $subject)
    {
        $keys = $this->getUnauthorizedMessageKeys($action, $subject);
        $variables = ['action' => $action];
        $variables['subject'] = class_exists($subject) ? $subject : snake_case($subject, ' ');
        $transKey = null;
        foreach ($keys as $key) {
            if (\Lang::has('messages.unauthorized.'.$key)) {
                $transKey = "messages.unauthorized.".$key;
                break;
            }
        }
        $message = ac_trans($transKey, $variables);
        return $message ?: null;
    }

    protected function getUnauthorizedMessageKeys($action, $subject)
    {
        $subject = snake_case(class_exists($subject) ? $subject : $subject);
        return array_flatten(array_map(function ($trySubject) use ($action) {
            return array_map(function ($tryAction) use ($trySubject, $action) {
                return "$tryAction.$trySubject";
            }, array_flatten([$action, $this->getAliasesForAction($action), 'manage']));
        }, [$subject, 'all']));
    }

    // Accepts an array of actions and returns an array of actions which match.
    // This should be called before "matches" and other checking methods since they
    // rely on the actions to be expanded.
    public function getExpandActions($actions)
    {
        $actions = (array) $actions;
        return array_flatten(array_map(function ($action) use ($actions) {
            return array_key_exists($action, $this->getAliasedActions()) ? [$action, $this->getExpandActions($this->getAliasedActions()[$action])] : $action;
        }, $actions));
    }

    // Given an action, it will try to find all of the actions which are aliased to it.
    // This does the opposite kind of lookup as 'getExpandActions()'.
    public function getAliasesForAction($action)
    {
        $action = (array) $action;
        $results = [];
        foreach ($this->getAliasedActions() as $aliasedAction => $actions) {
            if (array_intersect($action, $actions)) {
                $results = array_merge($results, parent::getAliasesForAction($aliasedAction));
            }
        }

        return array_unique($results);
    }

    protected function getDefaultAliasActions()
    {
        return [
            'read'   => ['index', 'show'],
            'create' => ['new', 'store'],
            'update' => ['edit'],
            'delete' => ['destroy'],
            //'manage' => ['any actions'],
        ];
    }

    protected function initDefaultAliases()
    {
        foreach ($this->getDefaultAliasActions() as $name => $actions) {
            $this->addAlias($name, $actions);
        }
    }
}
