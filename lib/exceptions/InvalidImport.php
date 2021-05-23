<?php

# Class InvalidImport
# Thrown when an import file is not valid.
class InvalidImport extends Exception
{
    private $errors = [];

    public function __construct($message = null, $code = 0, Throwable $previous = null, array $errors = []) {
        parent::__construct($message, $code, $previous);
        $this->errors = $errors;
    }

    # Method: getErrors
    # Gets the errors associated with the import
    #
    # Returns:
    # Array of errors returned by libxml.
    public function getErrors(): array {
        return $this->errors;
    }
}
