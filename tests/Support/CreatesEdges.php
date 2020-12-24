<?php

declare(strict_types=1);

namespace Telkins\Dag\Tests\Support;

use Illuminate\Support\Collection;

trait CreatesEdges
{
    protected string $source = 'test-source';

    protected function createEdge(int $startVertex, int $endVertex, string $source = null): ?Collection
    {
        return dag()->createEdge($startVertex, $endVertex, ($source ?? $this->source));
    }
}
