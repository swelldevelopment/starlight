--------------------------------------------------------------------------------
--------------------------------------------------------------------------------
--------------------------------------------------------------------------------
--------------------------------------------------------------------------------
# Routing

## Requirements

### Initialization

Before the router can be used it must be initialized.  The quickest method is to use the `RouterFactory` class to create new `Router` instance and setup the router base path: 

```
\Starlight\Http\Router\RouterFactory::init($routerObject, $routeBase, $classAlias);
```

The `$routerObject` will usually be `null` unless you have already setup a router object and want to pass it in.  The `$routeBase` parameter refers to the url "home" path for the routes.  If your api url is `http://my-site.com/api`, your `$routeBase` would be `/api`.  If your api url is `http://api.my-site.com/v1/some-resource` your `$routeBase` would be `/v1/some-resource`.  The `$classAlias` parameter is a `boolean` which determines whether or not to automatically alias the route classes as global for convenience.  If allowed, the following aliases will be made:

```
class_alias('\Starlight\Http\Response\ResponseFactory', 'Response');
class_alias('\Starlight\Http\Router\RouterFactory', 'Route');
class_alias('\Starlight\Http\Router\RouteController', 'RouteController');
```

### Dispatching

The router can accept HTTP request coming in and process them, however, you will need to dispatch the routes to provide a response.  Dispatching the routes should happen after the router is initialized and after your routes are defined (more on this topic below).

```
try {
    print \Route::dispatch();
} catch (\Exception $e) {
    die($e->getMessage());
}
```

## Basics

Routes may be defined in the `controller.php` file, which is automatically loaded. The most basic routes simply accept a URI and a `Closure`, providing a very simple and expressive method of defining routes:

    \Route::get('/foo', function () {
        return 'Hello World';
    });


#### Available Router Methods

The router allows you to register routes that respond to any HTTP verb:

    \Route::get($uri, $callback);
    \Route::post($uri, $callback);
    \Route::put($uri, $callback);
    \Route::patch($uri, $callback);
    \Route::delete($uri, $callback);
    
    // Special route to match ALL request types mentioned above
    \Route::any($uri, $callback);

### Required Parameters

Of course, sometimes you will need to capture segments of the URI within your route. For example, you may need to capture a user's ID from the URL. You may do so by defining route parameters:

    \Route::get('/user/[i:id]', function($match_info, $id) {
        return 'User '.$id;
    });

You may define as many route parameters as required by your route:

    \Route::get('/news/[articles|releases|publications:type]/page/[i:page]', function($match_info, $type, $page) {
        //
    });

The parameters will be passed into your route's `Closure` when the route is executed.

### Match Types

You can use the following limits on your named parameters. The router will create the correct regexes for you.

```
*                    // Match all request URIs
[i]                  // Match an integer
[i:id]               // Match an integer as 'id'
[a:action]           // Match alphanumeric characters as 'action'
[h:key]              // Match hexadecimal characters as 'key'
[:action]            // Match anything up to the next / or end of the URI as 'action'
[create|edit:action] // Match either 'create' or 'edit' as 'action'
[*]                  // Catch all (lazy, stops at the next trailing slash)
[*:trailing]         // Catch all as 'trailing' (lazy)
[**:trailing]        // Catch all (possessive - will match the rest of the URI)
.[:format]?          // Match an optional parameter 'format' - a / or . before the block is also optional
```

### Optional Parameters

Occasionally you may need to specify a route parameter, but make the presence of that route parameter optional. You may do so by placing a `?` mark after the parameter name. Make sure to give the route's corresponding variable a default value:

    \Route::get('/news/[articles|releases|publications:type]/page/[i:page]?', function($match_info, $type, $page = null) {
        return $page;
    });

## Route Handlers

The most basic way to hand a route match is with a `Closure`.

    \Route::get('/foo', function() {
        return 'Hello World';
    });

A more structured solution to handling routes would be to use a `Controller` class to handle routes.  This allows you to have one class that can handle multiple routes that are related to each other.  Consider the following:

```
\Route::get('/news/[articles|releases|publications:type]/programs/[*:program]/page/[*:page]?/?', function($match_info, $program, $page)
{
    // Handle route with Closure
});

\Route::get('/news/[articles|releases|publications:type]/programs/[*:program]?/?', function($match_info, $program, $page)
{
   // Handle route with Closure
});

\Route::get('/news/[articles|releases|publications:type]/programs/?', function($match_info, $program, $page)
{
  // Handle route with Closure
});
```

