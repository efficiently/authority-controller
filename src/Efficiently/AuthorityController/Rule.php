<?php namespace Efficiently\AuthorityController;

use \Authority\Rule as OriginalRule;

class Rule extends OriginalRule
{

    /**
     * Determine if current rule is relevant based on an action and resource
     *
     * @param string|array  $action Action in question
     * @param string|mixed  $resource Name of resource or instance of object
     * @return boolean
     */
    public function isRelevant($action, $resource)
    {
        // Nested resources can be passed through a associative array, this way conditions which are
        // dependent upon the association will work when using a class.
        $resource = is_array($resource) ? head(array_keys($resource)) : $resource;
        return parent::isRelevant($action, $resource);
    }

    /**
     * Determine if the instance's action matches the one passed in
     *
     * @param string|array $action Action in question
     * @return boolean
     */
    public function matchesAction($action)
    {
        $action = (array) $action;
        return $this->action === 'manage' || in_array($this->action, $action);
    }

    /**
     * @return boolean
     */
    public function onlyCondition()
    {
        return ! $this->isConditionsEmpty();
    }

    /**
     * @return boolean
     */
    public function isConditionsEmpty()
    {
        $conditions = $this->conditions;
        return empty($conditions);
    }
}
