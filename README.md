AuthorityController [![Build Status](https://travis-ci.org/efficiently/authority-controller.png?branch=master)](http://travis-ci.org/efficiently/authority-controller)
===================

AuthorityController 1.1 is an PHP authorization library for [Laravel 4.1](http://laravel.com) which restricts what resources a given user is allowed to access.

All permissions are defined in a single location:

    app/config/packages/efficiently/authority-controller/config.php

and not duplicated across controllers, routes, views, and database queries.

For [**Laravel 4.0**](http://laravel.com/docs/4-0) supports see [AuthorityController 1.0 branch](https://github.com/efficiently/authority-controller/tree/1.0)

#### Demo application

You can see in action this package with this Laravel 4.1 [**demo application**](https://github.com/efficiently/laravel_authority-controller_app).

#### Origins and Inspirations

It's an extension of the [`authority-l4`](https://github.com/machuga/authority-l4) package.

And a port of the best [Ruby](https://ruby-lang.org) authorization library: [CanCan](https://github.com/ryanb/cancan).

[Authority](https://github.com/machuga/authority) ports some features of CanCan and this package ports [_almost_](https://github.com/efficiently/authority-controller/blob/master/README.md#missing-features) all the other features.

Installation
---------------------------

#### With [Composer](https://getcomposer.org/)

1. Add `authority-controller` package to your `composer.json` file to require AuthorityController:

 ```bash
 composer require efficiently/authority-controller:1.1.*
 ```

2. Add the service provider to `app/config/app.php`:

 ```php
     'Efficiently\AuthorityController\AuthorityControllerServiceProvider',
 ```

3. Add the aliases (facades) to your Laravel app config file:

 ```php
     'Params'    => 'Efficiently\AuthorityController\Facades\Params',
     'Authority' => 'Efficiently\AuthorityController\Facades\Authority',
 ```

4. This will allow you to access the Authority class through the static interface you are used to with Laravel components.

 ```php
 Authority::can('update', 'SomeModel');
 ```

#### With [Laravel 4 Package Installer](https://github.com/rtablada/package-installer#laravel-4-package-installer)

1. Run this command:

 ```bash
 php artisan package:install efficiently/authority-controller
 ```

2. Then provide a version constraint for the `efficiently/authority-controller` requirement:

 ```
 1.1.*
 ```

Configuration
-------------
##### Create Roles and Permissions Tables

We have provided a basic table structure to get you started in creating your roles and permissions.

Run the Authority migrations

```bash
php artisan migrate --package=machuga/authority-l4
```

This will create the following tables

- roles
- role_user
- permissions

To utilize these tables, you can add the following methods to your `User` model. You will also need to create Role and Permission Model stubs.

```php
    //app/models/User.php
    public function roles()
    {
        return $this->belongsToMany('Role');
    }

    public function permissions()
    {
        return $this->hasMany('Permission');
    }

    public function hasRole($key)
    {
        foreach($this->roles as $role){
            if($role->name === $key)
            {
                return true;
            }
        }
        return false;
    }

    //app/models/Role.php
    class Role extends Eloquent {}

    //app/models/Permission.php
    class Permission extends Eloquent {}
```

##### Init resource filters and controller methods
In your `app/controllers/BaseController.php` file:

```php
class BaseController extends \Controller
{
    use Efficiently\AuthorityController\ControllerAdditions;
    //code...
}
```

Getting Started
---------------
AuthorityController expects that `Auth::user()` return the current authenticated user. First, set up some authentication ([from Scratch](https://bitbucket.org/beni/laravel-4-tutorial/wiki/User%20Management) or with [Confide](https://github.com/Zizaco/confide) package).

##### Defining Authority rules

User permissions are defined in an AuthorityController configuration file.

You can publish the AuthorityController default configuration file with the command below:

```
  php artisan config:publish efficiently/authority-controller
```

This will place a copy of the configuration file at `app/config/packages/efficiently/authority-controller`. The config file includes an `initialize` function, which is a great place to setup your rules and aliases.

```php
// app/config/packages/efficiently/authority-controller/config.php

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

See [Defining Authority rules](https://github.com/efficiently/authority-controller/wiki/Defining-Authority-rules) for details.

##### Check Authority rules & Authorization

The current user's permissions can then be checked using the `Authority::can()` and `Authority::cannot()` methods in the view and controller.

```
@if (Authority::can('update', $article))
    {{ link_to_route("articles.edit", "Edit", $article->id) }}
@endif
```

See [Checking Authority rules](https://github.com/efficiently/authority-controller/wiki/Checking-Authority-rules) for more information

The `authorize()` method in the controller will throw an exception if the user is not able to perform the given action.

```php
public function show($id)
{
    $this->article = Article::find($id);
    $this->authorize('read', $this->article);
}
```

Setting this for every action can be tedious, therefore the `loadAndAuthorizeResource()` method is provided to automatically authorize all actions in a RESTful style resource controller. It will use a before filter to load the resource into an instance variable and authorize it for every action.

```php
class ArticlesController extends \BaseController
{

    public function __construct()
    {
        $this->loadAndAuthorizeResource();
    }

    public function show($id)
    {
        // $this->article is already loaded and authorized
    }
}
```

See [Authorizing Controller Actions](https://github.com/efficiently/authority-controller/wiki/authorizing-controller-actions) for more information.

##### Exception Handling

The `Efficiently\AuthorityController\Exceptions\AccessDenied` exception is thrown when calling `authorize()` in the controller and the user is not able to perform the given action. A message can optionally be provided.

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

See [Exception Handling](https://github.com/efficiently/authority-controller/wiki/Exception-Handling) for more information.

Documentations
--------------
##### Wiki Docs

* [Defining Authority rules](https://github.com/efficiently/authority-controller/wiki/Defining-Authority-rules)
* [Checking Authority rules](https://github.com/efficiently/authority-controller/wiki/Checking-Authority-rules)
* [Authorizing Controller Actions](https://github.com/efficiently/authority-controller/wiki/Authorizing-Controller-Actions)
* [Exception Handling](https://github.com/efficiently/authority-controller/wiki/Exception-Handling)
* [See more](https://github.com/efficiently/authority-controller/wiki)

##### Authority Docs

Authority [introduction](https://github.com/machuga/authority/blob/2.0.1/readme.md#introduction).

Authority-L4 [general usage](https://github.com/machuga/authority-l4/blob/2.0.0/README.md#general-usage).

##### CanCan Wiki Docs

Because AuthorityController is a CanCan port, you can also read the Wiki docs of CanCan [here](https://github.com/ryanb/cancan/wiki).

Controller additions
--------------------
Your controllers have a `$params` property:

```php
class ProductsController extends \BaseController
{
    //code...

    public function update($id)
    {
        $this->params['id'] == $id;//-> true
        $this->params['product'];//-> ["name" => "Best movie"]
        $this->params['controller'];//-> 'products'
        $this->params['action'];//-> 'update'
        //code...
    }

    //code...
}
```

Changelog
---------
#### 1.1.1
* Less intrusive parameters injection in the controllers
    * Check if the current resolved controller responds to paramsBeforeFilter method. Otherwise the application crash.
    * Use the Controller alias of the current Laravel application instead of a hardcoded class name.

#### 1.1.0
* First beta release for Laravel **4.1** compatibility.
* Non backwards compatible with Laravel **4.0**.

#### 1.0.0
* First stable release, only compatible with Laravel **4.0**.
* For Laravel **4.1** supports, see [AuthorityController 1.1 branch](https://github.com/efficiently/authority-controller/tree/1.1).
* Fix AccessDenied class, the exception message didn't fallback to the default message if it was empty.

#### 0.10.0
* Non backwards compatible: `Params::get('controller')` behaviour is now like Rails. It returns controller name in snake_case and in plural.

#### 0.9.0
* First beta release

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

Good to know
------------
#### Compatibility
It's **only** compatible with **PHP >= 5.4** and **Laravel 4.1** framework.

#### This is beta-quality software
It works well according to our tests. The internal API may change and other features will be added.
We are working to make AuthorityController production quality software.

#### Differences between CanCan and AuthorityController
See Wiki page [Differences between CanCan and AuthorityController](https://github.com/efficiently/authority-controller/wiki/Differences-between-CanCan-and-AuthorityController)

#### Philosophy
It's following the D.R.W.J.P.I. principle:

> Don't Reinvent the Wheel, Just Port It !
> -- <cite>(c) 2013 A.D.</cite>

Questions or Problems?
----------------------
If you have any issues with AuthorityController, please add an [issue on GitHub](https://github.com/efficiently/authority-controller/issues) or fork the project and send a pull request.

To get the tests running you should install PHPUnit and run `phpunit tests`.


Special Thanks
--------------
AuthorityController was _heavily_ inspired by [CanCan](https://github.com/ryanb/cancan) and uses [Authority-L4](https://github.com/machuga/authority-l4).
