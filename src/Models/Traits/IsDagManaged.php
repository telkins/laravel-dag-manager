<?php

namespace Telkins\Dag\Models\Traits;

trait IsDagManaged
{
    /**
     * Scope a query to only include models descending from the specified model ID.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $modelId
     * @param string $source
     * @param string $order
     * @param bool $distinct
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDagDescendantsOf($query, int $modelId, string $source, string $order = 'asc', bool $distinct = true)
    {
        $query->whereIn($this->getQualifiedKeyName(), function ($query) use ($modelId, $source, $order, $distinct) {
            if ($distinct) {
                $query->distinct();
            }

            $query->select('dag_edges.start_vertex')
                ->from('dag_edges')
                ->where([
                    ['dag_edges.end_vertex', $modelId],
                    ['dag_edges.source', $source],
                ]);

            if ($order && in_array($order, ['asc', 'desc'])) {
                $query->orderBy('dag_edges.hops', $order);
            }
        });
    }
}
