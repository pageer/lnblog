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

require_once("lib/utils.php");
require_once("blogconfig.php");
require_once("lib/creators.php");
require_once("lib/lnblogobject.php");

/* Class: Blog
 * The "master" class which represents a weblog.  Nearly all functions are performed through this object.
 * This is the object that handles user security.
 *
 * Inherits:
 * <LnBlogObject>
 *
 * Events:
 * OnInit           - Fired when a blog object is created.
 * InitComplete     - Fired after the constructor has run.
 * OnInsert         - Run when a new blog is about to be created.
 * InsertComplete   - Run after a new blog object has been saved.
 * OnUpdate         - Run when a blog is about to be updated.
 * UpdateComplete   - Run after a blog is successfully updated.
 * OnDelete         - Run before a blog is deleted.
 * DeleteComplete   - Run after a blog is deleted.
 * OnUpgrade        - Run before the wrapper upgrade process.
 * UpgradeComplete  - Run when the wrapper upgrade is finished.
 * OnEntryPreview   - Run before populating entry template for preview.
 * OnArticlePreview - Fired before populating article template for preview.
 * OnEntryError     - Fired before populating template when on an error.
 * OnArticelError   - Fired before populating template when on an error.
 */

class Blog extends LnBlogObject {

	var $name;
	var $home_path;
	var $description;
	var $image;
	var $theme;
	var $max_entries = BLOG_MAX_ENTRIES;
	var $max_rss = BLOG_MAX_ENTRIES;
	var $user = "";
	var $pass = "";
	var $owner = ADMIN_USER;
	var $write_list;
	var $entrylist;
	var $last_blogentry;
	var $last_article;

	function Blog($path="") {
		
		$this->raiseEvent("OnInit");
	
		$this->name = '';
		if (defined("BLOG_ROOT")) { 
			$this->home_path = BLOG_ROOT;
		} elseif (sanitize(GET("blog")) && defined("INSTALL_ROOT")) {
			$this->home_path = INSTALL_ROOT.PATH_DELIM.sanitize(GET("blog"));
		} else {
			$this->home_path = $path ? realpath($path) : getcwd();
		}
		$this->description = '';
		$this->image = '';
		$this->max_entries = BLOG_MAX_ENTRIES;
		$this->max_rss = BLOG_MAX_ENTRIES;
		$this->theme = "default";
		$this->user = "";
		$this->pass = "";
		$this->owner = ADMIN_USER;
		$this->write_list = array();
		$this->entrylist = array();
		$this->last_blogentry = false;
		$this->last_article = false;
		$this->readBlogData();
		
		$this->raiseEvent("InitComplete");

	}

	/*
	Property: writers
	Set and return the list of users who can add posts to the blog.  
	
	Parameters:
	list - an arrays or comma-delimited	stringof user names.

	Returns:
	An array of user names.
	*/

	function writers($list=false) {
		if ($list === false) {
			return $this->write_list;
		} elseif (is_array($list)) {
			$this->write_list = $list;
		} else {
			$this->write_list = explode(",", $list);
		}
	}

	/*
	Method: blogExists
	Determines whether or not the object represents a saved blog.

	Returns:
	True if the blog exists, false if it doesn't.
	*/

	function blogExists() {
		if (is_file($this->home_path.PATH_DELIM.BLOG_CONFIG_PATH))
			return true;
		else return false;
	}
	
