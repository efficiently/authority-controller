<?php namespace Efficiently\AuthorityController;

use App;
use Route;
use ReflectionProperty;

class ControllerResource
{
    protected $controller, $name, $params;
    protected $options = [];

    public static function getNameByController($controller)
    {
        $name = preg_replace("/^(.+)Controller$/", "$1", $controller);

        return str_singular(snake_case(class_basename($name)));
    }

    public static function addBeforeFilter($controller, $method, $args)
    {
        $method = last(explode('::', $method));
        $resourceName = array_key_exists(0, $args) ? array_shift($args) : null;

        $lastArg = last($args);
        if (is_array($lastArg)) {
            $args = array_merge($args, array_extract_options($lastArg));
        }
        $options = array_extract_options($args);

        if (array_key_exists('prepend', $options)) {
            $beforeFilterMethod = "prependBeforeFilter";
            unset($options['prepend']);
        } else {
            $beforeFilterMethod =  "beforeFilter";
        }

        $resourceOptions = array_except($options, ['only', 'except']);
        $filterName = "controller.".$method.".".get_classname($controller)."(".md5(json_encode($args)).")";
        if (! Route::getFilter($filterName)) {//needed ?

            Route::filter($filterName, function() use($controller, $method, $resourceOptions, $resourceName) {
                $controllerResource = new ControllerResource($controller, $resourceName, $resourceOptions);

                call_user_func([$controllerResource, $method]);
            });

            call_user_func_array([$controller, $beforeFilterMethod], [ $filterName, array_only($options, ['only', 'except']) ]);
        }

    }

    public function __construct($controller, $name = null, $options = [])
    {
        $args = array_slice(func_get_args(), 1);
        $name = array_key_exists(0, $args) && is_string($args[0]) ? array_shift($args) : null;

        $lastArg = last($args);
        if (is_array($lastArg)) {
            $args = array_merge($args, array_extract_options($lastArg));
        }
        $options = $options ?: array_extract_options($args);

        $this->controller = $controller;
        $this->params = $controller->getParams();
        $this->name = $name;
        $this->options = $options;
    }

    public function loadAndAuthorizeResource()
    {
        $this->loadResource();
        $this->authorizeResource();
    }

    public function loadResource()
    {
        if ($this->loadedInstance()) {
            if (! $this->getResourceInstance()) {
                $this->setResourceInstance($this->loadResourceInstance());
            }
        } elseif ($this->loadedCollection()) {
            // Load resources of collection actions (E.g. 'index') here. Even if we don't support $instance->accessibleBy() (see: https://github.com/ryanb/cancan/blob/f2f40c7aac4a00a88651641129eaad71916c1c82/lib/cancan/model_additions.rb#L22)
            if (! $this->getCollectionInstance()) {
                $this->setCollectionInstance($this->loadCollection());
            }
        }

    }

    public function authorizeResource()
    {
        $resource = $this->getResourceInstance() ?: $this->getResourceClassWithParent();
        $this->controller->authorize($this->getAuthorizationAction(), $resource);
    }

    public function isParent()
    {
        return array_key_exists('parent', $this->options) ? $this->options['parent'] : ($this->name && $this->name !== $this->getNameFromController());
    }

    protected function loadResourceInstance()
    {
        if (! $this->isParent() && in_array($this->params['action'], $this->getCreateActions())) {
            return $this->buildResource();
        } elseif ($this->getIdParam() ||  array_key_exists('singleton', $this->options)) {
            return $this->findResource();
        }
    }

    protected function loadedInstance()
    {
        return $this->isParent() || $this->isMemberAction();
    }

    protected function loadedCollection()
    {
        return ! $this->getCurrentAuthority()->hasCondition($this->getAuthorizationAction(), $this->getResourceClass());
    }

    protected function loadCollection()
    {
        $resourceModel = App::make($this->getResourceBase());
        $collectionScope = $this->getCollectionScopeWithParams();
        $collection = $resourceModel;
        if ($collectionScope) {
            list($collectionScope, $collectionScopeParams) = $collectionScope;
            $collectionScope = camel_case(str_replace('scope', '', $collectionScope));
            $collection = call_user_func_array([$collection, $collectionScope], $collectionScopeParams);
        }

        return $collection->get();
    }

    protected function buildResource()
    {
        $resourceBase = $this->getResourceBase();
        $resourceParams = $this->getResourceParams();
        $resource = App::make($resourceBase, is_array($resourceParams) ? $resourceParams : []);

        return $this->setAttributes($resource);
    }

    protected function setAttributes($resource)
    {
        if (array_key_exists('singleton', $this->options) && $this->getParentResource()) {
            $resource->{camel_case($this->getParentName())}()->associate($this->getParentResource());
        }
        $resource->fill($this->getResourceParams());

        return $resource;
    }

