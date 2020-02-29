<?php
# Class: System
# This class encapsulates core LnBlog system functions.
#
# The system class exists in order to provide functions that are global to the
# installation rather then particular to a blog or user.  This includes things
# such as listing blogs and users, maintaining user permissions, and so forth.
#
# This may not be the best way to accomplish this particular purpose.  However,
# it is very convenient and doesn't cause any design problems for the time
# being.

class System {

    private static $default_group_ini = [
        'administrators' => [
            'Comment' => 'Can perform administrative actions like adding users and blogs.',
            'Level' => 255,
            'Members' => 'administrator',
        ],
        'editors' => [
            'Comment' => "Can create content and modify other user's content.",
            'Level' => 200,
            'Members' => '',
        ],
        'writers' => [
            'Comment' => 'Can post and modify their own content.',
            'Level' => 120,
            'Members' => '',
        ],
        'readers' => [
            'Comment' => 'Can log in and post comments.',
            'Level' => 50,
            'Members' => '',
        ],
        'guests' => [
            'Comment' => 'Unauthenticated users.',
            'Level' => 0,
            'Members' => '*',
        ],
    ];

    private static $default_system_ini = [
        'security' => [
            'NewUserDefaultGroup' => 'writers',
            'AnonymousGroup' => 'guests',
        ],
        'entryconfig' => [
            'AllowInitUpload' => 1,
            'AllowLocalPingback' =>  1,
            'GroupReplies' => 0,
        ],
        'register' => [
            'BlogList' => '',
        ],
        'plugins' => [
            'EventDefaultOff' => 0,
            'EventForceOff' => 0,
        ],
    ];

    // NOTE: Public for unit testing purposes (which is not great...)
    static public $static_instance;

    public function __construct() {
        $this->userdata = defined("USER_DATA_PATH") ? USER_DATA_PATH : "";

        $groups_ini_path = Path::mk($this->userdata, "groups.ini");
        $sys_ini_path = Path::mk($this->userdata, "system.ini");

        $group_ini_defaults = INIParser::fromArray(self::$default_group_ini, $groups_ini_path);
        $this->group_ini = new INIParser($groups_ini_path);
        $this->group_ini->merge($group_ini_defaults);

        $sys_ini_defaults = INIParser::fromArray(self::$default_system_ini, $sys_ini_path);
        $this->sys_ini = new INIParser($sys_ini_path);
        $this->sys_ini->merge($sys_ini_defaults);
    }

    public static function instance() {
        if (! isset(self::$static_instance)) {
            self::$static_instance = new System();
        }
        return self::$static_instance;
    }

    # Method: registerBlog
    # Registers a blog with the system.  This lets the system know that
    # the blog's directory is handled by LnBlog.
    #
    # Parameters:
    # blogid - The path to the blog.  This should be relative to the server's
    #          document root directory, e.g. a blog at
    #          http://somehost.com/blogs/techblog/ would use the blogid
    #          *blogs/techblog*.
    # Returns:
    # True if the blog is registered correctly or is already registered,
    # false if it fails to register.

    public function registerBlog($blogid) {
        $list = trim($this->sys_ini->value("register", "BlogList"));
        if (! $list) {
            $list = array();
        } else {
            $list = explode(",", $list);
        }
        $blogid = trim($blogid);
        if (in_array($blogid, $list)) {
            return true;
        } else {
            $list[] = $blogid;
            $list = implode(",", $list);
            $this->sys_ini->setValue("register", "BlogList", $list);
            return $this->sys_ini->writeFile();
        }
    }

    # Method: unregisterBlog
    # Unregisters a blog with the system.  This reverses registerBlog().
    #
    # Parameters:
    # blogid - THe path to the blog, as with registerBlog.
    #
    # Returns:
    # True on success, false on failure or if the blog was not registered.
    public function unregisterBlog($blogid) {
        $blogid = trim($blogid);
        $list = trim($this->sys_ini->value("register", "BlogList"));
        if ($list) {
            $list = explode(",", $list);
            $pos = array_search($blogid, $list);
            if ($pos !== false) {
                unset($list[$pos]);
                $list = implode(",", $list);
                $this->sys_ini->setValue("register", "BlogList", $list);
                return $this->sys_ini->writeFile();
            }
        }
        return false;
    }

