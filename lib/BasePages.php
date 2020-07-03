<?php
/*
    LnBlog - A simple file-based weblog focused on design elegance.
    Copyright (C) 2017 Peter A. Geer <pageer@skepticats.com>

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

use Psr\Log\LoggerInterface;

abstract class BasePages {

    const CSRF_TOKEN = 'xrf_tok';
    const TOKEN_POST_FIELD = 'xrf-token';

    protected $fs;
    protected $globals;

    abstract protected function getActionMap();
    abstract protected function defaultAction();

    public function __construct(FS $fs = null, GlobalFunctions $globals = null, LoggerInterface $logger = null) {
        $this->fs = $fs ?: NewFS();
        $this->globals = $globals ?: new GlobalFunctions();
        $this->logger = $logger ?: NewLogger();
    }

    public function routeRequestWithDefault($default_action) {
        $action = GET('action');
        $action_map = $this->getActionMap();
        if (!isset($action_map[$action])) {
            $action = $default_action;
        }
        $this->routeRequest($action);
    }

    public function routeRequest($action = null) {
        $action = $action ?: GET('action');
        $has_post_data = has_post() || !empty($_FILES);
        $allowedActions = $this->getCsrfWhitelist();
        if ($has_post_data && !in_array($action, $allowedActions)) {
            try {
                $this->validateCsrfToken($_POST);
                $this->validateOriginHeaders($_SERVER);
            } catch (CsrfDetected $e) {
                $this->getPage()->error(400);
            }
        }
        return $this->handleRequestRouting($action);
    }

    public function getCsrfWhitelist() {
        return ['webmention'];
    }

    public function getCsrfToken() {
        if (empty($_SESSION[self::CSRF_TOKEN])) {
            $_SESSION[self::CSRF_TOKEN] = hash('sha256', random_int(PHP_INT_MIN, PHP_INT_MAX));
        }
        return $_SESSION[self::CSRF_TOKEN];
    }

    protected function handleRequestRouting($action) {
        $action_map = $this->getActionMap();
        $action = $action ?: GET('action');
        $action_method = isset($action_map[$action]) ? $action_map[$action] : null;

        if (is_array($action_method) && count($action_method) === 2) {

            list($class, $method) = $action_method;
            $object = new $class();
            return $object->$method();

        } elseif ($action_method) {

            return $this->$action_method();

        } elseif ( isset($_GET['script']) ) {

            $file = $this->scriptPath($_GET['script']);
            $this->dumpAssetFile($file, "// Failed to find $file");
            return true;

        } elseif ( isset($_GET['style']) ) {

            $file = $this->stylePath($_GET['style']);
            $this->dumpAssetFile($file, "/* Failed to find $file */");
            return true;

        } elseif ( isset($_GET['plugin']) ) {

            define("PLUGIN_DO_OUTPUT", true);
            $plugin_name = preg_replace('[^A-Za-z0-9_\-]', '', $_GET['plugin']);
            $paths = array(@BLOG_ROOT, USER_DATA_PATH, INSTALL_ROOT);
            foreach ($paths as $path) {
                $plugin = Path::mk($path, 'plugins', "$plugin_name.php");
                if ($this->fs->file_exists($plugin)) {
                    require $plugin;
                    return true;
                }
            }
        }

        $this->defaultAction();
    }

    # Attempt to log in.  If the user is locked out, just totally bail out.
    protected function attemptLogin(User $user, $password) {
        $template = NewTemplate("user_lockout_tpl.php");
        $template->set('USER', $user);
        try {
            return $user->login($password);
        } catch (UserAccountLocked $locked_out) {
            $template->set('MODE', 'email');
            $this->globals->mail(
                $user->email(), 
                _("LnBlog account locked"),
                $template->process(),
                "From: LnBlog notifier <".EMAIL_FROM_ADDRESS.">"
            );
            $template->set('MODE', 'new');
            header("HTTP/1.0 429 Too Many Requests");
            echo $template->process();
            exit;
        } catch (UserLockedOut $locked) {
            $template->set('MODE', 'existing');
            header("HTTP/1.0 429 Too Many Requests");
            echo $template->process();
            exit;
        }
    }

    protected function getThemeAssetPath($type, $name) {
        if ( defined("BLOG_ROOT") &&
             file_exists(BLOG_ROOT."/$type/$name") ) {

            return BLOG_ROOT."/$type/$name";

        # Second case: Try the userdata directory
        } elseif ( defined('THEME_NAME') && defined('USER_DATA_PATH') &&
                   file_exists(USER_DATA_PATH.'/themes/'.THEME_NAME."/$type/$name") ) {
            return USER_DATA_PATH."/themes/".THEME_NAME."/$type/$name";

        # Third case: check the current theme directory
        } elseif ( defined('INSTALL_ROOT') && defined('THEME_NAME') &&
                   file_exists(INSTALL_ROOT."/themes/".THEME_NAME."/$type/$name") ) {
            return INSTALL_ROOT."/themes/".THEME_NAME."/$type/$name";

        # Fourth case: try the default theme
        } elseif ( defined('INSTALL_ROOT') &&
                   file_exists(INSTALL_ROOT."/themes/default/$type/$name") ) {
            return INSTALL_ROOT."/themes/default/$type/$name";

        # Last case: nothing found, so return the original string.
        } else {
            return $name;
        }
    }

    protected function getStylePath($name) {
        return $this->getThemeAssetPath("styles", $name);
    }

    protected function scriptPath($name) {
        return $this->getThemeAssetPath("scripts", $name);
    }

    protected function validateCsrfToken($post_data) {
        $context = ['uri' => $_SERVER['REQUEST_URI'] ?? ''];
        if (empty($post_data[self::TOKEN_POST_FIELD])) {
            $this->logger->warning("CSRF token not present in POST data", $context);
            throw new CsrfDetected("Token is missing");
        }

        $csrf_token = $this->getCsrfToken();
        if ($post_data[self::TOKEN_POST_FIELD] != $csrf_token) {
            $this->logger->error("CSRF token present but not valid", $context);
            throw new CsrfDetected("Token not valid");
        }
    }

    protected function validateOriginHeaders($server_vars) {
        $context = ['uri' => $server_vars['REQUEST_URI'] ?? ''];
        $target_origin_domain = '';
        $source_origin_domain = '';

        if (defined('BLOG_ROOT_URL')) {
            $target_origin_domain = parse_url(BLOG_ROOT_URL, PHP_URL_HOST);
        } elseif (!empty($server_vars['HTTP_HOST'])) {
            $target_origin_domain = $server_vars['HTTP_HOST'];
        } elseif (!empty($server_vars['HTTP_X_FORWARDED_HOST'])) {
            $target_origin_domain = $server_vars['HTTP_X_FORWARDED_HOST'];
        }

        if (!empty($server_vars['HTTP_ORIGIN'])) {
            $source_origin_domain = parse_url($server_vars['HTTP_ORIGIN'], PHP_URL_HOST);
        } elseif (!empty($server_vars['HTTP_REFERER'])) {
            $source_origin_domain = parse_url($server_vars['HTTP_REFERER'], PHP_URL_HOST);
        }

        if (!$source_origin_domain && $this->shouldBlockOnMissingOrigin()) {
            $this->logger->error("No request origin was found, blocking request", $context);
            throw new CsrfDetected("Request origin not found");
        }

        if ($source_origin_domain && $source_origin_domain !== $target_origin_domain) {
            $context += ['source' => $source_origin_domain, 'target' => $target_origin_domain];
            $this->logger->error("Origins do not match", $context);
            throw new CsrfDetected("Origin mismatch");
        }
    }

    private function shouldBlockOnMissingOrigin() {
        if ($this->globals->defined("BLOCK_ON_MISSING_ORIGIN")) {
            return $this->globals->constant("BLOCK_ON_MISSING_ORIGIN");
        }
        return true;
    }

    protected function createTemplate(string $template_name) {
        return new PHPTemplate($template_name, $this);
    }

    protected function getPage() {
        return Page::instance();
    }

    private function dumpAssetFile($file, $default) {
        if ($this->fs->file_exists($file)) {
           $this->fs->readfile($file);
        } else {
            $this->fs->echo_to_output($default);
        }
    }
}
