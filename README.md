# LaraSPF
[![Latest Stable Version](https://poser.pugx.org/railgun1v9/lara-spf/v/stable)](https://packagist.org/packages/railgun1v9/lara-spf)
[![Total Downloads](https://poser.pugx.org/railgun1v9/lara-spf/downloads)](https://packagist.org/packages/railgun1v9/lara-spf)
[![Latest Unstable Version](https://poser.pugx.org/railgun1v9/lara-spf/v/unstable)](https://packagist.org/packages/railgun1v9/lara-spf)
[![License](https://poser.pugx.org/railgun1v9/lara-spf/license)](https://packagist.org/packages/railgun1v9/lara-spf)

Sorting, Pagination and Filtering for Laravel builders and collections utilizing query parameters of the URI.

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