    # Method: getBlogList
    # Get a list of blogs handled by LnBlog.
    #
    # Returns:
    # An array of blog objects.

    public function getBlogList() {
        $list = trim($this->sys_ini->value("register", "BlogList"));
        if (! $list) return array();
        $list = explode(",", $list);
        $ret = array();
        foreach ($list as $item) {
            $b = NewBlog($item);
            $b->skip_root = true;
            $ret[] = $b;
        }
        return $ret;
    }

    # Method: getThemeList
    # Gets a list of installed system and user themes.
    #
    # Returns:
    # An array of theme names.
    public function getThemeList() {
        $dir = scan_directory(mkpath(INSTALL_ROOT,"themes"), true);
        if (is_dir(mkpath(USER_DATA_PATH,"themes"))) {
            $user_dir = scan_directory(mkpath(USER_DATA_PATH,"themes"), true);
            if ($user_dir) $dir = array_merge($dir, $user_dir);
        }
        if (defined("BLOG_ROOT") && is_dir(mkpath(BLOG_ROOT,"themes"))) {
            $blog_dir = scan_directory(mkpath(BLOG_ROOT,"themes"), true);
            if ($blog_dir) $dir = array_merge($dir, $blog_dir);
        }
        $dir = array_unique($dir);
        sort($dir);
        return $dir;
    }

    # Method: getUserBlogs
    # Gets the list of blogs to which a given user can add posts.
    #
    # Parameters:
    # usr - A User object for the user to check.
    #
    # Returns:
    # An array of Blog objects.

    public function getUserBlogs($usr) {
        $list = $this->getBlogList();
        $ret = array();
        foreach ($list as $blog) {
            if ( $this->canAddTo($blog, $usr) ) {
                $ret[] = $blog;
            }
        }
        return $ret;
    }

    # Method: getUserList
    # Get a list of all users.
    #
    # Returns:
    # An array of user objects.

    public function getUserList() {
        if (! is_dir(USER_DATA_PATH)) return false;
        $dirhand = opendir(USER_DATA_PATH);

        $u = NewUser();

        $ret = array();
        while ( false !== ($ent = readdir($dirhand)) ) {
            # Should we check for user.ini, passwd.php, or both?
            if ($u->exists($ent)) $ret[] = NewUser($ent);
        }
        closedir($dirhand);
        return $ret;
    }

    # Method: getGroupList
    # Gets a list of groups.
    #
    # Parameters:
    # usr - The user whose groups we want to get.
    #
    # Returns:
    # An array of group names.

    public function getGroupList() {
        return $this->group_ini->getSectionNames();
    }

    # Method: getGroups
    # Gets the groups to which a particular user belongs.
    #
    # Parameters:
    # usrid - The username of the user in question.
    #
    # Returns:
    # An array of group names.

    public function getGroups($usrid) {
        $groups = $this->group_ini->getSectionNames();
        $ret = array();
        foreach ($groups as $grp) {
            $list = explode(',', $this->group_ini->value($grp, "Members"));
            if (in_array($usrid, $list)) {
                $ret[] = $grp;
            }
        }
        return $ret;
    }

    # Method: inGroup
    # Determines whether or not a particular user belongs to a given group.
    #
    # Parameters:
    # usrid - The username of the user to check.
    # grp   - The group name to which the user should belong.
    #
    # Returns:
    # True if usrid is in grp or if everyone is in grp, false otherwise.

    public function inGroup($usrid, $grp) {
        $members = $this->group_ini->value($grp, "Members");
        $list = explode(',', $members);
        return in_array($usrid, $list) || in_array('*', $list);
    }