Instead of using a `Closure` to handle each related route above, we can provide a `string` with the name of a controller class and method to handle the route:

```
\Route::get('/news/[articles|releases|publications:type]/programs/[*:program]/page/[*:page]?/?', '\app\controllers\News@programs');
\Route::get('/news/[articles|releases|publications:type]/programs/[*:program]?/?', '\app\controllers\News@programs');
\Route::get('/news/[articles|releases|publications:type]/programs/?', '\app\controllers\News@programs');
```

In the above example `\app\controllers\News@programs` refers to the `\app\controllers\News` controller class and the `programs` method of that class.  Use the `@` to separate your class name and the method for the route handler.  A basic controller for the route handler woudl look like this:

```
<?php
namespace app\controllers;

class News extends \app\controllers\_Controller
{
  // Taxonomy templates for news
  public function programs($type, $program=NULL, $page=1)
  {
    // No program set, redirect to main
    if(!$program)
    {
      header('Location: /'.$type, true, 301);
      exit;
    }

    return \View::scope('templates')->make('tmpl-news-program', ['type' => $type, 'program' => $program, 'paged' => $page]);
  }
}
```


All routes in the previous example will be handled by the `\app\controllers\News` controller, using the `programs` method (which contains all required and optional parameters for the routes).  Typically a controller will be used to handle multiple route actions.

```
// Using \app\controllers\News@index method as a handler
\Route::get('/news/[articles|releases|publications:type]/page/[i:page]?/?', '\app\controllers\News@index');
\Route::get('/news/[articles|releases|publications:type]/?', '\app\controllers\News@index');

// Using the same controller, but the programs method is used as a handler for these routes
\Route::get('/news/[articles|releases|publications:type]/programs/[*:program]/page/[*:page]?/?', '\app\controllers\News@programs');
\Route::get('/news/[articles|releases|publications:type]/programs/[*:program]?/?', '\app\controllers\News@programs');
\Route::get('/news/[articles|releases|publications:type]/programs/?', '\app\controllers\News@programs');
```

As seen, there can be a potential for many related routes to be used.  This is where the advantage of route controllers comes in.  It allows you to organize all logic in a single place for related routes.


## Route Filters

Route filters provide a convenient way of limiting access to a given route, which is useful for creating areas of your site which require authentication or any other type of restriction. These filters are defined after the router has been initialized.

There are two types of filters available, `before` and `after`.  `before` filters are used to check for certain conditions and restrict access based on the result of the check.  `after` filters are used to modify the response already received from the route handler or provide some post-route procedures such as logging.  The most used filter type is the `before` filter.

#### Defining A Route Filter

    \Route::filter('christmas', function()
    {
        if(date('m/d') != '12/25')
        {
            return 'Today is NOT Christmas.';
        }
    });

The first parameter of the `\Route::filter` method is the custom name of the filter which you can use to attach it to routes.  If the filter returns a response, that response is considered the response to the request and the route will not execute. Any `after` filters on the route are also cancelled.

#### Attaching A Filter To A Route

    \Route::get('/party', ['before' => 'christmas', function()
    {
        return 'Today is Christmas. Party on!';
    }]);

#### Attaching Multiple Filters To A Route

    \Route::get('/party', ['before' => ['auth', 'christmas'], function()
    {
        return 'You are authenticated and today is Christmas!';
    }]);

#### Specifying Route Parameters in the Filter

    \Route::filter('admin', function($match_info, $id)
    {
        if($id != 1) return 'Access restricted, you must be the admin.';
    });

    \Route::get('/user/[i:id]', ['before' => 'admin', function()
    {
        return 'Hello World';
    }]);

`after` filters receive a `$response` as the 2nd argument passed to the filter:

    \Route::filter('log', function($match_info, $response)
    {
        //
    });


## Route Groups

Route groups allow you to share route attributes and even prefix route matches across multiple routes without needing to define those attributes on each individual route. Shared attributes are specified in an array format as the first parameter to the `\Route::scope` method.

A previous exmple showed the following:

```
\Route::get('/news/[articles|releases|publications:type]/programs/[*:program]/page/[*:page]?/?', '\app\controllers\News@programs');
\Route::get('/news/[articles|releases|publications:type]/programs/[*:program]?/?', '\app\controllers\News@programs');
\Route::get('/news/[articles|releases|publications:type]/programs/?', '\app\controllers\News@programs');
```

