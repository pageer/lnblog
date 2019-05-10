<?php
require_once __DIR__.'/../pages/pagelib.php';

class WebPages extends BasePages {

    protected $blog;
    protected $user;
    protected $publisher;

    private $last_pingback_results = array();
    private $last_upload_error = array();

    public function __construct(Blog $blog = null, User $user = null) {
        parent::__construct();
        $this->user = $user ?: NewUser();
        $this->blog = $blog ?: NewBlog();
        $this->getPage()->setDisplayObject($this->blog);

        EventRegister::instance()->addHandler('BlogEntry', 'PingbackComplete', $this, 'handlePingbackComplete');
        EventRegister::instance()->addHandler('BlogEntry', 'UploadError', $this, 'handleUploadError');
    }

    protected function getActionMap() {
        return array(
            'about'      => 'about',
            'newentry'   => 'entryedit',
            'editentry'  => 'entryedit',
            'delentry'   => 'delentry',
            'delcomment' => 'delcomment',
            'edit'       => 'updateblog',
            'login'      => 'AdminPages::bloglogin',
            'logout'     => 'AdminPages::bloglogout',
            'upload'     => 'fileupload',
            'removefile' => 'removefile',
            'useredit'   => 'editlogin',
            'plugins'    => 'AdminPages::pluginsetup',
            'tags'       => 'tagsearch',
            'pluginload' => 'AdminPages::pluginloading',
            'profile'    => 'AdminPager::userinfo',
            'managereply'=> 'managereplies',
            'editfile'   => 'editfile',
            'blogpaths'  => 'blogpaths',
            'webmention' => 'webmention',
        );
    }

    protected function defaultAction() {
        return $this->showblog();
    }

    protected function redirectOr403($redirect = '', $message = '') {
        if ($redirect) {
            $this->getPage()->redirect($redirect);
        } else {
            $this->getPage()->error(403, $message);
        }
    }

    protected function verifyUserCanModifyBlog($redirect = '', $message = '') {
        if (! $this->user->checkLogin() || ! System::instance()->canModify($this->blog, $this->user)) {
            $this->redirectOr403($redirect, $message);
        }
    }

    protected function verifyUserIsLoggedIn($redirect = '', $message = '') {
        if (! $this->user->checkLogin()) {
            $this->redirectOr403($redirect, $message);
        }
    }

    protected function reqVar($key) {
        if (isset($_POST[$key])) {
            return $_POST[$key];
        } elseif (isset($_GET[$key])) {
            return $_GET[$key];
        } else {
            return null;
        }
    }

    public function about() {
        $tpl = NewTemplate("about_tpl.php");

        $tpl->set("NAME", PACKAGE_NAME);
        $tpl->set("VERSION", PACKAGE_VERSION);
        $tpl->set("URL", PACKAGE_URL);
        $tpl->set("NAME", PACKAGE_NAME);
        $tpl->set("DESCRIPTION", PACKAGE_DESCRIPTION);
        $tpl->set("COPYRIGHT", PACKAGE_COPYRIGHT);

        $content = $tpl->process();
        $this->getPage()->display($content, $this->blog);
    }

    public function blogpaths() {

        $blog_path = $this->reqVar("blogpath");

        $blog = NewBlog($blog_path);
        $tpl = NewTemplate("blog_path_tpl.php");
        $this->getPage()->setDisplayObject($blog);

        $inst_root = INSTALL_ROOT;
        $inst_url = INSTALL_ROOT_URL;
        $blog_url = $blog->getURL();

        $this->verifyUserCanModifyBlog($blog->uri('login'));

        if (has_post()) {
            $inst_root = POST("installroot");
            $inst_url = POST("installrooturl");
            $ret = write_file(mkpath(BLOG_ROOT,"pathconfig.php"),
                              pathconfig_php_string($inst_root, $inst_url, $blog_url));
            if (!$ret) {
                $tpl->set("UPDATE_MESSAGE", _("Error updating blog paths."));
            } else {
                $this->getPage()->redirect($blog_url);
                exit;
            }
        }

        $tpl->set("INST_URL", $inst_url);
        $tpl->set("INST_ROOT", $inst_root);
        $tpl->set("POST_PAGE", current_file());
        $tpl->set("UPDATE_TITLE", sprintf(_("Update paths for %s"), $blog->name));

        $body = $tpl->process();
        $this->getPage()->title = sprintf(_("Update blog paths - %s"), htmlspecialchars($blog->name));
        $this->getPage()->addStylesheet("form.css");
        $this->getPage()->display($body, $blog);
    }

    public function delcomment() {
        $entry = NewEntry();

        $this->verifyUserIsLoggedIn();

        # which determines if the resposne is to be deleted.
        extract($this->get_posted_responses());

        $tpl = NewTemplate('confirm_tpl.php');
        $tpl->set("CONFIRM_PAGE", current_file() );
        $tpl->set("OK_ID", 'conf');
        $tpl->set("OK_LABEL", _("Yes"));
        $tpl->set("CANCEL_ID", _("Cancel"));
        $tpl->set("CANCEL_LABEL", _("Cancel"));
        $tpl->set("PASS_DATA_ID", "responselist");

        $anchors = '';

        if ( count($response_array) <= 0 ) {

            # No permission to delete anything, so bail out.

            $title = _("Permission denied");
            $message = _("You do not have permission to delete any of the selected responses.");

        } elseif ( count($response_array) > 0 && count($denied_array) > 0 ) {

            # We have some responses that can't be deleted, so confirm with the user.

            $title = _("Delete responses");
            $good_list = '';
            $bad_list = '';

            foreach ($response_array as $resp) {
                $anchors .= ($anchors == '' ? '' : ',').$resp->getAnchor();
                $good_list .= get_list_text($resp);
            }

            foreach ($denied_array as $obj) {
                $bad_list .= get_list_text($obj);
            }

            $message = _("Delete the following responses?").
                       '<ul>'.$good_list.'</ul>'.
                       _('The following responses will <strong>not</strong> be deleted because you do not have sufficient permissions.').
                       '<ul>'.$bad_list.'</ul>';

        } elseif (POST('conf') || GET("conf") == "yes") {

            # We already have confirmation, either from the form or the query string,
            # and a list of only valid responses, so now we can actually delete them.

            $ret = do_delete($response_array);

            if ( count($ret) > 0) {

                $list = '';

                foreach ($ret as $obj) {
                    $anchors .= ($anchors == '' ? '' : ',').$resp->getAnchor();
                    $list .= get_link_text($obj);
                }
                $title = _("Error deleting responses");
                $message = _("Unable to delete the following responses.  Do you want to try again?");
                $message .= $list;
            } else {
                $this->getPage()->redirect($entry->permalink());
                exit;
            }

        } else {

            # Last is the case where we have all good responses, but no confirmation.

            $list = '';

            foreach ($response_array as $resp) {
                $anchors .= ($anchors == '' ? '' : ',').$resp->getAnchor();
                $list .= get_list_text($resp);
            }

            $title = _("Delete responses");
            $message = _("Do you want to delete the following responses?");
            $message .= $list;

        }

        $tpl->set("CONFIRM_TITLE", $title);
        $tpl->set("CONFIRM_MESSAGE",$message);
        $tpl->set("PASS_DATA", $anchors);

        $body = $tpl->process();
        $this->getPage()->title = sprintf("%s - %s", $this->blog->name, $title);

        $this->getPage()->display($body, $this->blog);
    }

