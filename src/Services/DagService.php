<?php

declare(strict_types=1);

namespace Telkins\Dag\Services;

use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Telkins\Dag\Tasks\AddDagEdge;
use Telkins\Dag\Tasks\RemoveDagEdge;

use function collect;
use function is_int;
use function max;
use function min;

class DagService
{
    protected int $maxHops;
    private ?string $connection;
    private string $tableName;

    public function __construct(array $config)
    {
        $this->maxHops = $config['max_hops'];
        $this->connection = $config['default_database_connection_name'];
        $this->tableName = $config['table_name'];
    }

    /**
     * @throws \Telkins\Dag\Exceptions\CircularReferenceException
     * @throws \Telkins\Dag\Exceptions\TooManyHopsException
     */
    public function createEdge(int $startVertex, int $endVertex, string $source): ?Collection
    {
        DB::connection($this->connection)->beginTransaction();
        try {
            $newEdges = (new AddDagEdge($startVertex, $endVertex, $source, $this->maxHops, $this->tableName, $this->connection))->execute();

            DB::connection($this->connection)->commit();
        } catch (Exception $e) {
            DB::connection($this->connection)->rollBack();

            throw $e;
        }

        return $newEdges;
    }

    public function deleteEdge(int $startVertex, int $endVertex, string $source): bool
    {
        DB::connection($this->connection)->beginTransaction();
        try {
            $removed = (new RemoveDagEdge($startVertex, $endVertex, $source, $this->tableName, $this->connection))->execute();

            DB::connection($this->connection)->commit();
        } catch (Exception $e) {
            DB::connection($this->connection)->rollBack();

            throw $e;
        }

        return $removed;
    }

    /**
     * Scope a query to only include models that are relations of (descendants or ancestors) of the specified model ID.
     */
    public function queryDagRelationsForModel(
        Builder $query,
        Model $model,
        array $modelIds,
        string $source,
        bool $down,
        ?int $maxHops = null,
        bool $or = false
    ): void {
        $this->guardAgainstInvalidModelIds($modelIds);

        $maxHops = $this->maxHops($maxHops);

        $method = $or ? 'orWhereIn' : 'whereIn';

        $query->$method($model->getQualifiedKeyName(), function ($query) use ($modelIds, $source, $maxHops, $down) {
            $selectField = $down ? 'start_vertex' : 'end_vertex';
            $whereField = $down ? 'end_vertex' : 'start_vertex';

            $query->select("{$this->tableName}.{$selectField}")
                ->from($this->tableName)
                ->where([
                    [$this->tableName.'.source', $source],
                    [$this->tableName.'.hops', '<=', $maxHops],
                ])
                ->whereIn("{$this->tableName}.{$whereField}", $modelIds);
            // ->when(is_array($modelIds), function ($query) use ($whereField, $modelIds) {
            //     return $query->whereIn("{$this->tableName}.{$whereField}", $modelIds);
            // }, function ($query) use ($whereField, $modelIds) {
            //     return $query->where("{$this->tableName}.{$whereField}", $modelIds);
            // });
        });
    }

    protected function guardAgainstInvalidModelIds(array $modelIds): void
    {
        collect($modelIds)->each(function ($id) {
            if (! is_int($id)) {
                throw new InvalidArgumentException('Argument, $modelIds, must be an integer or an array of integers.');
            }
        });
    }

    protected function maxHops(?int $maxHops): int
    {
        $maxHopsConfig = $this->maxHops;
        $maxHops = $maxHops ?? $maxHopsConfig; // prefer input over config
        $maxHops = min($maxHops, $maxHopsConfig); // no larger than config
        $maxHops = max($maxHops, 0); // no smaller than zero

        return $maxHops;
    }
}
