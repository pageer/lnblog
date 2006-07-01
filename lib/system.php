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

require_once("lib/creators.php");

class System {
	
	function System() {
		$this->group_ini = NewINIParser(USER_DATA_PATH.PATH_DELIM."groups.ini");
		$this->sys_ini = NewINIParser(USER_DATA_PATH.PATH_DELIM."system.ini");
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
	
	function registerBlog($blogid) {
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
	
	function getBlogList() {
		$list = trim($this->sys_ini->value("register", "BlogList"));
		if (! $list) return array();
		$list = explode(",", $list);
		$ret = array();
		foreach ($list as $item) {
			$ret[] = NewBlog($item);
		}
		return $ret;
	}
	
	# Method: getUserBlogs
	# Gets the list of blogs to which a given user can add posts.
	#
	# Parameters:
	# usr - A User object for the user to check.
	#
	# Returns:
	# An array of Blog objects.
	
	function getUserBlogs($usr) {
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
	
	function getUserList() {
		if (! is_dir(USER_DATA_PATH)) return false;
		$dirhand = opendir(USER_DATA_PATH);
		$ret = array();
		while ( false !== ($ent = readdir($dirhand)) ) {
			# Should we check for user.ini, passwd.php, or both?
			if ($ent != "." && $ent != ".." &&
			    file_exists(mkpath(USER_DATA_PATH,$ent,"user.ini"))) {
					$ret[] = NewUser($ent);
			}
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
	
	function getGroupList() {
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
	
	function getGroups($usrid) {
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
	
	function inGroup($usrid, $grp) {
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
	
	function groupExists($grp) {
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
	
	function addToGroup(&$usr, $group) {
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

	function hasAdministrator() {
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
	
	function isOwner($usrid, $obj) {
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
	function canAddTo($parm, $usr=false) {
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
	
	function canModify($parm, $usr=false) {
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
	
	function canDelete($parm, $usr=false) {
		return $this->canModify($parm,$usr);
	}
}

$SYSTEM = new System();

?>
