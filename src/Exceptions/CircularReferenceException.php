<?php

namespace Telkins\Dag\Exceptions;

use Exception;

class CircularReferenceException extends Exception
{
    /**
     * Create a new exception instance.
     *
     * @param  string|null  $message
     * @param  mixed|null  $code
     * @param  \Exception|null  $previous
     * @return void
     */
    public function __construct($message = null, $code = null, Exception $previous = null)
    {
        parent::__construct($message ?? 'This operation caused a circular reference.', $code, $previous);
    }
}
