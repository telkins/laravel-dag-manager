<?php

declare(strict_types=1);

namespace Telkins\Dag\Actions;

use Illuminate\Support\Collection;
use Telkins\Dag\Concerns\EdgeData;
use Telkins\Dag\Concerns\UsesDagConfig;
use Telkins\Dag\Exceptions\CircularReferenceException;
use Telkins\Dag\Exceptions\TooManyHopsException;
use Telkins\Dag\Models\DagEdge;

class AddDagEdge
{
    use UsesDagConfig;

    public function __construct(
        private CreateCompleteEdge $createCompleteEdge,
    ) {}

    /**
     * @throws CircularReferenceException
     * @throws TooManyHopsException
     */
    public function __invoke(EdgeData $edgeData, int $maxHops): ?Collection
    {
        if ($this->edgeExists($edgeData)) {
            return null;
        }

        $this->guardAgainstCircularRelation($edgeData);

        return tap(
            $this->getNewlyInsertedEdges(($this->createCompleteEdge)($edgeData)),
            fn ($newEdges) => $this->guardAgainstExceedingMaximumHops($newEdges, $maxHops),
        );
    }

    protected function edgeExists(EdgeData $edgeData): bool
    {
        $edgeClass = $this->dagEdgeModel();

        return $edgeClass::where([
                ['start_vertex', $edgeData->startVertex],
                ['end_vertex', $edgeData->endVertex],
                ['hops', 0],
                ['source', $edgeData->source],
            ])->count() > 0;
    }

    /**
     * @throws CircularReferenceException
     */
    protected function guardAgainstCircularRelation(EdgeData $edgeData): void
    {
        if ($edgeData->startVertex === $edgeData->endVertex) {
            throw new CircularReferenceException();
        }

        $edgeClass = $this->dagEdgeModel();

        if ($edgeClass::where([
                ['start_vertex', $edgeData->endVertex],
                ['end_vertex', $edgeData->startVertex],
                ['source', $edgeData->source],
            ])->count() > 0) {
            throw new CircularReferenceException();
        }
    }

    protected function getNewlyInsertedEdges(DagEdge $edge): Collection
    {
        $edgeClass = $this->dagEdgeModel();

        return $edgeClass::where('direct_edge_id', $edge->id)
            ->orderBy('hops')
            ->get();
    }

    /**
     * @throws TooManyHopsException
     */
    protected function guardAgainstExceedingMaximumHops(Collection $newEdges, int $maxHops): void
    {
        if ($newEdges->isNotEmpty() && ($newEdges->last()->hops > $maxHops)) {
            throw TooManyHopsException::make($maxHops);
        }
    }
}
