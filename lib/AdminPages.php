<?php
/*
    LnBlog - A simple file-based weblog focused on design elegance.
    Copyright (C) 2005-2011 Peter A. Geer <pageer@skepticats.com>

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
require_once __DIR__.'/../pages/pagelib.php';

class AdminPages extends BasePages {

    protected function getActionMap() {
        return array(
            'index' => 'index',
            'login' => 'bloglogin',
            'logout' => 'bloglogout',
            'newblog' => 'newblog',
            'newlogin' => 'newlogin',
            'pluginload' => 'pluginloading',
            'plugins' => 'pluginsetup',
            'profile' => 'userinfo',
            'fssetup' => 'fssetup',
            'editfile' => [WebPages::class, 'editfile'],
            'useredit' => [WebPages::class, 'editlogin'],
            'webmention' => [WebPages::class, 'webmention'],
        );
    }

    protected function defaultAction() {
        return $this->index();
    }

    protected function redirect($action, $params = array()) {
        $params['action'] = $action;

        $blog = NewBlog();
        if ($blog->isBlog()) {
            $params['blog'] = $blog->blogid;
        }

        $url = "";
        foreach ($params as $key => $val) {
            $url .= ($url ? '&' : '?') . $key . '=' . urlencode($val);
        }

        Page::instance()->redirect($url);
    }

    private function show_confirm_page($title, $message, $page, $yes_id, $yes_label, $no_id, $no_label, $data_id, $data) {
        $tpl = $this->createTemplate('confirm_tpl.php');
        $tpl->set('CONFIRM_TITLE', $title);
        $tpl->set('CONFIRM_MESSAGE', $message);
        $tpl->set('CONFIRM_PAGE', $page);
        $tpl->set('OK_ID', $yes_id);
        $tpl->set('OK_LABEL', $yes_label);
        $tpl->set('CANCEL_ID', $no_label);
        $tpl->set('CANCEL_LABEL', $no_label);
        $tpl->set('PASS_DATA_ID', $data_id);
        $tpl->set('PASS_DATA', $data);
        $form = $tpl->process();
        Page::instance()->display($form);
        exit;
    }

    # Method: index
    # The page for the main administration menu.
    #
    # This page has a number of administration options, some of which are
    # vestigial.  The options are as follows.
    #
    # Modify site-wide menubar        - Modifies the "site map" in the menu bar.
    # Add new user                    - Creates a new user.
    # Add new blog                    - Creates a new weblog.
    # Update blog data                - Upgrade format of blog storage files.
    # Upgrade blog to current version - Recreate wrapper scripts for a blog.
    # Fix directory permissions       - Make blog file permissions sane.
    # Log out                         - Log out of the admin pages.
    #
    # Of the above options, fixing directory permissions and updating blog data
    # are largely unused.  Directory permissions are only relevant when switching
    # file writing methods, and even then this option isn't too useful.
    #
    # Updating blog data applies mostly to a previous change in the storage
    # format.  It is possible that this will be used again in the future, but
    # there are no plans for it.
    #
    # The upgrading to the current version, on the other hand, is more relevant.
    # It is used to create a new set of wrapper scripts in the standard setup.
    # "Wrappers scripts", as used by LnBlog, are simply very small PHP scripts
    # (usually less than five lines) that do nothing but use the PHP include()
    # statement to execute another script.  The purpose of these scripts is to
    # make all functions relevant to a blog accessible under the root blog URL,
    # thus giving a clean URL structure to the entire blog.  Since new features
    # requiring new pages are added from time to time, this upgrade function
    # will probably be used once every few upgrades, depending on the changes
    # between releases.
    #
    # It is also important to note that the event system includes
    # OnUpgrade and UpgradeComplete events for the <Blog> object.  These events
    # are raised when this upgrade feature is run, so that plugins may perform
    # any needed updates at that time.
    public function index() {

        if ( ! file_exists(USER_DATA_PATH.PATH_DELIM.FS_PLUGIN_CONFIG) ) {
            $this->redirect("fssetup");
            exit;
        }

        $update =  "update";
        $upgrade = "upgrade";
        Page::instance()->title = sprintf(_("%s Administration"), PACKAGE_NAME);

        $tpl = $this->createTemplate('blog_admin_tpl.php');
        $tpl->set("SHOW_NEW");
        $tpl->set("FORM_ACTION", current_file());

        # Check if there is at least one administrator.
        # If not, then we need to create one.

        $usr = NewUser();

        if (! System::instance()->hasAdministrator()) {
            $this->redirect("newlogin");
            exit;
        } elseif (! $usr->checkLogin() || ! $usr->isAdministrator()) {
            $this->redirect("login");
            exit;
        }

        if ( POST('upgrade') && POST('upgrade_btn') ) {

            $b = NewBlog(POST('upgrade'));
            $file_list = $b->upgradeWrappers();
            if (empty($file_list)) {
                $status = spf_("Upgrade of %s completed successfully.",
                               $b->blogid);
            } elseif ($file_list === false) {
                $status = spf_("Error: %s does not seem to exist.", $b->blogid);
            } else {
                $status = spf_("Error: The following file could not be written - %s.",
                               implode("<br />", $file_list));
            }
            $tpl->set("UPGRADE_STATUS", $status);

        } elseif ( POST('register') && POST('register_btn') ) {
            $blog = NewBlog(POST('register'));
            if (! $blog->isBlog()) {
                $status = spf_("The path '%s' is not an LnBlog weblog.", POST('register'));
            } else {
                $ret = System::instance()->registerBlog($blog->blogid);
                if ($ret) $status = spf_("Blog %s successfully registered.", $blog->blogid);
                else $status = spf_("Registration error: exited with code %s", $ret);
            }
            $tpl->set("REGISTER_STATUS", $status);

        } elseif ( POST('delete') && POST('delete_btn') ) {

            if (POST('confirm_form') || GET('confirm')) {

                $blog = NewBlog(POST('delete'));
                if (! $blog->isBlog()) {
                    $status = spf_("The path '%s' is not an LnBlog weblog.", POST('delete'));
                } else {
                    $ret = System::instance()->unregisterBlog($blog->blogid);
                    $ret = $ret && $blog->delete();
                    if ($ret) {
                        $status = spf_("Blog %s successfully deleted.", $blog->blogid);
                    } else {
                        $status = spf_("Delete error: exited with code %s", $ret);
                    }
                }
                $tpl->set("DELETE_STATUS", $status);

            } else {
                //-- Check whether this is safe...
                $this->show_confirm_page(_("Confirm blog deletion"), spf_("Really delete blog '%s'?", POST('delete')), current_file(),
                                  'delete_btn', _('Yes'), 'cancel_btn', _('No'), 'delete', POST('delete'));
            }
        } elseif ( POST('fixperm') && POST('fixperm_btn') ) {
            $p = new Path();
            if ($p->isAbsolute(POST('fixperm'))) $fixperm_path = trim(POST('fixperm'));
            else $fixperm_path = calculate_document_root().PATH_DELIM.trim(POST('fixperm'));
            $b = NewBlog(POST($fixperm_path));
            $upgrade_status = $b->fixDirectoryPermissions();
            if ($upgrade_status) $status = _("Permission update completed successfully.");
            else $status = spf_("Error: Update exited with status %s.", $upgrade_status);
            $tpl->set("FIXPERM_STATUS", $status);

        } elseif (POST("username") && POST("edituser")) {

            $usr = NewUser();
            if ($usr->exists(POST("username"))) {
                Page::instance()->redirect("index.php?action=useredit&user=".POST('username'));
            } else {
                $status = spf_("User %s does not exist.", POST('username'));
            }

        }

        $blogs = System::instance()->getBlogList();
        $blog_names = array();
        foreach ($blogs as $blg) {
            $blog_names[] = $blg->blogid;
        }
        $tpl->set("BLOG_ID_LIST", $blog_names);

        $users = System::instance()->getUserList();
        $user_ids = array();
        foreach ($users as $u) {
            $user_ids[] = $u->username();
        }
        $tpl->set("USER_ID_LIST", $user_ids);

        $body = $tpl->process();
        Page::instance()->display($body);
    }

    # Method: bloglogin
    # Allows users to log in, then redirects them back to the page they came
    # from.  In the "standard" setup, this page is included by the login.php
    # wrapper script in each blog.
    public function bloglogin() {
        $blog = NewBlog();
        Page::instance()->setDisplayObject($blog);

        if ($blog->isBlog()) {
            $page_name = spf_("%s - Login", $blog->name);
            $form_name = spf_("%s Login", $blog->name);
            $redir_url = $blog->getURL();
            $admin_login = false;
        } else {
            $page_name = _("System Administration");
            $form_name = _("System Administration Login");
            $redir_url = "?action=index";
            $admin_login = true;
        }

        $user_name = "user";
        $password = "passwd";

        $tpl = $this->createTemplate(LOGIN_TEMPLATE);
        $tpl->set("FORM_TITLE", $form_name);
        $tpl->set("FORM_ACTION", current_file());
        $tpl->set("UNAME", $user_name);
        $tpl->set("PWD", $password);
        if ( strstr(POST('referer'), 'action=login') !== false ||
             strstr(POST('referer'), 'action=logout') !== false ) {
            $tpl->set("REF", '?action=index' );
        } else {
            $tpl->set("REF", SERVER("HTTP_REFERER") );
        }

        if ( POST($user_name) && POST($password) ) {
            $usr = NewUser(trim(POST($user_name)));
            $ret = $this->attemptLogin($usr, POST($password));
            if (POST("referer")) {
                if ( strstr(POST('referer'), 'login.php') !== false ||
                     strstr(POST('referer'), 'logout.php') !== false ) {
                    $tpl->set("REF", 'index.php' );
                    $redir_url = 'index.php';
                } else {
                    $tpl->set("REF", POST("referer") );
                    $redir_url = POST("referer");
                }
            }
            # Throw up an error if a regular user tries to log in as administrator.
            if ( $admin_login && ! $usr->isAdministrator() ) {
                $tpl->set("FORM_MESSAGE", _("Only the administrator account can log into the administrative pages."));
            } else {
                if ($ret) {
                    Page::instance()->redirect($redir_url);
                } else {
                    $tpl->set("FORM_MESSAGE", _("Error logging in.  Please check your username and password."));
                }
            }
        }

        $body = $tpl->process();
        Page::instance()->title = $page_name;
        Page::instance()->addStylesheet("form.css");
        Page::instance()->display($body, $blog);
    }

    # Method: bloglogout
    # Logs the user out and redirects them to the front page of the current blog.
    # In the stardard setup, this is included by the logout.php wrapper script
    # in each blog.
    public function bloglogout() {
        $blog = NewBlog();
        Page::instance()->setDisplayObject($blog);

        $cancel_id = "cancel";
        $ok_id = "ok";

        $redir_url = $blog->isBlog() ? $blog->getURL() : "?action=index";

        $tpl = $this->createTemplate(CONFIRM_TEMPLATE);
        $tpl->set("CONFIRM_TITLE", _("Logout"));
        $tpl->set("CONFIRM_MESSAGE", _("Do you really want to log out?"));
        $tpl->set("CONFIRM_PAGE", current_file());
        $tpl->set("OK_ID", $ok_id);
        $tpl->set("OK_LABEL", _("Yes"));
        $tpl->set("CANCEL_ID", $cancel_id);
        $tpl->set("CANCEL_LABEL", _("No"));

        if (POST($ok_id)) {
            $usr = NewUser();
            $usr->logout();
            Page::instance()->redirect($redir_url);
        } else if (POST($cancel_id)) {
            Page::instance()->redirect($redir_url);
        }

        $body = $tpl->process();
        if ($blog->isBlog()) Page::instance()->title = sprintf(_("%s - Logout"), $blog->name);
        else                 Page::instance()->title = _("Administration - Logout");
        Page::instance()->display($body, $blog);
    }


    # Method: newblog
    # Used to create a new weblog.
    #
    # The form is initially populated with a reasonable list of default values,
    # taken from <blogconfig.php>.  The user will probably want to change the
    # path and will need to set a name and description.  The blog owner
    # may also need to be changed and the list of allowed writers may need to
    # be set as well.
    public function newblog() {
        if (POST("blogpath")) {
            $path = POST("blogpath");
        } else {
            $path = false;
        }

        $blog = NewBlog($path);
        $usr = NewUser();
        if (! $usr->isAdministrator() && $usr->checkLogin()) {
            $this->redirect("login");
            exit;
        }
        $tpl = $this->createTemplate("blog_modify_tpl.php");
        $blog->owner = $usr->username();

        if (POST("blogpath")) $blog->home_path = POST("blogpath");
        else $blog->home_path = "myblog";

        if (has_post()) {

            $blog->owner = POST("owner");
            blog_get_post_data($blog);
        }

        $tpl->set("SHOW_BLOG_PATH");
        $tpl->set("BLOG_PATH_REL", $blog->home_path);
        $tpl->set("BLOG_OWNER", $blog->owner);
        blog_set_template($tpl, $blog);
        $tpl->set("BLOG_PATH", $blog->home_path);
        $tpl->set("POST_PAGE", current_file());
        $tpl->set("UPDATE_TITLE", _("Create new weblog"));

        # If the user doesn't give us an absolute path, assume it's relative
        # to the DOCUMENT_ROOT.  We put it down here so that the form data
        # gets displayed as it was entered.
        $p = new Path($blog->home_path);
        if (! $p->isAbsolute($blog->home_path)) {
            $blog->home_path = Path::mk(calculate_document_root(),$blog->home_path);
        }

        if ( has_post() ) {
            $ret = false;
            if (strcasecmp(realpath($blog->home_path), realpath(INSTALL_ROOT)) == 0) {
                $tpl->set("UPDATE_MESSAGE", spf_("The blog path you specified is the same as your %s installation path.  This is not allowed, as it will break your installation.  Please choose a different path for your blog.", PACKAGE_NAME));
            } else {
                $ret = $blog->insert();
                if ($ret) {
                    $ret = System::instance()->registerBlog($blog->blogid);
                    if ($ret) {
                        Page::instance()->redirect($blog->getURL());
                        exit;
                    } else {
                        $tpl->set("UPDATE_MESSAGE", _("Blog create but not registered.  This means the system will not list it on the admin pages.  Please try registering this blog by hand from the administration page."));
                    }
                } else {
                    $tpl->set("UPDATE_MESSAGE", _("Error creating blog.  This could be a problem with the file permissions on your server.  Please refer to the <a href=\"http://www.skepticats.com/LnBlog/documentation/\">documentation</a> for more information."));
                }
            }
        }

        $body = $tpl->process();
        Page::instance()->title = _("Create new blog");
        Page::instance()->addStylesheet("form.css");
        Page::instance()->display($body);
    }

    protected function populate_fields(&$tpl) {
        global $confirm, $full_name, $email, $homepage;
        $tpl->set("UNAME_VALUE", trim(POST('user')));
        $tpl->set("PWD_VALUE", trim(POST('passwd')));
        $tpl->set("CONFIRM_VALUE", trim(POST($confirm)));
        $tpl->set("FULLNAME_VALUE", trim(POST($full_name)));
        $tpl->set("EMAIL_VALUE", trim(POST($email)));
        $tpl->set("HOMEPAGE_VALUE", trim(POST($homepage)));
    }

    # Method: newlogin
    # Used to create a new user account.
    #
    # When used to create the administrator account, the username box is locked.
    # To change the administrator username, set the <ADMIN_USER> configuration
    # constant in the userdata/userconfig.php file.
    public function newlogin() {
        $redir_page = "index.php";
        $tpl = $this->createTemplate(CREATE_LOGIN_TEMPLATE);

        # Check if there is at least one administrator.
        # If not, then we need to create one.
        if (System::instance()->hasAdministrator()) {
            $page_name = _("Create New Login");
            $form_title = _("Create New Login");
            $first_user = false;
        } else {
            $page_name = _("Create Administrator Login");
            $form_title = _("Create Aministration Login");
            $tpl->set("UNAME_VALUE", ADMIN_USER);
            #$tpl->set("DISABLE_UNAME", true);
            $first_user = true;
        }

        # If there is an administrator, then we want to restrict creating new accounts.
        $u = NewUser();
        if (System::instance()->hasAdministrator() &&
            (! $u->checkLogin() || ! $u->isAdministrator()) ) {
            $this->redirect("index");
            exit;
        }

        $user_name = "user";
        $password = "passwd";
        $confirm = "confirm";
        $full_name = "fullname";
        $email = "email";
        $homepage = "homepage";
        $reset="reset";  # Set to 1 to reset the password.

        $tpl->set("FORM_TITLE", $form_title);
        $tpl->set("FORM_ACTION", current_file());
        $tpl->set("UNAME", $user_name);
        $tpl->set("PWD", $password);
        $tpl->set("CONFIRM", $confirm);
        $tpl->set("FULLNAME", $full_name);
        $tpl->set("EMAIL", $email);
        $tpl->set("HOMEPAGE", $homepage);

        $cust_path = mkpath(USER_DATA_PATH,CUSTOM_PROFILE);
        $cust_ini = NewINIParser($cust_path);
        $section = $cust_ini->getSection(CUSTOM_PROFILE_SECTION);
        $tpl->set("CUSTOM_FIELDS", $section);

        $post_complete = POST('user') && POST('passwd') && POST($confirm);
        $partial_post = POST('user') || POST('passwd') || POST($confirm);

        if ($post_complete) {
            if ( POST($confirm) != POST('passwd') ) {
                $tpl->set("FORM_MESSAGE",
                    "<span style=\"color: red\">".
                    _("The passwords you entered do not match.").
                    "</span>");
                $this->populate_fields($tpl);
            } else {
                $usr = NewUser();
                $usr->username(trim(POST('user')));
                $usr->password(trim(POST('passwd')));
                $usr->name(trim(POST($full_name)));
                $usr->email(trim(POST($email)));
                $usr->homepage(trim(POST($homepage)));
                foreach ($section as $key=>$val) {
                    $usr->custom[$key] = POST($key);
                }

                $ret = $usr->save();

                if ($ret) {
                    if ($first_user) {
                        $ret = $usr->addToGroup('administrators');
                        $ret = $ret && $this->attemptLogin($usr, POST('passwd'));
                    }
                    if (!$ret) {
                        $tpl->set("FORM_MESSAGE",
                                  _("Error: Failed to make this user an administrator."));
                        $this->populate_fields($tpl);
                    }
                } else {
                    $tpl->set("FORM_MESSAGE",
                              _("Error: Failed to save user information."));
                    $this->populate_fields($tpl);
                }

                if ($ret) {
                    $this->redirect("index");
                    exit;
                }
            }
        } elseif ($partial_post) {
            # Let's do them in reverse, so that the most logical message appears.
            if (! POST($confirm)) $tpl->set("FORM_MESSAGE",
                    '<span style="color: red">'.
                    _("You must confirm your password.").
                    '</span>');
            if (! POST('passwd')) $tpl->set("FORM_MESSAGE",
                    '<span style="color: red">'.
                    _("You must enter a password.").
                    '</span>');
            if (! POST('user')) $tpl->set("FORM_MESSAGE",
                    '<span style="color: red">'.
                    _("You must enter a username.").
                    '</span>');
            $this->populate_fields($tpl);
        }

        $body = $tpl->process();
        Page::instance()->title = $page_name;
        Page::instance()->addStylesheet("form.css");
        Page::instance()->display($body);
    }

    protected function plug_sort($a, $b) {
        if (is_numeric($a["order"]) && ! is_numeric($b["order"])) {
            return -1;
        } elseif (! is_numeric($a["order"]) && is_numeric($b["order"])) {
            return 1;
        } elseif ($a["order"] > $b["order"]) {
            return 1;
        } elseif ($a["order"] < $b["order"]) {
            return -1;
        } elseif ($a["enabled"] && ! $b["enabled"]) {
            return -11;
        } elseif (! $a["enabled"] && $b["enabled"]) {
            return 1;
        } else {
            return 0;
        }
    }

    # Quick fix for name mangling on forms
    protected function namefix($pg) {
        return preg_replace("/\W/", "_", $pg);
    }

    public function pluginloading() {

        $user = NewUser();

        if (defined("BLOG_ROOT")) {
            $blg = NewBlog();
            if (! System::instance()->canModify($blg, $user) || ! $user->checkLogin()) {
                $this->redirect("login");
                exit;
            }
        } elseif (! $user->isAdministrator() || ! $user->checkLogin()) {
            $this->redirect("login");
            exit;
        }

        $tpl = $this->createTemplate(PLUGIN_LOAD_TEMPLATE);

        if (has_post()) {

            $disabled = array();
            $first = array();

            foreach (PluginManager::instance()->plugin_list as $plug) {
                if (! POST($this->namefix($plug)."_en")) $disabled[] = $plug;
                if (is_numeric(POST($this->namefix($plug)."_ord")))
                    $first[$plug] = POST($this->namefix($plug)."_ord");
            }
            asort($first);
            $lfirst = array_keys($first);

            PluginManager::instance()->disabled = $disabled; #implode(",", $disabled);
            PluginManager::instance()->load_first = $lfirst; #implode(",", $first);

            if (defined("BLOG_ROOT")) $file = BLOG_ROOT.PATH_DELIM."plugins.xml";
            else $file = USER_DATA_PATH.PATH_DELIM."plugins.xml";

            $parser = NewConfigFile($file);
            $parser->setValue("Plugin_Manager", "exclude_list", implode(",",$disabled));
            $parser->setValue("Plugin_Manager", "load_first", implode(",",$lfirst));
            $ret = $parser->writeFile();

            if (! $ret) {
                $tpl->set("UPDATE_MESSAGE", spf_("Error updating file %s", $file));
            } else {
                # We redirect so that the user sees the changes right away.
                Page::instance()->redirect(current_uri());
            }
        }

        # Create an array of arrays to send to the template for display.

        $disp_list = array();
        foreach (PluginManager::instance()->plugin_list as $plug) {
            $disp_list[$this->namefix($plug)] =
                array("order"=>_("Unspecified"),
                      "enabled"=> !in_array($plug, PluginManager::instance()->disabled),
                      "file"=>$plug);
        }

        $i=1;
        foreach (PluginManager::instance()->load_first as $plug) {
            if (isset($disp_list[$this->namefix($plug)]))
                $disp_list[$this->namefix($plug)]["order"] = $i++;
        }

        uasort($disp_list, array($this, "plug_sort"));

        $tpl->set("PLUGIN_LIST", $disp_list);

        Page::instance()->includeJqueryUi();
        Page::instance()->title = spf_("%s Plugin Loading Configuration", PACKAGE_NAME);
        Page::instance()->display($tpl->process());
    }

    public function pluginsetup() {
        $usr = NewUser();
        $blg = NewBlog();

        if ($blg->isBlog()) {
            if (! System::instance()->canModify($blg, $usr) || !$usr->checkLogin()) {
                $this->redirect('login');
                exit;
            }
        } elseif (! $usr->isAdministrator() || !$usr->checkLogin()) {
            $this->redirect("login");
            exit;
        }

        if (has_post()) {
            $plug_name = sanitize(POST("plugin"));
            $plug = new $plug_name;
            $ret = $plug->updateConfig();

            if ($blg->isBlog()) {
                Page::instance()->redirect($blg->uri('pluginconfig'));
            } else {
                $this->redirect("plugins");
            }

            exit;

        } elseif ( sanitize(GET("plugin")) &&
                   class_exists(sanitize(GET("plugin"))) ) {
            $plug_name = sanitize(GET("plugin"));
            $plug = new $plug_name;
            $body = '<h4>'._('Plugin Configuration').'</h4>';
            $body .= '<ul><li>'._('Name').': '.get_class($plug).'</li>';
            $body .= '<li>'._('Version').': '.$plug->plugin_version.'</li>';
            $body .= '<li>'._('Description').': '.$plug->plugin_desc.'</li></ul>';
            ob_start();
            $ret = $plug->showConfig(Page::instance(), $this->getCsrfToken());
            $buff = ob_get_contents();
            ob_end_clean();
            $body .= is_string($ret) ? $ret : $buff;
            if ($blg->isBlog() ) $url = $blg->uri('pluginconfig');
            else $url = current_uri(true,'');

            $body .= '<p><a href="'.$url.'">'._("Back to plugin list").'</a></p>';
        } else {
            $plug_list = PluginManager::instance()->getPluginList();
            sort($plug_list);
            $body = "<h4>"._('Plugin Configuration')."</h4><ul>";
            $body .= '<table><tr><th>Plugin</th><th>Version</th><th>Description</th></tr>';
            foreach ($plug_list as $plug) {
                $p = new $plug;
                $url = make_uri(false,array("plugin"=>$plug),false);
                $body .= '<tr><td><a href="'.$url.'">'.$plug.'</a></td>';
                $body .= '<td style="text-align: center">'.$p->plugin_version.'</td><td>'.$p->plugin_desc.'</td></tr>';
            }
            $body .= '</table>';
        }
        Page::instance()->title = spf_("%s Plugin Configuration", PACKAGE_NAME);
        Page::instance()->display($body);
    }

    public function userinfo() {
        $uid = GET("user");
        $uid = $uid ? $uid : POST("user");
        $uid = $uid ? $uid : basename(getcwd());
        $uid = preg_replace("/\W/", "", $uid);

        $usr = NewUser($uid);
        $tpl = $this->createTemplate("user_info_tpl.php");
        Page::instance()->setDisplayObject($usr);
        $usr->exportVars($tpl);

        $priv_path = mkpath(USER_DATA_PATH,$usr->username(),"profile.ini");
        $cust_path = mkpath(USER_DATA_PATH,"profile.ini");
        $cust_ini = NewINIParser($priv_path);
        $cust_ini->merge(NewINIParser($cust_path));

        $tpl->set("CUSTOM_FIELDS", $cust_ini->getSection("profile fields"));
        $tpl->set("CUSTOM_VALUES", $usr->custom);

        $ret = $tpl->process();
        $user_file = mkpath(USER_DATA_PATH,$uid,"profile.htm");

        if (file_exists($user_file)) {
            $ret .= implode("\n", file($user_file));
        }

        Page::instance()->title = _("User Information");
        Page::instance()->display($ret);
    }

    private function ftp_file_exists($file, $ftp_obj) {

        $dir_list = ftp_nlist($ftp_obj->connection, $ftp_obj->localpathToFSPath(dirname($file)));
        if (! is_array($dir_list)) $dir_list = array();

        foreach ($dir_list as $ent) {
            if (basename($file) == basename($ent)) {
                #echo basename($file)." == $ent<br />";
                return true;
            } #else echo basename($file)." != $ent<br />";
        }
        return false;
    }

    # Takes a file or directory on the local host and an FTP connection.
    # Connects to the FTP server, changes to the root directory, and
    # checks the directory listing.  It then goes down the local directory
    # tree until it finds a directory that contains one of the entries in the
    # listing.  This directory is the FTP root.

    private function find_dir($dir, $conn) {
        # Change to the root directory.
        ftp_chdir($conn, "/");
        $ftp_list = ftp_nlist($conn, ".");

        # Save the drive letter (if it exists).
        $drive = substr($dir, 0, 2);

        # Get the current path into an array.
        if (PATH_DELIM != "/") {
            if (substr($dir, 1, 1) == ":") $dir = substr($dir, 3);
            $dir = str_replace(PATH_DELIM, "/", $dir);
        }

        if (substr($dir, 0, 1) == "/") $dir = substr($dir, 1);
        $dir_list = explode("/", $dir);

        # For each local directory element, loop through contents of the FTP
        # root directory.  If the current element is in FTP root, then the
        # parent of the current element is the root.
        # $ftp_root starts at root and has the current directory appended at the
        # end of each outer loop iteration.  Thus, $ftp_root always holds the
        # parent of the currently processing directory.
        # Note that we must account for Windows drive letters, grubmle, grumble.
        if (PATH_DELIM == "/") {
            $ftp_root = "/";
        } else {
            $ftp_root = $drive.PATH_DELIM;
        }
        foreach ($dir_list as $dir) {
            foreach ($ftp_list as $ftpdir) {
                if ($dir == $ftpdir && $ftpdir != ".." && $ftpdir != ".") {
                    return $ftp_root;
                }
            }
            $ftp_root .= $dir.PATH_DELIM;
        }

    }

    # Test how and if native file writing works.
    protected function nativefs_test() {

        $ret = array('write'=>false, 'delete'=>false,
                     'user'=>'', 'group'=>'',
                     'summary'=>'');

        $stat_data = false;

        $f = @fopen("tempfile.tmp", "w");
        if ($f !== false) {

            $old = umask(0777);
            $can_write = fwrite($f, "Test");
            fclose($f);
            umask($old);

            $stat_data = stat("tempfile.tmp");
            if ($stat_data) {
                $ret['user'] = $stat_data['uid'];
                $ret['group'] = $stat_data['gid'];
            }

            $ret['delete'] = @unlink("tempfile.tmp");
        }

        $ret['summary'] = _("NativeFS test results:")."<br />".
            spf_("Create new files: %s", $ret['write'] ? "yes" : "no")."<br />".
            spf_("Delete files: %s", $ret['delete'] ? "yes" : "no")."<br />".
            spf_("File owner: %s", $ret['user'])."<br />".
            spf_("File group: %s", $ret['group']);

        return $ret;
    }

    # Check that all required fields have been populated by the user.
    protected function check_fields() {
        $errs = array();
        $plugin = trim(POST('use_ftp'));

        if (trim(POST("docroot")) != '') $errs[] = _("No document root set.");

        $ret = (POST('use_ftp') == 'ftpfs' || POST('use_ftp') == 'nativefs');
        if (! $ret) $errs[] = _("Invalid file writing mode.");

        $ret = is_numeric(POST('permdir')) && strlen(POST('permdir')) == 4;
        $ret = $ret && is_numeric(POST('permscript')) && strlen(POST('permscript')) == 4;
        $ret = $ret && is_numeric(POST('permfile')) && strlen(POST('permfile')) == 4;
        if (! $ret) $errs[] = _("Invalid permissions specified.");


        if ($plugin == 'nativefs') {
            # Nothing to do?
        } elseif ($plugin == 'ftpfs') {
            if (trim(POST('ftp_user')) == '') $errs[] = _("Missing FTP username.");
            if (trim(POST('ftp_pwd')) == '') $errs[] = _("Missing FTP password.");
            if (trim(POST('ftp_conf')) == '') $errs[] = _("Missing FTP password confirmation.");
            if (trim(POST('ftp_host')) == '') $errs[] = _("Missing FTP hostname.");

            if (POST('ftp_pwd') != POST('ftp_conf')) $errs[] = _("FTP passwords do not match.");
        }

        if (count($errs) > 0) return $errs;
        else return true;
    }

    protected function template_set_post_data(&$tpl) {
        $tpl->set("DOC_ROOT", POST("docroot") );
        if (POST("use_ftp") == "ftpfs") $tpl->set("USE_FTP", POST("use_ftp") );
        $tpl->set("USER", POST("ftp_user") );
        $tpl->set("PASS", POST("ftp_pwd") );
        $tpl->set("CONF", POST("ftp_conf") );
        $tpl->set("HOST", POST("ftp_host") );
        $tpl->set("ROOT", POST("ftp_root") );
        $tpl->set("PREF", POST("ftp_prefix") );
        $tpl->set("HOSTTYPE", POST("hosttype"));
        $tpl->set("PERMDIR", POST('permdir'));
        $tpl->set("PERMSCRIPT", POST('permscript'));
        $tpl->set("PERMFILE", POST('permfile'));
    }

    protected function serialize_constants() {
        $ret = '';
        $consts = array("DOCUMENT_ROOT", "SUBDOMAIN_ROOT", "DOMAIN_NAME",
                        "FS_PLUGIN", "FTPFS_USER",
                        "FTPFS_PASSWORD", "FTPFS_HOST",
                        "FTP_ROOT", "FTPFS_PATH_PREFIX",
                        "FS_DEFAULT_MODE", "FS_SCRIPT_MODE", "FS_DIRECTORY_MODE");
        foreach ($consts as $c) {
            if (defined($c)) {
                if (is_numeric(constant($c))) {
                    $ret .= 'define("'.$c.'", '.constant($c).');'."\n";
                } else {
                    $ret .= 'define("'.$c.'", "'.constant($c).'");'."\n";
                }
            }
        }
        if ($ret) $ret = "<?php\n$ret?>";
        return $ret;
    }

    public function fssetup() {
        if ( file_exists(Path::mk(USER_DATA_PATH, FS_PLUGIN_CONFIG)) ) {
            $this->redirect('index');
        }

        Page::instance()->title = sprintf(_("%s Configuration"), PACKAGE_NAME);

        $tpl = $this->createTemplate(FS_CONFIG_TEMPLATE);

        $tpl->set("FORM_ACTION", '');

        if (has_post()) {

            $this->template_set_post_data($tpl);
            $field_test = $this->check_fields();

            # Note that DOCUMENT_ROOT is not strictly required, as all uses
            # of it should be wrapped in a document root calculation function
            # for legacy versions.  However....

            define('FS_DEFAULT_MODE', 0);
            define('FS_DIRECTORY_MODE', 0);
            define('FS_SCRIPT_MODE', 0);
            define("FS_PLUGIN", "nativefs");

            $fields = array(
                "docroot"=>"DOCUMENT_ROOT",
                "subdomroot"=>"SUBDOMAIN_ROOT",
                "domain"=>"DOMAIN_NAME",
            );
            foreach ($fields as $key=>$val) {
                if (POST($key)) {
                    define($val, trim(POST($key)));
                }
            }

            $content = $this->serialize_constants();

            if ($content) {

                @$fs = NewFS();
                $content = str_replace('\\', '\\\\', $content);

                # Try to create the fsconfig file.  Suppress error messages so users
                # don't get scared by expected permissions problems.
                if (! is_dir(USER_DATA_PATH)) {
                    @$ret = $fs->mkdir_rec(USER_DATA_PATH);
                }
                if (is_dir(USER_DATA_PATH)) {
                    $ret = $fs->write_file(Path::mk(USER_DATA_PATH, FS_PLUGIN_CONFIG), $content);
                }

                if ( $ret) {
                    $this->redirect("index");
                } else {
                    $tpl->set("FORM_MESSAGE", sprintf(
                        _("Error: Could not create fsconfig.php file.  Make sure that the directory %s exists on the server and is writable to the web server user."), USER_DATA_PATH));
                }

            } else {
                if (! $tpl->varSet("FORM_MESSAGE") ) {
                    $tpl->set("FORM_MESSAGE", _("Unexpected error: missing data?"));
                }
            }

        } else {
            $tpl->set("HOST", "localhost");
            $tpl->set("DOC_ROOT", calculate_document_root() );
        }

        $body = $tpl->process();
        Page::instance()->addStylesheet("form.css");
        Page::instance()->display($body);
    }
}
