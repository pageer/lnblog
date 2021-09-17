<?php
/*
    LnBlog - A simple file-based weblog focused on design elegance.
    Copyright (C) 2005 Peter A. Geer <pageer@skepticats.com>

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
*/

use LnBlog\Attachments\AttachmentContainer;
use LnBlog\Attachments\FileManager;
use LnBlog\User\AuthLog;
use LnBlog\User\LoginLimiter;

# Class: User
# Class to manipulate and authenticate users.  All details of user
# management, including checking authentication, should be done through
# this class.
#
# Inherits:
# <LnBlogObject>

class User extends LnBlogObject implements AttachmentContainer
{
    const USER_PROFILE_FILE = "user.xml";
    const RESET_RATE_LIMIT = 60;
    const RESET_TOKEN_EXPIRATION = 84600;

    public $username = '';
    public $passwd = '';
    public $salt = false;
    public $fullname = '';
    public $email = '';
    public $homepage = '';
    public $profile_page = '';
    public $default_group = '';
    public $custom = array();

    private $login_forced = false;
    private $set_cookies = true;
    private $fs = null;
    private $globals = null;
    private $limiter = null;
    private $file_manager = null;

    public static function get($usr=false, $pwd=false, FS $fs = null) {
        $s_usr = SESSION(CURRENT_USER);
        $c_usr = COOKIE(CURRENT_USER);
        if (!$usr && $c_usr) {
            $globals = new GlobalFunctions();
            $use_session = $globals->constant('AUTH_USE_SESSION');
            if ($s_usr == $c_usr || (! $use_session && $s_usr == '') ) {
                $usr = $c_usr;
            }
        }

        if ($usr && isset($_SESSION["user-".$usr])) {
            return new User($usr);
        } else {
            $user = new User($usr, $fs);
            $user->login($pwd);
            return $user;
        }
    }

    public static function logged_in() {
        $user = self::get();
        return $user->checkLogin();
    }

    public function __construct(
        $uname=false,
        FS $fs = null,
        GlobalFunctions $globals = null,
        LoginLimiter $limiter = null,
        FileManager $file_manager = null
    ) {
        $this->username = $uname ? $uname : '';
        $this->fs = $fs ?: NewFS();
        $this->globals = $globals ?: new GlobalFunctions();
        $this->limiter = $limiter ?: new LoginLimiter($this, $this->fs, $this->globals);
        $this->file_manager = $file_manager ?: new FileManager($this, $this->fs);

        $this->exclude_fields = array('salt', 'passwd', 'username');

        $this->loadUserCredentials();
    }

    # Method: exists
    # Determines if the object represents an existing, registered user.
    #
    # Returns:
    # True if the user exists, false otherwise.
    public function exists($uname=false) {
        if (! $uname) {
            $uname = $this->username;
        }
        return $this->fs->realpath($this->getPath("passwd.php", $uname)) &&
               ( $this->fs->realpath($this->getPath(self::USER_PROFILE_FILE, $uname)) ||
                 $this->fs->realpath($this->getPath("user.ini", $uname)) );
    }

    # Method: profileUrl
    # Get the URL to the user's profile page.
    #
    # Parameters:
    # url - The new value to set.  If missing, just return current value.
    public function profileUrl($url = null) {
        if ($url !== null) {
            $this->profile_page = $url;
        } else {
            return $this->profile_page;
        }
    }


    # Method: getProfileUrl
    # Get the configured or default URL for the user's profile.
    public function getProfileUrl() {
        $url = $this->profileUrl();
        return $url ?: $this->defaultProfileUrl();
    }

    # Method: exportVars
    # Convenience function to export relevant user data to a template.
    # Sets the username, full name, e-mail, homepage, and display name in
    # template variables USER_ID, USER_NAME, USER_EMAIL, USER_HOMEPAGE,
    # and USER_DISPLAY_NAME respectively.
    #
    # Parameters:
    # tpl - The template to put the data in, passed by reference.
    public function exportVars($tpl) {
        if ($this->username) {
            $tpl->set("USER_ID", $this->username);
        }
        if ($this->fullname) {
            $tpl->set("USER_NAME", $this->fullname);
        }
        if ($this->email) {
            $tpl->set("USER_EMAIL", $this->email);
        }
        if ($this->default_group) {
            $tpl->set("DEFAULT_GROUP", $this->defaultGroup());
        }
        $tpl->set("GROUPS", $this->groups());
        if (strpos($this->homepage, "://") === false &&
            trim($this->homepage) != "") {
            $this->homepage = "http://".$this->homepage;
        }
        if ($this->homepage) {
            $tpl->set("USER_HOMEPAGE", $this->homepage);
        }
        $tpl->set("USER_DISPLAY_NAME", $this->displayName());
        $tpl->set("PROFILE_LINK", $this->getProfileUrl());
    }