	/*
	Method: readBlogData
	Read and write a simple text file with the blog metadata.
	Format is key = data, each record is a single line, unrecognized
	keys are ignored.  This is for internal use only.
	*/
	function readBlogData() {
		$path = $this->home_path.PATH_DELIM.BLOG_CONFIG_PATH;
		if (is_file($path)) $config_data = file($path);
		else return false;
		if (! $config_data) return false;

		foreach ($config_data as $line) {
			# Split the string on the equal sign.  We skip the limit 
			# parameter for the sake of compatibility.
			$line_data = explode("=", $line);
			$key = strtolower(trim($line_data[0]));
			$line_data[0] = "";
			$data = trim(implode("", $line_data));
			# Now find the correct key and set the associated property.
			switch ($key) {
				case "name": $this->name = $data; break;
				case "description": $this->description = $data; break;
				case "image": $this->image = $data; break;
				case "max entries": $this->max_entries = $data; break;
				case "max rss": $this->max_rss = $data; break;
				case "theme": $this->theme = $data; break;
				case "owner": $this->owner = $data; break;
				case "write list": $this->write_list = explode(",", $data); break;
			}
			
		}
	}

	/*
	Method: writeBlogData
	Save the blog data to disk.  This is for internal use only.

	Returns:
	False on failure, something else on success.
	*/
	function writeBlogData() {
		$path = $this->home_path.PATH_DELIM.BLOG_CONFIG_PATH;
		$str = "Name = ".$this->name."\n";
		$str .= "Description = ".$this->description."\n";
		$str .= "Image = ".$this->image."\n";
		$str .= "Max Entries = ".$this->max_entries."\n";
		$str .= "Max RSS = ".$this->max_rss."\n";
		$str .= "Theme = ".$this->theme."\n";
		$str .= "Owner = ".$this->owner."\n";
		$str .= "Write List = ".implode(",", $this->write_list);
		$ret = write_file($path, $str);
		return $ret;
	}

	/*
	Method: getDay
	Get all the blog entries for a particular day.

	Parameters:
	year  - The year you want.
	month - The month you want.
	day   - The day you want.

	Returns:
	An array of BlogEntry objects posted on the given date, sorted in 
	reverse chronological order by post time.
	*/
	function getDay($year, $month, $day) {
		$fmtday = sprintf("%02d", $day);
		$month_dir = BLOG_ROOT.PATH_DELIM.BLOG_ENTRY_PATH.PATH_DELIM.$year.
			PATH_DELIM.sprintf("%02d", $month).PATH_DELIM.$fmtday;
		$day_list = scan_directory($month_dir, true);
		rsort($day_list);
		$match_list = array();
		$ent = NewBlogEntry();
		foreach ($day_list as $dy) {
			if (substr($dy, 0, 2) == $fmtday && 
			    $ent->isEntry($month_dir.PATH_DELIM.$dy) ) {
				$match_list[] = NewBlogEntry($month_dir.PATH_DELIM.$dy);
			}
		}
		foreach ($match_list as $ent) $this->entrylist[] = $ent;
		return $match_list;
	}

	/*
	Method: getMonth
	Get a list of all entries for the specified month.
	If you do not specify a year and month, then the 
	routine will try to get it from the current directory
	and/or URL.

	Parameters:
	year  - *Optional* year you want.
	month - *Optional* month you want.

	Returns:
	An array of BlogEntry objects posted in the given month,
	sorted in reverse chronological order by post date.
	*/
	function getMonth($year=false, $month=false) {
		$ent = NewBlogEntry();
		$curr_dir = getcwd();
		if ($year && $month) {
			$curr_dir = $this->home_path.PATH_DELIM.BLOG_ENTRY_PATH.
			            PATH_DELIM.$year.PATH_DELIM.$month;
		} elseif (sanitize(GET("month"), "/\D/") && 
		          sanitize(GET("year"), "/\D/")) {
			$curr_dir = $this->home_path.PATH_DELIM.BLOG_ENTRY_PATH.
			            PATH_DELIM.sanitize(GET("year"), "/\D/").PATH_DELIM.
							sanitize(GET("month"), "/\D/");
		}
		$ent_list = array();
		$dir_list = scan_directory($curr_dir, true);
		foreach ($dir_list as $file) {
			if ( $ent->isEntry($curr_dir.PATH_DELIM.$file) ) {
				$ent_list[] = $file;
			}
		}
		rsort($ent_list);
		foreach ($ent_list as $ent) $this->entrylist[] = NewBlogEntry($ent);
		return $this->entrylist;
	}

