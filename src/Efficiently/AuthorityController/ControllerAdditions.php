<?php namespace Efficiently\AuthorityController;

trait ControllerAdditions
{

    protected $params; // Sadly, we can't set an empty array as default value here, maybe a PHP Trait bug ?
    protected $currentAuthority;
    protected $currentUser;
    protected $_authorized;

   /**
    * Remove all of the Authority-Controller event listeners of the specified controller.
    * If $controllerName == '*', it removes all the Authority-Controller events
    * of every Controllers of the application.
    *
    *   \App\Http\Controllers\Controller::flushAuthorityEvents('*'); // Remove all Authority-Controller events of every Controllers
    *   \App\Http\Controllers\ProjectsController::flushAuthorityEvents(); // Remove all Authority-Controller events of ProjectsController
    *
    * @param string Controller name. If null it get the current Controller name.
    * @return void
    */
    public static function flushAuthorityEvents($controllerName = null)
    {
        $controllerName = $controllerName ?: get_called_class();

        $events = app('events');
        $listeners = (array) get_property($events, 'listeners');
        foreach ($listeners as $eventName => $listener) {
            $remove = false; // flag
            if ($controllerName === "*") { // All Controllers
                if (starts_with($eventName, "router.filter: controller.")) {
                    $remove = true;
                }
            } elseif (preg_match("/^router\.filter: controller\.[^.]+?\.$controllerName/", $eventName)) {
                $remove = true;
            }
            if ($remove) {
                $events->forget($eventName);
            }
        }

    }

    public function paramsBeforeFilter($filter, array $options = [])
    {
        $this->prependBeforeFilter($filter, $options);
    }

    /**
     * Register a new "before" filter before any "before" filters on the controller.
     *
     * @param  string  $filter
     * @param  array   $options
     * @return void
     */
    public function prependBeforeFilter($filter, array $options = [])
    {
        array_unshift($this->beforeFilters, $this->parseFilter($filter, $options));
    }

    /**
     * Register a new "after" filter before any "after" filters on the controller.
     *
     * @param  string  $filter
     * @param  array   $options
     * @return void
     */
    public function prependAfterFilter($filter, array $options = [])
    {
        array_unshift($this->afterFilters, $this->parseFilter($filter, $options));
    }

    /**
     * Sets up a before filter which loads and authorizes the current resource. This performs both
     * loadResource() and authorizeResource() and accepts the same arguments. See those methods for details.
     *
     *   class BooksController extends Controller
     *   {
     *       public function __construct()
     *       {
     *           $this->loadAndAuthorizeResource();
     *       }
     *   }
     *
     */
    public function loadAndAuthorizeResource($args = null)
    {
        $args = is_array($args) ? $args : func_get_args();
        ControllerResource::addBeforeFilter($this, __METHOD__, $args);
    }