    protected function findResource()
    {
        $resource = null;
        if (array_key_exists('singleton', $this->options) && is_method_callable($this->getParentResource(), $this->getName())) {
            $resource = call_user_func([$this->getParentResource(), $this->getName()]);
        } else {
            $resourceModel = App::make($this->getResourceBase());
            if (array_key_exists('findBy', $this->options)) {
                if (is_method_callable($resourceModel, "query") && is_method_callable($resourceModel->query(), "findBy".studly_case($this->options['findBy']))) {
                    $resource = call_user_func_array([$resourceModel, "findBy".studly_case($this->options['findBy']) ], [$this->getIdParam()]);
                } else {
                    $resource = $resourceModel->where($this->getResourcePrimaryKey(), $this->getIdParam())->firstOrFail();
                }
            } else {
                $resource = $resourceModel->where($this->getResourcePrimaryKey(), $this->getIdParam())->firstOrFail();
            }
        }

        if (! is_null($resource)) {
            return $resource;
        }
        throw new \Illuminate\Database\Eloquent\ModelNotFoundException;
    }

    protected function getAuthorizationAction()
    {
        return $this->isParent() ? "show" : $this->params['action'];
    }

    protected function getResourcePrimaryKey()
    {
        return (! array_key_exists('idParam', $this->options) && $this->isParent() && $this->getIdKey() === $this->getName()."_id") ? "id" : $this->getIdKey();
    }

    protected function getIdKey()
    {
        if (array_key_exists('idParam', $this->options)) {
            return $this->options['idParam'];
        } else {
            return $this->isParent() ? $this->getName()."_id" : "id";
        }
    }

    protected function getIdParam()
    {
        // if(array_key_exists('idParam', $this->options)) {
        //  return $this->params[ $this->options['idParam'] ];
        // } else {
        //  $idParam = $this->isParent() ? $this->getName()."_id" : "id";
        //  return array_key_exists($idParam, $this->params) ? $this->params[$idParam] : null;
        // }
        return array_key_exists($this->getIdKey(), $this->params) ? $this->params[$this->getIdKey()] : null;
    }

    protected function isMemberAction()
    {
        return in_array($this->params['action'], $this->getCreateActions()) || array_key_exists('singleton', $this->options) || ($this->getIdParam() && ! in_array($this->params['action'], $this->getCollectionActions()));
    }

    protected function getResourceClass()
    {
        if (array_key_exists('class', $this->options)) {
            return $this->options['class'] === false ? $this->getName() : $this->options['class'];
        } else {
            return studly_case($this->getNamespacedName());
        }
    }

    protected function getResourceClassWithParent()
    {
        // NOTICE: Against CanCan, we reverse the key and value, because in PHP an array key can't be an Object.
        return $this->getParentResource() ? [$this->getResourceClass() => $this->getParentResource()] : $this->getResourceClass();
    }

    protected function setResourceInstance($instance)
    {
        $instanceName = $this->getInstanceName();
        if (property_exists($this->controller, $instanceName)) {
            $reflection = new ReflectionProperty($this->controller, $instanceName);
            $reflection->setAccessible(true);
            $reflection->setValue($this->controller, $instance);
        } else {
            $this->controller->$instanceName = $instance;
        }
    }

    protected function getResourceInstance()
    {
        if ($this->loadedInstance()) {
            $instanceName = $this->getInstanceName();
            if (property_exists($this->controller, $instanceName)) {
                $reflection = new ReflectionProperty($this->controller, $instanceName);
                $reflection->setAccessible(true);
                return $reflection->getValue($this->controller);
            }
        }
    }

    protected function setCollectionInstance($instance)
    {
        $instanceName = str_plural($this->getInstanceName());
        if (property_exists($this->controller, $instanceName)) {
            $reflection = new ReflectionProperty($this->controller, $instanceName);
            $reflection->setAccessible(true);
            $reflection->setValue($this->controller, $instance);
        } else {
            $this->controller->$instanceName = $instance;
        }
    }

    protected function getCollectionInstance()
    {
        if ($this->loadedInstance()) {
            $instanceName = str_plural($this->getInstanceName());
            if (property_exists($this->controller, $instanceName)) {
                $reflection = new ReflectionProperty($this->controller, $instanceName);
                $reflection->setAccessible(true);
                return $reflection->getValue($this->controller);
            }
        }
    }