	/*
	Method: getMonthCount
	Get the number of entries in the given month.  If no month and year are
	given, try to get them from the current directory/URL.

	Parameters:
	year  - *Optional* year you want.
	month - *Optional* month you want.

	Returns:
	A non-negative integer representing the number of posts in that month.
	*/
	function getMonthCount($year=false, $month=false) {
		$ent = NewBlogEntry();
		$curr_dir = getcwd();
		if ($year && $month) {
			$curr_dir = $this->home_path.PATH_DELIM.BLOG_ENTRY_PATH.
			            PATH_DELIM.$year.PATH_DELIM.$month;
		} elseif (sanitize(GET("year"), "/\D/") && 
		          sanitize(GET("month"), "/\D/")) {
			$curr_dir = $this->home_path.PATH_DELIM.BLOG_ENTRY_PATH.
			            PATH_DELIM.sanitize(GET("year"), "/\D/").PATH_DELIM.
							sanitize(GET("month"), "/\D/");
		}
		$dir_list = scan_directory($curr_dir, true);
		$ent_count = 0;
		foreach ($dir_list as $file) {
			if ( $ent->isEntry($curr_dir.PATH_DELIM.$file) ) {
				$ent_count++;
			}
		}
		return $ent_count;
	}

	/*
	Method: getYearList
	Get a list of all years in the archive, sorted in reverse chronological
	order.  
	
	Returns:
	A two-dimensional array.  The first dimension has numeric indexes.  The
	second has two elements, indexed as "year" and "link", which hold the
	4-digit year and a permalink to the archive of that year respectively.
	*/
	function getYearList() {
		$year_list = scan_directory($this->home_path.PATH_DELIM.BLOG_ENTRY_PATH, true);
		$ret = array();
		foreach ($year_list as $yr) {
			$ret[] = array("year"=>$yr, 
			               "link"=>$this->getURL().BLOG_ENTRY_PATH."/".$yr."/");
		}
		return $ret;
	}

	/*
	Method: getMonthList
	Get a list of the months for the given year.  If no year is given, try 
	to extract it from the current directory/URL.

	Parameters:
	year - The year you want.
	
	Returns:
	A two-dimensional array.  The first dimension is numerically indexed, with
	elements sorted in reverse chronological order.  The second dimension has
	three elements, indexed as "year", "month", and "link".  These hold, 
	respectively, the year you specified, the 2-digit month, and a permalink
	to the archive for that month.
	*/
	function getMonthList($year=false) {
		if (! $year) {
			if (sanitize(GET("year"), "/\D/")) {
				$year = sanitize(GET("year"), "/\D/");
			} else $year = basename(getcwd());
		}
		$month_list = scan_directory($this->home_path.PATH_DELIM.BLOG_ENTRY_PATH.PATH_DELIM.$year, true);
		rsort($month_list);
		$ret = array();
		foreach ($month_list as $mo) {
			$ret[] = array("year"=>$year, "month"=>$mo,
			               "link"=>$this->getURL().BLOG_ENTRY_PATH."/".$year."/".$mo."/");
		}
		return $ret;
	}

	/*
	Method: getRecentMonthList
	Get a list of recent months, starting from the current month and going
	backward.  This is essentially a wrapper around	<getMonthList>.

	Parameters:
	nummonths - *Optional* number of months to return.  The default is 12.
	
	Returns:
	An array of the most recent months in the same format used by 
	<getMonthList>.  The total length of the first dimension of the array
	should be nummonths long.
	*/
	function getRecentMonthList($nummonths=12) {
		$count = 0;
		$ret = array();
		$year_list = $this->getYearList();
		foreach ($year_list as $year) {
			$month_list = $this->getMonthList($year["year"]);
			foreach ($month_list as $month) {
				$ret[] = $month;
				if (++$count >= $nummonths) break 2;
			}
		}
		return $ret;
	}

