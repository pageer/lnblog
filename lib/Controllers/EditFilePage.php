<?php

namespace LnBlog\Controllers;

use BasePages;
use Blog;
use FS;
use GlobalFunctions;
use Path;
use PHPTemplate;
use Psr\Log\LoggerInterface;
use System;
use UrlResolver;
use User;

class EditFilePage extends BasePages
{
    use UserAuthorizationTrait;

    private User $user;
    private Blog $blog;
    private UrlResolver $resolver;

    public function __construct(
        User $user,
        Blog $blog,
        UrlResolver $resolver,
        ?FS $fs = null,
        ?GlobalFunctions $globals = null,
        ?LoggerInterface $logger = null
    ) {
        parent::__construct($fs, $globals, $logger);
        $this->user = $user;
        $this->blog = $blog;
        $this->resolver = $resolver;
    }

    private function handlePost(string $file, PHPTemplate $tpl): string {
        $data = POST("output");
        $ret = $this->fs->write_file($file, $data);

        if (! $ret) {
            $tpl->set("EDIT_ERROR", _("Cannot create file"));
            $tpl->set(
                "ERROR_MESSAGE",
                spf_("Unable to create file %s.", $file)
            );
            $tpl->set("FILE_TEXT", htmlentities($data));
        }

        return $data;
    }

    private function handleGet(string $file, PHPTemplate $tpl): string {
        if (is_file($file)) {
            return file_get_contents($file);
        }

        if (! GET('map')) {
            $tpl->set("EDIT_ERROR", _("Create new file"));
            $tpl->set("ERROR_MESSAGE", _("The selected file does not exist.  It will be created."));
        }

        return "";
    }

    protected function getActionMap(): array {
        return ['editfile' => 'defaultAction'];
    }

    protected function defaultAction() {
        $file = GET("file");
        if (Path::isWindows()) {
            $file = str_replace('/', Path::$sep, $file);
        }
        $file = str_replace("..".Path::$sep, '', $file);

        $ent = NewBlogEntry();

        $relpath = INSTALL_ROOT;

        $message_403 = _("You do not have permission to edit this file.");

        $this->verifyUserIsLoggedIn(SERVER("referer"), $message_403);

        if ( GET("profile") == $this->user->username() ) {
            $relpath = Path::get(USER_DATA_PATH, $this->user->username());
        } elseif (GET('target') == 'userdata') {
            $relpath = USER_DATA_PATH;
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

        $tpl = $this->createTemplate("file_edit_tpl.php");

        # Prepare template for link list display.
        if (GET("list")) {
            $tpl->set("SHOW_LINK_EDITOR");
            $this->getPage()->addScript("sitemap.js");
        }

        $tpl->set("FORM_ACTION", make_uri(false, false, false));
        if (isset($_GET["list"])) {
            $tpl->set("PAGE_TITLE", _("Edit Link List"));
        } else {
            $tpl->set("PAGE_TITLE", _("Edit Text File"));
        }

        if (substr($file, 0, 9) == 'userdata' . DIRECTORY_SEPARATOR) {
            $file = Path::mk(USER_DATA_PATH, substr($file, 9));
        } else {
            $file = Path::mk($relpath, $file);
        }

        $data = has_post() ? $this->handlePost($file, $tpl) : $this->handleGet($file, $tpl);

        $tpl->set("FILE_TEXT", htmlentities($data));

        if (GET('map')) {
            $tpl->set("SITEMAP_MODE");
            $tpl->set(
                "FORM_MESSAGE", spf_(
                    'This page will help you create a site map to display in the navigation bar at the top of your blog.  This file is stored under the name %s in the root directory of your weblog for a personal sitemap or in the %s installation directory for the system default.  This file in simply a series of <abbr title="Hypertext Markup Language">HTML</abbr> links, each on it\'s own line, which the template will process into a list.  If you require a more complicated menu bar, you will have to create a custom template.',
                    basename(SITEMAP_FILE), PACKAGE_NAME
                )
            );
            $tpl->set("PAGE_TITLE", _("Create site map"));
        } else {
            $tpl->set("FILE_PATH", $file);
            $tpl->set("FILE_SIZE", file_exists($file) ? filesize($file) : 0);
            $tpl->set("FILE_URL", $this->resolver->localpathToUri($file, $this->blog));
        }
        $tpl->set("FILE", $file);

        $displayBlog = defined("BLOG_ROOT") ? $this->blog : false;

        $this->getPage()->raiseEvent('FileEditorReady');

        $this->getPage()->title = _("Edit file");
        $this->getPage()->addStylesheet("form.css");
        $this->getPage()->display($tpl->process(), $displayBlog);
    }
}
