# LaraSPF
[![Latest Stable Version](https://poser.pugx.org/railgun1v9/lara-spf/v/stable)](https://packagist.org/packages/railgun1v9/lara-spf)
[![Total Downloads](https://poser.pugx.org/railgun1v9/lara-spf/downloads)](https://packagist.org/packages/railgun1v9/lara-spf)
[![Latest Unstable Version](https://poser.pugx.org/railgun1v9/lara-spf/v/unstable)](https://packagist.org/packages/railgun1v9/lara-spf)
[![License](https://poser.pugx.org/railgun1v9/lara-spf/license)](https://packagist.org/packages/railgun1v9/lara-spf)

Sorting, Pagination and Filtering for Laravel builders and collections utilizing query parameters of the URI.

## Credits

This package uses functionalities from:

[johannesschobel/dingoquerymapper](https://github.com/johannesschobel/dingoquerymapper) - Sort and Paginate functionality
[CamiloManrique/laravel-filter](https://github.com/CamiloManrique/laravel-filter) - Filtering functionality

Both libraries had working functionalities that met my needs. but fell short in terms of security and other needs, but with a little editing and combining the two, I created an all-in-one solution to sorting, pagination and filtering.

## Installation

Require package with composer:

`composer require railgun1v9/lara-spf`

Add `railgun1v9\LaraSPF` to your service providers in `config/app.php`:

```php
'providers' => [
    railgun1v9\LaraSPF\FilterServiceProvider::class
]

```

You can publish the configuration file to change the default settings:

```
php artisan vendor:publish
```

## Usage

This package add two macro methods to the Builder class which allow to use the filtering methods without any further setup. Both methods can receive an Http Request, a Collection or an array as argument. The two methods are explained below.

> **Note on method names:** The macros method names can be customized on the configuration file, in case there are naming conflicts on the Builder class macros. The default names will be used on the examples.

Get a query builder instance:

```php
User::filter($request)
```
 
 With this method you get a query builder instance, in which you can keep applying query builder methods, including `get()`, `first()`, `paginate()` and many others.
  
Get a model instance:

```php
User::filterandGet($request)
```

This method handles the query building and fetching for you. It even handles pagination for you out of the box. For default, automatic pagination using this method is turned on, but you can change this behavior publishing the configuration file and editing it.

> Those methods can also be called without arguments, in which case no filters are applied to the query

Since those methods are macros of the Builder class, it can also chained when using related models on a model instance. For example, assuming that the model User has a relationship with Post model, you can use it like this:

```php
$user = User::find(1)
$user->posts()->filterAndGet($request);
```
