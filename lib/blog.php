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

require_once("utils.php");
require_once("blogconfig.php");
#require_once("blogentry.php");
require_once("template.php");
#require_once("rss.php");

class Blog {

	var $name;
	var $home_path;
	var $description;
	var $image;
	var $theme;
	var $max_entries = BLOG_MAX_ENTRIES;
	var $max_rss = BLOG_MAX_ENTRIES;
	var $user = "";
	var $pass = "";
	var $entrylist;

	# NOTE: Use of realpath() requires PHP 4.

	function Blog($path="") {
		$this->name = '';
		if (defined("BLOG_ROOT")) $this->home_path = BLOG_ROOT;
		else $this->home_path = $path ? realpath($path) : getcwd();
		# Check the home_path set above.
		#$this->findBaseDir();
		$this->description = '';
		$this->image = '';
		$this->max_entries = BLOG_MAX_ENTRIES;
		$this->max_rss = BLOG_MAX_ENTRIES;
		$this->theme = "default";
		$this->user = "";
		$this->pass = "";
		$this->entrylist = array();
		$this->readBlogData();
	}
	
	function blogExists() {
		if (is_file($this->home_path.PATH_DELIM.BLOG_CONFIG_PATH))
			return true;
		else return false;
	}
	
	# Function to search up the directory tree for the blog root.  It will
	# stop when it gets to the web root.

	function findBaseDir() {
		# Test the current home_path first.
		$curr_path = $this->home_path.PATH_DELIM.BLOG_CONFIG_PATH;
		if (is_file($curr_path)) return $this->home_path;
		
		$web_root = find_document_root($this->home_path);
		do {
			$curr_path = dirname($curr_path);
			$curr_file = $curr_path.PATH_DELIM.BLOG_CONFIG_PATH;
			if (is_file($curr_file)) {
				$this->home_path = $curr_path;
				return $curr_path;
			}
		} while ($curr_path != $web_root);
	}

	function hasData() {
		$path = $this->home_path.PATH_DELIM.BLOG_CONFIG_PATH;
		if (is_file($path)) return true;
		else return false;
	}

	# Read and write a simple text file with the blog metadata.
	# Format is key = data, each record is a single line, unrecognized
	# keys are ignored.
	
	function readBlogData() {
		$path = $this->home_path.PATH_DELIM.BLOG_CONFIG_PATH;
		if (is_file($path)) $config_data = file($path);
		else return false;
		if (! $config_data) return false;

		foreach ($config_data as $line) {
			# Split the string on the equal sign.  We pass on the limit 
			# parameter for the sake of compatibility.
			$line_data = explode("=", $line);
			$key = strtolower(trim($line_data[0]));
			$line_data[0] = "";
			$data = trim(implode("", $line_data));
			if (get_magic_quotes_runtime()) $data = stripslashes($data);
			# Now find the correct key and set the associated property.
			switch ($key) {
				case "name": $this->name = $data; break;
				case "description": $this->description = $data; break;
				case "image": $this->image = $data; break;
				case "max entries": $this->max_entries = $data; break;
				case "max rss": $this->max_rss = $data; break;
				case "theme": $this->theme = $data; break;
			}
			
		}
	}

	function writeBlogData() {
		$path = $this->home_path.PATH_DELIM.BLOG_CONFIG_PATH;
		$str = "Name = ".$this->name."\n";
		$str .= "Description = ".$this->description."\n";
		$str .= "Image = ".$this->image."\n";
		$str .= "Max Entries = ".$this->max_entries."\n";
		$str .= "Max RSS = ".$this->max_rss."\n";
		$str .= "Theme = ".$this->theme."\n";
		$ret = write_file($path, $str);
		return $ret;
	}

	function password($val="") {
		if ($val) $this->pass = md5($val);
		else return $this->pass;
	}
	
	function doLogin($uname, $pwd) {
		if ($uname == "" || $pwd == "") return false;
		$pw_hash = md5($pwd);
		$auth_tok = 
		$ret = ($uname == $this->user);
		$ret &= ($pw_hash == $this->pass);
		if ($ret) {
			setcookie($this->name."litu", md5($uname) );
			setcookie($this->name."litp", md5($pwd) );
		}
		return $ret;
	}

	function checkLogin() {
		$uname_hash = md5($uname);
		$pw_hash = md5($pwd);
		$ret = ($uname_hash == $this->user);
		$ret &= ($pw_hash == $this->pass);
		return $ret;
	}
	