    public function delentry() {
        $ent = NewEntry();

        $this->getPage()->setDisplayObject($ent);

        $is_draft = $ent->isDraft();
        $is_art = $ent->isArticle() ? true : false;

        $conf_id = _("OK");
        $cancel_id = _("Cancel");
        $message = spf_("Do you really want to delete '%s'?", $ent->subject);

        if (POST($conf_id)) {
            $err = false;
            if (System::instance()->canDelete($ent, $this->user) && $this->user->checkLogin()) {
                try {
                    $this->getPublisher()->delete($ent);
                } catch (EntryDeleteFailed $error) {
                    $message = spf_("Error: Unable to delete '%s'.  Try again?", $ent->subject);
                }
                $url = $is_draft ?$this->blog->uri('listdrafts') : $this->blog->getURL();
                $this->getPage()->redirect($url);
            } else {
                $message = _("Error: user ".$this->user->username()." does not have permission to delete this entry.");
            }
        } elseif (POST($cancel_id)) {

            $this->getPage()->redirect($ent->permalink());

        } elseif ( empty($_POST) && ! $this->user->checkLogin() ) {
            # Prevent user agents from just navigating to this page.
            # Note that users whose cookies expire while on the page will
            # still see error messages.
            header("HTTP/1.0 403 Forbidden");
            p_("Access to this page is restricted to logged-in users.");
            exit;
        }

        $tpl = NewTemplate(CONFIRM_TEMPLATE);
        $tpl->set("CONFIRM_TITLE", $is_art ? _("Remove article?") : _("Remove entry?"));
        $tpl->set("CONFIRM_MESSAGE",$message);
        #$tpl->set("CONFIRM_PAGE", current_file() );
        $tpl->set("CONFIRM_PAGE", '');
        $tpl->set("OK_ID", $conf_id);
        $tpl->set("OK_LABEL", _("Yes"));
        $tpl->set("CANCEL_ID", $cancel_id);
        $tpl->set("CANCEL_LABEL", _("No"));

        $body = $tpl->process();

        $this->getPage()->title = $is_art ? spf_("%s - Delete entry", $this->blog->name) :
                                 spf_("%s - Delete article", $this->blog->name);;
        $this->getPage()->display($body, $this->blog);
    }

    public function editfile() {

        $file = GET("file");
        if (PATH_DELIM  != '/') {
            $file = str_replace('/', PATH_DELIM, $file);
        }
        $file = str_replace("..".PATH_DELIM, '', $file);

        $ent = NewBlogEntry();

        $relpath = INSTALL_ROOT;

        $message_403 = _("You do not have permission to edit this file.");

        $this->verifyUserIsLoggedIn(SERVER("referer"), $message_403);

        if ( GET("profile") == $this->user->username() ) {
            $relpath = Path::get(USER_DATA_PATH, $this->user->username());
        } elseif ($ent->isEntry() ) {
            $this->getPage()->setDisplayObject($ent);
            $relpath = $ent->localpath();
            if (System::instance()->canModify($ent, $this->user) ) {
                $this->redirectOr403(SERVER("referer"), $message_403);
            }
        } elseif ($this->blog->isBlog() ) {
            $this->getPage()->setDisplayObject($this->blog);
            $relpath = $this->blog->home_path;
            $this->verifyUserCanModifyBlog(SERVER("referer"), $message_403);
        } elseif (! $this->user->isAdministrator() ) {
            $this->redirectOr403(SERVER("referer"), $message_403);
        }

        $tpl = NewTemplate("file_edit_tpl.php");

        # Prepare template for link list display.
        if (GET("list")) {
            $tpl->set("SHOW_LINK_EDITOR");
            $this->getPage()->addScript("sitemap.js");
        }

        $tpl->set("FORM_ACTION", make_uri(false,false,false));
        if (isset($_GET["list"])) {
            $tpl->set("PAGE_TITLE", _("Edit Link List"));
        } else {
            $tpl->set("PAGE_TITLE", _("Edit Text File"));
        }

        if (substr($file, 0, 9) == 'userdata/') {
            $file = Path::mk(USER_DATA_PATH, substr($file, 9));
        } else {
            $file = Path::mk($relpath, $file);
        }

        if (has_post()) {

            $data = POST("output");
            $ret = write_file($file, $data);

            if (! $ret) {
                $tpl->set("EDIT_ERROR", _("Cannot create file"));
                $tpl->set("ERROR_MESSAGE",
                          spf_("Unable to create file %s.", $file));
                $tpl->set("FILE_TEXT", htmlentities($data));
            }

        } else {

            if (is_file($file)) {
                $data = file_get_contents($file);
            } else {
                $data = "";
                if (! GET('map')) {
                    $tpl->set("EDIT_ERROR", _("Create new file"));
                    $tpl->set("ERROR_MESSAGE", _("The selected file does not exist.  It will be created."));
                }
            }
        }

        $tpl->set("FILE_TEXT", htmlentities($data));

        if (GET('map')) {
            $tpl->set("SITEMAP_MODE");
            $tpl->set("FORM_MESSAGE", spf_('This page will help you create a site map to display in the navigation bar at the top of your blog.  This file is stored under the name %s in the root directory of your weblog for a personal sitemap or in the %s installation directory for the system default.  This file in simply a series of <abbr title="Hypertext Markup Language">HTML</abbr> links, each on it\'s own line, which the template will process into a list.  If you require a more complicated menu bar, you will have to create a custom template.',
                                           basename(SITEMAP_FILE), PACKAGE_NAME));
            $tpl->set("PAGE_TITLE", _("Create site map"));
        } else {
            $tpl->set("FILE_PATH", $file);
            $tpl->set("FILE_SIZE", file_exists($file)?filesize($file):0);
            $tpl->set("FILE_URL", localpath_to_uri($file));
            $tpl->set("FILE", $file);
        }

        if (! defined("BLOG_ROOT")) {
            $this->blog = false;
        }

        $this->getPage()->raiseEvent('FileEditorReady');

        $this->getPage()->title = _("Edit file");
        $this->getPage()->addStylesheet("form.css");
        $this->getPage()->display($tpl->process(), $this->blog);
    }

    public function editlogin() {
        $edit_user = NewUser();

        $redir_url = $this->blog->isBlog() ? $this->blog->uri('blog') : INSTALL_ROOT_URL;
        $this->verifyUserIsLoggedIn($redir_url);

        if ($edit_user->isAdministrator() && isset($_GET['user'])) {
            $usr = NewUser($_GET['user']);
            if (! $usr->exists()) {
                $usr = NewUser();
            }
        } else {
            $usr = NewUser();
        }
        $this->getPage()->setDisplayObject($usr);

        # Allow us to use this to create the admin login.
        if ($this->blog->isBlog()) {
            $page_name = _("Change User Information");
            $form_title = spf_("New Login for %s", $this->blog->name);
            $redir_page = $this->blog->uri('blog');
        } else {
            $page_name = _("Change Administrator Login");
            $form_title = _("System Aministration Login");
            $redir_page = INSTALL_ROOT_URL;
        }

        $form_title = spf_("Modify User - %s", $usr->username());
        $user_name = "user";
        $reset="reset";  # Set to 1 to reset the password.

        $tpl = NewTemplate("login_create_tpl.php");
        $tpl->set("FORM_TITLE", $form_title);
        $tpl->set("FORM_ACTION", current_file());
        $tpl->set("FULLNAME_VALUE", htmlentities($usr->name()) );
        $tpl->set("EMAIL_VALUE", htmlentities($usr->email()) );
        $tpl->set("HOMEPAGE_VALUE", htmlentities($usr->homepage()) );
        $tpl->set("PROFILEPAGE_VALUE", htmlentities($usr->profileUrl()) );

        $this->blog_qs = ($this->blog->isBlog() ? "blog=".$this->blog->blogid."&amp;" : "");

        $tpl->set("UPLOAD_LINK", $this->blog->uri("upload", ['profile' => $usr->username()]));
        $edit_link_data = ["file"=>"profile.htm", 'profile'=>$usr->username()];
        $tpl->set("PROFILE_EDIT_LINK", $this->blog->uri("editfile", $edit_link_data));
        $tpl->set("PROFILE_EDIT_DESC", _("Edit extra profile data") );
        $tpl->set("UPLOAD_DESC", _("Upload file to profile") );

        # Populate the form with custom profile fields.
        $priv_path = mkpath(USER_DATA_PATH,$usr->username(),CUSTOM_PROFILE);
        $cust_path = mkpath(USER_DATA_PATH,CUSTOM_PROFILE);
        $cust_ini = NewINIParser($priv_path);
        $cust_ini->merge(NewINIParser($cust_path));

        $section = $cust_ini->getSection(CUSTOM_PROFILE_SECTION);
        $tpl->set("CUSTOM_FIELDS", $section);
        $tpl->set("CUSTOM_VALUES", $usr->custom);

        if (has_post()) {

            if ( trim(POST('passwd')) &&
                 trim(POST('passwd')) == trim(POST('confirm'))) {
                $pwd_change = true;
                $usr->password(trim(POST('passwd')));
            } elseif ( trim(POST('passwd')) &&
                       trim(POST('passwd')) == trim(POST('confirm'))) {
                $tpl->set("FORM_MESSAGE", _("The passwords you entered do not match."));
            } else {
                $pwd_change = false;
            }

            $usr->name(trim(POST('fullname')));
            $usr->email(trim(POST('email')));
            $usr->homepage(trim(POST('homepage')));
            $usr->profileUrl(trim(POST('profile_url')));

            foreach ($section as $key=>$val) {
                $usr->custom[$key] = trim(POST($key));
            }

            $usr->save();

            if ($pwd_change) {
                $usr->login(POST('passwd'));
            }

            $this->getPage()->redirect($redir_page);

        }

        $body = $tpl->process();
        if (! defined("BLOG_ROOT")) {
            $this->blog = false;
        }
        $this->getPage()->addStylesheet("form.css");
        $this->getPage()->title = $page_name;
        $this->getPage()->display($body, $this->blog);
    }

