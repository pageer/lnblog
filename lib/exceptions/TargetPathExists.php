<?php

class TargetPathExists extends Exception
{
    public function __construct($message = null, $code = 0, Throwable $previous = null) {
        $default_message = _('Target path already exists');
        parent::__construct($message ?? $default_message, $code, $previous);
    }
}
