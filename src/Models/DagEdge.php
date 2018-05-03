<?php

namespace Telkins\Dag\Models;

use Illuminate\Database\Eloquent\Model;

class DagEdge extends Model
{
    protected $fillable = [
        'entry_edge_id',
        'direct_edge_id',
        'exit_edge_id',
        'start_vertex',
        'end_vertex',
        'hops',
        'source',
    ];

    protected $casts = [
        'entry_edge_id' => 'int',
        'direct_edge_id' => 'int',
        'exit_edge_id' => 'int',
        'start_vertex' => 'int',
        'end_vertex' => 'int',
        'hops' => 'int',
    ];
}