    /**
     * Sets up a before filter which loads the model resource into an instance variable.
     * For example, given an ArticlesController it will load the current article into the @article
     * instance variable. It does this by either calling Article->find($this->params['id']); or
     * new Article($this->params['article']); depending upon the action. The index action will
     * automatically set $this->articles to Article::all(); or Article::$options['collectionScope']()->get();
     *
     * If a conditional callback is used in the Authority, the '<code>create</code>' and '<code>store</code>' actions will set
     * the initial attributes based on these conditions. This way these actions will satisfy
     * the authority restrictions.
     *
     * Call this method directly on the controller class.
     *
     *   class BooksController extends Controller
     *   {
     *       public function __construct()
     *       {
     *           $this->loadAndAuthorizeResource();
     *       }
     *   }
     *
     * A resource is not loaded if the instance variable is already set. This makes it easy to override
     * the behavior through a beforeFilter() on certain actions.
     *
     *   class BooksController extends Controller
     *   {
     *       public function __construct()
     *       {
     *           $this->beforeFilter('findBookByPermalink', ['only' => 'show']);
     *           $this->loadAndAuthorizeResource();
     *       }
     *
     *       protected function findBookByPermalink()
     *       {
     *           $this->book = Book::where('permalink', $this->params['id'])->firstOrFail();
     *       }
     *   }
     *
     * If a name is provided which does not match the controller it assumes it is a parent resource. Child
     * resources can then be loaded through it.
     *
     *   class BooksController extends Controller
     *   {
     *       public function __construct()
     *       {
     *           $this->loadResource('author');
     *           $this->loadResource('book', ['through' => 'author']);
     *       }
     *   }
     *
     * Here the author resource will be loaded before each action using $this->params['author_id']. The book resource
     * will then be loaded through the $this->author instance variable.
     *
     * That first argument is optional and will default to the singular name of the controller.
     * A hash of options (see below) can also be passed to this method to further customize it.
     *
     * See loadAndAuthorizeResource() to automatically authorize the resource too.
     *
     * Options:
     * ['<code>only</code>']
     *   Only applies before filter to given actions.
     *
     * ['<code>except</code>']
     *   Does not apply before filter to given actions.
     *
     * ['<code>through</code>']
     *   Load this resource through another one. This should match the name of the parent instance variable or method.
     *
     * ['<code>throughAssociation</code>']
     *   The name of the association to fetch the child records through the parent resource.
     *   This is normally not needed because it defaults to the pluralized resource name.
     *
     * ['<code>shallow</code>']
     *   Pass <code>true</code> to allow this resource to be loaded directly when parent is <code>null</code>.
     *   Defaults to <code>false</code>.
     *
     * ['<code>singleton</code>']
     *   Pass <code>true</code> if this is a singleton resource through a <code>hasOne</code> association.
     *
     * ['<code>parent</code>']
     *   True or false depending on if the resource is considered a parent resource.
     *   This defaults to <code>true</code> if a resource
     *   name is given which does not match the controller.
     *
     * ['<code>class</code>']
     *   The class to use for the model (string).
     *
     * ['<code>instanceName</code>']
     *   The name of the instance variable to load the resource into.
     *
     * ['<code>findBy</code>']
     *   Find using a different attribute other than id. For example.
     *
     *     $this->loadResource(['findBy' => 'permalink']);
     *     // will use where('permalink', $this->params['id'])->firstOrFail()
     *
     * ['<code>idParam</code>']
     *   Find using a param key other than 'id'. For example:
     *
     *     $this->loadResource(['idParam' => 'url']); // will use find($this->params['url'])
     *
     * ['<code>collection</code>']
     *   Specify which actions are resource collection actions in addition to <code>index</code>. This
     *   is usually not necessary because it will try to guess depending on if the id param is present.
     *
     *     $this->loadResource(['collection' => ['sort', 'list']]);
     *
     * ['<code>create</code>']
     *   Specify which actions are new resource actions in addition to <code>new</code>, <code>create</code>
     *    and <code>store</code>.
     *   Pass an action name into here if you would like to build a new resource instead of
     *   fetch one.
     *
     *     $this->loadResource(['create' => 'build']);
     *
     * ['<code>collectionScope</code>']
     *   The name of the query scope to fetch the collection records of collection actions (E.g. <code>index</code> action).
     *
     *     $this->loadResource(['collectionScope' => 'scopePopular']); // will use Article::popular()->get(); to fetch records of collection actions
     *
     *   You can pass parameters with an array. For example:
     *
     *     $this->loadResource(['collectionScope' => ['scopeOfType', 'published']]); // will use Article::ofType('published')->get();
     *
     *   By default, collection actions (<code>index</code> action) returns all the collection record with:
     *
     *     Article::get(); // which is equivalent to Article::all();
     *
     * ['<code>prepend</code>']
     *   Passing <code>true</code> will use prependBeforeFilter() instead of a normal beforeFilter().
     *
     */
    public function loadResource($args = null)
    {
        $args = is_array($args) ? $args : func_get_args();
        ControllerResource::addBeforeFilter($this, __METHOD__, $args);
    }

    /**
     * Sets up a before filter which authorizes the resource using the instance variable.
     * For example, if you have an ArticlesController it will check the $this->article instance variable
     * and ensure the user can perform the current action on it. Under the hood it is doing
     * something like the following.
     *
     *   $this->authorize($this->params['action'], $this->article ?: 'Article')
     *
     * Call this method directly on the controller class.
     *
     *   class BooksController extends Controller
     *   {
     *       public function __construct()
     *       {
     *           $this->authorizeResource();
     *       }
     *   }
     *
     * If you pass in the name of a resource which does not match the controller it will assume
     * it is a parent resource.
     *
     *   class BooksController extends Controller
     *   {
     *       public function __construct()
     *       {
     *           $this->authorizeResource('author');
     *           $this->authorizeResource('book');
     *       }
     *   }
     *
     * Here it will authorize '<code>show</code>', <code>$this->author</code> on every action before authorizing the book.
     *
     * That first argument is optional and will default to the singular name of the controller.
     * A hash of options (see below) can also be passed to this method to further customize it.
     *
     * See loadAndAuthorizeResource() to automatically load the resource too.
     *
     * Options:
     * ['<code>only</code>']
     *   Only applies before filter to given actions.
     *
     * ['<code>except</code>']
     *   Does not apply before filter to given actions.
     *
     * ['<code>singleton</code>']
     *   Pass <code>true</code> if this is a singleton resource through a <code>hasOne</code> association.
     *
     * ['<code>parent</code>']
     *   True or false depending on if the resource is considered a parent resource. This defaults to <code>true</code> if a resource
     *   name is given which does not match the controller.
     *
     * ['<code>class</code>']
     *   The class to use for the model (string). This passed in when the instance variable is not set.
     *   Pass <code>false</code> if there is no associated class for this resource and it will use a symbol of the resource name.
     *
     * ['<code>instance_name</code>']
     *   The name of the instance variable for this resource.
     *
     * ['<code>through</code>']
     *   Authorize conditions on this parent resource when instance isn't available.
     *
     * ['<code>prepend</code>']
     *   Passing <code>true</code> will use prependBeforeFilter() instead of a normal beforeFilter().
     *
     */
    public function authorizeResource($args = null)
    {
        $args = is_array($args) ? $args : func_get_args();
        ControllerResource::addBeforeFilter($this, __METHOD__, $args);
    }

