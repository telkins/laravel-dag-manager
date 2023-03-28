<?php

declare(strict_types=1);

namespace Telkins\Dag\Actions;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Telkins\Dag\Concerns\EdgeData;
use Telkins\Dag\Concerns\UsesDagConfig;

/**
 * Find and remove the specified direct edge and all dependent edge rows.
 */
class RemoveDagEdge
{
    use UsesDagConfig;

    public function __construct(
        private GetAllEdgeEntryIds $getAllEdgeEntryIds,
    ) {}

    public function __invoke(EdgeData $edgeData): bool
    {
        $edgeClass = $this->dagEdgeModel();

        $edge = $edgeClass::where([
            ['start_vertex', $edgeData->startVertex],
            ['end_vertex', $edgeData->endVertex],
            ['hops', 0],
            ['source', $edgeData->source],
        ])->first();

        if (! $edge) {
            return false;
        }

        return $this->removeDagEdge(
            ($this->getAllEdgeEntryIds)($edge, $edgeData->tableName, $edgeData->connection),
            $edgeData->tableName,
            $edgeData->connection,
        );
    }

    /**
     * Remove the specified direct edge and all dependent edge rows.
     */
    protected function removeDagEdge(Collection $edgeIds, string $tableName, ?string $connection): bool
    {
        if (! $edgeIds->count()) {
            return false;
        }

        DB::connection($connection)->table($tableName)
            ->whereIn('id', $edgeIds)
            ->delete();

        return true;
    }
}