	/*
	Method: getURL
	Get the URL for the blog homepage.

	Parameters:
	full_uri - *Optional* boolean for whether or not to return a full URI
	           or a root-relative one.  Default is true.

	Returns:
	A string holding the URI to the blog root directory.
	*/
	function getURL($full_uri=true) {
		return localpath_to_uri($this->home_path, $full_uri);
	}
	
	/*
	Method: getRecent
	Get the most recent entries across all months and years.

	Parameters:
	num_entries - *Optional* number of entries to return.  The default is to
	              use the <max_entries> property of the blog.  If -1 is
	              passed, then all entries in the blog will be returned.

	Returns:
	An array of BlogEntry objects.
	*/
	function getRecent($num_entries=false) {
	
		$show_max = $num_entries ? $num_entries : $this->max_entries;
		if (! $num_entries) $show_max = $this->max_entries;
		else $show_max = $num_entries;

		$this->getEntries($show_max);
		return $this->entrylist;
	}
	
	/*
	Method: getNextMax
	Convenience function to get "previous entries". 	Returns a list of 
	entries starting after the the end of the blog's <max_entires> property.
	
	Parameters:
	num_entries - The *optional* number or entries to return.  The default is
	              to use the blog's <max_entries> property.
	
	Returns:
	An array of BlogEntry objects.
	*/
	function getNextMax($num_entries=false) {
		
		$show_max = $num_entries ? $num_entries : $this->max_entries;
		if (! $num_entries) $show_max = $this->max_entries;
		else $show_max = $num_entries;
	
		$this->getEntries($show_max, $this->max_entries);
		return $this->entrylist;

	}
	