    // TODO: Add checkAuthorization() ?
    // More infos at: https://github.com/ryanb/cancan/blob/master/lib/cancan/controller_additions.rb#L256

    /**
     * Throws a Efficiently\AuthorityController\Exceptions\AccessDenied exception if the currentAuthority cannot
     * perform the given action. This is usually called in a controller action or
     * before filter to perform the authorization.
     *
     *   public function show($id)
     *   {
     *     $this->article = Article::find($id); // Tips: instead of $id, you can use $this->params['id']
     *     $this->authorize('read', $this->article);
     *
     *     // But you still need to return the view
     *     // return View::make('articles.show', compact_property($this, 'article'));
     *   }
     *
     * A 'message' option can be passed to specify a different message.
     *
     *   $this->authorize('read', $this->article, ['message' => "Not authorized to read ".$this->article->name]);
     *
     * You can also use I18n to customize the message. Action aliases defined in Authority work here.
     *
     *   return [
     *       'unauthorized' => [
     *           'manage' => [
     *               'all' => "Not authorized to :action :subject.",
     *               'user' => "Not allowed to manage other user accounts.",
     *           ],
     *           'update' => [
     *               'project' => "Not allowed to update this project."
     *           ],
     *       ],
     *   ];
     *
     * You can catch the exception and modify its behavior in the report() method of the app/Exceptions/Handler.php file.
     * For example here we set the error message to a flash and redirect to the home page.
     *
     *    public function report(Exception $e)
     *    {
     *        if ($e instanceof \Efficiently\AuthorityController\Exceptions\AccessDenied) {
     *            $msg = $e->getMessage();
     *            \Log::error('Access denied! '.$msg);
     *
     *            return Redirect::route('home')->with('flash_alert', $msg);
     *        }
     *
     *        return parent::report($e);
     *    }
     *
     *    //code...
     *
     * See the Efficiently\AuthorityController\Exceptions\AccessDenied exception for more details on working with the exception.
     *
     * See the loadAndAuthorizeResource() method to automatically add the authorize() behavior
     * to the default RESTful actions.
     *
     */
    public function authorize($args = null)
    {
        $args = is_array($args) ? $args : func_get_args();
        $this->_authorized = true;
        return call_user_func_array([$this->getCurrentAuthority(), 'authorize'], $args);
    }

    public function setCurrentAuthority($authority)
    {
        $this->currentAuthority = $authority;
    }

    // alias of setCurrentAuthority() to match CanCan API
    public function setCurrentAbility($ability)
    {
        $this->setCurrentAuthority($ability);
    }

    /**
     * Creates and returns the current user's authority and caches it. If you
     * want to override how the Authority is defined then this is the place.
     * Just define the method in the controller to change behavior.
     *
     *   public function getCurrentAuthority()
     *   {
     *     // instead of \App::make('authority');
     *     $this->currentAuthority = $this->currentAuthority ?: \App::make('UserAuthority', [$this->getCurrentAccount()]);
     *
     *     return $this->currentAuthority;
     *   }
     *
     * Notice it is important to cache the authority object so it is not
     * recreated every time.
     *
     */
    public function getCurrentAuthority()
    {
        if (is_null($this->currentAuthority)) {
            $this->currentAuthority = \App::make('authority');
        }

        return $this->currentAuthority;
    }

    // alias of getCurrentAuthority() to match CanCan API
    public function getCurrentAbility()
    {
        return $this->getCurrentAuthority();
    }

    public function getCurrentUser()
    {
        if (is_null($this->currentUser)) {
            $this->currentUser = $this->getCurrentAuthority()->getCurrentUser();
        }

        return $this->currentUser;
    }

    /**
     * Use in the controller or view to check the user's permission for a given action
     * and object.
     *
     *    $this->can('destroy', $this->project);
     *
     * You can also pass the class instead of an instance (if you don't have one handy).
     *
     *   @if (Authority::can('create', 'Project'))
     *     {{ link_to_route('projects.create', "New Project") }}
     *   @endif
     *
     * If it's a nested resource, you can pass the parent instance in an associative array. This way it will
     * check conditions which reach through that association.
     *
     *   @if (Authority::can('create', ['Project' => $category]))
     *     {{ link_to_route('categories.projects.create', "New Project") }}
     *   @endif
     *
     * This simply calls "can()" on the <code>$this->currentAuthority</code>. See Authority::can().
     *
     */
    public function can($args = null)
    {
        $args = is_array($args) ? $args : func_get_args();
        return call_user_func_array([$this->getCurrentAuthority(), 'can'], $args);
    }

    /**
     * Convenience method which works the same as "can()" but returns the opposite value.
     *
     *   $this->cannot('destroy', $this->project);
     *
     */
    public function cannot($args = null)
    {
        $args = is_array($args) ? $args : func_get_args();
        return call_user_func_array([$this->getCurrentAuthority(), 'cannot'], $args);
    }

    // setParams() should be forbidden for security reasons ?
    // public function setParams($params = [])
    // {
    //  $this->params = $params;
    // }

    public function getParams()
    {
        return (array) $this->params;
    }
}