    # Method: groupExists
    # Determines if a specified group exists or not.
    #
    # Parameters:
    # grp - The group name to check.
    #
    # Returns:
    # True if the group exists, false otherwise.

    public function groupExists($grp) {
        $grp = trim($grp);
        $groups = $this->group_ini->getSectionNames();
        return in_array($grp, $groups);
    }

    # Method: addToGroup
    # Adds a user to a group.
    #
    # Parameters:
    # usr   - A user object representing the user to add.
    # group - The group name to add the user to.
    #
    # Returns:
    # True on success, false on failure.  If the user is *already* in the group,
    # then the return value is true.  If the group does not exist, the value
    # is false.

    public function addToGroup(&$usr, $group) {
        $ret = false;
        $userid = $usr->username();

        if (! $this->groupExists($group)) return false;

        $members = trim($this->group_ini->value($group, "Members"));

        if (!$members) $list = array();
        else $list = explode(',', $members);

        if (in_array($userid, $list)) return true;

        $list[] = $userid;
        $list = implode(",", $list);
        $members = $this->group_ini->setValue($group, "Members", $list);
        $ret = $this->group_ini->writeFile();

        return $ret;
    }

    # Method: hasAdministrator
    # Determines if there is at least one user who is a system administrator.
    #
    # Returns:
    # True if the user defined by the ADMIN_USER constant exists or if there is
    # at least one existing user in the administrators group, false otherwise.

    public function hasAdministrator() {
        $has_admin = file_exists(mkpath(USER_DATA_PATH,ADMIN_USER,"passwd.php"));
        if (! $has_admin) {
            $users = $this->getUserList();
            foreach ($users as $u) {
                if ($this->inGroup($u->username(), 'administrators')) {
                    $has_admin = true;
                    break;
                }
            }
        }
        return $has_admin;
    }

    # Method: isOwner
    # Determines if a given user owns an object.
    #
    # Parameters:
    # usrid - The username of the user to check.
    # obj   - The object to check.  Must have a uid or owner property.
    #
    # Returns:
    # True if the user is the object's owner, false otherwise.

    public function isOwner($usrid, $obj) {
        if (isset($obj->uid)) $owner = $obj->uid;
        elseif (isset($obj->owner)) $owner = $obj->owner;
        else $owner = false;
        return ($usrid && $usrid == $owner);
    }

    # Method: canAddTo
    # Determines if a given user has permissions to add child objects to
    # some particular object.
    #
    # Parameters:
    # parm - An object of some kind, usually a Blog, BlogEntry, or Article.
    # usr  - A User object for the user whose permissions we want to check.
    # Returns:
    # True if the
    public function canAddTo($parm, $usr=false) {
        $ret = false;
        if (!$usr) {
            $usr = NewUser();
        }

        if ( $this->inGroup($usr->username(), 'administrators') ||
             $this->isOwner($usr->username(), $parm) ||
             ( method_exists($parm, 'getParent') &&
               $this->isOwner($usr->username(), $parm->getParent()) ) ) {
            $ret = true;
        }

        if (is_a($parm, 'blog')) {
            if ( in_array($usr->username(), $parm->writers() ) ) {
                $ret = true;
            }
        } else {
            $ret = true;
        }
        return $ret;
    }

    # Method: canModify
    # Like <canAddTo>, except determines if the user can perform updates.
    public function canModify($parm, $usr=false) {
        $ret = false;
        if (!$usr) $usr = NewUser();
        if ( $this->inGroup($usr->username(), 'administrators') ||
             $this->isOwner($usr->username(), $parm) ||
             ( method_exists($parm, 'getParent') &&
               $this->isOwner($usr->username(), $parm->getParent()) ) ) {
            $ret = true;
        }
        return $ret;
    }

    # Method: canDelete
    # Like <canAddTo>, except determines if the user can delete the object.
    public function canDelete($parm, $usr=false) {
        return $this->canModify($parm,$usr);
    }

