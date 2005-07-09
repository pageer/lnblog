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

define("LOGIN_TOKEN", "lToken");
define("LAST_LOGIN_TIME", "lastLTime");
define("CURRENT_USER", "uName");
define("PW_HASH", "uHash");

if (defined("INSTALL_ROOT")) {
	if ( is_file(INSTALL_ROOT.PATH_DELIM."passwd.php") ) {
		require_once("passwd.php");
	} else {
		$global_user_list = array();
	}
}

function create_passwd_file () {
	global $global_user_list;
	$data = "<?php\n";
	$data .= '$global_user_list = array();'."\n";
	foreach ($global_user_list as $name=>$udat) {
		$data .= '$global_user_list[\''.$name.'\'] = array(';
		$tmpdata = '';
		foreach ($udat as $key=>$val) {
			$val = str_replace("\\", "\\\\", $val);
			$val = str_replace("'", "\\'", $val);
			$tmpdata .= ($tmpdata != '' ? ', ' : '') . "'$key'=>'$val'";
		}
		$data .= $tmpdata.");\n";
	}
	$data .= "?>";
	return write_file(INSTALL_ROOT.PATH_DELIM."passwd.php", $data);
}

# Class to manipulate and authenticate users.  Is designed so that a user's
# login can be checked in two lines, to keep maintenance overhead low.

class User {

	var $username;
	var $passwd;
	var $salt;
	var $fullname;
	var $email;
	var $homepage;

	function User($uname=false, $pw=false) {
		global $global_user_list;
	
		$this->username = '';
		$this->passwd = '';
		$this->salt = '';
		$this->fullname = '';
		$this->email = '';
		$this->homepage = '';

		if (!$uname && ( SESSION(CURRENT_USER) || COOKIE(CURRENT_USER) ) ) {
			if ( SESSION(CURRENT_USER) == COOKIE(CURRENT_USER) ||
			    (SESSION(CURRENT_USER) == '' && COOKIE(CURRENT_USER) ) 
			   ) {
				$uname = COOKIE(CURRENT_USER);
			}
		}
		
		if ($uname && isset($global_user_list[$uname]) ) {
		#echo "<p>Doing user name</p>";
			$this->username = $uname;
			$this->passwd = $global_user_list[$uname]["pwd"];
			$this->salt = $global_user_list[$uname]["salt"];
			$this->fullname = $global_user_list[$uname]["fullname"];
			$this->email = $global_user_list[$uname]["email"];
			$this->homepage = $global_user_list[$uname]["homepage"];

			if ($pw) $this->login($pw);

		}
	}

	# Convenience function to export relevant user data to a template.

	function exportVars(&$tpl) {
		if ($this->username) $tpl->set("USER_ID", $this->username);
		if ($this->fullname) $tpl->set("USER_NAME", $this->fullname);
		if ($this->email) $tpl->set("USER_EMAIL", $this->email);
		if ($this->homepage) $tpl->set("USER_HOMEPAGE", $this->homepage);
		$tpl->set("USER_DISPLAY_NAME", $this->displayName() );
	}

	function get($uname) {
		if ($uname && isset($global_user_list[$uname]) ) {
			$this->passwd = $global_user_list[$uname]["pwd"];
			$this->salt = $global_user_list[$uname]["salt"];
			$this->fullname = $global_user_list[$uname]["fullname"];
			$this->email = $global_user_list[$uname]["email"];
			$this->homepage = $global_user_list[$uname]["homepage"];
			return true;
		} else return false;
	}

	function checkPassword($pass) {
	#echo "checking login";
		$hash = md5($pass.$this->salt);
		#echo "<p>Hash: $hash</p><p>Pass: $this->passwd</p>";
		$ret = ($hash == $this->passwd);
		return $ret;
	}

	# Save changes to user data.  Note that this function doesn't scale well,
	# as we cannot guarantee that the another user will not simultaneously
	# try to write the same file and clobber our changes.
	# The only real fix to this (that's worth the time to implement) is to 
	# switch to a database storage backend.

	function save() {
		global $global_user_list;
		if (!$this->username ||! $this->passwd) return false;
		$global_user_list[$this->username]["pwd"] = $this->passwd;
		$global_user_list[$this->username]["salt"] = $this->salt;
		$global_user_list[$this->username]["fullname"] = $this->fullname;
		$global_user_list[$this->username]["email"] = $this->email;
		$global_user_list[$this->username]["homepage"] = $this->homepage;
		create_passwd_file();
	}

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
			#echo "<p>$this->salt</p><p>$pwd</p><p>$this->passwd</p>";
			
