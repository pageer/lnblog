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
	
	public function __construct() {
		$this->userdata = defined("USER_DATA_PATH")?USER_DATA_PATH:"";
	
		$this->group_ini = new INIParser(Path::mk($this->userdata, "groups.ini"));
		$this->sys_ini = new INIParser(Path::mk($this->userdata, "system.ini"));

	}
	
	public static function instance() {
		static $static_instance;
		if (! isset($static_instance)) {
			$static_instance = new System();
		}
		return $static_instance;
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
		if (! $list) $list = array();
		else $list = explode(",", $list);
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
		global $SYSTEM;
		$has_admin = file_exists(mkpath(USER_DATA_PATH,ADMIN_USER,"passwd.php"));
		if (! $has_admin) {
			$users = $SYSTEM->getUserList();
			foreach ($users as $u) {
				if ($SYSTEM->inGroup($u->username(), 'administrators')) {
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
		if (!$usr) $usr = NewUser();
		
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

	# Function: uri_to_localpath
	# The reverse of <localpath_to_uri>, this function takes a URI handled by LnBlog
	# and converts it into a local path to the file or directory in question.  This
	# function assumes that the URI is fully qualified, e.g. 
	# |http://somehost.com/somepath/somefile.ext
	# Note that this may or may not play well with Apache .htaccess files.
	#
	# Parameters:
	# uri - The URI to convert.
	#
	# Returns:
	# A string containing the local path referenced by the URI.  Not that this path
	# may or may not exist.
	
	function uri_to_localpath($uri) {
	
		$url_bits = parse_url($uri);
		if (! $url_bits) return '';
		
		#$protocol = isset($url_bits['scheme']) ? $url_bits['scheme'] : '';
		#$domain = isset($url_bits['host']) ? $url_bits['host'] : '';
		$path = isset($url_bits['path']) ? $url_bits['path'] : '';
			
		# Account for user home directories in path.  Please note that this is 
		# an ugly, ugly hack to make this function work when I'm testing on my
		# local workstation, where I use ~/www for by web root.
		if ( preg_match(URI_TO_LOCALPATH_MATCH_RE, $path) ) {
			$path = preg_replace(URI_TO_LOCALPATH_MATCH_RE, URI_TO_LOCALPATH_REPLACE_RE, $path);
		}
		
		if (defined("DOMAIN_NAME") && defined("SUBDOMAIN_ROOT") &&
			isset($url_bits['host']) && 
			preg_match('/'.str_replace('.','\.',DOMAIN_NAME).'$/', DOMAIN_NAME) &&
			strpos($url_bits['host'], DOMAIN_NAME) > 1) {
				
			$pos = strpos($url_bits['host'], DOMAIN_NAME);
			$tmp_path = substr($url_bits['host'], 0, $pos - 1);
			$path = mkpath(SUBDOMAIN_ROOT, $tmp_path, $path);
		} else {
			$path = mkpath(DOCUMENT_ROOT, $path);
		}
		
		$p = new Path($path);
		return $p->getCanonical();
		
	}
	
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
