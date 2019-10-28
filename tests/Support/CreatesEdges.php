<?php

namespace Telkins\Dag\Tests\Support;

trait CreatesEdges
{
    protected $source = 'test-source';

    protected function createEdge(int $startVertex, int $endVertex, string $source = null)
    {
        return dag()->createEdge($startVertex, $endVertex, ($source ?? $this->source));
    }
}
