<?php

namespace Telkins\Dag\Exceptions;

use Exception;

class TooManyHopsException extends Exception
{
    /**
     * Create a new exception instance.
     *
     * @param  int $maximumAllowableHops
     * @return static
     */
    public static function make(int $maximumAllowableHops)
    {
        return new static("This operation exceeded the maximum allowable hops ({$maximumAllowableHops}).");
    }
}
