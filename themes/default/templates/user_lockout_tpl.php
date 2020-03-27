<?php

use LnBlog\User\LoginLimiter;

# Template: user_lockout_tpl.php
# Handles display of messages relating to the user being locked out of
# logging into their account.

$this->block('lockout.web_new', function ($vars) {
    extract($vars);
    p_("There have been too many failed login attempts.  This account is now locked.");
});

$this->block('lockout.web_existing', function ($vars) {
    extract($vars);
    p_("This account is locked.  Please try again later.");
});

$this->block('lockout.email', function ($vars) {
    extract($vars);
    pf_("Your LnBlog account %s has been locked out due to too many login attempts.\n\nYou will be able to log in again in %d minutes.", $USER->username(), LoginLimiter::TIME_LIMIT/60);
});

$this->block('main', function ($vars) {
    extract($vars);
    $mode = isset($MODE) ? $MODE : 'existing';
    switch ($mode) {
        case 'email':
            $this->showBlock('lockout.email');
            break;
        case 'new':
            $this->showBlock('lockout.web_new');
            break;
        case 'existing':
        default:
            $this->showBlock('lockout.web_existing');
            break;
    }
});
