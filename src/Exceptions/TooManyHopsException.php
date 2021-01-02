<?php

declare(strict_types=1);

namespace Telkins\Dag\Exceptions;

use Exception;

class TooManyHopsException extends Exception
{
    /** @todo Change return to static once on PHP 8. */
    public static function make(int $maximumAllowableHops): self
    {
        return new static("This operation exceeded the maximum allowable hops ({$maximumAllowableHops}).");
    }
}
