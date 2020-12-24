<?php

declare(strict_types=1);

namespace Telkins\Dag\Exceptions;

use Exception;

class TooManyHopsException extends Exception
{
    public static function make(int $maximumAllowableHops): TooManyHopsException
    {
        return new static("This operation exceeded the maximum allowable hops ({$maximumAllowableHops}).");
    }
}