    public function authenticateCredentials($password) {
        if (!$this->limiter->canLogIn($wait = false)) {
            throw new UserLockedOut();
        }

        if (!$this->exists()) {
            return false;
        }

        $result = $this->checkPassword($password);
        $time = new DateTime('@' . $this->globals->time());

        $log = $result ? 
            AuthLog::success($time, get_ip(), get_user_agent()) :
            AuthLog::failure($time, get_ip(), get_user_agent());

        $this->limiter->logAttempt($log);

        if (!$this->limiter->canLogIn($wait = true)) {
            throw new UserAccountLocked();
        }

        return $result;
    }

    # Method: checkPassword
    # Checks if a password is valid for the current user.
    #
    # Parameters:
    # pass - The password to check.
    #
    # Returns:
    # True if the password is correct, false owtherwise.
    private function checkPassword($pass) {
        if (!trim($pass)) {
            return false;
        }
        if (!$this->isNewFormatPasswordFile()) {
            $hash = md5($pass.$this->salt);
            return $hash == $this->passwd;
        }
        return password_verify($pass, $this->passwd);
    }

    # Method: createPasswordReset
    # Create a passoword reset code for the user.
    #
    # Returns:
    # String containing the reset code.
    public function createPasswordReset() {
        $timestamp = $this->globals->time();
        $reset_token = sha1(random_int(PHP_INT_MIN, PHP_INT_MAX));
        $tokens = $this->getPasswordResetTokens();

        foreach ($tokens as $token) {
            if ($token['timestamp'] + self::RESET_RATE_LIMIT > $timestamp) {
                throw new RateLimitExceeded();
            }
        }

        $tokens[] = [
            'timestamp' => $timestamp,
            'token' => $reset_token,
        ];
        $this->savePasswordResetTokens($tokens);
        return $reset_token;
    }

    # Method: verifyPasswordReset
    # Checks that a supplied password reset code is valid.
    #
    # Parameters:
    # code - String containing the reset code to validate
    #
    # Returns:
    # True if the code is acceptable, false otherwise.
    public function verifyPasswordReset($code) {
        $current_time = $this->globals->time();
        $token_list = $this->getPasswordResetTokens();
        foreach ($token_list as $entry) {
            $code_is_valid = $entry['token'] == $code;
            $token_not_expired = $current_time < $entry['timestamp'] + self::RESET_TOKEN_EXPIRATION;
            if ($code_is_valid && $token_not_expired) {
                return true;
            }
        }
        return false;
    }

    # Method: invalidatePasswordReset
    # Deletes the record of a particular password reset code.  This is intended
    # to be called after a code is used successfully.
    #
    # Parameters:
    # code - String containing the code to invalidate.
    public function invalidatePasswordReset($code) {
        $current_time = $this->globals->time();
        $new_token_list = [];
        $token_list = $this->getPasswordResetTokens();
        foreach ($token_list as $entry) {
            $is_invalidated_token = $code == $entry['token'];
            $is_expired = $entry['timestamp'] + self::RESET_TOKEN_EXPIRATION < $current_time;
            if (!$is_invalidated_token && !$is_expired) {
                $new_token_list[] = $entry;
            }
        }
        $this->savePasswordResetTokens($new_token_list);
    }

    # Method: save
    # Save changes to user data.
    #
    # Returns:
    # True if the changes were successfully saved, false otherwise.
    public function save() {
        if (!$this->username ||! $this->passwd) {
            return false;
        }

        $data = "<?php\nreturn '" . $this->passwd . "';\n";
        if (! $this->fs->is_dir($this->getPath(''))) {
            $ret = $this->fs->mkdir($this->getPath(''));
            if (! $ret) {
                return $ret;
            }
        }
        $ret = $this->fs->write_file($this->getPath("passwd.php"), $data);
        $data = $this->serializeXML();
        $ret = $this->fs->write_file($this->getPath(self::USER_PROFILE_FILE), $data);
        if ($ret) {
            $_SESSION["user-".$this->username] = serialize($this);
        }
        return $ret;
    }

