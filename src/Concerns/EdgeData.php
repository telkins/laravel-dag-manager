<?php

declare(strict_types=1);

namespace Telkins\Dag\Concerns;

use Spatie\LaravelData\Data;

class EdgeData extends Data
{
    public function __construct(
        public int $startVertex,
        public int $endVertex,
        public string $source,
        public string $tableName,
        public ?string $connection = null,
    ) {
    }
}
