<?php

namespace Telkins\Dag\Concerns;

trait UsesDagConfig
{
    public function defaultDatabaseConnectionName(): ?string
    {
        return config('laravel-dag-manager.default_database_connection_name') ?? config('database.default');
    }
}