Using route groups, we can clean up the code and simplify organization using this:

```
\Route::scope(['target' => '/news/[articles|releases|publications:type]/programs', 'controller' => '\app\controllers\News'], function()
{
  \Route::get('/[*:program]/page/[*:page]?/?', 'programs');
  \Route::get('/[*:program]?/?', 'programs');
  \Route::get('/?', 'programs');
});
```

To learn more about route groups, we'll walk through several common use-cases for the feature.

### Route Prefixes

The `target` attribute may be used to prefix each route in the group with a given URI. For example, you may want to prefix all route URIs within the group with `admin`:

    \Route::scope(['target' => '/admin'], function () {
        \Route::get('/users', function ()    {
            // Matches The "/admin/users" URL
        });
    });

You may also use the `prefix` parameter to specify common parameters for your grouped routes:

    \Route::scope(['target' => '/accounts/[i:account_id]'], function () {
        \Route::get('/detail', function($match_info, $account_id)    {
            // Matches The "/accounts/{account_id}/detail" URL
        });
    });

### Route Controllers

The `controller` attribute may be to specify a controller class to handle all routes within the scope.  Any routes in the scope just need to specify a method name of the controller class to use as a handler.

    \Route::scope(['target' => '/admin', 'controller' => '\app\controllers\Users'], function () {
        \Route::get('/users', 'index');     // Uses the "index" method of the \app\controllers\Users controller class to handle the route match
    });


### Route Filters

Continue to view options for specifying filters on a group of routes...

## Route Groups With Filters

A group of routes can define filters to use for each route within the scope:

```
// Only logged in users will be able to access these routes because of the "auth" filter
\Route::scope(['target' => '/news/[articles|releases|publications:type]/programs', 'controller' => '\app\controllers\News', 'before' => 'auth'], function()
{
  \Route::get('/[*:program]/page/[*:page]?/?', 'programs');
  \Route::get('/[*:program]?/?', 'programs');
  \Route::get('/?', 'programs');
});
```

Instead of specifing filters directly in the route with the `before` or `after` attributes, you can use your controller to define the filters using the `filter_before`, or `filter_after` methods.  Using the same controller from a previous example, we can add filters directly in the controller like this:

```
<?php
namespace app\controllers;

class News extends \app\controllers\_Controller
{
  // Route handler methods...
  
  // Filter before dispatching route for all routes using this controller
  public function filter_before()
  {
    if(!user_can_access_news_stuff()) return 'Access denied, you are not authorized to view restricted content.';
  }

  // Filter after the route gets the reponse
  public function filter_after($response)
  {
    // Maybe you want to log the $response...
  }
}
```

When spedifying a controller in the `\Route::scope`, the above filter methods are automatically run (if they are defined in the class), so no need to use use a `before` or `after` attributes in the scope.
 
```
 // filter_before() and filter_after() methods from the \app\controllers\News class will automatically be applied if the methods are defined
 \Route::scope(['target' => '/news/[articles|releases|publications:type]/programs', 'controller' => '\app\controllers\News'], function()
 {
   \Route::get('/[*:program]/page/[*:page]?/?', 'programs');
   \Route::get('/[*:program]?/?', 'programs');
   \Route::get('/?', 'programs');
 });
```

You can optionally specify additional `before` or `after` filters as attributes to scope to apply any other filters not included in your controller class:

```
// Controller filter_before() method will be run first, then the spedified "auth" filter will be applied after
\Route::scope(['target' => '/news/[articles|releases|publications:type]/programs', 'controller' => '\app\controllers\News', 'before' => 'auth'], function()
{
  \Route::get('/[*:program]/page/[*:page]?/?', 'programs');
  \Route::get('/[*:program]?/?', 'programs');
  \Route::get('/?', 'programs');
});
```

You can also run filters on specific routes within the group.  All group filters will run first, then route specific filters after:

```
// Controller filter_before() method will be run first, then the spedified "auth" filter will be applied after
\Route::scope(['target' => '/news/[articles|releases|publications:type]/programs', 'controller' => '\app\controllers\News', 'before' => 'auth'], function()
{
  \Route::get('/[*:program]/page/[*:page]?/?', ['target' => 'programs', 'before' => 'some_other_filter_just_for_this_route']);
  \Route::get('/[*:program]?/?', 'programs');
  \Route::get('/?', 'programs');
});
```