    # Method: password
    # Set or return the user's password>
    #
    # Parameters:
    # pwd - *Optional* password to set.
    #
    # Returns:
    # If pwd is false, return the user's password hash.  Otherwise, a new
    # password is set and there is no return value.
    public function password($pwd=false) {
        if (!$pwd) {
            return $this->passwd;
        } else {
            $this->passwd = password_hash($pwd, PASSWORD_DEFAULT);

            # Prevent password change from logging out on cookie-only config.
            if ( $this->username == COOKIE($this->config('CURRENT_USER')) ) {
                $expire_time = $this->config('LOGIN_EXPIRE_TIME');
                $this->setCookie(
                    $this->config('PW_HASH'), $this->passwd,
                    ($expire_time ? time() + $expire_time : false)
                );
            }
        }
    }

    # Method: username
    # Set or return the username.
    #
    # Parameters:
    # uid - *Optional* username to set.
    #
    # Returns:
    # The username if uid is false, otherwise the username is set and
    # there is no return value.
    public function username($uid=false) {
        if (!$uid) {
            return $this->username;
        } else {
            $this->username = $uid;
        }
    }

    # Method: name
    # Set or return the user's long name.
    #
    # Parameters:
    # nm - *Optional* name to set.
    #
    # Returns:
    # The user's full name if nm is false, otherwise the name is set and
    # there is no return value.
    public function name($nm=false) {
        if (!$nm) {
            return $this->fullname;
        } else {
            $this->fullname = $nm;
        }
    }

    # Method: displayName
    # Returns a name to display.  If a full name is defined, it uses that.
    # Otherwise, it reverts to the username.
    #
    # Returns:
    # A string contianing either the username or full name.
    public function displayName() {
        if ($this->fullname) {
            return $this->fullname;
        } else {
            return $this->username;
        }
    }

    # Method: email
    # Set or return the user's e-mail address.
    #
    # Parameters:
    # mail - *Optional* e-mail to set.
    #
    # Returns:
    # The user's e-mail address if mail is false, otherwise the address
    #  is set and there is no return value.
    public function email($mail=false) {
        if (!$mail) {
            return $this->email;
        } else {
            $this->email = $mail;
        }
    }

    # Method: homepage
    # Set or return the user's homepage.
    #
    # Parameters:
    # url - *Optional* URL to set as the homepage.
    #
    # Returns:
    # The user's homepage URL if url is false, otherwise the homepage
    # is set and there is no return value.
    public function homepage($url=false) {
        if (!$url) {
            return $this->homepage;
        } else {
            $this->homepage = $url;
        }
    }

    # Method: addToGroup
    # Adds the user to the specified group.
    #
    # Parameters:
    # groupname - The name of the group.
    #
    # Returns:
    # True on success, false on failure.
    public function addToGroup($groupname) {
        return System::instance()->addToGroup($this, $groupname);
    }

    # Method: login
    # Logs the user in.
    # Note that there are two login methods available, with the one used
    # being determined by the <AUTH_USE_SESSION> configuration constant.
    #
    # Parameters:
    # pwd - The password used to log in.
    # time - Current time, injectable for testing purposes.
    #
    # Returns:
    # False if the authentication fails, true otherwise.
    public function login($pwd) {
        # Reject empty usernames or passwords.
        if ( trim($this->username) == "" || trim($pwd) == "" ) {
            return false;
        }

        $current_user = $this->config('CURRENT_USER');
        $login_token = $this->config('LOGIN_TOKEN');
        $last_login_time = $this->config('LAST_LOGIN_TIME');
        $login_expire_time = $this->config('LOGIN_EXPIRE_TIME');
        $pw_hash = $this->config('PW_HASH');

        $time = $this->globals->time();
        $expire_time = $login_expire_time ? $time + $login_expire_time : false;
        $ts = gmdate("M d Y H:i:s", $time);

        if (!$this->authenticateCredentials($pwd)) {
            return false;
        }

        # If we have the old password format, convert on successful login
        if (! $this->isNewFormatPasswordFile()) {
            $this->passwd = password_hash($pwd, PASSWORD_DEFAULT);
            $this->salt = false;
            $this->save();
        }

        if ($this->config('AUTH_USE_SESSION')) {
            # Create a login token.
            $token = md5($this->getTokenLockComponent() . $ts);
            $_SESSION[$current_user] = $this->username;
            $_SESSION[$login_token] = $token;
            $_SESSION[$last_login_time] = $ts;
            $this->setCookie($last_login_time, "$ts", $expire_time);
            $this->setCookie($current_user, $this->username, $expire_time);
            $this->setCookie($login_token, $token, $expire_time);
        } else {
            $this->setCookie($current_user, $this->username, $expire_time);
            $this->setCookie($pw_hash, md5($this->passwd . $this->getTokenLockComponent()), $expire_time);
        }
        return true;
    }