    private function updatePageFromEntrySaveResult($res, $tpl, $ent) {
        if ($res['errors']) {
            $tpl->set("HAS_UPDATE_ERROR");
            $tpl->set("UPDATE_ERROR_MESSAGE", $res['errors'] . (isset($res['warnings']) ? $res['warnings'] : ''));
            $this->entry_set_template($tpl, $ent);
        } elseif ($res['warnings']) {
            $refresh_delay = 10;
            $error = $res['warnings'] . "<p>" .
                spf_(
                    'You will be redirected to <a href="%s">the new entry</a> in %d seconds.',
                    $ent->permalink(),
                    $refresh_delay
                ) . "</p>";
            $tpl->set("HAS_UPDATE_ERROR");
            $tpl->set("UPDATE_ERROR_MESSAGE", $error);
            $this->getPage()->refresh($ent->permalink(), $refresh_delay);
        } elseif ( POST('draft') ) {
            $this->getPage()->redirect($this->blog->uri('listdrafts'));
            return false;
        } elseif ($this->editIsPreview()) {
            if (GET('save') == 'draft' && !GET('ajax')) {
                $uri = create_uri_object($ent);
                $uri->separator = '&';
                $this->getPage()->redirect($uri->editDraft(true));
                return false;
            }

            $is_art = !empty($_POST['publisharticle']) || GET('type') == 'article';
            $this->user->exportVars($tpl);
            $this->blog->raiseEvent($is_art? "OnArticlePreview" : "OnEntryPreview");
            $this->entry_set_template($tpl, $ent);

            if (GET('ajax')) {
                $response = array(
                    'id' => $ent->entryID(),
                    'exists' => $ent->isEntry(),
                    'isDraft' => $ent->isDraft(),
                    'content' => rawurlencode($ent->get())
                );
                echo json_encode($response);
                return false;
            } else {
                $tpl->set("PREVIEW_DATA", $ent->get() );
            }
        } else {
            $this->getPage()->redirect($ent->permalink());
            return false;
        }
        return true;
    }

    private function getEntryPreSaveError($ent) {
        if (! $ent->data) {
            return _("error: entry contains no data.");
        }

        if (! $this->check_perms($this->blog, $ent, $this->user)) {
            return spf_("permission denied: user %s cannot update this entry.", $this->user->username());
        }

        return '';
    }

    public function entryedit() {

        $ent = $this->getEntry();
        $this->getPage()->setDisplayObject($ent->isEntry() ? $ent : $this->blog);
        $is_art = !empty($_POST['publisharticle']) || GET('type') == 'article';

        $ent->raiseEvent('OnUpdateUiInit');

        $tpl = $this->init_template($this->blog, $ent, $is_art);

        if ( empty($_POST) && ! $this->user->checkLogin() ) {
            return $this->redirectOr403(null, _("Access to this page is restricted to logged-in users."));
        }

        $ent->getPostData();

        if (!empty($_POST)) {
            $res = array('errors' => '', 'warnings' => '');
            $res['errors'] = $this->getEntryPreSaveError($ent);

            if (!$res['errors']) {
                $res = $this->persistEntry($ent, $is_art);
            }

            $continue = $this->updatePageFromEntrySaveResult($res, $tpl, $ent);

            if (!$continue) {
                return;
            }
        }

        $page_body = $tpl->process();

        $entry_data = array(
            'entryId' => $ent->entryID(),
            'entryExists' => $ent->isEntry(),
            'entryIsDraft' => $ent->isDraft(),
        );
        $this->getPage()->addInlineScript("window.entryData = " . json_encode($entry_data, true) . ";");

        $title = $is_art ? _("New Article") : _("New Entry");
        $this->getPage()->title = sprintf("%s - %s", $this->blog->name, $title);
        $this->getPage()->addStylesheet("form.css");
        $this->getPage()->addStylesheet("entry.css");
        $this->getPage()->addStylesheet("jquery.datetimepicker.css");
        $this->getPage()->addStylesheet("dropzone.css");
        $this->getPage()->addScript("jquery.form.js");
        $this->getPage()->addScript("jquery.datetimepicker.js");
        $this->getPage()->addScript("editor.js");
        $this->getPage()->addScript("dropzone.js");
        $this->getPage()->addScript("upload.js");
        $this->getPage()->addScript(lang_js());
        $this->getPage()->display($page_body, $this->blog);
    }

    protected function check_perms($blog, $entry, $user) {
        $sys = System::instance();
        return $user->checkLogin() && (
            (!$entry->isEntry() && $sys->canAddTo($blog, $user)) ||
            ($entry->isEntry() && $sys->canModify($entry, $user))
        );
    }

    protected function init_template($blog, $entry, $is_article = false) {
        $tpl = NewTemplate(ENTRY_EDIT_TEMPLATE);
        $tpl->set('PUBLISHED', false);

        $this->entry_set_template($tpl, $entry);

        if ($entry->isEntry()) {
            $tpl->set('PUBLISHED', $entry->isPublished());
            $tpl->set('ARTICLE', $entry->isArticle());
            $tpl->set('SEND_PINGBACKS', $entry->send_pingback);
            $tpl->set('ENTRYID', $entry->entryID());
        } else if ($is_article) {
            $tpl->set('ARTICLE', true);
            $tpl->set("GET_SHORT_PATH");
            $tpl->set("COMMENTS", false);
            $tpl->set("TRACKBACKS", false);
            $tpl->set("PINGBACKS", false);
            $tpl->set("HAS_HTML", $blog->default_markup);
            $send_pingbacks = $entry->isEntry() ?
                $this->send_pingback :
                $blog->autoPingbackEnabled();
            $tpl->set('SEND_PINGBACKS', $send_pingbacks);
        } else {
            $tpl->set('ARTICLE', true);
            $tpl->set("HAS_HTML", $blog->default_markup);
            $tpl->set('SEND_PINGBACKS', $blog->autoPingbackEnabled());
        }

        $auto_publish = POST('autopublishdate') ?: ($entry ? $entry->getAutoPublishDate() : '');
        $tpl->set('AUTO_PUBLISH_DATE', $auto_publish);

        $tpl->set("ALLOW_ENCLOSURE", $blog->allow_enclosure);
        sort($blog->tag_list);
        $tpl->set("BLOG_TAGS", $blog->tag_list);

        $tpl->set("FORM_ACTION", make_uri(false,false,false) );
        $blog->exportVars($tpl);

        return $tpl;
    }

    # Function: entry_set_template
    # Sets variables in an entry template for display.
    #
    # Parameters:
    # tpl - The template to populate.
    # ent - the BlogEntry or Article with which to populate the template.
    private function entry_set_template($tpl, $ent) {
        $tpl->set("URL", $ent->article_path);
        $tpl->set("PUBLISHARTICLE", $ent->is_article);
        $tpl->set("SUBJECT", htmlspecialchars($ent->subject));
        $tpl->set("TAGS", htmlspecialchars($ent->tags));
        $tpl->set("DATA", htmlspecialchars($ent->data));
        $tpl->set("ENCLOSURE", $ent->enclosure);
        $tpl->set("HAS_HTML", $ent->has_html);
        $tpl->set("COMMENTS", $ent->allow_comment);
        $tpl->set("TRACKBACKS", $ent->allow_tb);
        $tpl->set("PINGBACKS", $ent->allow_pingback);
        if ($ent->isArticle()) {
            $tpl->set("STICKY", $ent->isSticky());
        } else {
            $tpl->set("STICKY", $ent->is_sticky ? true : false);
        }

        $entry_attachments = $ent->isEntry() ? $ent->getAttachments() : [];
        $tpl->set("ENTRY_ATTACHMENTS", $entry_attachments);
        $tpl->set("BLOG_ATTACHMENTS", $this->blog->getAttachments());
    }

