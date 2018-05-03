# A SQL-based Directed Acyclic Graph (DAG) solution for Laravel.

[![Latest Stable Version](https://poser.pugx.org/telkins/laravel-dag-manager/v/stable)](https://packagist.org/packages/telkins/laravel-dag-manager)
[![Total Downloads](https://poser.pugx.org/telkins/laravel-dag-manager/downloads)](https://packagist.org/packages/telkins/laravel-dag-manager)
[![License](https://poser.pugx.org/telkins/laravel-dag-manager/license)](https://packagist.org/packages/telkins/laravel-dag-manager)

This package allows you to create, persist, and remove directed acyclic graphs.

* [Basic Usage](#basic-usage)
* [Installation](#installation)
* [Usage](#usage)
* [Warning](#warning)
* [Unit Testing](#unit-testing)
* [Additional Notes](#additional-notes)
* [Changelog](#changelog)
* [Contributing](#contributing)
* [Security](#security)
* [Credits](#credits)
* [License](#license)

## Basic Usage

Creating a direct edge:

```php
$newEdges = dag()->createEdge($startVertex, $endVertex, $source);
// $newEdges contains all new edges, including the specified direct edge, that were created as a result of the request.
```

Deleting a direct edge:

```php
$deleted = dag()->deleteEdge($startVertex, $endVertex, $source);
// $deleted is true if any edges were deleted as a result of the request, false otherwise.
```

## Installation

This package can be used in Laravel 5.6 or higher.

You can install the package via composer:

``` bash
composer require telkins/laravel-dag-manager
```

In Laravel 5.6 the service provider will automatically get registered.

You can optionally publish the config file with:
```bash
php artisan vendor:publish --provider="Telkins\Dag\Providers\DagServiceProvider" --tag="config"
```

This is the contents of the published config file:
```php
return [

    /*
    |--------------------------------------------------------------------------
    | Max Hops
    |--------------------------------------------------------------------------
    |
    | This value represents the maximum number of hops that are allowed where
    | hops "[i]ndicates how many vertex hops are necessary for the path; it is
    | zero for direct edges".
    |
    | The more hops that are allowed (and used), then the more DAG edges will
    | be created.  This will have an increasing impact on performance, space,
    | and memory.  Whether or not it's negligible, noticeable, or impactful
    | depends on a variety of factors.
    */

    'max_hops' => 5,
];
```

## Warning

From Kemal Erdogan's article, ["A Model to Represent Directed Acyclic Graphs (DAG) on SQL Databases"](https://www.codeproject.com/Articles/22824/A-Model-to-Represent-Directed-Acyclic-Graphs-DAG-o):

>In theory, the size of the transitive closure set of a fair DAG can be very large with this model, well beyond the millions. The maximum number of edges for a given DAG itself is a research topic in Graph Theory, but my practical tests show that there exist DAGs with 100 vertices and 300 edges whose transitive closure would create well beyond 20,000,000 rows with this algorithm.

Please be mindful of this when creating "excessively" large and/or complex graphs.

## Usage

tbd

## Unit Testing

tbd

## Additional Notes

Contributors may want to consider leveraging any of the following:
* [relaxedws/lca](https://github.com/relaxedws/lca): A PHP Library to find Lowest Common ancestor from a Directed Acyclic Graph.
* [clue/graph](https://github.com/clue/graph): A mathematical graph/network library written in PHP.
* [graphp/algorithms](https://github.com/graphp/algorithms): Graph algorithms in PHP, a collection of common (and not so common) ones.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

tbd

## Credits

Lots of credit goes to Kemal Erdogan and his article entitled, ["A Model to Represent Directed Acyclic Graphs (DAG) on SQL Databases"](https://www.codeproject.com/Articles/22824/A-Model-to-Represent-Directed-Acyclic-Graphs-DAG-o).  Additional credit: [xib/DAG_MySQL.sql](https://gist.github.com/xib/21786eeaa970911f0693)

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
