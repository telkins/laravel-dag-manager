<?php

declare(strict_types=1);

namespace Telkins\Dag\Models\Traits;

use Illuminate\Database\Eloquent\Builder;
use Telkins\Dag\Services\DagService;

/**
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait IsDagManaged
{
    /**
     * Scope a query to only include models descending from the specified model ID.
     *
     * @var int|array $modelIds
     */
    public function scopeDagDescendantsOf(Builder $query, $modelIds, string $source, ?int $maxHops = null)
    {
        $modelIds = $this->getIdsAsArray($modelIds);
        $this->getDagService()->queryDagRelationsForModel($query, $this, $modelIds, $source, true, $maxHops);
    }

    /**
     * Scope a query to only include models that are ancestors of the specified model ID.
     *
     * @var int|array $modelIds
     */
    public function scopeDagAncestorsOf(Builder $query, $modelIds, string $source, ?int $maxHops = null)
    {
        $modelIds = $this->getIdsAsArray($modelIds);
        $this->getDagService()->queryDagRelationsForModel($query, $this, $modelIds, $source, false, $maxHops);
    }

    /**
     * Scope a query to only include models that are related to (either descendants *or* ancestors) the specified model ID.
     *
     * @var int|array $modelIds
     */
    public function scopeDagRelationsOf(Builder $query, $modelIds, string $source, ?int $maxHops = null)
    {
        $modelIds = $this->getIdsAsArray($modelIds);
        $this->getDagService()->queryDagRelationsForModel($query, $this, $modelIds, $source, false, $maxHops);
        $this->getDagService()->queryDagRelationsForModel($query, $this, $modelIds, $source, true, $maxHops, true);
    }

    protected function getDagService(): DagService
    {
        return app(DagService::class);
    }

    private function getIdsAsArray($ids): array
    {
        return is_array($ids) ? $ids : [$ids];
    }
}