    private function handlePingbackPings($ent) {
        $errors = array();
        $err = '';

        foreach ($this->last_pingback_results as $res) {
            if ($res['response']['code']) {
                $errors[] = spf_('URI: %s', $res['uri']).'<br />'.
                            spf_("Error %d: %s<br />",
                                 $res['response']['code'],
                                 $res['response']['message']
                             );
            }
        }

        if ($errors) {
            $err = "<p>"._("Failed to send the following pingbacks:").
                   '<br />'.implode("\n<br />", $errors)."</p>";
        }
        return $err;
    }

    private function handleUploads() {
        $err = '';
        if ($this->last_upload_error) {
            $err = _("File upload errors:")."<br />".
                    implode("\n<br />", $this->last_upload_error);
            $err = "<p>$err</p>";
        }
        return $err;
    }

    public function fileupload() {
        $num_fields = 1;
        $target_under_blog = "";

        $ent = NewBlogEntry();
        $tpl = NewTemplate(UPLOAD_TEMPLATE);
        $tpl->set("NUM_UPLOAD_FIELDS", $num_fields);

        $blog_files = [];
        $entry_files = [];

        $target = false;
        if ( isset($_GET["profile"]) &&
             ($_GET["profile"] == $this->user->username() || $this->user->isAdministrator()) ) {
            $target = Path::mk(USER_DATA_PATH, $this->user->username());
        } elseif ($ent->isEntry() && System::instance()->canModify($ent, $this->user)) {
            $entry_data = array(
                'entryId' => $ent->entryID(),
                'entryExists' => $ent->isEntry(),
                'entryIsDraft' => $ent->isDraft(),
            );
            $this->getPage()->addInlineScript("window.entryData = " . json_encode($entry_data, true) . ";");

            $target = $ent->localpath();
            $entry_files = $ent->getAttachments();
            $blog_files = $this->blog->getAttachments();
        } elseif (System::instance()->canModify($this->blog, $this->user)) {
            $target = $this->blog->home_path;
            $blog_files = $this->blog->getAttachments();
            if ($target_under_blog) {
                $target = mkpath($target, $target_under_blog);
            }
        }

        # Check that the user is logged in.
        if (! $this->user->checkLogin()) {
            $target = false;
        }

        if ($target) {

            $file_name = "upload";
            $success = false;
            $messages = [];

            if (! empty($_FILES)) {
                $files = FileUpload::initUploads($_FILES[$file_name], $target);

                $result = $this->moveUploadedFiles($files);
                $messages = $result['messages'];
                $success = $result['success'];
                $msg = spf_(
                    "Select files to upload to the above location." .
                        "  The file size limit is %s.",
                    ini_get("upload_max_filesize")
                );
                if (!empty($messages)) {
                    $msg = implode("<br />", $messages);
                }

                $tpl->set("UPLOAD_MESSAGE", $msg);
            }

            if (POST('ajax') || GET('ajax')) {
                if (!$success) {
                    header("HTTP/1.0 500 Server Error");
                }
                header("Content-Type: application/json");
                echo json_encode(array(
                    'success' => $success,
                    'messages' => $messages,
                ));
                return false;
            }

            $query_string = isset($_GET["blog"])?"?blog=".$_GET["blog"]:'';
            $query_string .= isset($_GET["profile"]) ?
                ($query_string?"&amp;":"?")."profile=".$_GET["profile"] :
                "";

            $tpl->set("TARGET", current_file().$query_string);
            $size = ini_get("upload_max_filesize");
            $size = str_replace("K", "000", $size);
            $size = str_replace("M", "000000", $size);
            $tpl->set("MAX_SIZE", $size);
            $tpl->set("FILE", $file_name);
            $tpl->set("TARGET_URL", localpath_to_uri($target) );
            $tpl->set("BLOG_ATTACHMENTS", $blog_files);
            $tpl->set("ENTRY_ATTACHMENTS", $entry_files);

            $body = $tpl->process();

        } else {
            $body = "<h3>";
            if ($ent->isEntry()) {
                $body .= _("You do not have permission to upload files to this entry.");
            } else {
                $body .= _("You do not have permission to upload files to this weblog.");
            }
            $body .= "</h3>";
        }

        $this->getPage()->addStylesheet("form.css");
        $this->getPage()->addStylesheet("dropzone.css");
        $this->getPage()->title = _("Upload file");
        $this->getPage()->addScript(lang_js());
        $this->getPage()->addScript('dropzone.js');
        $this->getPage()->addScript('upload.js');
        $this->getPage()->display($body, $this->blog);
    }

    private function moveUploadedFiles($files) {
        $errors = [];
        $result = true;
        foreach ($files as $f) {
            $upload_attempted =
                $f->status() != FILEUPLOAD_NOT_INITIALIZED &&
                $f->size > 0;
            if ($upload_attempted) {
                $errors[] = $f->errorMessage();
                if ($f->completed()) {
                    $result = $result && $f->moveFile();
                } else {
                    $result = false;
                }
            }
        }
        return array("success" => $result, "messages" => $errors);
    }

    public function removefile() {
        $entry_id = POST("entry");
        $file_name = POST("file");

        $entry = NewEntry();

        try {
            if ($entry->isEntry()) {
                $entry->removeAttachment($file_name);
            } else {
                $this->blog->removeAttachment($file_name);
            }
        } catch (Exception $e) {
            $this->getPage()->error(500, "Could not delete file '$file'");
        }
    }

    public function managereplies() {

        $ent = NewEntry();

        if ( $ent && $ent->isEntry() ) {
            $main_obj = $ent;
        } else {
            $main_obj = $this->blog;
        }

        $this->getPage()->setDisplayObject($main_obj);

        if ($this->has_posted_responses()) {
            $body = $this->handle_deletes();
            if ($body === true) {
                #$this->getPage()->redirect(make_uri(false, false, false, '&'));
                # It seems that the POST data is passed on when you redirect to the same
                # page.  You learn something new every day.
                $body = $this->show_reply_list($main_obj);
            }
        } else {
            $body = $this->show_reply_list($main_obj);
        }

        $this->getPage()->title = $this->blog->title()." - "._('Manage replies');
        $this->getPage()->display($body, $this->blog);
    }

    protected function get_display_markup(&$item, $count) {
        $ret = reply_boxes($count, $item);
        $ret .= '<a href="'.$item->permalink().'">'.$item->title()."</a>";
        return $ret;
    }

    protected function has_posted_responses() {
        if (POST('responselist')) {
            return true;
        } elseif (POST('responseid0')) {
            $count = 0;
            while (POST("responseid$count")) $count++;
            for ($i = 0; $i <= $count; $i++) {
                if (POST("response$i"))     return true;
            }
            return false;
        } else {
            return false;
        }
    }

    protected function get_posted_responses() {
        $index = 1;
        $response_array = array();
        $denied_array = array();

        if (POST('responselist')) {

            $anchors = explode(',', $_POST['responselist']);
            foreach ($anchors as $a) {
                $obj = get_response_object($a, $this->user);
                if ($obj) {
                    $response_array[] = $obj;
                } else {
                    $denied_array[] = $a;
                }
            }

        } else {

            # For multiple deletes, there are two lists of fields.  The responseid#
            # is the anchor for that response, wile the response# is the
            # corresponding checkbox

            $index = 0;
            while ( isset($_POST['responseid'.$index]) ) {
                if ( POST('response'.$index) ) {
                    $obj = get_response_object($_POST['responseid'.$index], $this->user);
                    if ($obj) {
                        $response_array[] = $obj;
                    } else {
                        $denied_array[] = $_POST['responseid'.$index];
                    }
                }
                $index++;
            }

            # Here we extract any response that may have been passed in the query string.
            $getvars = array('comment', 'delete', 'response');
            foreach ($getvars as $var) {
                if (GET($var)) {
                    $obj = get_response_object(GET($var), $this->user);
                    if ($obj) {
                        $response_array[] = $obj;
                    } else {
                        $denied_array[] = GET($var);
                    }
                }
            }
        }

        return compact('response_array', 'denied_array');
    }

