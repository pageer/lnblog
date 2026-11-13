<?php

namespace LnBlog\Controllers;

use BasePages;
use Blog;
use FS;
use GlobalFunctions;
use Path;
use Psr\Log\LoggerInterface;
use User;

class EditLoginPage extends BasePages
{
    use UserAuthorizationTrait;

    private User $user;
    private Blog|false $blog;

    public function __construct(
        User $user,
        Blog $blog,
        ?FS $fs = null,
        ?GlobalFunctions $globals = null,
        ?LoggerInterface $logger = null
    ) {
        parent::__construct($fs, $globals, $logger);
        $this->user = $user;
        $this->blog = $blog;
    }

    protected function getActionMap(): array {
        return ['useredit' => 'defaultAction'];
    }

    public function defaultAction() {
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

        if ($this->blog->isBlog()) {
            $page_name = _("Change User Information");
            $redir_page = $this->blog->uri('blog');
        } else {
            $page_name = _("Change Administrator Login");
            $redir_page = INSTALL_ROOT_URL;
        }

        $form_title = spf_("Modify User - %s", $usr->username());
        $user_name = "user";
        $reset = "reset";

        $tpl = $this->createTemplate("login_create_tpl.php");
        $tpl->set("FORM_TITLE", $form_title);
        $tpl->set("FORM_ACTION", current_file());
        $tpl->set("FULLNAME_VALUE", htmlentities($usr->name()));
        $tpl->set("EMAIL_VALUE", htmlentities($usr->email()));
        $tpl->set("HOMEPAGE_VALUE", htmlentities($usr->homepage()));
        $tpl->set("PROFILEPAGE_VALUE", htmlentities($usr->profileUrl()));

        $tpl->set("UPLOAD_LINK", $this->blog->uri("upload", ['profile' => $usr->username()]));
        $edit_link_data = ["file" => "profile.htm", 'profile' => $usr->username()];
        $tpl->set("PROFILE_EDIT_LINK", $this->blog->uri("editfile", $edit_link_data));
        $tpl->set("PROFILE_EDIT_DESC", _("Edit extra profile data"));
        $tpl->set("UPLOAD_DESC", _("Upload file to profile"));

        $priv_path = Path::mk(USER_DATA_PATH, $usr->username(), CUSTOM_PROFILE);
        $cust_path = Path::mk(USER_DATA_PATH, CUSTOM_PROFILE);
        $cust_ini = NewINIParser($priv_path);
        $cust_ini->merge(NewINIParser($cust_path));

        $section = $cust_ini->getSection(CUSTOM_PROFILE_SECTION);
        $tpl->set("CUSTOM_FIELDS", $section);
        $tpl->set("CUSTOM_VALUES", $usr->custom);

        if (has_post()) {
            $this->handlePost($usr, $section, $tpl, $redir_page);
        }

        $body = $tpl->process();
        if (! defined("BLOG_ROOT")) {
            $this->blog = false;
        }
        $this->getPage()->addStylesheet("form.css");
        $this->getPage()->title = $page_name;
        $this->getPage()->display($body, $this->blog);
    }

    private function handlePost(User $usr, array $section, $tpl, string $redir_page): void {
        $pwd_change = false;
        if ( trim(POST('passwd')) &&
             trim(POST('passwd')) == trim(POST('confirm'))) {
            $pwd_change = true;
            $usr->password(trim(POST('passwd')));
        } elseif ( trim(POST('passwd')) &&
                   trim(POST('passwd')) == trim(POST('confirm'))) {
            $tpl->set("FORM_MESSAGE", _("The passwords you entered do not match."));
        }

        $usr->name(trim(POST('fullname')));
        $usr->email(trim(POST('email')));
        $usr->homepage(trim(POST('homepage')));
        $usr->profileUrl(trim(POST('profile_url')));

        foreach ($section as $key => $val) {
            $usr->custom[$key] = trim(POST($key));
        }

        $usr->save();

        if ($pwd_change) {
            $this->attemptLogin($usr, POST('passwd'));
        }

        $this->getPage()->redirect($redir_page);
    }
}
