<?php

use Telkins\Dag\Services\DagService;

if (! function_exists('dag')) {
    function dag() : DagService
    {
        return app(DagService::class);
    }
}
