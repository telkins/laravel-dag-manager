<?php

declare(strict_types=1);

namespace Telkins\Dag\Actions;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Telkins\Dag\Concerns\UsesDagConfig;
use Telkins\Dag\Models\DagEdge;

/**
 * Find and return all edge table entry IDs for a given edge.
 */
class GetAllEdgeEntryIds
{
    use UsesDagConfig;

    public function __invoke(DagEdge $edge, string $tableName, ?string $connection): Collection
    {
        /**
         * First, collect the "rows that were originally inserted...for this
         * direct edge".
         */
        $originalIds = DB::connection($connection)->table($tableName)
            ->select('id')
            ->where('direct_edge_id', $edge->id)
            ->get()
            ->pluck('id');

        /**
         * Next, "scan and find all dependent rows that were inserted
         * afterwards".
         */
        do {
            $dependentIds = DB::connection($connection)->table($tableName)
                ->select('id')
                ->where('hops', '>', 0)
                ->whereNotIn('id', $originalIds)
                ->where(function ($query) use ($originalIds) {
                    $query->whereIn('entry_edge_id', $originalIds)
                        ->orWhereIn('exit_edge_id', $originalIds);
                })
                ->get()
                ->pluck('id');

            $originalIds = $originalIds->merge($dependentIds);
        } while ($dependentIds->count());

        return $originalIds;
    }
}
