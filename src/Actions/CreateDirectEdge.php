<?php

declare(strict_types=1);

namespace Telkins\Dag\Actions;

use Telkins\Dag\Concerns\EdgeData;
use Telkins\Dag\Concerns\UsesDagConfig;
use Telkins\Dag\Models\DagEdge;

class CreateDirectEdge
{
    use UsesDagConfig;

    public function __invoke(EdgeData $edgeData): DagEdge
    {
        return tap(
            $this->createEdge($edgeData),
            fn ($edge) => $this->updateNewEdge($edge),
        );
    }

    private function createEdge(EdgeData $edgeData): DagEdge
    {
        $edgeClass = $this->dagEdgeModel();

        return $edgeClass::create([
            'start_vertex' => $edgeData->startVertex,
            'end_vertex'   => $edgeData->endVertex,
            'hops'         => 0,
            'source'       => $edgeData->source,
        ]);
    }

    private function updateNewEdge(DagEdge $edge): void
    {
        $edge->update([
            'entry_edge_id'  => $edge->id,
            'exit_edge_id'   => $edge->id,
            'direct_edge_id' => $edge->id,
        ]);
    }
}
