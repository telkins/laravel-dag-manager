<?php

declare(strict_types=1);

namespace Telkins\Dag\Exceptions;

use Exception;
use Throwable;

class CircularReferenceException extends Exception
{
    public function __construct(string $message = '', int $code = 0, ?Throwable $previous = null)
    {
        $message = ! empty($message) ? $message : 'This operation caused a circular reference.';

        parent::__construct($message, $code, $previous);
    }
}
