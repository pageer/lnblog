<?php

namespace LnBlog\Controllers;
use System;

trait UserAuthorizationTrait
{
    protected function verifyUserIsLoggedIn($redirect = '', $message = '') {
        if (! $this->user->checkLogin()) {
            $this->redirectOr403($redirect, $message);
        }
    }

    protected function verifyUserCanModifyBlog($redirect = '', $message = '') {
        if (! $this->user->checkLogin() || ! System::instance()->canModify($this->blog, $this->user)) {
            $this->redirectOr403($redirect, $message);
            return false;
        }
        return true;
    }
}
