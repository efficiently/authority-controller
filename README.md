AuthorityController [![Build Status](https://secure.travis-ci.org/efficiently/authority-controller.png?branch=master)](http://travis-ci.org/efficiently/authority-controller)
===================

AuthorityController is an PHP authorization library for [Laravel 4](http://laravel.com) which restricts what resources a given user is allowed to access.

All permissions are defined in a single location (the `app/config/packages/efficiently/authority-controller/config.php` file) and not duplicated across controllers, views, and database queries.

Good to know
------------
AuthorityController is an extension of the [`authority-l4`](https://github.com/machuga/authority-l4) package.

It's a port of the best Ruby authorization library: [`cancan`](https://github.com/ryanb/cancan) gem (Authority-L4 ports some features of CanCan and this package ports _almost_ all the other features).

It's **only** compatible with PHP >= 5.4 and the Laravel 4 framework.

**Warning**: This is beta-quality software.
It works well according to our tests, but it need more. The internal API may change and other features will be added.
We are working to make AuthorityController production quality software.

It's following the D.R.W.J.P.I. principle:

> Don't Reinvent the Wheel, Just Port It !
> -- <cite>(c) 2013 A.D.</cite>

Installation via Composer
-------------------------
Add `authority-controller` package to your `composer.json` file to require AuthorityController

```javascript
  require : {
    "laravel/framework": "4.0.*",
    "efficiently/authority-controller" : "dev-master"
  }
```

Now update Composer

    composer update

Then add the service provider to `app/config/app.php`

```php
    'Efficiently\AuthorityController\AuthorityControllerServiceProvider',
```

Congratulations, you have successfully installed AuthorityController.

Configuration
-------------

##### Publish the AuthorityController default configuration file

```
  php artisan config:publish efficiently/authority-controller
```

This will place a copy of the configuration file at `app/config/packages/efficiently/authority-controller`. The config file includes an 'initialize' function, which is a great place to setup your rules and aliases.

```php
<?php
// app/config/packages/efficiently/authority-controller

return [

    'initialize' => function($authority) {
        $user = Auth::guest() ? new User : $authority->getCurrentUser();

        // Action aliases. For example:
        $authority->addAlias('moderate', ['read', 'update', 'delete']);

        // Define abilities for the passed in user here. For example:
        if ($user->hasRole('admin')) {
            $authority->allow('manage', 'all');
        } else {
            $authority->allow('read', 'all');
        }

    }

];
```

##### Configure Authority-L4

Follows Authority-L4 configuration instructions from this [section](https://github.com/machuga/authority-l4/blob/2.0.0/README.md#create-roles-and-permissions-tables)

##### Add the aliases (facades) to your Laravel app config file.

```php
    'Params'    => 'Efficiently\AuthorityController\Facades\Params',
    'Authority' => 'Efficiently\AuthorityController\Facades\Authority',
```

This will allow you to access the Authority class through the static interface you are used to with Laravel components.

```php
Authority::can('update', 'SomeModel');
```

##### Init resources filter and controller methods
In your `app/controllers/BaseController.php` file:

```php
class BaseController extends \Controller
{
    use Efficiently\AuthorityControllerResource\ControllerAdditions;
    //code...
}
```

##### Basic usage
In your controller(s):

```php
class ProductsController extends \BaseController
{
    protected $product;

    function __construct()
    {
        $this->loadAndAuthorizeResource();
    }
    //code...
}
```

##### Exception Handling

The `Efficiently\AuthorityController\Exceptions\AccessDenied` exception is raised when calling `authorize()` in the controller and the user is not able to perform the given action. A message can optionally be provided.

```php
Authority::authorize('read', 'Product', 'Unable to read this product.');
```

You can catch the exception and modify its behavior in the `app/start/global.php` file. For example here we set the error message to a flash and redirect to the home page.


```php
App::error(function(Efficiently\AuthorityController\Exceptions\AccessDenied $e, $code, $fromConsole)
{
    $msg = $e->getMessage();
    if ($fromConsole) {
      return 'Error '.$code.': '.$msg."\n";
    }
    Log::error('Access denied! '.$msg);
    return Redirect::route('home')->with('flash_alert', $msg);
});
```

Documentations
--------------
Authority [introduction](https://github.com/machuga/authority/blob/2.0.1/readme.md#introduction).

Authority-L4 [general usage](https://github.com/machuga/authority-l4/blob/2.0.0/README.md#general-usage).

##### Wiki Docs

* [Defining Authority rules](https://github.com/efficiently/authority-controller/wiki/Defining-Authority-rules)
* [Checking Authority rules](https://github.com/efficiently/authority-controller/wiki/Checking-Authority-rules)
* [Authorizing Controller Actions](https://github.com/efficiently/authority-controller/wiki/Authorizing-Controller-Actions)
* [Exception Handling](https://github.com/efficiently/authority-controller/wiki/Exception-Handling)
* [See more](https://github.com/efficiently/authority-controller/wiki)

Because AuthorityController is a CanCan port, you can read the Wiki docs of CanCan [here](https://github.com/ryanb/cancan/wiki).


Controller additions
--------------------
Your controllers have now several utility properties/methods:
* `$params` property:

```php
class ProductsController extends \BaseController
{
    //code...

    public function update($id)
    {
        $this->params['id'] == $id;//-> true
        $this->params['product'];//-> ["name" => "Best movie"]
        $this->params['controller'];//-> 'ProductsController'
        $this->params['action'];//-> 'update'
        //code...
    }

    //code...
}
```

Missing features
----------------

1. In `ControllerResource` class, the [`#load_collection`](https://github.com/ryanb/cancan/blob/1.6.10/lib/cancan/controller_resource.rb#L80) method, who uses in the `User` model [`#accessible_by`](https://github.com/ryanb/cancan/blob/1.6.10/lib/cancan/model_additions.rb#L22) method. Looks complicated.
  Instead, use specific query scopes with `collectionScope` option to filtering your data in your collection (e.g. `index`) controller actions.
  Because you'll allowing/denying access by roles or check user's authorizations on each record of the collection.
2. In `Ability` class, the [`#attributes_for`](https://github.com/ryanb/cancan/blob/1.6.10/lib/cancan/ability.rb#L221) method.
  Looks useless with `Authority` because rules conditions are only possible by `Closure` not by associative array. And CanCan handles `#attribute_for` only for `Hash` (associative array) conditions.
3. `#skip_*` methods in `ControllerAdditions`.
4. For `allow()` and `deny()` methods of `Authority`, the third argument isn't an optional hash (associative array) of conditions but an anonymous function (Closure):

```php
$authority->allow('update', 'Product', function($self, $product) {
    return $product->available === true;
});
```

Differences between CanCan and AuthorityController
-------------------------------------------------

See Wiki page [Differences between CanCan and AuthorityController](https://github.com/efficiently/authority-controller/wiki/Differences-between-CanCan-and-AuthorityController)

Questions or Problems?
----------------------
If you have any issues with AuthorityController, please add an [issue on GitHub](https://github.com/efficiently/authority-controller/issues) or fork the project and send a pull request.

To get the tests running you should install PHPUnit and run `phpunit tests`.


Special Thanks
--------------
AuthorityController was _heavily_ inspired by [CanCan](https://github.com/ryanb/cancan) and uses [Authority-L4](https://github.com/machuga/authority-l4).
