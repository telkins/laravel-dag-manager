<?php

namespace Telkins\Dag\Services;

use Exception;
use Telkins\Dag\Tasks\AddDagEdge;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Telkins\Dag\Tasks\RemoveDagEdge;

class DagService
{
    /** @var integer */
    protected $maxHops;

    /**
     * [__construct description]
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->maxHops = $config['max_hops'];
        $this->connection = $config['default_database_connection_name'];
    }

    /**
     * [createEdge description]
     *
     * @param  int         $startVertex [description]
     * @param  int         $endVertex   [description]
     * @param  string      $source      [description]
     * @param  string|null $connection  [description]
     * @return null|Collection
     * @throws \Telkins\Dag\Exceptions\CircularReferenceException
     * @throws \Telkins\Dag\Exceptions\TooManyHopsException
     */
    public function createEdge(int $startVertex, int $endVertex, string $source)
    {
        DB::connection($this->connection)->beginTransaction();
        try {
            $newEdges = (new AddDagEdge($startVertex, $endVertex, $source, $this->maxHops, $this->connection))->execute();

            DB::connection($this->connection)->commit();
        } catch (Exception $e) {
            DB::connection($this->connection)->rollBack();

            throw $e;
        }

        return $newEdges;
    }

    /**
     * [deleteEdge description]
     *
     * @param  int         $startVertex [description]
     * @param  int         $endVertex   [description]
     * @param  string      $source      [description]
     * @param  string|null $connection  [description]
     * @return bool
     */
    public function deleteEdge(int $startVertex, int $endVertex, string $source) : bool
    {
        DB::connection($this->connection)->beginTransaction();
        try {
            $removed = (new RemoveDagEdge($startVertex, $endVertex, $source, $this->connection))->execute();

            DB::connection($this->connection)->commit();
        } catch (Exception $e) {
            DB::connection($this->connection)->rollBack();

            throw $e;
        }

        return $removed;
    }
}