    protected function get_reply_list(&$blog, &$ent) {
        if ( $ent && $ent->isEntry() ) {
            $use_ent = true;
            $obj = $ent;
        } else {
            $use_ent = false;
            $obj = $blog;
        }

        if (GET("type") == 'comment') {
            $method = $use_ent ? 'getCommentArray' : 'getEntryComments';
        } elseif (GET("type") == 'trackback') {
            $method = $use_ent ? 'getCommentArray' : 'getEntryComments';
        } elseif (GET("type") == 'pingback') {
            $method = $use_ent ? 'getCommentArray' : 'getEntryComments';
            $cmt_array = $blog->getEntryComments();
        } else {
            $method = $use_ent ? 'getReplyArray' : 'getEntryComments';
            $cmt_array = $blog->getEntryReplies();
        }
    }

    protected function get_archive_objects(&$blog, $type) {
        $ret = array();
        if (isset($_GET['year']) && isset($_GET['month'])) {
            $ents = $blog->getMonth(sanitize(GET('year'), '/\D/'),
                                    sanitize(GET('month'), '/\D/'));
        } elseif (isset($_GET['year'])) {
            $ents = $blog->getYear(sanitize(GET('year'), '/\D/'));
        } else {
            return array();
        }

        $ret = array();
        foreach ($ents as $e) {
            $ret = array_merge($ret, $this->get_reply_objects($e, $type));
        }
        return $ret;
    }

    protected function get_reply_objects(&$main_obj, $type) {
        switch ($type) {
            case 'comment':
                return $main_obj->getComments();
            case 'trackback':
                return $main_obj->getTrackbacks();
            case 'pingback':
                return $main_obj->getPingbacks();
            default:
                return $main_obj->getReplies();
        }
    }

    protected function get_title($main_obj, $type) {

        if (is_a($main_obj, 'Blog')) {
            $title = $main_obj->name;
        } else {
            $title = $main_obj->subject;
        }

        $title = '<a href="'.
                 $main_obj->uri('permalink').
                 '">'.$main_obj->title().'</a>';

        if (GET('year')) $year = sanitize(GET('year'), '/\D/');
        else $year = false;

        if (GET('year') && GET('month')) {
            $year = sanitize(GET('year'), '/\D/');
            $month = sanitize(GET('month'), '/\D/');
            $date = fmtdate("%B %Y", strtotime("$year-$month-01"));
        } elseif (GET('year')) {
            $date = sanitize(GET('year'), '/\D/');
        } else {
            $date = false;
        }

        switch ($type) {
            case 'comment':
                $ret = $date ? spf_("Comments on entries for %s", $date):
                               spf_("All comments on '%s'", $title);
                break;
            case 'trackback':
                $ret = $date ? spf_("TrackBacks on entries for %s", $date):
                               spf_("All TrackBacks for '%s'", $title);
                break;
            case 'pingback':
                $ret = $date ? spf_("Pingbacks on entries for %s", $date):
                               spf_("All Pingbacks for '%s'", $title);
                break;
            default:
                $ret = $date ? spf_("Replies on entries for %s", $date):
                               spf_("All replies for '%s'", $title);
        }
        return $ret;
    }

    protected function show_reply_list(&$main_obj) {
        $tpl = NewTemplate(LIST_TEMPLATE);

        if (GET('year') || GET('month')) {
            $repl_array = $this->get_archive_objects($main_obj, GET('type'));
        } else {
            $repl_array = $this->get_reply_objects($main_obj, GET('type'));
        }

        $tpl->set('FORM_ACTION', make_uri(false, false, false));

        $tpl->set('LIST_TITLE', $this->get_title($main_obj, GET('type')));
        $tpl->set('LIST_HEADER',
                spf_("View reply type:").
                ' <a href="'.make_uri(false, array('type'=>'all'), false).'">'.
                _("All Replies").'</a> | '.
                '<a href="'.make_uri(false, array('type'=>'comment'), false).'">'.
                _("Comments").'</a> | '.
                '<a href="'.make_uri(false, array('type'=>'trackback'), false).'">'.
                _("TrackBacks").'</a> | '.
                '<a href="'.make_uri(false, array('type'=>'pingback'), false).'">'.
                _("Pingbacks").'</a>');
        $tpl->set('FORM_FOOTER', '<input type="submit" value="'._('Delete').'" />');
        $ITEM_LIST = array();

        $idx = 0;
        foreach ($repl_array as $ent) {
            $ITEM_LIST[] = $this->get_display_markup($ent, $idx);
            $idx++;
        }

        $tpl->set("ITEM_LIST", $ITEM_LIST);
        $tpl->set("ORDERED");
        return $tpl->process();
    }

    protected function handle_deletes() {

        extract($this->get_posted_responses());

        $tpl = NewTemplate('confirm_tpl.php');
        $tpl->set("CONFIRM_PAGE", make_uri(false, false, false) );
        $tpl->set("OK_ID", 'conf');
        $tpl->set("OK_LABEL", _("Yes"));
        $tpl->set("CANCEL_ID", _("Cancel"));
        $tpl->set("CANCEL_LABEL", _("Cancel"));
        $tpl->set("PASS_DATA_ID", "responselist");

        $anchors = '';

        if ( count($response_array) <= 0 ) {

            # No permission to delete anything, so bail out.

            $title = _("Permission denied");
            $message = _("You do not have permission to delete any of the selected responses.");

        } elseif ( count($response_array) > 0 && count($denied_array) > 0 ) {

            # We have some responses that can't be deleted, so confirm with the user.

            $title = _("Delete responses");
            $good_list = '';
            $bad_list = '';

            foreach ($response_array as $resp) {
                $anchors .= ($anchors == '' ? '' : ',').$resp->getAnchor();
                $good_list .= get_list_text($resp);
            }

            foreach ($denied_array as $obj) {
                $bad_list .= get_list_text($obj);
            }

            $message = _("Delete the following responses?").
                    '<ul>'.$good_list.'</ul>'.
                    _('The following responses will <strong>not</strong> be deleted because you do not have sufficient permissions.').
                    '<ul>'.$bad_list.'</ul>';

        } elseif (POST('conf') || GET("conf") == "yes") {

            # We already have confirmation, either from the form or the query string,
            # and a list of only valid responses, so now we can actually delete them.

            $ret = do_delete($response_array);

            if ( count($ret) > 0) {

                $list = '';

                foreach ($ret as $obj) {
                    $anchors .= ($anchors == '' ? '' : ',').$resp->getAnchor();
                    $list .= get_link_text($obj);
                }
                $title = _("Error deleting responses");
                $message = _("Unable to delete the following responses.  Do you want to try again?");
                $message .= $list;
            } else {
                # Success!  Clear the PASS_DATA_ID to prevent erroneous errors
                # when the page reloads.
                $tpl->set("PASS_DATA_ID", "");
                return true;
            }

        } else {

            # Last is the case where we have all good responses, but no confirmation.

            $list = '';

            foreach ($response_array as $resp) {
                $anchors .= ($anchors == '' ? '' : ',').$resp->globalID();
                $list .= get_list_text($resp);
            }

            $title = _("Delete responses");
            $message = _("Do you want to delete the following responses?");
            $message .= $list;

        }

        $tpl->set("CONFIRM_TITLE", $title);
        $tpl->set("CONFIRM_MESSAGE",$message);
        $tpl->set("PASS_DATA", $anchors);

        return $tpl->process();

    }

    public function showall() {
        $this->blog = NewBlog();
        $this->getPage()->setDisplayObject($this->blog);

        $title = spf_("All entries for %s.", $this->blog->name);
        $this->blog->getRecent(-1);

        $tpl = NewTemplate(LIST_TEMPLATE);
        $tpl->set("LIST_TITLE", spf_("Archive of %s", $title));

        $LINK_LIST = array();

        foreach ($this->blog->entrylist as $ent) {
            $LINK_LIST[] = array("link"=>$ent->permalink(), "title"=>$ent->subject);
        }

        $tpl->set("LINK_LIST", $LINK_LIST);
        $body = $tpl->process();

        $this->getPage()->title = $this->blog->name." - ".$title;
        $this->getPage()->display($body, $this->blog);
    }

