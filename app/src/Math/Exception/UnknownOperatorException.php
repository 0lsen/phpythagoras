<?php

namespace Math\Exception;


class UnknownOperatorException extends \Exception
{
    public function __construct($message = "", $code = 0, \Throwable $previous = null)
    {
        parent::__construct("Unknown Operator: " . $message, $code, $previous);
    }
}