<?php

namespace LnBlog\Notifications;

use GlobalFunctions;

# Class: Notifier
# Handles sending user notifications, i.e. e-mailing people
#
# This functionality is centralized so that we can disable it for certain operations, such as imports.
class Notifier
{
    private static $is_enabled = true;
    private $globals;

    # Method: enabled
    # Globally set whether notifications are enabled.
    # 
    # When notifications are disabled, all calls will respond as if sending the message succeeded,
    # but no message will actually be sent.
    #
    # Parameters: 
    # is_enabled - Optional boolean for whether the setting should be enabled or not.
    #              If not given or null, the setting is not changed.
    #
    # Returns:
    # Boolean containing the current (i.e. new) enablement status.
    public function enabled(bool $is_enabled = null): bool {
        if ($is_enabled !== null) {
            self::$is_enabled = $is_enabled;
        }
        return self::$is_enabled;
    }

    public function __construct(GlobalFunctions $globals = null) {
        $this->globals = $globals ?: new GlobalFunctions();
    }

    # Method: sendEmail
    # Wrapper around the native mail() function, but with suppression support
    #
    # Parameters:
    # to - the "to" line for the mail
    # subject - the "subject" line for the mail
    # message - the main content of the mail
    # additional_headers - string of additional headers to send
    # additional_parameters - string of extra parameter to pass to the mail program
    #
    # Returns:
    # True on success, false on failure.
    public function sendEmail($to, $subject, $message, $additional_headers = '', $additional_parameters = ''): bool {
        if (!self::enabled()) {
            return true;
        }
        return @$this->globals->mail($to, $subject, $message, $additional_headers, $additional_parameters);
    }
}
