<?php

namespace Telkins\Dag\Models\Traits;

use InvalidArgumentException;

trait IsDagManaged
{
    /**
     * Scope a query to only include models descending from the specified model ID.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int|array $modelIds
     * @param string    $source
     */
    public function scopeDagDescendantsOf($query, $modelIds, string $source, ?int $maxHops = null)
    {
        $this->queryDagRelations($query, $modelIds, $source, true, $maxHops);
    }

    /**
     * Scope a query to only include models that are ancestors of the specified model ID.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int|array $modelIds
     * @param string    $source
     */
    public function scopeDagAncestorsOf($query, $modelIds, string $source, ?int $maxHops = null)
    {
        $this->queryDagRelations($query, $modelIds, $source, false, $maxHops);
    }

    /**
     * Scope a query to only include models that are related to (either descendants *or* ancestors) the specified model ID.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int|array $modelIds
     * @param string    $source
     */
    public function scopeDagRelationsOf($query, $modelIds, string $source, ?int $maxHops = null)
    {
        $this->queryDagRelations($query, $modelIds, $source, false, $maxHops);
        $this->queryDagRelations($query, $modelIds, $source, true, $maxHops, true);
    }

    /**
     * Scope a query to only include models that are relations of (descendants or ancestors) of the specified model ID.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int|array $modelIds
     * @param string    $source
     * @param bool      $down
     */
    protected function queryDagRelations($query, $modelIds, string $source, bool $down, ?int $maxHops = null, bool $or = false)
    {
        $modelIds = is_array($modelIds) ? $modelIds : [$modelIds];

        $this->guardAgainstInvalidModelIds($modelIds);

        $maxHops = $this->maxHops($maxHops);

        $method = $or ? 'orWhereIn' : 'whereIn';

        $query->$method($this->getQualifiedKeyName(), function ($query) use ($modelIds, $source, $maxHops, $down) {
            $selectField = $down ? 'start_vertex' : 'end_vertex';
            $whereField = $down ? 'end_vertex' : 'start_vertex';

            $query->select("dag_edges.{$selectField}")
                ->from('dag_edges')
                ->where([
                    ['dag_edges.source', $source],
                    ['dag_edges.hops', '<=', $maxHops],
                ])
                ->whereIn("dag_edges.{$whereField}", $modelIds);
                // ->when(is_array($modelIds), function ($query) use ($whereField, $modelIds) {
                //     return $query->whereIn("dag_edges.{$whereField}", $modelIds);
                // }, function ($query) use ($whereField, $modelIds) {
                //     return $query->where("dag_edges.{$whereField}", $modelIds);
                // });
        });
    }

    protected function guardAgainstInvalidModelIds($modelIds)
    {
        collect($modelIds)->each(function ($id) {
            if (! is_int($id)) {
                throw new InvalidArgumentException('Argument, $modelIds, must be an integer or an array of integers.');
            }
        });
    }

    protected function maxHops(?int $maxHops): int
    {
        $maxHopsConfig = config('laravel-dag-manager.max_hops');
        $maxHops = $maxHops ?? $maxHopsConfig; // prefer input over config
        $maxHops = min($maxHops, $maxHopsConfig); // no larger than config
        $maxHops = max($maxHops, 0); // no smaller than zero

        return $maxHops;
    }
}
