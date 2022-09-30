<?php

declare(strict_types=1);

namespace Telkins\Dag\Concerns;

trait UsesDagConfig
{
    public function defaultDatabaseConnectionName(): ?string
    {
        return config('laravel-dag-manager.default_database_connection_name') ?? config('database.default');
    }

    public function defaultTableName(): ?string
    {
        return config('laravel-dag-manager.table_name');
    }

    public function dagEdgeModel(): ?string
    {
        return config('laravel-dag-manager.edge_model');
    }
}
