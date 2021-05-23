<?php

class EntryAlreadyExists extends Exception
{
    public function __construct($message = null, $code = 0, Throwable $previous = null) {
        $default_message = _('Entry already exists');
        parent::__construct($message ?? $default_message, $code, $previous);
    }
}
