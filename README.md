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

### Practical usage example

Returning from a route:
```php
Route::get('/users', function(){
    return User::filterAndGet(request());
});
```

Returning from a controller:
```php
public function index(Request $request){
    return User::filterAndGet($request);
}
```

> **A note on Eloquent API Resources:** If you are using Laravel 5.5, you can also use this package with Eloquent Resources new feature:
```php
Route::get('/users', function(){
    return UserResource::collection(User::filterAndGet(request()));
});
```

## Filtering rules

Now, this is an important section. We have explained how to install and call the filter methods, but how can you actually define your filters? Well, it's rather simple for basic queries and a little more verbose if you need to query based on related models.

### Defining the filtering columns

In your request, you simply use the column names as keys and the comparison values as, well the values.

#### Example

Let's assume that your app URL is http://www.example.com and you defined a route /users that points to a controller method filter like this:

```php
    namespace App\Http\Controllers;
    
    use App\Users;
    
    class UsersController extends Controller
    {
        public function filter(Request $request)
        {
            return User::filterAndGet($request);
        }
    }
```

Let's say you want to retrieve the users from Germany. Your Http Request object should have a key named country with its value set to Germany. The URI request would be like this:

```php
    http://www.example.com/users?country=Germany
```

Now let's say you want to be more specific and you want to retrieve the users who, not only are from Germany, but also are males. Your URI would turn into something like this:

```php
    http://www.example.com/users?country=Germany&gender=Male
```

Remember that you can also use Collections and array to pass the desired filters. The equivalent of the last example using an array as parameter would be like this:

```php
    $filters = ["country" => "Germany", "gender" => "Male"]
    User::filterAndGet($filters);
```

#### Query comparison operators

The above examples work only with exact matches, but you would probably need a more loose comparison like the one that >, <, LIKE and != operators offer. In order to use this operators, you append a keyword at the end of the column name, separated by a '/' character. This separation character can be changed on the configuration file.

This is the list of the keywords and their corresponding operators:

- **start:** >= value
- **end:** <= value
- **like:** LIKE %value%
- **not:** != value

#### Example

Retrieve the users from Germany and under 30 years:

```php
    http://www.example.com/users?country=Germany&age%2Fend=30
```

In the previous example, %2F is the encoding for the '/' character.

Equivalent form using an array as parameter:

```php
    $filters = ["country" => "Germany", "age/end" => 30]
    User::filterAndGet($filters);
```

### Advanced Usage

#### Appending related models to the response

Sometimes you might need to fetch additional related models from your query. You can achieve this adding the keyword "relationships" to your input and setting its value to a comma-separated list of relationships (as defined in your model class, not the table name) you want to include.

> If you have a column named "relationships" in your model, the filter will behave unexpectedly. In that case, change search in the config file for the relationship key on the keyword array and change its value to any other word you want that won't cause conflicts with your column names.

For example, let's say you have an User model with two related models, Posts and Comments, related model in a one to many relationship (a User can have many Posts and Comments). Now, you want to get only the users from Germany, but you want to include the posts and comments from the users in the response. The input would look like this:

```php
    $filters = ["country" => "Germany", "relationships" => "posts,comments"]
    User::filterAndGet($filters);
```

This way, the Posts and Comments models from each user will be included in the response.

> Be careful when loading relationships with many models, because all of them will be loaded, and can lead to very slow response times.


#### Filtering based on related models

Just as you can include related models on your result, you can also filter your results based on related models. You need only to prepend the relationship and a "@" character to the column name. For example, let's say a User model has a one on one relationship named "account_info", with a model named AccountInfo, and this model has an attribute called "name". To filter based the user model on the column "name" from the AccountInfo model, the input would be:

```php
    $filters = ["account_info@name" => "John"]
    User::filterAndGet($filters);
```

#### Get sum aggregate

Sometimes, you don't need the actual models from a query. Instead you might need the total sum of one or more attributes. In that case, you should add the keyword "sum" to your input, and setting its value to the column or columns you want to get the total sum (the columns should be comma-separated). An example would be getting the total votes from some user posts:

```php
    $filters = ["user_id" => 1, sum" => "votes"]
    User::filterAndGet($filters);
```

Another option is using the Eloquent relationships method:

```php
    $user = User::find(1)
    $filters = ["sum" => "votes"]
    $user->posts()->filterAndGet($filters);
```