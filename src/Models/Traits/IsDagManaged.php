<?php

namespace Telkins\Dag\Models\Traits;

trait IsDagManaged
{
    /**
     * Scope a query to only include models descending from the specified model ID.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int    $modelId
     * @param string $source
     */
    public function scopeDagDescendantsOf($query, int $modelId, string $source, ?int $maxHops = null)
    {
        $this->scopeDagRelationsOf($query, $modelId, $source, true, $maxHops);
    }

    /**
     * Scope a query to only include models that are ancestors of the specified model ID.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int    $modelId
     * @param string $source
     */
    public function scopeDagAncestorsOf($query, int $modelId, string $source, ?int $maxHops = null)
    {
        $this->scopeDagRelationsOf($query, $modelId, $source, false, $maxHops);
    }

    /**
     * Scope a query to only include models that are relations of (descendants or ancestors) of the specified model ID.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int    $modelId
     * @param string $source
     * @param bool.  $down
     */
    public function scopeDagRelationsOf($query, int $modelId, string $source, bool $down, ?int $maxHops = null)
    {
        $maxHopsConfig = config('laravel-dag-manager.max_hops');
        $maxHops = $maxHops ?? $maxHopsConfig;
        $maxHops = min($maxHops, $maxHopsConfig);

        $query->whereIn($this->getQualifiedKeyName(), function ($query) use ($modelId, $source, $maxHops, $down) {
            $selectField = $down ? 'start_vertex' : 'end_vertex';
            $whereField = $down ? 'end_vertex' : 'start_vertex';

            $query->select("dag_edges.{$selectField}")
                ->from('dag_edges')
                ->where([
                    ["dag_edges.{$whereField}", $modelId],
                    ['dag_edges.source', $source],
                    ['dag_edges.hops', '<=', $maxHops],
                ]);
        });
    }
}
