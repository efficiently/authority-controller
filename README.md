AuthorityController [![Build Status](https://travis-ci.org/efficiently/authority-controller.png?branch=master)](http://travis-ci.org/efficiently/authority-controller)
===================

AuthorityController is an PHP authorization library for [Laravel 5.0 & 5.1](http://laravel.com) which restricts what resources a given user is allowed to access.

All permissions are defined in a single location:

    config/authority-controller.php

and not duplicated across controllers, routes, views, and database queries.

For [**Laravel 4.1 or 4.2**](http://laravel.com/docs/4.2) supports see [AuthorityController 1.2 branch](https://github.com/efficiently/authority-controller/tree/1.2)

#### Demo application

You can see in action this package with this Laravel 5.1 [**demo application**](https://github.com/efficiently/laravel_authority-controller_app#readme).

#### Origins and Inspirations

It's an extension of the [`authority-laravel`](https://github.com/authority-php/authority-laravel) package.

And a port of the best [Ruby](https://ruby-lang.org) authorization library: [CanCan](https://github.com/ryanb/cancan).

[Authority](https://github.com/authority-php/authority) ports some features of CanCan and this package ports [_almost_](https://github.com/efficiently/authority-controller/blob/master/README.md#missing-features) all the other features.

Installation
---------------------------

#### With [Composer](https://getcomposer.org/)

1. Add `authority-controller` package to your `composer.json` file to require AuthorityController:

 ```bash
 composer require efficiently/authority-controller:dev-master
 ```

2. Add the service provider to `config/app.php`:

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

Configuration
-------------
##### Create Roles and Permissions Tables

We have provided a basic table structure to get you started in creating your roles and permissions.

Publish them to your migrations directory or copy them directly.

```bash
php artisan vendor:publish --provider="Efficiently\AuthorityController\AuthorityControllerServiceProvider" --tag="migrations"
```

Run the migrations

```bash
php artisan migrate
```

This will create the following tables

- roles
- role_user
- permissions

To utilize these tables, you can add the following methods to your `User` model. You will also need to create Role and Permission Model stubs (replacing `App\Authority\` with you own namespace)..

```php
    //app/User.php
    public function roles()
    {
        return $this->belongsToMany('App\Authority\Role');
    }

    public function permissions()
    {
        return $this->hasMany('App\Authority\Permission');
    }

    public function hasRole($key)
    {
        $hasRole = false;
        foreach ($this->roles as $role) {
            if ($role->name === $key) {
                $hasRole = true;
                break;
            }
        }

        return $hasRole;
    }

    //app/Authority/Role.php
    <?php namespace App\Authority;

    use Illuminate\Database\Eloquent\Model;

    class Role extends Model {}

    //app/Authority/Permission.php
    <?php namespace App\Authority;

    use Illuminate\Database\Eloquent\Model;

    class Permission extends Model {}
```

##### Init resource filters and controller methods
In your `app/Http/Controllers/Controller.php` file to add the `ControllerAdditions` trait:

```php
<?php namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesCommands;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;

abstract class Controller extends BaseController
{
  	use DispatchesCommands, ValidatesRequests;
    use \Efficiently\AuthorityController\ControllerAdditions;
    //code...
}
```

Getting Started
---------------
AuthorityController expects that `Auth::user()` return the current authenticated user. Now, by default Laravel 5 handles [this](http://laravel.com/docs/5.0/authentication#retrieving-the-authenticated-user).

##### Defining Authority rules

User permissions are defined in an AuthorityController configuration file.

You can publish the AuthorityController default configuration file with the command below:

```bash
php artisan vendor:publish --provider="Efficiently\AuthorityController\AuthorityControllerServiceProvider" --tag="config"
```

This will place a copy of the configuration file at `config/authority-controller.php`. The config file includes an `initialize` function, which is a great place to setup your rules and aliases.

```php
//config/authority-controller.php

return [
    'initialize' => function($authority) {
        $user = Auth::guest() ? new App\User : $authority->getCurrentUser();

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
    $this->article = App\Article::find($id);
    $this->authorize('read', $this->article);
}
```

Setting this for every action can be tedious, therefore the `loadAndAuthorizeResource()` method is provided to automatically authorize all actions in a RESTful style resource controller. It will use a before filter to load the resource into an instance variable and authorize it for every action.

```php
<?php namespace App\Http\Controllers;

class ArticlesController extends Controller
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
Authority::authorize('read', 'App\Product', 'Unable to read this product.');
```

You can catch the exception and modify its behavior in the `render()` method of the `app/Exceptions/Handler.php` file. For example here we set the error message to a flash and redirect to the home page.

```php
//app/Exceptions/Handler.php

  /**
   * Render an exception into an HTTP response.
   *
   * @param  \Illuminate\Http\Request  $request
   * @param  \Exception  $e
   * @return \Illuminate\Http\Response
   */
	public function render($request, Exception $e)
    {
        if ($e instanceof \Efficiently\AuthorityController\Exceptions\AccessDenied) {
            $msg = $e->getMessage();
            \Log::error('Access denied! '.$msg);

            return redirect('/home')->with('flash_alert', $msg);
        }

        return parent::render($request, $e);
    }

    //code...
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

Authority [introduction](https://github.com/authority-php/authority/blob/2.2.2/readme.md#introduction).

Authority-Laravel [general usage](https://github.com/authority-php/authority-laravel/blob/2.4.3/README.md#general-usage).

##### CanCan Wiki Docs

Because AuthorityController is a CanCan port, you can also read the Wiki docs of CanCan [here](https://github.com/ryanb/cancan/wiki).

Controller additions
--------------------
Your controllers have now a `$params` property:

```php
<?php namespace App\Http\Controllers;

class ProductsController extends Controller
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
#### 2.1-dev
* Laravel 5.1 support!

#### 2.0.0
* Laravel 5.0 support!
* Use your Laravel Aliases to resolve your models namespace name.
* Or auto guessing them, e.g. `User` => `App\User`
* Add a new config option `controllerClass` which is by default `Illuminate\Routing\Controller`
* Support Route Model Binding in the Parameters class.
  See: http://laravel.com/docs/5.0/routing#route-model-binding and issue [#21](https://github.com/efficiently/authority-controller/issues/21)
* Use [authority-laravel](https://github.com/authority-php/authority-laravel) package instead of [authority-l4](https://github.com/machuga/authority-l4).
* Upgrade Notes <small>(if you used previously this package with Laravel 4)</small>:
  * Move your `authory-controller` config file from `app/config/packages/efficiently/authority-controller/config.php` to `config/authority-controller.php`
  * Publish the `authory-controller` migrations files <small>(see the section [Create Roles and Permissions Tables](https://github.com/efficiently/authority-controller/blob/2.0/README.md#create-roles-and-permissions-tables) of this README)</small>

#### 1.2.4
* Add `BaseController::flushAuthorityEvents()` static method.
  Useful for functional tests with Codeception (see issue [#14](https://github.com/efficiently/authority-controller/issues/14) and [this Wiki page](https://github.com/efficiently/authority-controller/wiki/Testing-Authority-rules#functional-tests-with-codeception) for more explanations).
* Fix User::hasRoles() method to avoid duplicate roles.

#### 1.2.3
* Follow [PSR-2](http://www.php-fig.org) coding style

#### 1.2.2
* Run tests with Laravel 4.2

#### 1.2.1
* Fix `composer.json` file.

#### 1.2.0
* Security fix: conditional callback was never evaluated when an actual instance object was present.
* Non backwards compatible: Deny rules override prior rules and Allow rules don't override prior rules but instead are logically or'ed (fix [#5](https://github.com/efficiently/authority-controller/issues/5)).
  Match more CanCan default behavior unlike `authority-php\authority` package.
  Read the Wiki doc for more information: [Authority-Precedence](https://github.com/efficiently/authority-controller/wiki/Authority-Precedence).
* Support PHP 5.4, 5.5, 5.6 and HipHop Virtual Machine (hhvm).
* Update [`Parameters`](https://github.com/efficiently/authority-controller/blob/18c2ad7788385da4e0309708772ea40cc8be0f53/src/Efficiently/AuthorityController/Parameters.php#L46) class to allow custom routes with `id` and `parent_id` routes's parameters (fix [#6](https://github.com/efficiently/authority-controller/issues/6)).

#### 1.1.3
* Upgrade Authority-L4 package to fix Laravel 4.1 support.

#### 1.1.2
* Tweak the mock system who simulates Eloquent's constructor method.

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
$authority->allow('update', 'App\Product', function ($self, $product) {
    return $product->available === true;
});
```

Good to know
------------
#### Compatibility
It's **only** compatible with **PHP >= 5.4** and **Laravel >= 4.1** framework.

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
AuthorityController was _heavily_ inspired by [CanCan](https://github.com/ryanb/cancan) and uses [Authority-Laravel](https://github.com/authority-php/authority-laravel).