	function getMonth($year=false, $month=false) {
		$ent = new BlogEntry;
		$curr_dir = getcwd();
		if ($year && $month) 
			$curr_dir .= BLOG_ENTRY_PATH.PATH_DELIM.$year.PATH_DELIM.$month;
		$ent_list = array();
		$dir_list = scan_directory($curr_dir, true);
		foreach ($dir_list as $file) {
			if ( is_dir($file) && $ent->isEntry($curr_dir.PATH_DELIM.$file) ) {
				$ent_list[] = $file;
			}
		}
		rsort($ent_list);
		foreach ($ent_list as $ent) $this->entrylist[] = new BlogEntry($ent);
	}

	function getMonthCount($year=false, $month=false) {
		$ent = new BlogEntry;
		$curr_dir = getcwd();
		if ($year && $month) {
			$curr_dir .= BLOG_ENTRY_PATH.PATH_DELIM.$year.PATH_DELIM.$month;
		}
		$dir_list = scan_directory($curr_dir, true);
		$ent_count = 0;
		foreach ($dir_list as $file) {
			if ( is_dir($file) && $ent->isEntry($curr_dir.PATH_DELIM.$file) ) {
				$ent_count++;
			}
		}
		return $ent_count;
	}

	# Returns the URL for the blog homepage.

	function getURL($full_uri=true) {
		return localpath_to_uri($this->home_path, $full_uri);
	}
	
	# Get the most recent entries across all months and years.
	
	function getRecent($num_entries=false) {
	
		$entry = new BlogEntry;
	
		$ent_dir = $this->home_path.PATH_DELIM.BLOG_ENTRY_PATH;
		$dirhand = opendir($ent_dir);
		$show_max = $num_entries ? $num_entries : $this->max_entries;
		$num_found = 0;
		
		$year_list = scan_directory($ent_dir, true);
		rsort($year_list);

		foreach ($year_list as $year) {
			$month_list = scan_directory($ent_dir.PATH_DELIM.$year, true);
			rsort($month_list);
			foreach ($month_list as $month) {
				$path = $ent_dir.PATH_DELIM.$year.PATH_DELIM.$month;
				$ents = scan_directory($path, true);
				rsort($ents);
				foreach ($ents as $e) {
					$ent_path = $path.PATH_DELIM.$e;
					if ( $entry->isEntry($ent_path) ) {
						$this->entrylist[] = new BlogEntry($ent_path);
						$num_found++;
						# If we've hit the max, then break out of all 3 loops.
						if ($num_found >= $show_max) break 3;
					}
				}
			}
		}
		
	}

	# Export blog variables to a PHPTemplate class.

	function exportVars(&$tpl) {
		$tpl->set("BLOG_NAME", $this->name);
		$tpl->set("BLOG_DESCRIPTION", $this->description);
		$tpl->set("BLOG_IMAGE", $this->image);
		$tpl->set("BLOG_MAX_ENTRIES", $this->max_entries);
		$tpl->set("BLOG_BASE_DIR", $this->home_path);
		$tpl->set("BLOG_URL", $this->getURL() );
		$tpl->set("BLOG_URL_ROOTREL", $this->getURL(false));
		$tpl->set("BLOG_RSS1_FEED", $this->getURL().BLOG_FEED_PATH."/".BLOG_RSS1_NAME);
		$tpl->set("BLOG_RSS2_FEED", $this->getURL().BLOG_FEED_PATH."/".BLOG_RSS2_NAME);
	}

	function getWeblog () {
		$tpl = new PHPTemplate(BLOG_TEMPLATE);
		$tpl->set("BLOG_NAME", $this->name);
		$tpl->set("BLOG_DESCRIPTION", $this->description);
		$tpl->set("BLOG_IMAGE", $this->image);
		$tpl->set("BLOG_MAX_ENTRIES", $this->max_entries);
		$tpl->set("BLOG_BASE_DIR", $this->home_path);
		$tpl->set("BLOG_URL", $this->getURL() );
		$ret = "";
		if (! $this->entrylist) $this->getRecent();
		foreach ($this->entrylist as $ent) $ret .= $ent->get();
		$tpl->set("BODY", $ret);
		return $ret;
	}