    /**
     * The object that methods (such as "find", "new" or "build") are called on.
     * If the 'through' option is passed it will go through an association on that instance.
     * If the 'shallow' option is passed it will use the getResourceClass() method if there's no parent
     * If the 'singleton' option is passed it won't use the association because it needs to be handled later.
     */
    protected function getResourceBase()
    {
        if (array_key_exists('through', $this->options)) {
            if ($this->getParentResource()) {
                if (array_key_exists('singleton', $this->options)) {
                    return $this->getResourceClass();
                } elseif (array_key_exists('throughAssociation', $this->options)) {
                    $associationName = $this->options['throughAssociation'];
                    return get_classname($this->getParentResource()->$associationName()->getModel());
                } else {
                    $associationName = str_plural(camel_case($this->getName()));
                    return get_classname($this->getParentResource()->$associationName()->getModel());
                }
            } elseif (array_key_exists('shallow', $this->options)) {
                return $this->getResourceClass();
            } else {
                throw new Exceptions\AccessDenied(null, $this->getAuthorizationAction(), $this->getResourceClass()); // maybe this should be a record not found error instead?
            }
        } else {
            return $this->getResourceClass();
        }
    }

    protected function getParentName()
    {
        if (array_key_exists('through', $this->options)) {
            return array_first(array_flatten((array) $this->options['through']), function($key, $value) {
                return $this->fetchParent($value);
            });
        }
    }

    // The object to load this resource through.
    protected function getParentResource()
    {
        if ($this->getParentName()) {
            return $this->fetchParent($this->getParentName());
        }
    }

    protected function fetchParent($name)
    {
        $name = camel_case($name);
        if (property_exists($this->controller, $name)) {
            $reflection = new ReflectionProperty($this->controller, $name);
            $reflection->setAccessible(true);
            return $reflection->getValue($this->controller);
        } elseif (is_method_callable($this->controller, "get".studly_case($name))) {
            $name ="get".studly_case($name);
            return $this->controller->$name();
        } elseif (is_method_callable($this->controller, $name)) {
            return $this->controller->$name();
        }
    }

    protected function getResourceParams()
    {
        if (array_key_exists('class', $this->options)) {
            $paramsKey = $this->extractKey($this->options['class']);
            if (array_key_exists($paramsKey, $this->params)) {
                return $this->params[$paramsKey];
            }
        }

        return $this->getResourceParamsByNamespacedName();
    }

    protected function getResourceParamsByNamespacedName()
    {
        $paramsKey = $this->extractKey($this->getNamespacedName());

        return array_key_exists($paramsKey, $this->params) ? $this->params[$paramsKey] : [];
    }

    protected function getNamespace()
    {
        return array_slice(preg_split("/\\\\|\//", $this->params['controller']), 0, -1);
    }

    protected function getNamespacedName()
    {
        $namespaceName = null;
        $namespace = $this->getNamespace();
        if (! empty($namespace)) {
            $namespaceName = studly_case(str_singular(implode("\\", array_flatten([$namespace, studly_case($this->getName())]))));
        }

        return class_exists($namespaceName) ? $namespaceName : $this->getName();
    }

    protected function getCurrentAuthority()
    {
        return $this->controller->getCurrentAuthority();
    }

    // Alias of getCurrentAuthority() to match CanCan API
    protected function getCurrentAbility()
    {
        return $this->getCurrentAuthority();
    }

    protected function getName()
    {
        return $this->name ? $this->name : $this->getNameFromController();
    }

    protected function getNameFromController()
    {
        return static::getNameByController($this->params['controller']);
    }

    protected function getInstanceName()
    {
        if (array_key_exists('instanceName', $this->options)) {
            return $this->options['instanceName'];
        } else {
            return camel_case($this->getName());
        }
    }

    protected function getCollectionActions()
    {
        $optionsCollection = array_key_exists('collection', $this->options) ? $this->options['collection'] : [];
        return array_unique(array_flatten(array_merge(['index'], (array) $optionsCollection)));
    }

    // NOTICE: Against Rails, 'new' action is named 'create' in Laravel.
    // And the Rails 'create' action is named 'store' in Laravel.
    protected function getCreateActions()
    {
        // We keep the 'new' option to match CanCan API
        $optionNew = array_key_exists('new', $this->options) ? $this->options['new'] : [];
        $optionCreate = array_key_exists('create', $this->options) ? $this->options['create'] : [];
        $options = array_merge((array) $optionNew, (array) $optionCreate);
        return array_unique(array_flatten(array_merge(['new', 'create', 'store'], $options)));
    }

    // Alias of getCreateActions() to match CanCan API
    protected function getNewActions()
    {
        return $this->getCreateActions();
    }

    protected function extractKey($value)
    {
        return str_replace('/', '', snake_case(preg_replace('/\\\\/', '', $value)));
    }

    protected function getCollectionScope()
    {
        return array_key_exists('collectionScope', $this->options) ? $this->options['collectionScope'] : null;
    }

    public function getCollectionScopeWithParams()
    {
        $collectionScope = $this->getCollectionScope();
        if ($collectionScope) {
            $collectionScopeParams = [];
            if (is_array($collectionScope)) {
                $collectionScopeParams = array_splice($collectionScope, 1);
                $collectionScope = array_shift($collectionScope);
            }
            return [$collectionScope, $collectionScopeParams];
        } else {
            return $collectionScope;
        }
    }

}
