<?php

namespace Telkins\Dag\Models\Traits;

use InvalidArgumentException;

trait IsDagManaged
{
    /**
     * Scope a query to only include models descending from the specified model ID.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int|array $modelId
     * @param string    $source
     */
    public function scopeDagDescendantsOf($query, $modelId, string $source, ?int $maxHops = null)
    {
        $this->scopeDagRelationsOf($query, $modelId, $source, true, $maxHops);
    }

    /**
     * Scope a query to only include models that are ancestors of the specified model ID.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int|array $modelId
     * @param string    $source
     */
    public function scopeDagAncestorsOf($query, $modelId, string $source, ?int $maxHops = null)
    {
        $this->scopeDagRelationsOf($query, $modelId, $source, false, $maxHops);
    }

    /**
     * Scope a query to only include models that are relations of (descendants or ancestors) of the specified model ID.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int|array $modelId
     * @param string    $source
     * @param bool      $down
     */
    public function scopeDagRelationsOf($query, $modelId, string $source, bool $down, ?int $maxHops = null)
    {
        if (! is_int($modelId) && ! is_array($modelId)) {
            throw new InvalidArgumentException('Argument, $modelId, must be of type integer or array.');
        }

        $maxHopsConfig = config('laravel-dag-manager.max_hops');
        $maxHops = $maxHops ?? $maxHopsConfig; // prefer input over config
        $maxHops = min($maxHops, $maxHopsConfig); // no larger than config
        $maxHops = max($maxHops, 0); // no smaller than zero

        $query->whereIn($this->getQualifiedKeyName(), function ($query) use ($modelId, $source, $maxHops, $down) {
            $selectField = $down ? 'start_vertex' : 'end_vertex';
            $whereField = $down ? 'end_vertex' : 'start_vertex';

            $query->select("dag_edges.{$selectField}")
                ->from('dag_edges')
                ->where([
                    ['dag_edges.source', $source],
                    ['dag_edges.hops', '<=', $maxHops],
                ])
                ->when(is_array($modelId), function ($query) use ($whereField, $modelId) {
                    return $query->whereIn("dag_edges.{$whereField}", $modelId);
                }, function ($query) use ($whereField, $modelId) {
                    return $query->where("dag_edges.{$whereField}", $modelId);
                });
        });
    }
}