			# Prevent password change from logging out on cookie-only config.
			if ( $this->username == COOKIE(CURRENT_USER) )
				setcookie(PW_HASH, $this->passwd);
		}
	}

	function username($uid=false) {
		if (!$uid) return $this->username;
		else $this->username = $uid;
	}

	function name($nm=false) {
		if (!$nm) return $this->fullname;
		else $this->fullname = $nm;
	}

	# Returns a name to display.  If a full name is defined, it uses that.
	# Otherwise, it reverts to the username.

	function displayName() {
		if ($this->fullname) return $this->fullname;
		else return $this->username;
	}

	function email($mail=false) {
		if (!$mail) return $this->email;
		else $this->email = $mail;
	}

	function homepage($url=false) {
		if (!$url) return $this->homepage;
		else $this->homepage = $url;
	}

	function login($pwd) {
		global $global_user_list;
		
		# Reject empty usernames or passwords.
		if ( trim($this->username) == "" || trim($pwd) == "" ) return false;
		
		# User does not exist.
		if ( ! isset($global_user_list[$this->username]) ) return false;

		if (AUTH_USE_SESSION) {
		#echo "Do login";
			#$check_passwd = md5($this->passwd.$this->salt);
			$ts = gmdate("M d Y H:i:s", time());
			if ($this->checkPassword($pwd)) {
				# Create a login token.
				#echo "<br />login OK";
				$token = md5(get_ip().$ts);
				setcookie(CURRENT_USER, $this->username);
				SESSION(CURRENT_USER, $this->username);
				SESSION(LOGIN_TOKEN, $token);
				SESSION(LAST_LOGIN_TIME, $ts);
				setcookie(LAST_LOGIN_TIME, "$ts");
		#echo "<p>".COOKIE(LAST_LOGIN_TIME)."</p><p>$token</p><p>".SESSION(LOGIN_TOKEN)."</p><p>".SESSION(LAST_LOGIN_TIME)."</p><p>Curr TS: $ts, ".$_COOKIE[LAST_LOGIN_TIME]."</p>";
				$ret = true;
			} else $ret = false;
			return $ret;
		} else {
			setcookie(CURRENT_USER, $this->username);
			setcookie(PW_HASH, $this->passwd);
		}
	}

	function logout() {
		if (AUTH_USE_SESSION) {
			SESSION(CURRENT_USER, false);
			SESSION(LOGIN_TOKEN, false);
			SESSION(LAST_LOGIN_TIME, false);
			setcookie(LOGIN_TOKEN, "");
			setcookie(LAST_LOGIN_TIME, "");
		} else {
			setcookie(CURRENT_USER, "");
			setcookie(PW_HASH, "");
		}
	}

	function checkLogin() {
		# If the constructor doesn't detect a user name, then we're obviously
		# not logged in.
		if (!$this->username) return false;
		if (AUTH_USE_SESSION) {
		#echo "<p>Try auth</p>";
			# Check the stored login token and time against the one for the
			# current session.  Return false on failure, or if the current
			# session doesn't belong to the user we want.
			$cookie_ts = COOKIE(LAST_LOGIN_TIME);
			$auth_token = md5(get_ip().$cookie_ts);
			$auth_ok = ($auth_token == SESSION(LOGIN_TOKEN) );
			#if ($auth_ok) echo "<p>Auth OK 1</p>";
			#else echo "<p>$auth_token != ".SESSION(LOGIN_TOKEN)."</p>";
			$auth_ok = $auth_ok && ($cookie_ts == SESSION(LAST_LOGIN_TIME) );
			#if ($auth_ok) echo "<p>Auth OK 2</p>";
			#else echo "<p>$cookie_ts != ".SESSION(LAST_LOGIN_TIME)."</p>";
		#echo "<p>".COOKIE(LAST_LOGIN_TIME)."</p><p>$auth_token</p><p>".SESSION(LOGIN_TOKEN)."</p><p>".SESSION(LAST_LOGIN_TIME)."</p>";
			if ($auth_ok) return true;
			else return false;
		} else {
			# Check the cookies for the user and password hash and compare to 
			# the password hash for this user on the server.
			# This is NOT secure, but it is convenient.
			$usr = COOKIE(CURRENT_USER);
			$pwhash = COOKIE(PW_HASH);
			if ($this->username == $usr && $this->passwd = $pwhash) return true;
			else return false;
		}
	}

	function isAdministrator() {
		return ($this->username == ADMIN_USER);
	}

}
?>