	function updateRSS1 () {

		$feed = new RSS1;
		$path = $this->home_path.PATH_DELIM.BLOG_FEED_PATH.PATH_DELIM.BLOG_RSS1_NAME;
		$feed_url = localpath_to_uri($path);

		$feed->url = $feed_url;
		$feed->image = $this->image;
		$feed->description = $this->description;
		$feed->site = $this->getURL();
	
		if (! $this->entrylist) $this->getRecent($this->max_rss);
		foreach ($this->entrylist as $ent) 
			$feed->entrylist[] = new RSS1Entry($ent->permalink(), $ent->subject, $ent->subject);
		
		$ret = $feed->writeFile($path);	
		return $ret;
	}

	function updateRSS2 () {

		$feed = new RSS2;
		$path = $this->home_path.PATH_DELIM.BLOG_FEED_PATH.PATH_DELIM.BLOG_RSS2_NAME;
		$feed_url = localpath_to_uri($path);

		$feed->url = $feed_url;
		$feed->image = $this->image;
		$feed->description = $this->description;
		$feed->title = $this->name;
	
		if (! $this->entrylist) $this->getRecent($this->max_rss);
		foreach ($this->entrylist as $ent) 
			$feed->entrylist[] = new RSS2Entry($ent->permalink(), 
				$ent->subject, 
				"<![CDATA[".$ent->markup($ent->data)."]]>", 
				$ent->commentlink() );
		
		$ret = $feed->writeFile($path);	
		return $ret;
	}

	function validate() {
		$ret = preg_match("/^\d+$/", $this->blogid);
		$ret &= preg_match("/^\d+$/", $this->max_entries);
		$ret &= preg_match("/^\w+$/", $this->short_name);
		$ret &= (trim($this->ownerid) != "");
		return $ret;
	}

	function createYear($year) {
		$path = $this->home_path.PATH_DELIM.BLOG_ENTRY_PATH.PATH_DELIM.$year;
		if (is_file($path.PATH_DELIM."config.php")) return true;
		if (! is_dir($path)) { 
			$ret = mkdir_rec($path);
			$ret &= create_directory_wrappers($path, YEAR_ENTRIES);
		}
		return $ret;
	}

	function createMonth($year, $month) {
		$path = $this->home_path.PATH_DELIM.BLOG_ENTRY_PATH.PATH_DELIM.$year.PATH_DELIM.PATH_DELIM.$month;
		if (is_file($path.PATH_DELIM."config.php")) return true;
		if (! is_dir($path)) {
			$ret = 
			$ret &= create_directory_wrappers($path, MONTH_ENTRIES);
		}
		return $ret;
	}

	function createPath($year, $month) {
		$ret = $this->createYear($year);
		if ($ret) $ret = $this->createMonth($year, $month);
		return $ret;
	}

	# This is an upgrade function that will create new config and wrapper 
	# scripts to upgrade a directory of blog data to the current version.
	# The data files should always work unmodified, so they do not need to
	# be upgraded.  This should not be required too often, if all goes well.
	# It is assumed that this function will only be run from the package
	# installation directory.
	# NOTE: Uses realpath(), which requires PHP 4.

	function upgradeWrappers () {
		$inst_path = getcwd();
		# Upgrade the base blog directory first.  All other directories will
		# get a copy of the config.php created here.
		$ret = create_directory_wrappers($this->home_path, BLOG_BASE, $inst_path);
		
		# Upgrade the articles.
		$path = $this->home_path.PATH_DELIM.BLOG_ARTICLE_PATH;
		$ret &= create_directory_wrappers($path, BLOG_ARTICLES);
		
		$path = $this->home_path.PATH_DELIM.BLOG_ENTRY_PATH;
		$ret &= create_directory_wrappers($path, BLOG_ENTRIES);
		$dir_list = scan_directory($path, true);
		foreach ($dir_list as $yr) {
			$year_path = $path.PATH_DELIM.$yr;
			$ret &= create_directory_wrappers($year_path, YEAR_ENTRIES);
			$year_list = scan_directory($year_path, true);
			foreach ($year_list as $mn) {
				$month_path = $year_path.PATH_DELIM.$mn;
				$ret &= create_directory_wrappers($month_path, MONTH_ENTRIES);
				$month_list = scan_directory($month_path, true);
				foreach ($month_list as $ent) {
					$ent_path = $month_path.PATH_DELIM.$ent;
					$cmt_path = $ent_path.PATH_DELIM.ENTRY_COMMENT_DIR;
					$ret &= create_directory_wrappers($ent_path, ENTRY_BASE);
					$ret &= create_directory_wrappers($cmt_path, ENTRY_COMMENTS);
				}
			}
		}
		$path = $this->home_path.PATH_DELIM.BLOG_ARTICLE_PATH;
		$ret &= create_directory_wrappers($path, BLOG_ARTICLES);
		$dir_list = scan_directory($path, true);
		foreach ($dir_list as $ar) {
			$ar_path = $path.PATH_DELIM.$ar;
			$cmt_path = $ar_path.PATH_DELIM.ENTRY_COMMENT_DIR;
			$ret &= create_directory_wrappers($ar_path, ARTICLE_BASE);
			$ret &= create_directory_wrappers($cmt_path, ENTRY_COMMENTS);
		}
		return $ret;
	}

