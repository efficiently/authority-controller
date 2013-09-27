AuthorityController [![Build Status](https://secure.travis-ci.org/efficiently/authority-controller.png?branch=master)](http://travis-ci.org/efficiently/authority-controller)
===================

AuthorityController is an PHP authorization library for [Laravel 4](http://laravel.com) which restricts what resources a given user is allowed to access.

All permissions are defined in a single location (the `app/config/packages/efficiently/authority-controller/config.php` file) and not duplicated across controllers, views, and database queries.

Good to know
------------
AuthorityController is an extension of the [`authority-l4`](https://github.com/machuga/authority-l4) package.

It's a port of the best Ruby authorization library: [`cancan`](https://github.com/ryanb/cancan) gem (Authority-L4 ports some features of CanCan and this package ports _almost_ all the other features).

It's **only** compatible with PHP >= 5.4 and the Laravel 4 framework.

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

Follows Authority-L4 configuration instructions from this [section](https://github.com/machuga/authority-l4/blob/master/README.md#create-roles-and-permissions-tables)

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
Authority [introduction](https://github.com/machuga/authority#introduction).

Authority-L4 [general usage](https://github.com/machuga/authority-l4#general-usage).

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

1. In `ControllerResource` class, the [`#load_collection`](https://github.com/ryanb/cancan/blob/60cf6a67ef59c0c9b63bc27ea0101125c4193ea6/lib/cancan/controller_resource.rb#L80) method, who uses in the `User` model [`#accessible_by`](https://github.com/ryanb/cancan/blob/f2f40c7aac4a00a88651641129eaad71916c1c82/lib/cancan/model_additions.rb#L22) method. Looks complicated.
  Instead, use specific query scopes with `collectionScope` option to filtering your data in your collection (e.g. `index`) controller actions.
  Because you'll allowing/denying access by roles or check user's authorizations on each record of the collection.
2. In `Ability` class, the [`#attributes_for`](https://github.com/ryanb/cancan/blob/master/lib/cancan/ability.rb#L221) method.
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

* RESTful action names are a bit different between Rails and Laravel :
  * 'new' action is 'create'
  * 'create' action is 'store'
* In Laravel, unlike Rails, by default `id` parameter of a `Product` resource is `products`.
And `shop_id` parameter of a `Shop` parent resource is `shops`.
So we need to reaffect correct parameter name(s) before executing a controller action. See [`ControllerAdditions::callAction()`](https://github.com/efficiently/authority-controller/tree/master/src/Efficiently/AuthorityController/ControllerAdditions.php).
* In PHP all properties, methods and option names are in `camelCase` (`$myProduct->myCamelCaseMethod();`), in Ruby they are in `snake_case`.
* Some methods don't have _exactly_ the same name:
  * `Ability#alias_action` => `Authority::addAlias`
  * `Ability#can` => `Authority::allow`
  * `Ability#cannot` => `Authority::deny`
  * `Ability|Controller#can?` => `Authority|Controller::can`
  * `Ability|Controller#cannot?` => `Authority|Controller::cannot`
  * `Ability|Controller#authorize!` => `Authority|Controller::authorize`
* In Ruby (with ActiveSupport) getter, setter, bool(true/false) methods are writing like this:

```ruby
class Product
  # Pro tips: You can write "attr_accessor :name" instead of the two methods declarations below
  def name=(value)
    @name = value
  end

  def name
    @name
  end

  def named?
    @name.present?
  end
end

my_product = Product.new
# Setter method
my_product.name = "Best movie"
# Getter method
my_product.name #=> "Best movie"
# Check if my_product is set
my_product.named? #=> true
```

* In PHP getter, setter, bool(true/false) methods are writing like this:

```php
class Product
{
    protected $name;

    public function setName($value)
    {
        $this->name = $value;
    }
    public function getName()
    {
        return $this->name;
    }

    public function isNamed()
    {
        return !!$this->name;
    }
}

$myProduct = new Product;
// Setter method
$myProduct->setName("Best movie");
// Getter method
$myProduct->getName(); //=> "Best movie"
// Check if $myProduct is set
$myProduct->isNamed(); //=> true
```

Questions or Problems?
----------------------
If you have any issues with AuthorityController, please add an [issue on GitHub](https://github.com/efficiently/authority-controller/issues) or fork the project and send a pull request.

To get the tests running you should instal PHPUnit and run `phpunits tests`.


Special Thanks
--------------
AuthorityController was _heavily_ inspired by [CanCan](https://github.com/ryanb/cancan) and uses [Authority-L4](https://github.com/machuga/authority-l4).