    function show_base_archives(&$blog) {

        $tpl = NewTemplate(LIST_TEMPLATE);

        if ( strtolower(GET('list')) == 'yes') {
            $list = $blog->getRecentMonthList(0);
            foreach ($list as $key=>$item) {
                $ts = strtotime($item['year'].'-'.$item['month'].'-01');
                $list[$key]["title"] = strftime("%B %Y", $ts);
            }
            $footer_text = '<a href="'.
                           make_uri(false, array("list"=>"no"), false).'">'.
                           _("Show year list").'</a>';
        } else {
            $list = $blog->getYearList();
            foreach ($list as $key=>$item) {
                $list[$key]["title"] = $item["year"];
            }
            $footer_text = '<a href="'.
                           make_uri(false, array("list"=>"yes"), false).'">'.
                           _("Show month list").'</a>';
        }

        if (System::instance()->canModify($blog))
            $footer_text .= ' | <a href="'.
                            $blog->uri('manage_all').'">'.
                            #make_uri(false, array('action'=>'manage_reply'), false).
                            _("Manage replies").'</a>';

        $tpl->set('LIST_TITLE', $this->get_list_title($blog));
        if (empty($list)) {
            $tpl->set("LIST_HEADER", _("There are no entries for this blog."));
        } else {
            $tpl->set("LIST_FOOTER", $footer_text);
        }

        $tpl->set('LINK_LIST', $list);

        return $tpl->process();
    }

    function show_year_archives(&$blog, $year) {
        $tpl = NewTemplate(LIST_TEMPLATE);

        if ( strtolower(GET('list')) == 'yes' ) {
            $ents = $blog->getYear($year);
            $links = array();
            foreach ($ents as $ent) {
                $lnk = array('link'=>$ent->permalink(), 'title'=>$ent->title());
                $links[] = $lnk;
            }
            $footer_text = '<a id="list_toggle" href="'.
                        make_uri(false, array("list"=>"no"), false).'">'.
                        _("Show month list").'</a>';
        } else {
            $links = $blog->getMonthList($year);
            foreach ($links as $key=>$val) {
                $ts = mktime(0, 0, 0, $val["month"], 1, $val["year"]);
                $month_name = fmtdate("%B", $ts);
                $links[$key]["title"] = $month_name;
            }

            $footer_text = '<a href="'.make_uri(false, array("list"=>"yes"), false).'">'.
                            _("Show entry list").'</a>';
        }

        if (System::instance()->canModify($blog))
            $footer_text .= ' | <a href="'.
                            $blog->uri('manage_year', $year).'">'.
                            #make_uri(false, array('action'=>'manage_reply'), false).
                            _("Manage replies").'</a>';
        $footer_text .= " | ".
                        '<a href="'.$blog->uri('archives').'/">'.
                        _("Back to main archives").'</a>';

        $tpl->set('LIST_TITLE', $this->get_list_title($blog, $year));
        if (empty($links)) {
            $tpl->set("LIST_HEADER", _("There are no entries for this year."));
        } else {
            $tpl->set("LIST_FOOTER", $footer_text);
        }
        $tpl->set('LINK_LIST', $links);

        return $tpl->process();
    }

    protected function show_month_archives(&$blog, $year, $month) {
        $list = $blog->getMonth($year, $month);

        if ( strtolower(GET('show')) == 'all' ) {
            $this->getPage()->addStylesheet("entry.css");
            return $blog->getWeblog();
        } else {

            $tpl = NewTemplate(LIST_TEMPLATE);

            $links = array();
            foreach ($list as $ent) {
                $links[] = array("link"=>$ent->permalink(), "title"=>$ent->subject);
            }

            $footer_text = '<a href="?show=all">'.
                           _("Show all entries at once").'</a>'.
                           (System::instance()->canModify($blog) ?
                            ' | <a href="'.$blog->uri('manage_month', $year, $month).'">'.
                            _("Manage replies").'</a>' : '').
                           ' | <a href="'.$blog->getURL().BLOG_ENTRY_PATH."/$year/".
                           '">'.spf_("Back to archive of %s", $year).'</a>';

            $tpl->set('LIST_TITLE', $this->get_list_title($blog, $year, $month));
            if (empty($links)) {
                $tpl->set("LIST_HEADER", _("There are no entries for this month."));
            } else {
                $tpl->set('LIST_FOOTER', $footer_text);
            }

            $tpl->set('LINK_LIST', $links);

            return $tpl->process();
        }
    }

    protected function show_day_archives(&$blog, $year, $month, $day) {

        $ret = $blog->getDay($year, $month, $day);

        if (count($ret) == 1) {
            $body = array(true, $ret[0]->permalink());
        } elseif (count($ret) == 0) {
            $body = spf_("No entry found for %d-%d-%d", $year, $month, $day);
        } else {
            $this->getPage()->addStyleSheet("entry.css");
            $body = $blog->getWeblog();
        }
        return $body;
    }

    protected function get_list_title(&$blog, $year=false, $month=false) {
        if ($month) {
            $date = fmtdate("%B %Y", strtotime("$year-$month-01"));
        } elseif ($year) {
            $date = fmtdate("%Y", strtotime("$year-01-01"));
        } else {
            $date = "'".$blog->title()."'";
        }
        return spf_("Archives for %s", $date);
    }

    public function showarchive() {
        $this->getPage()->setDisplayObject($this->blog);

        $monthdir = basename(getcwd());
        $yeardir = basename(dirname(getcwd()));
        $month = false;

        # First, check for the month and year in the query string.
        if (sanitize(GET('month'), '/\D/') &&
            sanitize(GET('year'), '/\D/') ) {
            $month = sanitize(GET('month'), '/\D/');
            $year = sanitize(GET('year'), '/\D/');

        # Failing that, try the directory names.
        } elseif ( preg_match('/^\d\d$/', $monthdir) &&
                   preg_match('/^\d\d\d\d$/',$yeardir) ) {
            $month = $monthdir;
            $year = $yeardir;

        # If THAT fails, then there must not be a month, so try just the year.
        } elseif ( sanitize(GET('year'), '/\D/') ) {
            $year = sanitize(GET('year'), '/\D/');
        } elseif ( preg_match('/^\d\d\d\d$/',$monthdir) ) {
            $year = $monthdir;

        # If we still don't have a year, show the base archives.
        } else {
            $year = false;
        }

        $day = isset($_GET['day']) ? sprintf("%02d", GET("day") ) : false;

        if ($year && $month && $day) {

            $body = $this->show_day_archives($this->blog, $year, $month, $day);
            if (is_array($body)) {
                $this->getPage()->redirect( $body[1] );
                exit;
            }

        } elseif ($year && $month) {

            $body = $this->show_month_archives($this->blog, $year, $month);

        } elseif ($year) {

            $body = $this->show_year_archives($this->blog, $year);

        } else {

            $body = $this->show_base_archives($this->blog);

        }

        if (GET('ajax')) {
            echo $body;
        } else {
            $this->getPage()->display($body, $this->blog);
        }
    }

    public function showarticles() {
        $this->getPage()->setDisplayObject($this->blog);

        $year_dir = basename(getcwd());
        $title = $this->blog->name." - ".$year_dir;
        $list = scan_directory(getcwd(), true);
        sort($list);

        $tpl = NewTemplate(LIST_TEMPLATE);
        $tpl->set("LIST_TITLE", spf_("%s articles", $this->blog->name));

        $LINK_LIST = $this->blog->getArticleList(false, false);

        $tpl->set("LINK_LIST", $LINK_LIST);
        $body = $tpl->process();

        $this->getPage()->title = $title;
        $this->getPage()->display($body, $this->blog);
    }

    # Function: show_blog_page
    # Shows the main blog page.  This is typically the front page of the blog.
    protected function show_blog_page(&$blog) {
        $ret = $blog->getWeblog();
        $this->getPage()->title = $blog->title();
        $this->getPage()->addStylesheet("entry.css");
        return $ret;
    }

    public function showblog() {
        $this->blog->autoPublishDrafts();
        $this->getPage()->setDisplayObject($this->blog);

        $content = $this->show_blog_page($this->blog);
        $this->getPage()->display($content, $this->blog);
    }