	# A quick utility function to fix the borked permissions from not setting
	# the correct umask when creating directories.  This resulted in 
	# directories that I couldn't alter via FTP.

	function fixDirectoryPermissions($start_dir=false) {
		$fs = CreateFS();
		if (! $start_dir) $start_dir = $this->home_path;
		$dir_list = scan_directory($start_dir, true);
		$ret = true;
		foreach ($dir_list as $dir) {
			$path = $start_dir.PATH_DELIM.$dir;
			$ret &= $fs->chmod($path, $fs->defaultMode() );
			$ret &= $this->fixDirectoryPermissions($path);
		}
		$fs->destruct();
		return $ret;
	}

	function setLogin($uname, $pwd) {
		return create_passwd_file($this->home_path, $uname, $pwd);
	}

	function insert ($path=false) {
		if (! check_login()) return false;
		$fs = CreateFS();
		# Get the installation directory, then create and get the blog
		# directory.  These directories are added to the include_path using
		# a config file that is copied to all entry directories.
		# It is assumed that this will only be run from the install directory.
		
		if (get_magic_quotes_gpc()) {
			$this->home_path = stripslashes($this->home_path);
			$this->name = stripslashes($this->name);
			$this->description = stripslashes($this->description);
			$this->image = stripslashes($this->image);
			$this->theme = stripslashes($this->theme);
		}
		
		$inst_path = getcwd();
		if ($path) $this->home_path = canonicalize($path);
		#$this->home_path = realpath(getcwd().PATH_DELIM.$this->home_path);
		$this->home_path = canonicalize($this->home_path);
		if (! is_dir($this->home_path)) $fs->mkdir_rec($this->home_path);
		chdir($this->home_path);
		$this->home_path = getcwd();
		$blog_path = $this->home_path;
		$ent_path = $this->home_path.PATH_DELIM.BLOG_ENTRY_PATH;
		if (! is_dir($ent_path) ) $fs->mkdir_rec($ent_path);
		$rss_path = $this->home_path.PATH_DELIM.BLOG_FEED_PATH;
		if (! is_dir($rss_path) ) $fs->mkdir_rec($rss_path);
		$blog_templ_dir = $blog_path.PATH_DELIM.BLOG_TEMPLATE_DIR;
		$sys_templ_dir = $inst_path.PATH_DELIM.BLOG_TEMPLATE_DIR;
		
		$ret = create_directory_wrappers($blog_path, BLOG_BASE, $inst_path);
		$ret &= create_directory_wrappers($ent_path, BLOG_ENTRIES);
		 
		$ret = $this->writeBlogData();
		$fs->destruct();
		return $ret;
	}
	
	function update () {
		if (! check_login()) return false;
		if (get_magic_quotes_gpc()) {
			$this->home_path = stripslashes($this->home_path);
			$this->name = stripslashes($this->name);
			$this->description = stripslashes($this->description);
			$this->image = stripslashes($this->image);
			$this->theme = stripslashes($this->theme);
		}
		return $this->delete() && $this->writeBlogData();
	}
	
	function delete () {
		if (! check_login()) return false;
		$fs = CreateFS();
		if (! is_dir($this->home_path.PATH_DELIM.BLOG_DELETED_PATH) )
			$fs->mkdir_rec($this->home_path.PATH_DELIM.BLOG_DELETED_PATH);
		$source = $this->home_path.PATH_DELIM.BLOG_CONFIG_PATH;
		$target = $this->home_path.PATH_DELIM.BLOG_DELETED_PATH.PATH_DELIM.BLOG_CONFIG_PATH."-".date(ENTRY_PATH_FORMAT);
		$ret = $fs->rename($source, $target);
		$fs->destruct();
		return $ret;
	}
	
}

?>
