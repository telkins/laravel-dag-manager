<?php

declare(strict_types=1);

namespace Telkins\Dag\Actions;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Telkins\Dag\Concerns\EdgeData;
use Telkins\Dag\Concerns\UsesDagConfig;
use Telkins\Dag\Models\DagEdge;

class CreateImpliedEdges
{
    use UsesDagConfig;

    public function __invoke(DagEdge $edge, EdgeData $edgeData): void
    {
        $this->createAsIncomingEdgesToB($edge, $edgeData);

        $this->createAToBsOutgoingEdges($edge, $edgeData);

        $this->createAsIncomingEdgesToEndVertexOfBsOutgoingEdges($edge, $edgeData);
    }

    protected function createAsIncomingEdgesToB(DagEdge $edge, EdgeData $edgeData): void
    {
        $select = DB::connection($edgeData->connection)->table($edgeData->tableName)
            ->select([
                'id as entry_edge_id',
                DB::connection($edgeData->connection)->raw("{$edge->id} as direct_edge_id"),
                DB::connection($edgeData->connection)->raw("{$edge->id} as exit_edge_id"),
                'start_vertex',
                DB::connection($edgeData->connection)->raw("{$edgeData->endVertex} as end_vertex"),
                DB::connection($edgeData->connection)->raw('(hops + 1)  as hops'),
                DB::connection($edgeData->connection)->raw("'{$edgeData->source}' as source"),
            ])->where([
                ['end_vertex', $edgeData->startVertex],
                ['source', $edgeData->source],
            ]);

        $this->executeInsert($select, $edgeData);
    }

    protected function createAToBsOutgoingEdges(DagEdge $edge, EdgeData $edgeData): void
    {
        $select = DB::connection($edgeData->connection)->table($edgeData->tableName)
            ->select([
                DB::connection($edgeData->connection)->raw("{$edge->id} as entry_edge_id"),
                DB::connection($edgeData->connection)->raw("{$edge->id} as direct_edge_id"),
                'id as exit_edge_id',
                DB::connection($edgeData->connection)->raw("{$edgeData->startVertex} as start_vertex"),
                'end_vertex',
                DB::connection($edgeData->connection)->raw('(hops + 1)  as hops'),
                DB::connection($edgeData->connection)->raw("'{$edgeData->source}' as source"),
            ])->where([
                ['start_vertex', $edgeData->endVertex],
                ['source', $edgeData->source],
            ]);

        $this->executeInsert($select, $edgeData);
    }

    protected function createAsIncomingEdgesToEndVertexOfBsOutgoingEdges(DagEdge $edge, EdgeData $edgeData): void
    {
        $select = DB::connection($edgeData->connection)->table($edgeData->tableName.' as a')
            ->select([
                DB::connection($edgeData->connection)->raw('a.id as entry_edge_id'),
                DB::connection($edgeData->connection)->raw("{$edge->id} as direct_edge_id"),
                'b.id as exit_edge_id',
                'a.start_vertex',
                'b.end_vertex',
                DB::connection($edgeData->connection)->raw('(a.hops + b.hops + 2)  as hops'),
                DB::connection($edgeData->connection)->raw("'{$edgeData->source}' as source"),
            ])->crossJoin($edgeData->tableName.' as b')
            ->where([
                ['a.end_vertex', $edgeData->startVertex],
                ['b.start_vertex', $edgeData->endVertex],
                ['a.source', $edgeData->source],
                ['b.source', $edgeData->source],
            ]);

        $this->executeInsert($select, $edgeData);
    }

    protected function executeInsert(Builder $select, EdgeData $edgeData): void
    {
        $bindings = $select->getBindings();

        $insertQuery = 'INSERT into `'.$edgeData->tableName.'` (
            `entry_edge_id`,
            `direct_edge_id`,
            `exit_edge_id`,
            `start_vertex`,
            `end_vertex`,
            `hops`,
            `source`) '
            . $select->toSql();

        DB::connection($edgeData->connection)->insert($insertQuery, $bindings);
    }
}