    protected function draft_item_markup(&$ent) {
        $del_uri = $ent->uri('delete');
        $edit_uri = $ent->uri('editDraft');
        $title = $ent->subject ? $ent->subject : $ent->date;
        $ret = '<a href="'.$edit_uri.'">'.$title.'</a> '.
               '<span style="font-size: 80%; color: gray;">' . date("Y-m-d", $ent->post_ts) .
               '</span> (<a href="'.$del_uri.'">'._("Delete").'</a>)';
        return $ret;
    }

    public function showdrafts() {
        if (isset($_GET['action']) && $_GET['action'] == 'edit') {
            return $this->entryedit();
        } elseif (isset($_GET['action']) && $_GET['action'] == 'upload') {
            return $this->fileupload();
        }

        $list_months = false;

        $usr = User::get();
        $this->getPage()->setDisplayObject($this->blog);

        if (! $usr->checkLogin() || ! System::instance()->canModify($this->blog, $usr)) {
            $this->getPage()->error(403);
        }

        $title = spf_("%s - Drafts", $this->blog->name);

        $tpl = NewTemplate(LIST_TEMPLATE);
        $tpl->set("LIST_TITLE", spf_("Drafts for %s", $this->blog->name));

        $drafts = $this->blog->getDrafts();
        $linklist = array();
        foreach ($drafts as $d) {
            $linklist[] = $this->draft_item_markup($d);
        }
        $linklist = array_reverse($linklist);

        $tpl->set("ITEM_LIST", $linklist);
        $body = $tpl->process();

        $this->getPage()->title = $title;
        $this->getPage()->display($body, $this->blog);
    }

    # Function: show_comment_page
    # Show the page of comments on the entry.
    protected function show_comment_page(&$blg, &$ent, &$usr) {

        $this->getPage()->title = $ent->title() . " - " . $blg->title();

        # This code will detect if a comment has been submitted and, if so,
        # will add it.  We do this before printing the comments so that a
        # new comment will be displayed on the page.
        # Here we include and call handle_comment() to output a comment form, add a
        # comment if one has been posted, and set "remember me" cookies.
        $comm_output = '';
        if ($ent->allow_comment) {
            $comm_output = handle_comment($ent, true);
        }

        $content = '';

        # Allow a query string to get just the comment form, not the actual comments.
        if (! GET('post')) {
            $content = show_comments($ent, $usr);
            # Extra styles to add.  Build the list as we go to keep from including more
            # style sheets than we need to.
            $this->getPage()->addStylesheet("reply.css");
        } elseif (! $ent->allow_comment) {
            $content = '<p>'._('Comments are closed on this entry.').'</p>';
        }
        $content .= $comm_output;

        $this->getPage()->addScript(lang_js());
        $this->getPage()->addScript("entry.js");

        return $content;

    }

    # Function: show_pingback_page
    # Show the page of Pingbacks for the entry.
    protected function show_pingback_page(&$blg, &$ent, &$usr) {

        $this->getPage()->title = $ent->title() . " - " . $blg->title();
        $this->getPage()->addScript(lang_js());
        $this->getPage()->addStylesheet("reply.css");
        $this->getPage()->addScript("entry.js");
        $body = show_pingbacks($ent, $usr);
        if (! $body) $body = '<p>'.
            spf_('There are no pingbacks for %s',
                 sprintf('<a href="%s">\'%s\'</a>',
                         $ent->permalink(), $ent->subject)).'</p>';
        return $body;
    }

    # Function: show_trackback_page
    # Shows the page of TrackBacks for the entry.
    protected function show_trackback_page(&$blg, &$ent, &$usr) {

        $this->getPage()->title = $ent->title() . " - " . $blg->title();
        $this->getPage()->addScript(lang_js());
        $this->getPage()->addStylesheet("reply.css");
        $this->getPage()->addScript("entry.js");
        $body = show_trackbacks($ent, $usr);
        if (! $body) {
            $body = '<p>'.spf_('There are no trackbacks for %s',
                               sprintf('<a href="%s">\'%s\'</a>',
                                       $ent->permalink(), $ent->subject)).'</p>';
        }
        return $body;
    }

    # Function: show_trackback_ping_page
    # Show the page from which users can send a TrackBack ping.
    protected function show_trackback_ping_page(&$blog, &$ent, &$usr) {

        $tpl = NewTemplate("send_trackback_tpl.php");

        if (System::instance()->canModify($ent, $usr) && $usr->checkLogin()) {
            $tb = NewTrackback();

            # Set default values for the trackback properties.
            $tb->title = $ent->title();
            $tb->blog = $blog->title();
            $tb->data = $ent->getAbstract();
            $tb->url = $ent->permalink();

            # If the form has been posted, send the trackback.
            if (has_post()) {

                $tb->url = trim(POST('url'));
                $tb->blog = POST('blog_name');
                $tb->data = POST('excerpt');
                $tb->title = POST('title');

                if ( ! trim(POST('target_url')) || ! POST('url') ) {
                    $tpl->set("ERROR_MESSAGE", _("You must supply an entry URL and a target URL."));
                } else {
                    $ret = $tb->send( trim(POST('target_url')) );
                    if ($ret['error'] == '0') {
                        $refresh_time = 5;
                        $tpl->set("ERROR_MESSAGE",
                                  spf_("Trackback ping succeded.  You will be returned to the entry in %d seconds.", $refresh_time));
                        $this->getPage()->refresh($ent->permalink(), $refresh_time);
                    } else {
                        $tpl->set("ERROR_MESSAGE",
                                  spf_('Error %s: %s', $ret['error'], $ret['message']).
                                  '<br /><textarea rows="20" cols="20">'.
                                  $ret['response'].'</textarea>');
                    }
                }
            }

            $tpl->set("TB_URL", $tb->url );
            $tpl->set("TB_TITLE", $tb->title);
            $tpl->set("TB_EXCERPT", $tb->data );
            $tpl->set("TB_BLOG", $tb->blog);
            $tpl->set("TARGET_URL", trim(POST('target_url')));

        } else {
            $tpl->set("ERROR_MESSAGE",
                      spf_("User %s cannot send trackback pings from this entry.",
                           $usr->username()));
        }


        $this->getPage()->title = _("Send Trackback Ping");
        $this->getPage()->addStyleSheet("form.css");

        return $tpl->process();
    }

    # Function: show_entry_page
    # Handles displaying the main permalink for a BlogEntry or Article.
    protected function show_entry_page(&$blg, &$ent, &$usr) {

        # Here we include and call handle_comment() to output a comment form, add a
        # comment if one has been posted, and set "remember me" cookies.
        $comm_output = '';
        if ($ent->allow_comment) {
            $comm_output = handle_comment($ent);
        }

        # Get the entry AFTER posting the comment so that the comment count is right.
        $this->getPage()->title = $ent->title() . " - " . $blg->title();
        $show_ctl = System::instance()->canModify($ent, $usr) && $usr->checkLogin();
        $content =  $ent->getFull($show_ctl);

        if (System::instance()->sys_ini->value("entryconfig", "GroupReplies", 0)) {
            $content .= show_all_replies($ent, $usr);
        }

        # Add comment form if applicable.
        $content .= $comm_output;

        if ($ent->enclosure) {
            $enc = $ent->getEnclosure();
            if ($enc) {
                $enc_arr = array("rel"=>'enclosure',
                                 "href"=>$enc['url'],
                                 "type"=>$enc['type']);
                $this->getPage()->addLink($enc_arr);
            }
        }

        if ($ent->allow_pingback) {
            $pingback_url = INSTALL_ROOT_URL."xmlrpc.php";
            $this->getPage()->addHeader("X-Pingback", $pingback_url);
            $this->getPage()->addLink(['rel'=>'pingback', 'href' => $pingback_url]);
            $webmention_url = INSTALL_ROOT_URL . "index.php?action=webmention";
            $this->getPage()->addHeader("Link", "<$webmention_url>; rel=\"webmention\"");
            $this->getPage()->addLink(['rel'=>'webmention', 'href' => $webmention_url]);
        }
        $this->getPage()->addScript(lang_js());
        $this->getPage()->addScript("entry.js");
        $this->getPage()->addStylesheet("reply.css");
        $this->getPage()->addStylesheet("entry.css");

        return $content;
    }