    # Method: logout
    # Logs the user out and destroys login tokens.
    # Note that this is also subject to <AUTH_USE_SESSION>
    public function logout() {
        $current_user = $this->config('CURRENT_USER');
        $login_token = $this->config('LOGIN_TOKEN');
        $last_login_time = $this->config('LAST_LOGIN_TIME');
        $login_expire_time = $this->config('LOGIN_EXPIRE_TIME');
        $pw_hash = $this->config('PW_HASH');
        if ($this->config('AUTH_USE_SESSION')) {
            unset($_SESSION[$current_user]);
            unset($_SESSION[$login_token]);
            unset($_SESSION[$last_login_time]);
            $this->setCookie($current_user, "", time() - 3600);
            $this->setCookie($login_token, "", time() - 3600);
            $this->setCookie($last_login_time, "", time() - 3600);
        } else {
            $this->setCookie($current_user, "", time() - 3600);
            $this->setCookie($pw_hash, "", time() - 3600);
        }
    }

    # Method: forceLoggedIn
    # Force the account to be in a logged-in state.
    #
    # This should *only* be used in contexts where the normal login process doesn't apply,
    # such as running from the command-line.
    #
    # Parameters:
    # status - Boolean indicating if the user should be logged in.
    public function forceLoggedIn(bool $status) {
        $this->login_forced = $status;
    }

    # Method: checkLogin
    # Checks tokens to determine if the user is logged in.
    #
    # Parameters:
    # uname - *Optional* username to check.
    #
    # Returns:
    # True if the user has valid login tokens, false otherwise.
    public function checkLogin($uname=false) {
        # If the constructor doesn't detect a user name, then we're obviously
        # not logged in.
        if (!$this->username) {
            return false;
        }

        # This is for command-line usage, where the session/cookie stuff doesn't apply.
        if ($this->login_forced) {
            return true;
        }

        $current_user = $this->config('CURRENT_USER');
        $login_token = $this->config('LOGIN_TOKEN');
        $last_login_time = $this->config('LAST_LOGIN_TIME');
        $login_expire_time = $this->config('LOGIN_EXPIRE_TIME');
        $pw_hash = $this->config('PW_HASH');

        $auth_ok = false;

        if ($this->config('AUTH_USE_SESSION')) {
            # Check the stored login token and time against the one for the
            # current session.  Return false on failure, or if the current
            # session doesn't belong to the user we want.
            $cookie_ts = COOKIE($last_login_time);
            $auth_token = md5($this->getTokenLockComponent() . $cookie_ts);
            $auth_ok = ($auth_token == SESSION($login_token) );
            $auth_ok = $auth_ok && ($cookie_ts == SESSION($last_login_time) );
            if ($uname) {
                $auth_ok = $auth_ok && ($this->username == $uname);
            }
        } else {
            # Check the cookies for the user and password hash and compare to
            # the password hash for this user on the server.
            # This is NOT secure, but it is convenient.
            $usr = COOKIE($current_user);
            $pwhash = COOKIE($pw_hash);
            $auth_ok = ($this->username == $usr &&
                        md5($this->passwd . $this->getTokenLockComponent()) == $pwhash);
        }

        return $auth_ok;
    }

    # Method: isAdministrator
    # Determines if the user is the system administrator.
    #
    # Returns:
    # True if the username is the same as that of the system administrator,
    # false otherwise.  Note that the system administrator's username
    # is controlled by the <ADMIN_USER> configuration constant.
    public function isAdministrator() {
        return ($this->username == $this->config('ADMIN_USER') ||
                System::instance()->inGroup($this->username, 'administrators'));
    }

    # Method: enableCookies
    # Used primarily for testing, sets whether cookies actually get set.
    public function enableCookies($enabled) {
        $this->set_cookies = $enabled;
    }

    public function getAttachments() {
        return $this->file_manager->getAll();
    }

    public function addAttachment($path, $name = '') {
        $this->file_manager->attach($path, $name);
    }

    public function removeAttachment($name) {
        $this->file_manager->remove($name);
    }

