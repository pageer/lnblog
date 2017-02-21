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

# Class: User
# Class to manipulate and authenticate users.  All details of user 
# management, including checking authentication, should be done through 
# this class.  
#
# Inherits:
# <LnBlogObject>

define("USER_PROFILE_FILE", "user.xml");

class User extends LnBlogObject {

	public $username = '';
	public $passwd = '';
	public $salt = '';
	public $fullname = '';
	public $email = '';
	public $homepage = '';
	public $profile_page = '';
	public $default_group = '';
	public $custom = array();
	
	public static function get($usr=false, $pwd=false) {
		$s_usr = SESSION(CURRENT_USER);
		$c_usr = COOKIE(CURRENT_USER);
		if (!$usr && $c_usr) {
			if ($s_usr == $c_usr || (! AUTH_USE_SESSION && $s_usr == '') ) {
				$usr = $c_usr;
			}
		}
		
		if ($usr && isset($_SESSION["user-".$usr])) {
			return unserialize($_SESSION["user-".$usr]);
		} else {
			return new User($usr, $pwd);
		}
	}
	
	public static function logged_in() {
		$user = self::get();
		return $user->checkLogin();
	}

	public function __construct($uname=false, $pw=false) {

		$this->username = $uname ? $uname : '';
		
		$this->exclude_fields = array('salt', 'passwd', 'username');

		if ($uname && realpath(mkpath(USER_DATA_PATH,$uname,"passwd.php"))) {
		
			global $pwd;
			global $salt;
			include_once(USER_DATA_PATH."/".$uname."/passwd.php");
			$this->username = $uname;
			$this->passwd = $pwd;
			$this->salt = $salt;
			
			$xmlfile = realpath(mkpath(USER_DATA_PATH,$uname,USER_PROFILE_FILE));
			$inifile = realpath(mkpath(USER_DATA_PATH,$uname,"user.ini"));
			
			if ($xmlfile) {
				$this->deserializeXML($xmlfile);
			} elseif ($inifile) {
				$ini = NewIniParser($inifile);
				$this->fullname = $ini->value("userdata", "name", "");
				$this->email    = $ini->value("userdata", "email", "");
				$this->homepage = $ini->value("userdata", "homepage", "");
				$this->default_group = 
					$ini->value('userdata','default_group',
					            System::instance()->sys_ini->value('security',
					                                    'NewUserDefaultGroup',
					                                    ''));
				$this->custom = $ini->getSection("customdata");
			}
			$_SESSION["user-".$uname] = serialize($this);
			
		}

		if ($pw) $this->login($pw);
	
	}
	
