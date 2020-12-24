<?php

declare(strict_types=1);

namespace Telkins\Dag\Tasks;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Telkins\Dag\Exceptions\CircularReferenceException;
use Telkins\Dag\Exceptions\TooManyHopsException;
use Telkins\Dag\Models\DagEdge;

class AddDagEdge
{
    /** @var string */
    protected $connection;

    /** @var int */
    protected $endVertex;

    /** @var int */
    protected $maxHops;

    /** @var string */
    protected $source;

    /** @var int */
    protected $startVertex;

    /**
     * @param int $startVertex
     * @param int $endVertex
     * @param string $source
     * @param int $maxHops
     */
    public function __construct(int $startVertex, int $endVertex, string $source, int $maxHops, ?string $connection)
    {
        $this->endVertex = $endVertex;
        $this->source = $source;
        $this->startVertex = $startVertex;
        $this->maxHops = $maxHops;
        $this->connection = $connection;
    }

    /**
     * @throws CircularReferenceException
     * @throws TooManyHopsException
     */
    public function execute(): ?Collection
    {
        if ($this->edgeExists()) {
            return null;
        }

        $this->guardAgainstCircularRelation();

        $newEdges = $this->getNewlyInsertedEdges($this->createEdge());

        $this->guardAgainstExceedingMaximumHops($newEdges);

        return $newEdges;
    }

    /**
     * [edgeExists description]
     *
     * @return bool
     */
    protected function edgeExists(): bool
    {
        return DagEdge::where([
                ['start_vertex', $this->startVertex],
                ['end_vertex', $this->endVertex],
                ['hops', 0],
                ['source', $this->source],
            ])->count() > 0;
    }

    /**
     * @throws CircularReferenceException
     */
    protected function guardAgainstCircularRelation(): void
    {
        if ($this->startVertex === $this->endVertex) {
            throw new CircularReferenceException();
        }

        if (DagEdge::where([
                ['start_vertex', $this->endVertex],
                ['end_vertex', $this->startVertex],
                ['source', $this->source],
            ])->count() > 0) {
            throw new CircularReferenceException();
        }
    }

    protected function createEdge(): DagEdge
    {
        $edge = $this->createDirectEdge($this->startVertex, $this->endVertex, $this->source);

        $this->createAsIncomingEdgesToB($edge, $this->startVertex, $this->endVertex, $this->source);

        $this->createAToBsOutgoingEdges($edge, $this->startVertex, $this->endVertex, $this->source);

        $this->createAsIncomingEdgesToEndVertexOfBsOutgoingEdges($edge, $this->startVertex, $this->endVertex, $this->source);

        return $edge;
    }

    protected function createDirectEdge(): DagEdge
    {
        $edge = DagEdge::create([
            'start_vertex' => $this->startVertex,
            'end_vertex'   => $this->endVertex,
            'hops'         => 0,
            'source'       => $this->source,
        ]);

        $edge->update([
            'entry_edge_id'  => $edge->id,
            'exit_edge_id'   => $edge->id,
            'direct_edge_id' => $edge->id,
        ]);

        return $edge;
    }

    protected function createAsIncomingEdgesToB(DagEdge $edge): void
    {
        $select = DB::connection($this->connection)->table('dag_edges')
            ->select([
                'id as entry_edge_id',
                DB::connection($this->connection)->raw("{$edge->id} as direct_edge_id"),
                DB::connection($this->connection)->raw("{$edge->id} as exit_edge_id"),
                'start_vertex',
                DB::connection($this->connection)->raw("{$this->endVertex} as end_vertex"),
                DB::connection($this->connection)->raw('(hops + 1)  as hops'),
                DB::connection($this->connection)->raw("'{$this->source}' as source"),
            ])->where([
                ['end_vertex', $this->startVertex],
                ['source', $this->source],
            ]);

        $this->executeInsert($select);
    }

    protected function createAToBsOutgoingEdges(DagEdge $edge): void
    {
        $select = DB::connection($this->connection)->table('dag_edges')
            ->select([
                DB::connection($this->connection)->raw("{$edge->id} as entry_edge_id"),
                DB::connection($this->connection)->raw("{$edge->id} as direct_edge_id"),
                'id as exit_edge_id',
                DB::connection($this->connection)->raw("{$this->startVertex} as start_vertex"),
                'end_vertex',
                DB::connection($this->connection)->raw('(hops + 1)  as hops'),
                DB::connection($this->connection)->raw("'{$this->source}' as source"),
            ])->where([
                ['start_vertex', $this->endVertex],
                ['source', $this->source],
            ]);

        $this->executeInsert($select);
    }

    protected function createAsIncomingEdgesToEndVertexOfBsOutgoingEdges(DagEdge $edge): void
    {
        $select = DB::connection($this->connection)->table('dag_edges as a')
            ->select([
                DB::connection($this->connection)->raw('a.id as entry_edge_id'),
                DB::connection($this->connection)->raw("{$edge->id} as direct_edge_id"),
                'b.id as exit_edge_id',
                'a.start_vertex',
                'b.end_vertex',
                DB::connection($this->connection)->raw('(a.hops + b.hops + 2)  as hops'),
                DB::connection($this->connection)->raw("'{$this->source}' as source"),
            ])->crossJoin('dag_edges as b')
            ->where([
                ['a.end_vertex', $this->startVertex],
                ['b.start_vertex', $this->endVertex],
                ['a.source', $this->source],
                ['b.source', $this->source],
            ]);

        $this->executeInsert($select);
    }

    protected function executeInsert(Builder $select): void
    {
        $bindings = $select->getBindings();

        $insertQuery = 'INSERT into dag_edges (
            entry_edge_id,
            direct_edge_id,
            exit_edge_id,
            start_vertex,
            end_vertex,
            hops,
            source) '
            . $select->toSql();

        DB::connection($this->connection)->insert($insertQuery, $bindings);
    }

    protected function getNewlyInsertedEdges(DagEdge $edge): Collection
    {
        return DagEdge::where('direct_edge_id', $edge->id)
            ->orderBy('hops')
            ->get();
    }

    /**
     * @throws TooManyHopsException
     */
    protected function guardAgainstExceedingMaximumHops(Collection $newEdges): void
    {
        if ($newEdges->isNotEmpty() && ($newEdges->last()->hops > $this->maxHops)) {
            throw TooManyHopsException::make($this->maxHops);
        }
    }
}
