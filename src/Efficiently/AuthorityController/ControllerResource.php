<?php namespace Efficiently\AuthorityController;

use App;
use Event;
use Route;

class ControllerResource
{
    protected $controller;
    protected $name;
    protected $params;
    protected $options = [];

    public static function getNameByController($controller)
    {
        $name = preg_replace("/^(.+)Controller$/", "$1", $controller);

        return str_singular(snake_case(class_basename($name)));
    }

    public static function addBeforeFilter($controller, $method, $args)
    {
        $method = last(explode('::', $method));
        $resourceName = array_key_exists(0, $args) ? snake_case(array_shift($args)) : null;

        $lastArg = last($args);
        if (is_array($lastArg)) {
            $args = array_merge($args, array_extract_options($lastArg));
        }
        $options = array_extract_options($args);

        if (array_key_exists('prepend', $options) && $options['prepend'] === true) {
            $beforeFilterMethod = "prependBeforeFilter";
            unset($options['prepend']);
        } else {
            $beforeFilterMethod =  "beforeFilter";
        }

        $resourceOptions = array_except($options, ['only', 'except']);
        $filterPrefix = "router.filter: ";
        $filterName = "controller.".$method.".".get_classname($controller)."(".md5(json_encode($args)).")";

        $router = App::make('router');
        if (! Event::hasListeners($filterPrefix.$filterName)) {
            $router->filter($filterName, function () use ($controller, $method, $resourceOptions, $resourceName) {
                $controllerResource = App::make('Efficiently\AuthorityController\ControllerResource', [
                    $controller, $resourceName, $resourceOptions
                ]);
                $controllerResource->$method();
            });

            call_user_func_array([$controller, $beforeFilterMethod], [$filterName, array_only($options, ['only', 'except'])]);
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

        $resource = App::make($resourceBase, is_array($resourceParams) ? [$resourceParams] : []);

        return $this->setAttributes($resource);
    }

    protected function setAttributes($resource)
    {
        if (array_key_exists('singleton', $this->options) && $this->getParentResource()) {
            $resource->{camel_case($this->getParentName())}()->associate($this->getParentResource());
        }
        // TODO: ?Implements initial attributes feature?
        // See: https://github.com/ryanb/cancan/blob/1.6.10/lib/cancan/controller_resource.rb#L91

        return $resource;
    }

    protected function findResource()
    {
        $resource = null;
        if (array_key_exists('singleton', $this->options) && respond_to($this->getParentResource(), $this->getName())) {
            $resource = call_user_func([$this->getParentResource(), $this->getName()]);
        } else {
            $resourceModel = App::make($this->getResourceBase());
            if (array_key_exists('findBy', $this->options)) {
                if (respond_to($resourceModel, "findBy".studly_case($this->options['findBy']))) {
                    $resource = call_user_func_array([$resourceModel, "findBy".studly_case($this->options['findBy']) ], [$this->getIdParam()]);
                } elseif (respond_to($resourceModel, camel_case($this->options['findBy']))) {
                    $resource = call_user_func_array([$resourceModel, camel_case($this->options['findBy']) ], [$this->getIdParam()]);
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
        return array_key_exists($this->getIdKey(), $this->params) ? print_r($this->params[$this->getIdKey()], true) : "";
    }

    protected function isMemberAction()
    {
        return in_array($this->params['action'], $this->getCreateActions()) || array_key_exists('singleton', $this->options) || ($this->getIdParam() && ! in_array($this->params['action'], $this->getCollectionActions()));
    }

    // Returns the class name used for this resource. This can be overriden by the 'class' option.
    // If false is passed in it will use the resource name as a lowercase string in which case it should
    // only be used for authorization, not loading since there's no class to load through.
    protected function getResourceClass()
    {
        if (array_key_exists('class', $this->options)) {
            if ($this->options['class'] === false) {
                return $this->getName();
            } elseif (is_string($this->options['class'])) {
                return studly_case($this->options['class']);
            }
        }

        return studly_case($this->getNamespacedName());
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
            set_property($this->controller, $instanceName, $instance);
        } else {
            $this->controller->$instanceName = $instance;
        }
    }

    protected function getResourceInstance()
    {
        if ($this->loadedInstance()) {
            $instanceName = $this->getInstanceName();
            if (property_exists($this->controller, $instanceName)) {
                return get_property($this->controller, $instanceName);
            }
        }
    }

    protected function setCollectionInstance($instance)
    {
        $instanceName = str_plural($this->getInstanceName());
        if (property_exists($this->controller, $instanceName)) {
            set_property($this->controller, $instanceName, $instance);
        } else {
            $this->controller->$instanceName = $instance;
        }
    }

    protected function getCollectionInstance()
    {
        if ($this->loadedInstance()) {
            $instanceName = str_plural($this->getInstanceName());
            if (property_exists($this->controller, $instanceName)) {
                return get_property($this->controller, $instanceName);
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
                // Maybe this should be a record not found error instead?
                throw new Exceptions\AccessDenied(null, $this->getAuthorizationAction(), $this->getResourceClass());
            }
        } else {
            return $this->getResourceClass();
        }
    }

    protected function getParentName()
    {
        if (array_key_exists('through', $this->options)) {
            return array_first(array_flatten((array) $this->options['through']), function ($key, $value) {
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
            return get_property($this->controller, $name);
        } elseif (respond_to($this->controller, "get".studly_case($name))) {
            $name ="get".studly_case($name);
            return $this->controller->$name();
        } elseif (respond_to($this->controller, $name)) {
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

    protected function getNamespace($controllerName = null)
    {
        $controllerName = $controllerName ?: $this->params['controller'];

        return array_slice(preg_split("/\\\\|\//", $controllerName), 0, -1);
    }

    protected function getNamespacedName()
    {
        $namespaceName = null;
        $namespace = $this->getNamespace();
        if (! empty($namespace)) {
            $namespaceName = studly_case(str_singular(implode("\\", array_flatten([$namespace, studly_case($this->getName())]))));
        }

        if (class_exists($namespaceName)) {
            return $namespaceName;
        }

        $className = studly_case($this->getName());
        if (class_exists($className)) {
            // Support Laravel Alias
            $aliasLoader = \Illuminate\Foundation\AliasLoader::getInstance();
            $aliasName = array_get($aliasLoader->getAliases(), $className);
            return class_exists($aliasName) ? $aliasName : $className;
        }

        $controllerNamespaces = $this->getNamespace(get_classname($this->controller));
        // Detect the Root Namespace, based on the current controller namespace
        // And test if the resource class exists with it
        // Borrowed from: https://github.com/laravel/framework/blob/v5.0.13/src/Illuminate/Routing/UrlGenerator.php#L526
        if (! empty($controllerNamespaces) && ! (strpos($className, '\\') === 0)) {
            $rootNamespace = head($controllerNamespaces);
            $guessName = $rootNamespace.'\\'.$className;
            if (class_exists($guessName)) {
                return $guessName;
            }
        }

        return $this->getName();
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
