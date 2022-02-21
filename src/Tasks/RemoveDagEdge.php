<?php

declare(strict_types=1);

namespace Telkins\Dag\Tasks;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Telkins\Dag\Models\DagEdge;

class RemoveDagEdge
{
    protected ?string $connection;
    protected int $endVertex;
    protected string $source;
    protected int $startVertex;
    protected string $tableName;

    public function __construct(int $startVertex, int $endVertex, string $source, string $tableName, ?string $connection = null)
    {
        $this->startVertex = $startVertex;
        $this->endVertex = $endVertex;
        $this->source = $source;
        $this->tableName = $tableName;
        $this->connection = $connection;
    }

    /**
     * Find and remove the specified direct edge and all dependent edge rows.
     */
    public function execute(): bool
    {
        $edgeClass = config('laravel-dag-manager.edge_model');

        $edge = $edgeClass::where([
            ['start_vertex', $this->startVertex],
            ['end_vertex', $this->endVertex],
            ['hops', 0],
            ['source', $this->source],
        ])->first();

        if (! $edge) {
            return false;
        }

        return $this->removeDagEdge($edge);
    }

    /**
     * Remove the specified direct edge and all dependent edge rows.
     */
    protected function removeDagEdge(DagEdge $edge): bool
    {
        $idsToDelete = $this->getIdsToDelete($edge);

        if (! $idsToDelete->count()) {
            return false;
        }

        DB::connection($this->connection)->table($this->tableName)
            ->whereIn('id', $idsToDelete)
            ->delete();

        return true;
    }

    /**
     * Find and return all edge table entries that need to be deleted.
     */
    protected function getIdsToDelete(DagEdge $edge): Collection
    {
        /**
         * First, collect the "rows that were originally inserted...for this
         * direct edge".
         */
        $idsToDelete = DB::connection($this->connection)->table($this->tableName)
            ->select('id')
            ->where('direct_edge_id', $edge->id)
            ->get()
            ->pluck('id');

        /**
         * Next, "scan and find all dependent rows that were inserted
         * afterwards".
         */
        do {
            $moreDeleteIds = DB::connection($this->connection)->table($this->tableName)
                ->select('id')
                ->where('hops', '>', 0)
                ->whereNotIn('id', $idsToDelete)
                ->where(function ($query) use ($idsToDelete) {
                    $query->whereIn('entry_edge_id', $idsToDelete)
                        ->orWhereIn('exit_edge_id', $idsToDelete);
                })
                ->get()
                ->pluck('id');

            $idsToDelete = $idsToDelete->merge($moreDeleteIds);
        } while ($moreDeleteIds->count());

        return $idsToDelete;
    }
}
