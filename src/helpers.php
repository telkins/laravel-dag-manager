<?php

declare(strict_types=1);

use Telkins\Dag\Services\DagService;

if (! function_exists('dag')) {
    function dag(): DagService
    {
        return app(DagService::class);
    }
}
