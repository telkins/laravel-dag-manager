<?php

namespace Telkins\Dag\Tasks;

use Telkins\Dag\Models\DagEdge;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RemoveDagEdge
{
    /** @var string */
    protected $connection;

    /** @var int */
    protected $endVertex;

    /** @var string */
    protected $source;

    /** @var int */
    protected $startVertex;

    /**
     * @param int    $startVertex
     * @param int    $endVertex
     * @param string $source
     */
    public function __construct(int $startVertex, int $endVertex, string $source, ?string $connection)
    {
        $this->endVertex = $endVertex;
        $this->source = $source;
        $this->startVertex = $startVertex;
        $this->connection = $connection;
    }

    /**
     * Find and remove the specified direct edge and all dependent edge rows.
     *
     * @return bool
     */
    public function execute()
    {
        $edge = DagEdge::where([
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
     *
     * @param  DagEdge $edge
     * @return bool
     */
    protected function removeDagEdge(DagEdge $edge) : bool
    {
        $idsToDelete = $this->getIdsToDelete($edge);

        if (! $idsToDelete->count()) {
            return false;
        }

        DB::connection($this->connection)->table('dag_edges')
            ->whereIn('id', $idsToDelete)
            ->delete();

        return true;
    }

    /**
     * Find and return all edge table entries that need to be deleted.
     *
     * @param  DagEdge    $edge
     * @return Collection
     */
    protected function getIdsToDelete(DagEdge $edge) : Collection
    {
        /**
         * First, collect the "rows that were originally inserted...for this
         * direct edge".
         */
        $idsToDelete = DB::connection($this->connection)->table('dag_edges')
            ->select('id')
            ->where('direct_edge_id', $edge->id)
            ->get()
            ->pluck('id');

        /**
         * Next, "scan and find all dependent rows that were inserted
         * afterwards".
         */
        do {
            $moreDeleteIds = DB::connection($this->connection)->table('dag_edges')
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