	/*
	Method: getEntries
	Scan entries in reverse chronological order, starting at a given offset,
	and get a given number of them.

	Parameters:
	number - The number of entries to return.
	offset - *Optional* number of entries from the beginning of the list to 
	         skip.  The default is 0, i.e. start at the beginning.

	Returns:
	An array of BlogEntry objects.
	*/
	function getEntries($number,$offset=0) {
	
		$entry = NewBlogEntry();
		$this->entrylist = array();
		if ($number == 0) return;
	
		$ent_dir = $this->home_path.PATH_DELIM.BLOG_ENTRY_PATH;
		$dirhand = opendir($ent_dir);
		$num_scanned = 0;
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
						if ($num_scanned >= $offset) {
							$this->entrylist[] = NewBlogEntry($ent_path);
							$num_found++;
							# If we've hit the max, then break out of all 3 loops.
							if ($num_found >= $number && $number != -1) break 3;
						}
						$num_scanned++;
					}
				}  # End month loop
			}  # End year loop
		}  # End archive loop
		
	}

	# Gets the appropriate list of archive links.  This could be a list
	# of years, months, or entries, depending on the circumstances.

	function getArchive() {

	}

	/*
	Method: getArticles
	Returns a list of all articles, in no particular order.

	Returns:
	An array of Article objects.
	*/
	function getArticles() {
		$art = NewArticle();
		$art_path = $this->home_path.PATH_DELIM.BLOG_ARTICLE_PATH.PATH_DELIM;
		$art_list = scan_directory($art_path);
		$ret = array();
		foreach ($art_list as $dir) {
			if ($art->isArticle($art_path.$dir) ) {
				$ret[] = NewArticle($art_path.$dir);
			}
		}
		return $ret;
	}

	/*
	Method: getArticleList
	Get a list of articles with title and permalink.

	Parameters:
	number      - *Optional* number of articles to return.  Default is all.
	sticky_only - *Optionally* return only "sticky" articles. 
	              Default is true.
	
	Returns:
	A two-dimensional array.  The first is numerically indexed.  The second
	is two elements indexed as "title" and "link".  These represent the title
	of the article and the permalink to it respectively.
	*/
	function getArticleList($number=false, $sticky_only=true) {
		$art = NewArticle();
		$art_path = $this->home_path.PATH_DELIM.BLOG_ARTICLE_PATH.PATH_DELIM;
		$art_list = scan_directory($art_path);
		if (!$art_list) $art_list = array();
		$ret = array();
		$count = 0;
		foreach ($art_list as $dir) {
			if ( ! $sticky_only && $art->isArticle($art_path.$dir) ) {
				$sticky_test = $art->readSticky($art_path.$dir);
				if ($sticky_test) {
					$ret[] = $sticky_test;
				} else {
					$a = NewArticle($art_path.$dir);
					$ret[] = array("title"=>$a->subject, "link"=>$a->permalink());
				}
				$count++;
			} elseif ($sticky_only && $art->isSticky($art_path.$dir) ) {
				$ret[] = $art->readSticky($art_path.$dir);
				$count++;
			}
			if ($number && $count >= $number) break;
		}
		return $ret;

	}
	
	/*
	Method: exportVars
	Export blog variables to a PHPTemplate class.  
	This is for internal use only.

	Parameters:
	tpl - The PHPTemplate to populate.
	*/
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

	/*
	Method: getWeblog
	Gets the markup to display for the front page of a weblog.

	Returns:
	A string holding the HTML to display.
	*/
	function getWeblog () {
		$tpl = NewTemplate(BLOG_TEMPLATE);
		$tpl->set("BLOG_NAME", $this->name);
		$tpl->set("BLOG_DESCRIPTION", $this->description);
		$tpl->set("BLOG_IMAGE", $this->image);
		$tpl->set("BLOG_MAX_ENTRIES", $this->max_entries);
		$tpl->set("BLOG_BASE_DIR", $this->home_path);
		$tpl->set("BLOG_URL", $this->getURL() );
		$ret = "";
		if (! $this->entrylist) $this->getRecent();
		foreach ($this->entrylist as $ent) {
			$ret .= $ent->get($this->canModifyEntry($ent) );
		}
		$tpl->set("BODY", $ret);
		return $ret;
	}

	/*
	Method: upgradeWrappers
	This is an upgrade function that will create new config and wrapper 
	scripts to upgrade a directory of blog data to the current version.
	The data files should always work unmodified, so they do not need to
	be upgraded.  This should not be required too often, if all goes well.
	
	Precondition:
	It is assumed that this function will only be run from the package
	installation directory.

	Returns:
	True on success, false on failure.
	*/
	function upgradeWrappers () {
		$this->raiseEvent("OnUpgrade");
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
					$tb_path = $ent_path.PATH_DELIM.ENTRY_TRACKBACK_DIR;
					$ret &= create_directory_wrappers($ent_path, ENTRY_BASE);
					$ret &= create_directory_wrappers($cmt_path, ENTRY_COMMENTS);
					$ret &= create_directory_wrappers($tb_path, ENTRY_TRACKBACKS);
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
		$this->raiseEvent("UpgradeComplete");
		return $ret;
	}

	# A quick utility function to fix the borked permissions from not setting
	# the correct umask when creating directories.  This resulted in 
	# directories that I couldn't alter via FTP.

	function fixDirectoryPermissions($start_dir=false) {
		$fs = NewFS();
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

	function insert ($path=false) {
		if (! $this->canAddBlog() ) return false;
		$this->raiseEvent("OnInsert");
		$fs = NewFS();
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
		if (! is_dir($this->home_path)) {
			$ret = $fs->mkdir_rec($this->home_path);
			if (! $ret) return false;
		}
		
		chdir($this->home_path);
		$this->home_path = getcwd();
		$blog_path = $this->home_path;
		$ent_path = $this->home_path.PATH_DELIM.BLOG_ENTRY_PATH;
		if (! is_dir($ent_path) ) {
			$ret = $fs->mkdir_rec($ent_path);
			if (! $ret) return false;
		}
		$rss_path = $this->home_path.PATH_DELIM.BLOG_FEED_PATH;
		if (! is_dir($rss_path) ) {
			$ret = $fs->mkdir_rec($rss_path);
			if (! $ret) return false;
		}
		$blog_templ_dir = $blog_path.PATH_DELIM.BLOG_TEMPLATE_DIR;
		$sys_templ_dir = $inst_path.PATH_DELIM.BLOG_TEMPLATE_DIR;
		
		$ret = create_directory_wrappers($blog_path, BLOG_BASE, $inst_path);
		$ret &= create_directory_wrappers($ent_path, BLOG_ENTRIES);
		 
		$ret = $this->writeBlogData();
		$fs->destruct();
		$this->raiseEvent("InsertComplete");
		return $ret;
	}
	
	function update () {
		if (! $this->canModifyBlog() ) return false;
		$this->raiseEvent("OnUpdate");
		if (get_magic_quotes_gpc()) {
			$this->home_path = stripslashes($this->home_path);
			$this->name = stripslashes($this->name);
			$this->description = stripslashes($this->description);
			$this->image = stripslashes($this->image);
			$this->theme = stripslashes($this->theme);
		}
		$ret = $this->delete() && $this->writeBlogData();
		$this->raiseEvent("UpdateComplete");
		return $ret;
	}
	
	function delete () {
		if (! $this->canModifyBlog()) return false;
		$this->raiseEvent("OnDelete");
		$fs = NewFS();
		if (! is_dir($this->home_path.PATH_DELIM.BLOG_DELETED_PATH) )
			$fs->mkdir_rec($this->home_path.PATH_DELIM.BLOG_DELETED_PATH);
		$source = $this->home_path.PATH_DELIM.BLOG_CONFIG_PATH;
		$target = $this->home_path.PATH_DELIM.BLOG_DELETED_PATH.PATH_DELIM.BLOG_CONFIG_PATH."-".date(ENTRY_PATH_FORMAT_LONG);
		$ret = $fs->rename($source, $target);
		$fs->destruct();
		$this->raiseEvent("DeleteComplete");
		return $ret;
	}

#---------------------------------------------------------------------------
# Security checking functions.

	# Determines if the user can add a new entry.  The user must be in the
	# blog's write list or be the blog owner.

	function canAddEntry($usr=false) {
		$u = NewUser($usr);
		if (! $u->checkLogin() ) return false;
		if (ADMIN_USER == $u->username() ||
		    $this->owner == $u->username() ) return true;
		foreach ($this->write_list as $writer)
			if ($u->username() == $writer) return true;
		return false;
	}

	# Determines if the user can edit or delete the entry.  Also applies to
	# modifying user comments.

	function canModifyEntry($ent=false, $usr=false) {
		$u = NewUser($usr);
		if (! $u->checkLogin() ) return false;
		if (!$ent) $ent = NewBlogEntry();
		if (ADMIN_USER == $u->username() ||
		    $this->owner == $u->username() ||
		    $ent->uid == $u->username() ) return true;
		return false;
	}

	# Same as canAddEntry(), but for articles.

	function canAddArticle($usr=false) {
		$u = NewUser($usr);
		if (! $u->checkLogin() ) return false;
		if (ADMIN_USER == $u->username() ||
		    $this->owner == $u->username() ) return true;
		foreach ($this->write_list as $writer)
			if ($u->username() == $writer) return true;
		return false;
	}

	# Again, same as camModifyEntry(), but for articles.

	function canModifyArticle($ent=false, $usr=false) {
		$u = NewUser($usr);
		if (! $u->checkLogin() ) return false;
		if (!$ent) $ent = NewBlogEntry();
		if (ADMIN_USER == $u->username() ||
		    $this->owner == $u->username() ||
		    $ent->uid == $u->username() ) return true;
		return false;
	}

	function canAddBlog($usr=false) {
		$u = NewUser($usr);
		if (! $u->checkLogin() ) return false;
		if (ADMIN_USER == $u->username() ) return true;
		return false;
	}

	function canModifyBlog($usr=false) {
		$u = NewUser($usr);
		if (! $u->checkLogin() ) return false;
		if (ADMIN_USER == $u->username() ||
		    $this->owner == $u->username() ) return true;
		return false;
	}

#---------------------------------------------------------------------------
# Interface with BlogEntry and Article classes.  

	# Set data for the preview page.

	function previewEntry(&$tpl) {
		$u = NewUser();
		$this->last_blogentry =& NewBlogEntry();
		# Set the username for the preview.
		if (!$this->last_blogentry->isEntry()) 
			$this->last_blogentry->uid = $u->username();
		if ( has_post() ) $this->last_blogentry->getPostData();
		else return false;
		$this->raiseEvent("OnEntryPreview");
		$tpl->set("PREVIEW_DATA", $this->last_blogentry->get() );
		$tpl->set("SUBJECT", $this->last_blogentry->subject);
		$tpl->set("DATA", $this->last_blogentry->data);
		$tpl->set("HAS_HTML", $this->last_blogentry->has_html);
		$tpl->set("COMMENTS", $this->last_blogentry->allow_comment);
		return true;
	}

	function previewArticle(&$tpl) {
		$u = NewUser();
		$this->last_article =& NewArticle();
		# Set the username for the preview.
		if (!$this->last_article->isEntry()) 
			$this->last_article->uid = $u->username();
		if ( has_post() ) $this->last_article->getPostData();
		else return false;
		$this->raiseEvent("OnArticlePreview");
		$tpl->set("PREVIEW_DATA", $this->last_article->get() );
		$tpl->set("SUBJECT", $this->last_article->subject);
		$tpl->set("DATA", $this->last_article->data);
		$tpl->set("HAS_HTML", $this->last_article->has_html);
		$tpl->set("COMMENTS", $this->last_article->allow_comment);
		return true;
	}

	function errorEntry($error, &$tpl) {
		$this->last_blogentry =& NewBlogEntry();
		if ( has_post() ) $this->last_blogentry->getPostData();
		else return false;
		$this->raiseEvent("OnEntryError");
		$tpl->set("SUBJECT", $this->last_blogentry->subject);
		$tpl->set("DATA", $this->last_blogentry->data);
		$tpl->set("HAS_HTML", $this->last_blogentry->has_html);
		$tpl->set("COMMENTS", $this->last_blogentry->allow_comment);
		switch ($error) {
			case UPDATE_SUCCESS:
				break;
			case UPDATE_NO_DATA_ERROR:
				$tpl->set("HAS_UPDATE_ERROR");
				$tpl->set("UPDATE_ERROR_MESSAGE", "No data entered.");
				break;
			case UPDATE_ENTRY_ERROR:
				$tpl->set("HAS_UPDATE_ERROR");
				$tpl->set("UPDATE_ERROR_MESSAGE", "Error updating blog entry.");
				break;
			case UPDATE_RSS1_ERROR:
				$tpl->set("HAS_UPDATE_ERROR");
				$tpl->set("UPDATE_ERROR_MESSAGE", "Error updating RSS1 feed.");
				break;
			case UPDATE_RSS2_ERROR:
				$tpl->set("HAS_UPDATE_ERROR");
				$tpl->set("UPDATE_ERROR_MESSAGE", "Error updating RSS2 feed.");
				break;
			case UPDATE_AUTH_ERROR:
				$tpl->set("HAS_UPDATE_ERROR");
				$tpl->set("UPDATE_ERROR_MESSAGE", "Security error: you can't perform this operation.");
				break;
		}
		return true;

	}

	function errorArticle($error, &$tpl) {
		$this->last_article =& NewArticle();;
		if ( has_post() ) $ent->getPostData();
		else return false;
		$this->raiseEvent("OnArticleError");
		$tpl->set("SUBJECT", $this->last_article->subject);
		$tpl->set("DATA", $this->last_article->data);
		$tpl->set("HAS_HTML", $this->last_article->has_html);
		$tpl->set("COMMENTS", $this->last_article->allow_comment);
		switch ($error) {
			case UPDATE_SUCCESS:
				break;
			case UPDATE_NO_DATA_ERROR:
				$tpl->set("HAS_UPDATE_ERROR");
				$tpl->set("UPDATE_ERROR_MESSAGE", "No data entered.");
				break;
			case UPDATE_ENTRY_ERROR:
				$tpl->set("HAS_UPDATE_ERROR");
				$tpl->set("UPDATE_ERROR_MESSAGE", "Error updating blog entry.");
				break;
			case UPDATE_AUTH_ERROR:
				$tpl->set("HAS_UPDATE_ERROR");
				$tpl->set("UPDATE_ERROR_MESSAGE", "Security error: you can't perform this operation.");
				break;
		}
		return true;

	}

	function newEntry() {
		$ent = NewBlogEntry();
		$this->last_blogentry =& $ent;
		$ent->getPostData();
		if ($ent->data == '') return UPDATE_NO_DATA_ERROR;
		if (! $this->canAddEntry() ) return UPDATE_AUTH_ERROR;
		$ret = $ent->insert();
		if ($ret !== false) $ret = UPDATE_SUCCESS;
		else $ret = UPDATE_ENTRY_ERROR;
		return $ret;
	}

	function updateEntry($path=false) {
		$ent = NewBlogEntry($path);
		$this->last_blogentry =& $ent;
		$ent->getPostData();
		if ($ent->data == '') return UPDATE_NO_DATA_ERROR;
		if (! $this->canModifyEntry($ent) ) return UPDATE_AUTH_ERROR;
		$ret = $ent->update();
		if ($ret !== false) $ret = UPDATE_SUCCESS;
		else $ret = UPDATE_ENTRY_ERROR;
		return $ret;
	}

	function deleteEntry($path=false) {
		$ent = NewBlogEntry($path);
		$this->last_blogentry =& $ent;
		if (! $this->canModifyEntry($ent) ) return UPDATE_AUTH_ERROR;
		$ret = $ent->delete();
		if ($ret !== false) $ret == UPDATE_SUCCESS;
		else $ret = UPDATE_ENTRY_ERROR;
		return $ret;

	}

	function newArticle($path=false) {
		$ent = NewArticle();
		$this->last_article =& $ent;
		$ent->getPostData();
		if ($ent->data == '') return UPDATE_NO_DATA_ERROR;
		if (! $this->canAddArticle() ) return UPDATE_AUTH_ERROR;
		# Set the branch and base path where the article will live.
		$base = false;
		$branch = false;
		if ($path) {
			if (strstr($path, "/")) {
				$base = dirname($path);
				$branch = basename($path);
			} else {
				$branch = $path;
			}
		}
		$ret = $ent->insert($branch, $base);
		if ($ret) {
			$ent->setSticky();
			$ret = UPDATE_SUCCESS;
		} else $ret = UPDATE_ENTRY_ERROR;
		return $ret;
	}

	function updateArticle($path=false) {
		$ent = NewArticle($path);
		$this->last_article =& $ent;
		$ent->getPostData();
		if ($ent->data == '') return UPDATE_NO_DATA_ERROR;
		if (! $this->canModifyArticle($ent) ) return UPDATE_AUTH_ERROR;
		$ret = $ent->update();
		if (! $ret) $ret = UPDATE_ENTRY_ERROR;
		else $ret = UPDATE_SUCCESS;
		return $ret;
	}

	function deleteArticle($path=false) {
		$ent = NewArticle($path);
		$this->last_article =& $ent;
		if (! $this->canModifyEntry($ent) ) return UPDATE_AUTH_ERROR;
		$ret = $ent->delete();
		if (! $ret) $ret = UPDATE_ENTRY_ERROR;
		else $ret = UPDATE_SUCCESS;
		return $ret;

	}

}
?>