	# Method: exists
	# Determines if the object represents an existing, registered user.
	#
	# Returns:
	# True if the user exists, false otherwise.
	function exists($uname=false) {
		if (! $uname) $uname = $this->username;
		return realpath(mkpath(USER_DATA_PATH,$uname,"passwd.php")) &&
		       ( realpath(mkpath(USER_DATA_PATH,$uname,USER_PROFILE_FILE)) || 
		         realpath(mkpath(USER_DATA_PATH,$uname,"user.ini")) );
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
	
	protected function defaultProfileUrl() {
		$blog = NewBlog();
		if (file_exists(mkpath(USER_DATA_PATH,$this->username,"index.php"))) {
			$qs = $blog->isBlog() ? array('blog'=>$blog->blogid) : false;
			$ret = make_uri(localpath_to_uri(USER_DATA_PATH."/".$this->username."/"), $qs);
		} else {
			$qs = array("user"=>$this->username);
			if ($blog->isBlog()) {
				$qs['blog'] = $blog->blogid;
			}
			$ret = make_uri(INSTALL_ROOT_URL."userinfo.php", $qs);
		}
		return $ret;
	}
	
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
	function exportVars($tpl) {
		if ($this->username) $tpl->set("USER_ID", $this->username);
		if ($this->fullname) $tpl->set("USER_NAME", $this->fullname);
		if ($this->email) $tpl->set("USER_EMAIL", $this->email);
		if ($this->default_group) $tpl->set("DEFAULT_GROUP",$this->defaultGroup());
		$tpl->set("GROUPS", $this->groups());
		if (strpos($this->homepage, "http://") === false && 
		    trim($this->homepage) != "") {
			$this->homepage = "http://".$this->homepage;
		}
		if ($this->homepage) $tpl->set("USER_HOMEPAGE", $this->homepage);
		$tpl->set("USER_DISPLAY_NAME", $this->displayName() );
		$tpl->set("PROFILE_LINK", $this->getProfileUrl());
	}

	# Method: checkPassword
	# Checks if a password is valid for the current user.
	# 
	# Parameters:
	# pass - The password to check.
	# 
	# Returns:
	# True if the password is correct, false owtherwise.
	function checkPassword($pass) {
		if (!trim($pass)) return false;
		$hash = md5($pass.$this->salt);
		$ret = ($hash == $this->passwd);
		return $ret;
	}

	# Method: save
	# Save changes to user data.
	#
	# Returns:
	# True if the changes were successfully saved, false otherwise.
	function save() {
		if (!$this->username ||! $this->passwd) return false;
		$fs = NewFS();
		
		$data = "<?php\n".
		        '$pwd = "'.$this->passwd.'";'."\n".
		        '$salt = "'.$this->salt.'";'."\n?>";
		if (! is_dir(USER_DATA_PATH.PATH_DELIM.$this->username)) {
			$ret = $fs->mkdir(USER_DATA_PATH.PATH_DELIM.$this->username);
			if (! $ret) return $ret;
		}
		$ret = write_file(mkpath(USER_DATA_PATH,$this->username,"passwd.php"), $data);
		
		#$ini = NewINIParser(mkpath(USER_DATA_PATH,$this->username,USER_PROFILE_FILE));
		#$ini->setValue("userdata", "name", $this->fullname);
		#$ini->setValue("userdata", "email", $this->email);
		#$ini->setValue("userdata", "homepage", $this->homepage);
		#$ini->setValue("userdata", "default_group", $this->defaultGroup());

		#foreach ($this->custom as $key=>$val) {
		#	$ini->setValue("customdata", $key, $val);
		#}
		#$ret = $ini->writeFile();
		$data = $this->serializeXML();
		$ret = write_file(mkpath(USER_DATA_PATH,$this->username,USER_PROFILE_FILE), $data);
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
	function password($pwd=false) {
		if (!$pwd) return $this->passwd;
		else {
			mt_srand(time());
			# Generate a new salt
			$num_chars = mt_rand(6, 12);
			$slt = '';
			for ($i = 0; $i < $num_chars; $i++) $slt .= chr(mt_rand(65, 90));
			$this->salt = $slt;
			$this->passwd = md5($pwd.$this->salt);
			
			# Prevent password change from logging out on cookie-only config.
			if ( $this->username == COOKIE(CURRENT_USER) )
				set_domain_cookie(PW_HASH, $this->passwd, 
					(LOGIN_EXPIRE_TIME ? time() + LOGIN_EXPIRE_TIME:false));
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
	function username($uid=false) {
		if (!$uid) return $this->username;
		else $this->username = $uid;
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
	function name($nm=false) {
		if (!$nm) return $this->fullname;
		else $this->fullname = $nm;
	}

	# Method: displayName
	# Returns a name to display.  If a full name is defined, it uses that.
	# Otherwise, it reverts to the username.
	#
	# Returns:
	# A string contianing either the username or full name.
	function displayName() {
		if ($this->fullname) return $this->fullname;
		else return $this->username;
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
	function email($mail=false) {
		if (!$mail) return $this->email;
		else $this->email = $mail;
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
	function homepage($url=false) {
		if (!$url) return $this->homepage;
		else $this->homepage = $url;
	}
	
	# Method: groups
	# Lists the groups to which the user belongs.
	#
	# Returns: 
	# An array of group names to which the user belongs.
	
	function groups() {
		return System::instance()->getGroups($this->username);
	}

	# Method: defaultGroup
	# Gets or sets the default group for this user.  This is the group to which 
	# all of the user's creations will belong by default.
	#
	# Parameters:
	# Val - If set, the value to which the default group should be changed.
	#
	# Returns:
	# A string with the name of the group.

	function defaultGroup($val=false) {
		if ($val) $this->default_group = $val;
		else return $this->default_group;
	}
	
	# Method: addToGroup
	# Adds the user to the specified group.
	#
	# Parameters:
	# groupname - The name of the group.
	#
	# Returns:
	# True on success, false on failure.
	
	function addToGroup($groupname) {
		return System::instance()->addToGroup($this, $groupname);
	}
	
	# Method: login
	# Logs the user in.
	# Note that there are two login methods available, with the one used
	# being determined by the <AUTH_USE_SESSION> configuration constant.
	#
	# Parameters:
	# pwd - The password used to log in.
	#
	# Returns:
	# False if the authentication fails, true otherwise.

	function login($pwd) {
		
		# Reject empty usernames or passwords.
		if ( trim($this->username) == "" || trim($pwd) == "" ) {
			return false;
		}
		
		# User does not exist.
		#if ( ! isset($this->user_list[$this->username]) ) return false;

		$ts = gmdate("M d Y H:i:s", time());
		if ($this->checkPassword($pwd)) {
			if (AUTH_USE_SESSION) {
				# Create a login token.
				$token = md5(get_ip().$ts);
				$_SESSION[CURRENT_USER] = $this->username; 
				$_SESSION[LOGIN_TOKEN] = $token;
				$_SESSION[LAST_LOGIN_TIME] = $ts;
				set_domain_cookie(LAST_LOGIN_TIME, "$ts", 
					(LOGIN_EXPIRE_TIME ? time()+LOGIN_EXPIRE_TIME:false));
				set_domain_cookie(CURRENT_USER, $this->username, 
					(LOGIN_EXPIRE_TIME ? time()+LOGIN_EXPIRE_TIME:false));
				set_domain_cookie(LOGIN_TOKEN, $token, 
					(LOGIN_EXPIRE_TIME ? time()+LOGIN_EXPIRE_TIME:false));
				$ret = true;
			} else {
				set_domain_cookie(CURRENT_USER, $this->username, 
					(LOGIN_EXPIRE_TIME ? time() + LOGIN_EXPIRE_TIME:false));
				set_domain_cookie(PW_HASH, md5($this->passwd.get_ip()), 
					(LOGIN_EXPIRE_TIME ? time() + LOGIN_EXPIRE_TIME:false));
				$ret = true;
			}
		} else $ret = false;
		return $ret;
	}

	# Method: logout
	# Logs the user out and destroys login tokens.
	# Note that this is also subject to <AUTH_USE_SESSION>
	function logout() {
		if (AUTH_USE_SESSION) {
			unset($_SESSION[CURRENT_USER]);
			unset($_SESSION[LOGIN_TOKEN]);
			unset($_SESSION[LAST_LOGIN_TIME]);
			set_domain_cookie(CURRENT_USER, "", time() - 3600);
			set_domain_cookie(LOGIN_TOKEN, "", time() - 3600);
			set_domain_cookie(LAST_LOGIN_TIME, "", time() - 3600);
		} else {
			set_domain_cookie(CURRENT_USER, "", time() - 3600);
			set_domain_cookie(PW_HASH, "", time() - 3600);
		}
	}

	# Method: checkLogin
	# Checks tokens to determine if the user is logged in.
	#
	# Parameters:
	# uname - *Optional* username to check.
	#
	# Returns:
	# True if the user has valid login tokens, false otherwise.
	function checkLogin($uname=false) {
		# If the constructor doesn't detect a user name, then we're obviously
		# not logged in.
		if (!$this->username) {
			return false;
		}
		
		$auth_ok = false;
		
		if (AUTH_USE_SESSION) {
			# Check the stored login token and time against the one for the
			# current session.  Return false on failure, or if the current
			# session doesn't belong to the user we want.
			$cookie_ts = COOKIE(LAST_LOGIN_TIME);
			$auth_token = md5(get_ip().$cookie_ts);
			$auth_ok = ($auth_token == SESSION(LOGIN_TOKEN) );
			$auth_ok = $auth_ok && ($cookie_ts == SESSION(LAST_LOGIN_TIME) );
			if ($uname) {
				$auth_ok = $auth_ok && ($this->username == $uname);
			}
		} else {
			# Check the cookies for the user and password hash and compare to 
			# the password hash for this user on the server.
			# This is NOT secure, but it is convenient.
			$usr = COOKIE(CURRENT_USER);
			$pwhash = COOKIE(PW_HASH);
			$auth_ok = ($this->username == $usr && 
			            md5($this->passwd.get_ip()) == $pwhash);
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
	function isAdministrator() {
		return ($this->username == ADMIN_USER || 
		        System::instance()->inGroup($this->username, 'administrators'));
	}

}
