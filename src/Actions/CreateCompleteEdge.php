<?php

declare(strict_types=1);

namespace Telkins\Dag\Actions;

use Telkins\Dag\Concerns\EdgeData;
use Telkins\Dag\Concerns\UsesDagConfig;
use Telkins\Dag\Models\DagEdge;

class CreateCompleteEdge
{
    use UsesDagConfig;

    public function __construct(
        private CreateDirectEdge $createDirectEdge,
        private CreateImpliedEdges $createImpliedEdges,
    ) {}

    public function __invoke(EdgeData $edgeData): DagEdge
    {
        return tap(
            ($this->createDirectEdge)($edgeData),
            fn ($edge) => ($this->createImpliedEdges)($edge, $edgeData),
        );
    }
}
