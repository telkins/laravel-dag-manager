<?php

declare(strict_types=1);

namespace Telkins\Dag\Tests\Support;

use Illuminate\Database\Eloquent\Model;
use Telkins\Dag\Models\Traits\IsDagManaged;

class TestModel extends Model
{
    use IsDagManaged;

    protected $guarded = [];
}