    ########## Junk code ###############3

    # Function: calculate_document_root
    # An alternate way to find the document root.  This one works by comparing
    # the current URL on the server to the current directory.  The idea is that
    # we can find the location of the current URL in the path and remove it to
    # get the document root.  Note that this function IS case-sensitive.
    #
    # Returns:
    # The calculated document root path.

    function calculate_document_root() {

        # Bail out if DOCUMENT_ROOT is already defined.
        if ( defined("DOCUMENT_ROOT") ) return DOCUMENT_ROOT;

        # Get the current URL and the path to the file.
        $curr_uri = current_uri();
        $curr_file = getcwd().PATH_DELIM.basename($curr_uri);
        if (! file_exists($curr_file)) $curr_file = getcwd();
        if (PATH_DELIM != "/") $curr_uri = str_replace("/", PATH_DELIM, $curr_uri);

        if ( preg_match(URI_TO_LOCALPATH_MATCH_RE, $curr_uri) ) {
            $curr_uri = preg_replace(URI_TO_LOCALPATH_MATCH_RE, URI_TO_LOCALPATH_REPLACE_RE,$curr_uri);
        }

        # Find the location
        $pos = strpos($curr_file, $curr_uri);
        while (! $pos && strlen($curr_uri) > 1) {
            $curr_uri = dirname($curr_uri);
            $pos = strpos($curr_file, $curr_uri);
        }
        return substr($curr_file, 0, $pos + 1);

    }

    function calculate_server_root($path, $assume_subdomain=false) {

        $ret = '';

        if ($this->subdomainroot && $this->docroot) {

            # If the path doesn't start with either the subdomain or document
            # root, then something is very, very wrong, so we need to bail
            # the hell out and do it loud!
            if ( ! (strpos($path, DOCUMENT_ROOT)  === 0 ||
                    strpos($path, SUBDOMAIN_ROOT) === 0) ) {
                echo "Bad file passed to calculate_server_root() in ".__FILE__.
                     ".  The path '".$path."' is not under the document root (".
                      DOCUMENT_ROOT.") or the subdomain root (".SUBDOMAIN_ROOT.
                      ").  Cannot get server root.";
                return false;
            }

            # Case 1 - The document root and subdomain root aretitle the same.
            if (SUBDOMAIN_ROOT == DOCUMENT_ROOT) {

                $ret = DOCUMENT_ROOT;

            # Case 2 - The document root is inside subdomain root.
            } elseif (strpos(DOCUMENT_ROOT, SUBDOMAIN_ROOT) === 0) {

                # If the path contains the document root, assume that we're NOT
                # in a subdomain.
                $ret = ( strpos($path, DOCUMENT_ROOT) === 0) ?
                       DOCUMENT_ROOT: SUBDOMAIN_ROOT;

            # Case 3 - The subdomain root is inside the document root.
            } elseif (strpos(SUBDOMAIN_ROOT, DOCUMENT_ROOT) === 0) {

                # If the path is in the document root, but not the subdomain
                # root, then we're definitely not in a subdomain.
                if (strpos($path, DOCUMENT_ROOT) === 0 &&
                    strpos($path, SUBDOMAIN_ROOT) === false) {
                    $ret = DOCUMENT_ROOT;
                } else {
                    # Otherwise, there's no way to tell if the directory is a subdomain
                    # without hitting the network, so just pass the decision to the caller.
                    $ret = $assume_subdomain ? SUBDOMAIN_ROOT : DOCUMENT_ROOT;
                }

            # Case 4 - The two directories are independent.
            } else {
                # If path is under the document, return that.  Otherwise, return the
                # subdomain root.  This ends up the same as case 2.
                $ret = ( strpos($path, DOCUMENT_ROOT) === 0) ?
                       DOCUMENT_ROOT: SUBDOMAIN_ROOT;
            }

        } else {
            $ret = calculate_document_root();
        }

        return $ret;

    }
}

$SYSTEM = System::instance();
