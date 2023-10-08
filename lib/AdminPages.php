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

use LnBlog\Export\ExportTarget;
use LnBlog\Export\ExporterFactory;
use LnBlog\Forms\BlogExportForm;
use LnBlog\Forms\BlogImportForm;
use LnBlog\Forms\BlogRegistrationForm;
use LnBlog\Storage\BlogRepository;
use LnBlog\Storage\UserRepository;

class AdminPages extends BasePages
{
    protected $importer_factory;

    protected function getActionMap() {
        return array(
            'index' => 'index',
            'import' => 'importblog',
            'export' => 'exportblog',
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
        $tpl->set('CANCEL_ID', $no_id);
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
        $configs_exist = 
            $this->fs->file_exists(Path::mk(INSTALL_ROOT, SystemConfig::PATH_CONFIG_NAME)) &&
            $this->fs->file_exists(Path::mk(USER_DATA_PATH, FS_PLUGIN_CONFIG));

        if (!$configs_exist) {
            $this->redirect("fssetup");
            exit;
        }

        $update =  "update";
        $upgrade = "upgrade";
        Page::instance()->title = sprintf(_("%s Administration"), PACKAGE_NAME);

        $tpl = $this->createTemplate('blog_admin_tpl.php');
        $tpl->set("SHOW_NEW");
        $tpl->set("FORM_ACTION", current_file());

        $registrationForm = new BlogRegistrationForm($this->fs, SystemConfig::instance());
        $tpl->set("REGISTER_FORM", $registrationForm);
        $tpl->set("PAGE", $this);

        $this->populateBlogPathDefaults($tpl);

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
            try {
                $file_list = $b->upgradeWrappers();
                $status = empty($file_list) ?
                    spf_("Upgrade of %s completed successfully.", $b->blogid) :
                    spf_("Error: The following file could not be written - %s.", implode("<br />", $file_list));
            } catch (\Exception $e) {
                $status = spf_("Error: %s does not seem to exist.", $b->blogid);
            }
            $tpl->set("UPGRADE_STATUS", $status);

        } elseif ( POST('register') && POST('register_btn') ) {
            try {
                $registrationForm->process($_POST);
            } catch (FormInvalid $e) {
                // Nothing to do here - the form displays its own error.
            }
        } elseif ( POST('delete') && POST('delete_btn') ) {

            if (POST('confirm_form') || GET('confirm')) {

                $blog = NewBlog(POST('delete'));
                if (! $blog->isBlog()) {
                    $status = spf_("The path '%s' is not an LnBlog weblog.", POST('delete'));
                } else {
                    try {
                        SystemConfig::instance()->unregisterBlog($blog->blogid);
                        SystemConfig::instance()->writeConfig();
                        $ret = true;
                    } catch (FileWriteFailed $e) {
                        $ret = false;
                    }

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
                $this->show_confirm_page(
                    _("Confirm blog deletion"), spf_("Really delete blog '%s'?", POST('delete')), current_file(),
                    'delete_btn', _('Yes'), 'cancel_btn', _('No'), 'delete', POST('delete')
                );
            }
        } elseif ( POST('fixperm') && POST('fixperm_btn') ) {
            $fixperm_path = trim(POST('fixperm'));
            $b = NewBlog($fixperm_path);
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
            $tpl->set("REF", '?action=index');
        } else {
            $tpl->set("REF", SERVER("HTTP_REFERER"));
        }

        if ( POST($user_name) && POST($password) ) {
            $usr = NewUser(trim(POST($user_name)));
            $ret = $this->attemptLogin($usr, POST($password));
            if (POST("referer")) {
                if ( strstr(POST('referer'), 'login.php') !== false ||
                     strstr(POST('referer'), 'logout.php') !== false ) {
                    $tpl->set("REF", 'index.php');
                    $redir_url = 'index.php';
                } else {
                    $tpl->set("REF", POST("referer"));
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

    public function importblog() {
        $this->requireAdministrator();

        $form = new BlogImportForm($this->fs, SystemConfig::instance(), $this->getBlogRepository());

        if (has_post()) {
            try {
                $form->process($_POST);
            } catch (Exception $e) {
            }
        }

        $body = $form->render($this);
        Page::instance()->addPackage('jquery-ui');
        Page::instance()->title = _('Import blog');
        Page::instance()->addStylesheet("form.css");
        Page::instance()->display($body);
    }

    public function exportblog() {
        $this->requireAdministrator();

        $form = new BlogExportForm(new ExporterFactory(), SystemConfig::instance(), $this->getBlogRepository());

        if (has_post()) {
            try {
                /** @var ExportTarget $export_data */
                $export_data = $form->process($_POST);
                $filename = $export_data->getExportFile();
                header('Content-Type: application/xml');
                header("Content-Disposition: attachment; filename=$filename");
                echo $export_data->getAsText();
                return;
            } catch (Exception $e) {
            }
        }

        $body = $form->render($this);
        Page::instance()->addPackage('jquery-ui');
        Page::instance()->title = _('Export blog');
        Page::instance()->addStylesheet("form.css");
        Page::instance()->display($body);
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

        if (POST("blogpath")) {
            $blog->home_path = POST("blogpath");
        } else {
            $base_path = dirname(SystemConfig::instance()->installRoot()->path());
            $blog->home_path = Path::mk($base_path, "myblog");
        }

        if (POST("blogid")) {
            $blog->blogid = POST("blogid");
        } else {
            $blog->blogid = 'myblog';
        }

        if (has_post()) {
            $blog->owner = POST("owner");
            $this->blogGetPostData($blog);
        }

        $blogurl = POST('blogurl');
        if (!$blogurl) {
            $lnblog_name = basename(SystemConfig::instance()->installRoot()->path());
            $blogurl = preg_replace("|$lnblog_name/|i", $blog->blogid, SystemConfig::instance()->installRoot()->url());
        }

        $this->populateBlogPathDefaults($tpl);

        $tpl->set("SHOW_BLOG_PATH");
        $tpl->set("BLOG_ID", $blog->blogid);
        $tpl->set("BLOG_URL", $blogurl);
        $tpl->set("BLOG_OWNER", $blog->owner);
        $this->blogSetTemplate($tpl, $blog);
        $tpl->set("BLOG_PATH", $blog->home_path);
        $tpl->set("POST_PAGE", current_file());
        $tpl->set("UPDATE_TITLE", _("Create new weblog"));

        if ( has_post() ) {
            $blogid = POST('blogid');
            $blogpath = POST('blogpath');
            $blogurl = POST('blogurl');
            $validation_error = '';
            try {
                $this->validateBlogRegistration($blogid, $blogpath, $blogurl);
            } catch (Exception $err) {
                $validation_error = $err->getMessage();
            }
            $ret = false;
            if ($validation_error) {
                $tpl->set("UPDATE_MESSAGE", $validation_error);
            } else {
                try {
                    SystemConfig::instance()->registerBlog($blog->blogid, new UrlPath($blog->home_path, $blogurl));
                    $ret = $blog->insert();
                    SystemConfig::instance()->writeConfig();
                } catch (FileWriteFailed $e) {
                    $blog->delete();
                    $ret = false;
                }
                if ($ret) {
                    Page::instance()->redirect($blog->getURL());
                    exit;
                } else {
                    $tpl->set("UPDATE_MESSAGE", _("Error creating blog.  This could be a problem with the file permissions on your server.  Please refer to the <a href=\"http://www.skepticats.com/LnBlog/documentation/\">documentation</a> for more information."));
                }
            }
        }

        $user_repo = new UserRepository();
        $user_list = $user_repo->getAll();
        $tpl->set('USER_LIST', $user_list);

        $body = $tpl->process();
        Page::instance()->addPackage('tag-it');
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

        $cust_path = Path::mk(USER_DATA_PATH, CUSTOM_PROFILE);
        $cust_ini = NewINIParser($cust_path);
        $section = $cust_ini->getSection(CUSTOM_PROFILE_SECTION);
        $tpl->set("CUSTOM_FIELDS", $section);

        $post_complete = POST('user') && POST('passwd') && POST($confirm);
        $partial_post = POST('user') || POST('passwd') || POST($confirm);

        if ($post_complete) {
            if ( POST($confirm) != POST('passwd') ) {
                $tpl->set(
                    "FORM_MESSAGE",
                    "<span style=\"color: red\">".
                    _("The passwords you entered do not match.").
                    "</span>"
                );
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
                        $tpl->set(
                            "FORM_MESSAGE",
                            _("Error: Failed to make this user an administrator.")
                        );
                        $this->populate_fields($tpl);
                    }
                } else {
                    $tpl->set(
                        "FORM_MESSAGE",
                        _("Error: Failed to save user information.")
                    );
                    $this->populate_fields($tpl);
                }

                if ($ret) {
                    $this->redirect("index");
                    exit;
                }
            }
        } elseif ($partial_post) {
            # Let's do them in reverse, so that the most logical message appears.
            if (! POST($confirm)) $tpl->set(
                "FORM_MESSAGE",
                '<span style="color: red">'.
                    _("You must confirm your password.").
                '</span>'
            );
            if (! POST('passwd')) $tpl->set(
                "FORM_MESSAGE",
                '<span style="color: red">'.
                    _("You must enter a password.").
                '</span>'
            );
            if (! POST('user')) $tpl->set(
                "FORM_MESSAGE",
                '<span style="color: red">'.
                    _("You must enter a username.").
                '</span>'
            );
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
            $parser->setValue("Plugin_Manager", "exclude_list", implode(",", $disabled));
            $parser->setValue("Plugin_Manager", "load_first", implode(",", $lfirst));
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

        Page::instance()->addPackage('jquery-ui');
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
            else $url = current_uri(true, '');

            $body .= '<p><a href="'.$url.'">'._("Back to plugin list").'</a></p>';
        } else {
            $plug_list = PluginManager::instance()->getPluginList();
            sort($plug_list);
            $body = "<h4>"._('Plugin Configuration')."</h4><ul>";
            $body .= '<table><tr><th>Plugin</th><th>Version</th><th>Description</th></tr>';
            foreach ($plug_list as $plug) {
                $p = new $plug;
                $url = make_uri(false, array("plugin"=>$plug), false);
                $body .= '<tr><td><a href="'.$url.'">'.$plug.'</a></td>';
                $body .= '<td style="text-align: center">'.$p->plugin_version.'</td><td>'.$p->plugin_desc.'</td></tr>';
            }
            $body .= '</table>';
        }
        Page::instance()->addStylesheet("form.css");
        Page::instance()->title = spf_("%s Plugin Configuration", PACKAGE_NAME);
        Page::instance()->display($body);
    }

    public function userinfo() {
        if (GET('blog')) {
            $blog = NewBlog(GET('blog'));
            SystemConfig::instance()->definePathConstants($blog->home_path);
        }
        $uid = GET("user");
        $uid = $uid ? $uid : POST("user");
        $uid = $uid ? $uid : basename(getcwd());
        $uid = preg_replace("/\W/", "", $uid);

        $usr = NewUser($uid);
        $tpl = $this->createTemplate("user_info_tpl.php");
        Page::instance()->setDisplayObject($usr);
        $usr->exportVars($tpl);

        $priv_path = Path::mk(USER_DATA_PATH, $usr->username(), "profile.ini");
        $cust_path = Path::mk(USER_DATA_PATH, "profile.ini");
        $cust_ini = NewINIParser($priv_path);
        $cust_ini->merge(NewINIParser($cust_path));

        $tpl->set("CUSTOM_FIELDS", $cust_ini->getSection("profile fields"));
        $tpl->set("CUSTOM_VALUES", $usr->custom);

        $ret = $tpl->process();
        $user_file = Path::mk(USER_DATA_PATH, $uid, "profile.htm");

        if ($this->fs->file_exists($user_file)) {
            $ret .= implode("\n", file($user_file));
        }

        $name = $usr->fullname ?: $usr->username;
        Page::instance()->title = spf_("Profile for %s", $name);
        Page::instance()->display($ret);
    }

    protected function template_set_post_data(&$tpl) {
        if (POST("use_ftp") == "ftpfs") $tpl->set("USE_FTP", POST("use_ftp"));
        $tpl->set("USER", POST("ftp_user"));
        $tpl->set("PASS", POST("ftp_pwd"));
        $tpl->set("CONF", POST("ftp_conf"));
        $tpl->set("HOST", POST("ftp_host"));
        $tpl->set("ROOT", POST("ftp_root"));
        $tpl->set("PREF", POST("ftp_prefix"));
        $tpl->set("HOSTTYPE", POST("hosttype"));
        $tpl->set("PERMDIR", POST('permdir'));
        $tpl->set("PERMSCRIPT", POST('permscript'));
        $tpl->set("PERMFILE", POST('permfile'));
    }

    protected function serialize_constants() {
        $ret = '';
        $consts = array(
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
        $config = SystemConfig::instance();
        if ($config->configExists()) {
            $this->redirect('index');
        }

        Page::instance()->title = sprintf(_("%s Configuration"), PACKAGE_NAME);

        $tpl = $this->createTemplate(FS_CONFIG_TEMPLATE);
        $userdata_dir_name = 'userdata';

        $tpl->set("FORM_ACTION", '');
        $install_root = dirname(__DIR__);
        $install_root_url = preg_replace('|index\.php.*|', '', current_url());
        $lnblog_dir_name = basename(dirname(__DIR__));
        $userdata = Path::mk(dirname(dirname(__DIR__)), $userdata_dir_name);
        $userdata_url = preg_replace("|$lnblog_dir_name/index.php\.*|i", $userdata_dir_name . '/', current_url());

        // Setting a userdata directory outside the document root is currently
        // not supported, so if we detect that LnBlog IS the document root, 
        // adjust the default path accordingly.
        $root_url_path = parse_url($install_root_url, PHP_URL_PATH);
        if ($root_url_path === '/') {
            $userdata = Path::mk($install_root, $userdata_dir_name);
            $userdata_url = $install_root_url . $userdata_dir_name . '/';
        }

        if (has_post()) {

            $this->template_set_post_data($tpl);
            
            $install_root = POST('installroot');
            $install_root_url = POST('installrooturl');
            $userdata = POST('userdata');
            $userdata_url = POST('userdataurl');
            $error = '';

            try {
                $this->validateInstallRootAndUserdata($install_root, $install_root_url, $userdata, $userdata_url);
                $config->installRoot(new UrlPath($install_root, $install_root_url));
                $config->userData(new UrlPath($userdata, $userdata_url));
                $config->writeConfig();
            } catch (Exception $e) {
                $error = $e->getMessage();
            }

            define('FS_DEFAULT_MODE', 0);
            define('FS_DIRECTORY_MODE', 0);
            define('FS_SCRIPT_MODE', 0);
            define("FS_PLUGIN", "nativefs");

            $content = $this->serialize_constants();

            @$fs = NewFS();
            $content = str_replace('\\', '\\\\', $content);

            if (! is_dir($config->userData()->path())) {
                $ret = $fs->mkdir_rec($config->userData()->path());
                if ($ret) {
                    $fs->copy(Path::mk($install_root, 'userdata', '.htaccess'), Path::mk($userdata, '.htaccess'));
                } else {
                    $error = spf_("Unable to create directory %s", $userdata);
                }
            }

            if (is_dir($config->userData()->path())) {
                $ret = $fs->write_file(Path::mk($config->userData()->path(), FS_PLUGIN_CONFIG), $content);
                if (!$ret) {
                    $error = spf_("Unable to create directory %s/%s", $userdata, FS_PLUGIN_CONFIG);
                }
            }

            if (!$error) {
                $this->redirect("index");
            } else {
                $tpl->set("FORM_MESSAGE", spf_("Error: ", $error));
            }
        }

        $tpl->set('INSTALL_ROOT', $install_root);
        $tpl->set('INSTALL_ROOT_URL', $install_root_url);
        $tpl->set('USERDATA', $userdata);
        $tpl->set('USERDATA_URL', $userdata_url);

        $body = $tpl->process();
        Page::instance()->addStylesheet("form.css");
        Page::instance()->display($body);
    }

    private function validateBlogRegistration(string $blogid, string $path, string $url) {
        $realpath = $this->fs->realpath($path);
        $registry = SystemConfig::instance()->blogRegistry();
        $install_root = SystemConfig::instance()->installRoot();
        $userdata = SystemConfig::instance()->userData();

        if (empty($blogid) || empty($path) || empty($url)) {
            throw new Exception(_('Not all blog data was specified'));
        }

        if (isset($registry[$blogid])) {
            throw new Exception(spf_("Blog ID '%s' is already registered", $blogid));
        }

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new Exception(spf_("The URL '%s' is not a valid URL", $url));
        }

        if ($realpath == $this->fs->realpath($install_root->path())) {
            throw new Exception(spf_("The blog path you specified is the same as your %s installation path.  This is not allowed, as it will break your installation.  Please choose a different path for your blog.", PACKAGE_NAME));
        }

        if ($realpath == $this->fs->realpath($userdata->path())) {
            throw new Exception(spf_("The blog path you specified is the same as your %s userdata path.  This is not supported.", PACKAGE_NAME));
        }

        foreach ($registry as $blogid => $urlpath) {
            $blog_path = $this->fs->realpath($urlpath->path());
            # If the directory exists, use the real path, otherwise, just take what we're passed.
            $passed_path = $realpath ?: $path;
            if ($passed_path == $blog_path) {
                throw new Exception(spf_("The blog path '%s' is already registered.", $path));
            }
            if ($url == $urlpath->url()) {
                throw new Exception(spf_("The blog URL '%s' is already registered.", $url));
            }
        }
    }

    private function validateInstallRootAndUserdata(string $install_root, string $install_root_url, string $userdata, string $userdata_url) {
        if (!$this->fs->file_exists(Path::mk($install_root, 'blogconfig.php'))) {
            throw new Exception(spf_("The path '%s' is not a valid %s installation", $install_root, PACKAGE_NAME));
        }

        if (!filter_var($install_root_url, FILTER_VALIDATE_URL)) {
            throw new Exception(spf_("The URL '%s' is not valid", $install_root_url));
        }

        if (!filter_var($userdata_url, FILTER_VALIDATE_URL)) {
            throw new Exception(spf_("The URL '%s' is not valid", $userdata_url));
        }
    }

    private function requireAdministrator(User $user = null): void {
        if ($user === null) {
            $user = User::get();
        }
        if (!$user->checkLogin()) {
            $this->redirect("login");
            exit;
        }
        if (!$user->isAdministrator()) {
            $this->getPage()->error(403);
        }
    }

    private function getBlogRepository(): BlogRepository {
        return new BlogRepository();
    }
}
