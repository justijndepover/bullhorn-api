<?php

namespace Justijndepover\Bullhorn\Exceptions;

use Exception;

class CouldNotAquireBhRestTokenException extends Exception
{
    public static function make(string $code, string $message): self
    {
        return new static("Error $code: Could not aquire Bullhorn rest token: $message", $code);
    }
}