    public function showitem() {
        # Handle inclusion of other pages.  This is basically the "wrapper wrapper"
        # portion of the script.
        if ( isset($_GET['action']) && strtolower($_GET['action']) == 'upload' ) {
            $this->fileupload();
            exit;
        } elseif ( isset($_GET['action']) && strtolower($_GET['action']) == 'edit' ) {
            $this->entryedit();
            exit;
        } elseif ( isset($_GET['action']) && strtolower($_GET['action']) == 'managereplies' ) {
            $this->managereplies();
            exit;
        }

        $ent = NewEntry();
        $this->getPage()->setDisplayObject($ent);

        $page_type = strtolower(GET('show'));
        if (! $page_type) {
            $page_type = basename(getcwd());
        }

        $tb = NewTrackback();

        if ($tb->incomingPing() && strtolower(GET('action')) != 'ping') {

            if ($ent->allow_tb) {
                $content = $tb->receive();
            } else {
                $content = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n".
                           "<response>\n".
                           "<error>1</error>\n";
                           "<message>"._("This entry does not accept trackbacks.")."</message>\n";
                           "</response>\n";
            }
            echo $content;
            exit;

        } elseif ( strtolower(GET("action")) == 'ping' ) {

            $content = $this->show_trackback_ping_page($this->blog, $ent, $this->user);

        } elseif ( $page_type == 'pingback' || $page_type == ENTRY_PINGBACK_DIR) {

            $content = $this->show_pingback_page($this->blog, $ent, $this->user);

        } elseif ( $page_type == 'trackback' || $page_type == ENTRY_TRACKBACK_DIR ) {

            $content = $this->show_trackback_page($this->blog, $ent, $this->user);

        } elseif ( $page_type == 'comment' || $page_type == ENTRY_COMMENT_DIR ) {

            $content = $this->show_comment_page($this->blog, $ent, $this->user);

        } else {

            $content = $this->show_entry_page($this->blog, $ent, $this->user);

        }

        $this->getPage()->display($content);
    }

    public function tagsearch() {
        $this->getPage()->setDisplayObject($this->blog);

        $tags = htmlspecialchars(GET("tag"));
        $show_posts = GET("show") ? true : false;
        $limit = GET("limit") ? GET("limit") : 0;

        if (! $tags) {

            $links = array();
            foreach ($this->blog->tag_list as $tag) {
                $links[] = array('link'=>$this->blog->uri('tags', $tag), 'title'=>ucwords($tag));
            }
            $tpl = NewTemplate(LIST_TEMPLATE);
            $tpl->set("LIST_TITLE", _("Topics for this weblog"));
            $tpl->set("LINK_LIST", $links);
            $body = $tpl->process();

        } else {

            $tag_list = explode(",", $tags);

            if (! is_array($tag_list)) $tag_list = array();
            foreach ($tag_list as $key=>$val) {
                $tag_list[$key] = trim($val);
            }

            $ret = $this->blog->getEntriesByTag($tag_list, $limit, true);
            if ($show_posts) {
                $body = $this->blog->getWeblog();
                $this->getPage()->addStylesheet("entry.css");
            } else {
                $links = array();
                foreach ($ret as $ent) {
                    $links[] = array("link"=>$ent->permalink(), "title"=>$ent->subject);
                }
                $tpl = NewTemplate(LIST_TEMPLATE);
                $tpl->set("LIST_TITLE", _("Entries filed under: ").implode(", ", $tag_list));
                $tpl->set("LIST_FOOTER", '<a href="?show=all&amp;tag='.$tags.'">'.
                                            _("Display all entries at once").'</a>');
                $tpl->set("LINK_LIST", $links);
                $body = $tpl->process();
            }
            $this->getPage()->title = $this->blog->name.' - '._("Topic Search");
        }

        $this->getPage()->display($body);
    }

    public function updateblog() {
        if (POST("blogpath")) $blog_path = POST("blogpath");
        elseif (GET("blogpath")) $blog_path = GET("blogpath");
        else $blog_path = false;

        $blog = NewBlog($blog_path);
        $usr = User::get();
        $tpl = NewTemplate("blog_modify_tpl.php");
        $this->getPage()->setDisplayObject($blog);

        if (! $usr->checkLogin() || ! System::instance()->canModify($blog, $usr)) {
            $this->getPage()->error(403);
        }

        # NOTE - we should sanitize this input to avoid XSS attacks.  Then again,
        # since this page is not publicly accessible, is that needed?

        if (has_post()) {
            # Only the site administrator can change a blog owner.
            if ($usr->username() == ADMIN_USER && POST("blogowner") ) {
                $blog->owner = POST("blogowner");
            }
            blog_get_post_data($blog);

            $ret = $blog->update();
            System::instance()->registerBlog($blog->blogid);
            if (!$ret) $tpl->set("UPDATE_MESSAGE", _("Error: unable to update blog."));
            else $this->getPage()->redirect($blog->getURL());
        }

        if ($usr->username() == ADMIN_USER) {
            $tpl->set("BLOG_OWNER", $blog->owner);
        }
        blog_set_template($tpl, $blog);
        $tpl->set("POST_PAGE", current_file());
        $tpl->set("UPDATE_TITLE", sprintf(_("Update %s"), $blog->name));

        $body = $tpl->process();
        $this->getPage()->title = spf_("Update blog - %s", $blog->name);
        $this->getPage()->addStylesheet("form.css");
        $this->getPage()->display($body, $blog);
    }

    public function webmention() {
        $source = POST('source');
        $target = POST('target');
        try {
            $this->getSocialWebServer()->addWebmention($source, $target);
        } catch (WebmentionInvalidReceive $invalid) {
            $extra_content = "\r\n\r\n" . $invalid->getMessage();
            $this->getPage()->error(400, $extra_content);
        } catch (Exception $error) {
            $this->getPage()->error(500);
        }
    }

    private function persistEntry($ent, $is_art) {
        $res = array('errors' => '', 'warnings' => '');

        $send_pingbacks = false;
        $do_preview = $this->editIsPreview();
        $create_draft = $this->editIsDraft($ent);
        $save_entry = $this->editIsSave($ent);

        try {
            if ($is_art && $this->editIsPost($ent)) {
                $this->getPublisher()->publishArticle($ent);
            } elseif ($this->editIsPost($ent)) {
                $this->getPublisher()->publishEntry($ent);
                $send_pingbacks = true;
            } elseif ($create_draft) {
                $this->getPublisher()->createDraft($ent);
            } elseif ($save_entry) {
                $this->getPublisher()->update($ent);
                $send_pingbacks = $ent->isPublished() && !$do_preview;
            }
        } catch (Exception $e) {
            $res['errors'] = $e->getMessage();
        }

        $res['errors'] .= $this->handleUploads();
        $res['warnings'] = $this->handlePingbackPings($ent);

        return $res;
    }

    private function editIsPost($ent) {
        return POST('post') && !$ent->isPublished();
    }

    private function editIsPreview() {
        return POST('preview') || GET('preview');
    }

    private function editIsDraft($ent) {
        return (POST('draft') && !$ent->isEntry()) || (
                (POST('preview') || GET('preview')) &&
                GET('save') == 'draft' && !$ent->isEntry()
            );
    }

    private function editIsSave($ent) {
        return $ent->isEntry() && (
            (POST('draft') && !$ent->isPublished()) ||
            (POST('post') && $ent->isPublished()) ||
            ($this->editIsPreview() && GET('save') == 'draft')
        );
    }

    public function handlePingbackComplete($param, $data) {
        $this->last_pingback_results = $data;
    }

    public function handleUploadError($params, $data) {
        $this->last_upload_error = $data;
    }

    protected function getPage() {
        return Page::instance();
    }

    protected function getFs() {
        return NewFS();
    }

    protected function getEntry($path = false) {
        // Pick up entry ID from a POST param so we can inject it for AJAX saves.
        $path = empty($_POST['entryid']) ? false : $_POST['entryid'];
        if ($path && strpos($_POST['entryid'], '/') === false) {
            $path = BLOG_DRAFT_PATH . '/' . $path;
        }
        return NewEntry($path, $this->getFs());
    }

    protected function getPublisher() {
        if (!$this->publisher) {
            $fs = NewFS();
            $wrappers = new WrapperGenerator($fs);
            $this->publisher = new Publisher($this->blog, $this->user, $fs, $wrappers);
        }
        return $this->publisher;
    }

    protected function getSocialWebServer() {
        $mapper = new EntryMapper();
        $client = new HttpClient();
        return new SocialWebServer($mapper, $client);
    }
}