    public function getManagedFiles() {
        return ['user.xml', 'passwd.php', 'profile.htm', 'logins.json'];
    }

    public function localpath() {
        return $this->getPath('');
    }

    private function getPasswordResetTokens() {
        $file_path = $this->getPath('pwreset.php');
        if ($this->fs->file_exists($file_path)) {
            $token_list = $this->globals->include($file_path);
            if (is_array($token_list)) {
                return $token_list;
            }
        }
        return [];
    }

    private function savePasswordResetTokens($tokens) {
        $file_path = $this->getPath('pwreset.php');
        if (empty($tokens)) {
            $this->fs->delete($file_path);
        } else {
            $content = "<?php\n";
            $content .= "return " . var_export($tokens, true) . ";\n";
            $this->fs->write_file($file_path, $content);
        }
    }

    # Method: loadUserCredentials
    # Load the user's password and any metadata.
    private function loadUserCredentials() {
        $passwd_path = $this->getPath("passwd.php");
        if (!$this->username || !$this->fs->realpath($passwd_path)) {
            return;
        }

        $defined_vars = ['pwd' => '', 'salt' => ''];
        $password_hash = $this->globals->include($this->getPath("passwd.php"), $defined_vars);
        if ($password_hash === 1) {
            $this->passwd = $defined_vars['pwd'];
            $this->salt = $defined_vars['salt'];
        } else {
            $this->passwd = $password_hash;
            $this->salt = false;
        }

        $xmlfile = $this->fs->realpath($this->getPath(self::USER_PROFILE_FILE));
        $inifile = $this->fs->realpath($this->getPath("user.ini"));

        if ($xmlfile) {
            $this->deserializeXML($xmlfile);
        } elseif ($inifile) {
            $ini = NewIniParser($inifile);
            $this->fullname = $ini->value("userdata", "name", "");
            $this->email    = $ini->value("userdata", "email", "");
            $this->homepage = $ini->value("userdata", "homepage", "");
            $this->default_group =
                $ini->value(
                    'userdata', 'default_group',
                    System::instance()->sys_ini->value(
                        'security',
                        'NewUserDefaultGroup',
                        ''
                    )
                );
            $this->custom = $ini->getSection("customdata");
        }
        $_SESSION["user-".$this->username] = serialize($this);
    }

    private function defaultProfileUrl() {
        $blog = NewBlog();
        if ($this->fs->file_exists($this->getPath("index.php"))) {
            $qs = $blog->isBlog() ? array('blog'=>$blog->blogid) : false;
            $resolver = new UrlResolver();
            $ret = $resolver->localpathToUri($this->getPath(''));
        } else {
            $qs = array("action" => "profile", "user"=>$this->username);
            if ($blog->isBlog()) {
                $qs['blog'] = $blog->blogid;
            }
            $ret = make_uri($this->config('INSTALL_ROOT_URL')."index.php", $qs);
        }
        return $ret;
    }

    private function groups() {
        return System::instance()->getGroups($this->username);
    }

    private function defaultGroup($val=false) {
        if ($val) {
            $this->default_group = $val;
        } else {
            return $this->default_group;
        }
    }

    private function setCookie($name, $value = '', $expires = 0) {
        $force_https_login = $this->config('FORCE_HTTPS_LOGIN');
        $force_https = $this->config('FORCE_HTTPS_LOGIN');
        $domain_name = $this->ifConfig('DOMAIN_NAME', '');
        $can_send_over_protocol = !$force_https_login || SERVER('HTTPS');
        if ($this->set_cookies && $can_send_over_protocol) {
            $domain = $domain_name ? ("." . $domain_name) : '';
            $this->globals->setcookie($name, $value, $expires, "/", $domain, $force_https);
        }
    }

    private function isNewFormatPasswordFile() {
        # In the new format, the salt is included in the password hash.
        return $this->salt === false;
    }

    private function getTokenLockComponent() {
        return $this->config('LOGIN_IP_LOCK') ? get_ip() : get_user_agent();
    }

    private function getPath($file, $username = '') {
        $user_data_path = SystemConfig::instance()->userData()->path();
        if (!$username) {
            $username = $this->username;
        }
        return Path::mk($user_data_path, $username, $file);
    }

    private function config($var) {
        return $this->globals->constant($var);
    }

    private function ifConfig($var, $default) {
        if ($this->globals->defined($var)) {
            return $this->globals->constant($var);
        }
        return $default;
    }
}
