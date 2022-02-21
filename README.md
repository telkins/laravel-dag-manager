# A SQL-based Directed Acyclic Graph (DAG) solution for Laravel

[![Latest Stable Version](https://poser.pugx.org/telkins/laravel-dag-manager/v/stable)](https://packagist.org/packages/telkins/laravel-dag-manager)
![run tests](https://github.com/telkins/laravel-dag-manager/workflows/run%20tests/badge.svg)
[![Total Downloads](https://poser.pugx.org/telkins/laravel-dag-manager/downloads)](https://packagist.org/packages/telkins/laravel-dag-manager)
[![License](https://poser.pugx.org/telkins/laravel-dag-manager/license)](https://packagist.org/packages/telkins/laravel-dag-manager)

This package allows you to create, persist, and remove directed acyclic graphs.

* [Basic Usage](#basic-usage)
* [Installation](#installation)
* [Usage](#usage)
* [Warning](#warning)
* [Testing](#testing)
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

This package requires PHP 7.2 or higher as well as Laravel 6.0 or higher.

You can install the package via composer:

```bash
composer require telkins/laravel-dag-manager
```

The package will automatically register itself.

You can publish the migration with:

```bash
php artisan vendor:publish --provider="Telkins\Dag\Providers\DagServiceProvider" --tag="migrations"
```

Note: The default migration assumes you are using integers for your DAG edge IDs.

You can optionally publish the config file with:
```bash
php artisan vendor:publish --provider="Telkins\Dag\Providers\DagServiceProvider" --tag="config"
```

This is the contents of the published config file:
```php
return [
    /**
     *-------------------------------------------------------------------------
     * Max Hops
     *-------------------------------------------------------------------------
     *
     * This value represents the maximum number of hops that are allowed where
     * hops "[i]ndicates how many vertex hops are necessary for the path; it is
     * zero for direct edges".
     *
     * The more hops that are allowed (and used), then the more DAG edges will
     * be created.  This will have an increasing impact on performance, space,
     * and memory.  Whether or not it's negligible, noticeable, or impactful
     * depends on a variety of factors.
     */
    'max_hops' => 5,

    /**
     *-------------------------------------------------------------------------
     * Default Database Connection Name
     *-------------------------------------------------------------------------
     *
     * This is the name of the database connection where the dag table
     * can be found.
     *
     * Set to `null` to use the default connection.
     */
    'default_database_connection_name' => null,

    /**
     *-------------------------------------------------------------------------
     * Table Name
     *-------------------------------------------------------------------------
     *
     * This is the name of the table where the dag structure
     * will be stored.
     */
    'table_name' => 'dag_edges',
];
```

## Warning

From Kemal Erdogan's article, ["A Model to Represent Directed Acyclic Graphs (DAG) on SQL Databases"](https://www.codeproject.com/Articles/22824/A-Model-to-Represent-Directed-Acyclic-Graphs-DAG-o):

>In theory, the size of the transitive closure set of a fair DAG can be very large with this model, well beyond the millions. The maximum number of edges for a given DAG itself is a research topic in Graph Theory, but my practical tests show that there exist DAGs with 100 vertices and 300 edges whose transitive closure would create well beyond 20,000,000 rows with this algorithm.

Please be mindful of this when creating "excessively" large and/or complex graphs.

## Usage

For Eloquent models that are "DAG managed", you can add the `Telkins\Models\Traits\IsDagManaged` trait:
```php
use Illuminate\Database\Eloquent\Model;
use Telkins\Models\Traits\IsDagManaged;

class MyModel extends Model
{
    use IsDagManaged;

    // ...
}
```

This will allow you to easily access certain functionality from your model class.

To apply a scope that only includes models that are descendants of the specified model ID:
```php
$descendants = MyModel::dagDescendantsOf($myModel->id, 'my-source')->get();
```

An ID and source must be provided.

Likewise, to apply a scope that only includes models that are ancestors of the specified model ID:
```php
$ancestors = MyModel::dagAncestorsOf($myModel->id, 'my-source')->get();
```

Again, an ID and source must be provided.

Finally, one can apply a scope that will get both ancestors and descendants:
```php
$ancestors = MyModel::dagRelationsOf($myModel->id, 'my-source')->get();
```

Each of the aforementioned methods also allow the caller to constrain the results based on the number of hops.  So, if you want to get the immediate children of the specified model ID, then you could do the following:
```php
$descendants = MyModel::dagDescendantsOf($myModel->id, 'my-source', 0)->get();
```

And, of course, in order to get the parents and grandparents of the specified model ID, you could do the following:
```php
$ancestors = MyModel::dagAncestorsOf($myModel->id, 'my-source', 1)->get();
```

Not providing the `$maxHops` parameter means that all descendants, ancestors, or relations will be returned.

### Custom DAG edge model

You can use your own model class if you need to customise the behaviour of the DAG edge model.

Your custom model class must extend the `Telkins\Models\DagEdge` class:

```php
namespace App\Models;

use Telkins\Models\DagEdge as BaseModel;

class MyDagEdge extends BaseModel
{
...
```

You can then specify the fully qualified class name of your custom model in the package config file.

```php
// config/laravel-dag-manager.php
...
'edge_model' => \App\Models\MyDagEdge::class,
...
```

## Testing

```bash
composer test
```

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

- [Travis Elkins](https://github.com/telkins)
- [All contributors](../../contributors)

Additionally:
- Kemal Erdogan and his article entitled, ["A Model to Represent Directed Acyclic Graphs (DAG) on SQL Databases"](https://www.codeproject.com/Articles/22824/A-Model-to-Represent-Directed-Acyclic-Graphs-DAG-o).
- [xib](https://github.com/xib) and his MySQL stored procedures port (which is not currently used, but may be in a future version): [xib/DAG_MySQL.sql](https://gist.github.com/xib/21786eeaa970911f0693)

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
