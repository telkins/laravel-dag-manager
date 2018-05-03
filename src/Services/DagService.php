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
    }

    /**
     * [createEdge description]
     *
     * @param  int        $startVertex
     * @param  int        $endVertex
     * @param  string     $source
     * @return null|Collection
     * @throws \Telkins\Dag\Exceptions\CircularReferenceException
     * @throws \Telkins\Dag\Exceptions\TooManyHopsException
     */
    public function createEdge(int $startVertex, int $endVertex, string $source)
    {
        DB::beginTransaction();
        try {
            $newEdges = (new AddDagEdge($startVertex, $endVertex, $source, $this->maxHops))->execute();

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();

            throw $e;
        }

        return $newEdges;
    }

    /**
     * [deleteEdge description]
     *
     * @param  int    $startVertex
     * @param  int    $endVertex
     * @param  string $source
     * @return bool
     */
    public function deleteEdge(int $startVertex, int $endVertex, string $source) : bool
    {
        DB::beginTransaction();
        try {
            $removed = (new RemoveDagEdge($startVertex, $endVertex, $source))->execute();

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();

            throw $e;
        }

        return $removed;
    }
}
