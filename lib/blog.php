<?php
/*
    LnBlog - A simple file-based weblog focused on design elegance.
    Copyright (C) 2005-2011 Peter A. Geer <pageer@skepticats.com>

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
require_once("lib/uri.php");

/* Class: Blog
 * The "master" class which represents a weblog.  Nearly all functions are 
 * performed through this object. This is the object that handles user security.
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

# Constant: ROOT_ID
# Defines the magic string that denotes a blog on the server's document root.
# Normally, LnBlog uses the document root-relative path to the blog as the blog
# identifier, but this is the empty string for the document root.  Therefore, 
# this constant sets a magic value to use.
@define("ROOT_ID", ".");

class Blog extends LnBlogObject {

	var $name;
	var $home_path;
	var $image;
	var $theme;
	var $max_entries = BLOG_MAX_ENTRIES;
	var $max_rss = BLOG_MAX_ENTRIES;
	var $allow_enclosure = 1;
	var $default_markup = MARKUP_BBCODE;
	var $owner = ADMIN_USER;
	var $write_list;
	var $tag_list;
	
	var $auto_pingback;
	var $gather_replies;
	
	var $entrylist;
	var $last_blogentry;
	var $last_article;
	var $custom_fields;

	function Blog($path="") {

		if ($path) {
			$this->getPathFromPassedValue($path);
		} else {
			$this->getPathFromEnvironment();
		}
		
		# Canonicalize the home path.
		if (is_dir($this->home_path)) {
			$this->home_path = realpath($this->home_path);
		}

		$this->setFieldDefaults();
		$this->raiseEvent("OnInit");

		$this->readBlogData();
		
		$this->raiseEvent("InitComplete");

	}
	
	function setFieldDefaults() {
		# System configuration information.
		$this->sw_version = '';
		$this->last_upgrade = '';
		$this->url_method = '';
		
		# Various default blog properties go here.
		$this->name = '';
		$this->description = '';
		$this->image = '';
		$this->max_entries = BLOG_MAX_ENTRIES;
		$this->max_rss = BLOG_MAX_ENTRIES;
		$this->allow_enclosure = 1;
		$this->default_markup = MARKUP_BBCODE;
		$this->theme = "default";
		$this->owner = ADMIN_USER;
		$this->write_list = array();
		$this->tag_list = array();
		$this->front_page_abstract = false;
		
		$this->auto_pingback = true;
		$this->gather_replies = true;
		
		$this->entrylist = array();
		$this->last_blogentry = false;
		$this->last_article = false;
		
		$this->custom_fields = array();
	}
	
	function getPathFromEnvironment() {
		if (isset($_GET['blog']) ) $path = trim(sanitize($_GET["blog"]));
		else $path = '';
		
		if ($path) {
			$this->getPathFromPassedValue($path);
		} elseif (defined("BLOG_ROOT")) { 
			$this->home_path = BLOG_ROOT;
			$this->setBlogID();
		} else {
			$this->home_path = getcwd();
			$this->setBlogID();
		}
	}
	
	function getPathFromPassedValue($path) {
		if ($path == ROOT_ID) {
			$this->home_path = calculate_document_root();
			$this->blogid = ROOT_ID;
		} elseif (is_dir($path)) {
			$this->home_path = realpath($path);
			$this->setBlogID();
		} else {
			$this->home_path = $this->get_blog_path($path);
			$this->blogid = $path;
		}
	}
	
	function setBlogID() {
		$root = calculate_server_root($this->home_path);
		$this->blogid = trim(substr($this->home_path, strlen($root)),  DIRECTORY_SEPARATOR);
		if (! $this->blogid) $this->blogid = ROOT_ID;
	}
	
	#######################################################
	# Section: RSS compatibility methods
	#######################################################
	
	function title($no_escape=false) {
		$ret = $this->name ? $this->name : '';
		return ( $no_escape ? $ret : htmlspecialchars($ret) );
	}
	
	function description() {
		return htmlspecialchars($this->description);
	}
	
	function get_blog_path($id) {
		$system = System::instance();
		$path = test_server_root($id);
		if ( ! is_dir($path) ) {
			$path = $system->sys_ini->value("bloglist", $id);
			if (! is_dir($path)) {
				echo spf_("Unable to locate blogID %s.  Make sure the ID is correct or add an entry to the [bloglist] section of system.ini.", $id);
				return false;
			}
		}
		return $path;
	}
	
	/*
	Method: isBlog
	Determines whether the object represents an existing blog.

	Returns:
	True if the blog metadata exists, false otherwise.
	*/
	function isBlog($blog=false) {
	
		return file_exists(Path::get($this->home_path, BLOG_CONFIG_PATH)) ||
		       file_exists(Path::get($this->home_path.PATH_DELIM.'blogdata.txt'));
	}

	function getParent() { return false; }

	/*
	Method: writers
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
	Method: readBlogData
	Read and write a simple text file with the blog metadata.
	Format is key = data, each record is a single line, unrecognized
	keys are ignored.  This is for internal use only.
	*/
	function readBlogData() {
		$path = $this->home_path.PATH_DELIM.BLOG_CONFIG_PATH;
		if (is_file($path)) {
			$ini = NewINIParser($path);
			$data = $ini->getSection("blog");
			foreach ($data as $key=>$val) {
				if (is_array($this->$key)) {
					$this->$key = explode(",", $val);
					if (! $this->$key) $this->$key = array();
				} else {
					$this->$key = $val;
				}
			}
			
			$this->sw_version = $ini->value('system','SoftwareVersion','0.7pre');
			$this->last_upgrade = $ini->value('system','LastUpgrade','');
			$this->url_method = $ini->value('system','URLMethod','wrapper');
			
		} elseif (is_file($this->home_path.PATH_DELIM.'blogdata.txt')) {
			$path = $this->home_path.PATH_DELIM.'blogdata.txt';
			$config_data = file($path);
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
		$ini = NewINIParser($path);
		$props = array("name", "description", "image", "max_entries", 
		               "max_rss", "allow_enclosure", "theme", "owner", 
		               "default_markup", "write_list", "tag_list",
		               "gather_replies", "auto_pingback", "front_page_abstract");
		foreach ($props as $key) {
			if (is_array($this->$key)) {
				$ini->setValue("blog", $key, implode(",", $this->$key));
			} else {
				$ini->setValue("blog", $key, $this->$key);
			}
		}
		
		$ini->setValue('system','SoftwareVersion',$this->sw_version);
		$ini->setValue('system','LastUpgrade',$this->last_upgrade);
		$ini->setValue('system','URLMethod',$this->url_method);
		
		$ret = $ini->writeFile();
		return $ret;
	}

	/*
	Method: getDateRange
	Get all blog entries posted between a given range of dates.
	Note that the range is inclusive.

	Parameters:
	end_date   - A string containing end date of the range in "yyyy-mm-dd" 
	             format.  This can also use "yyyy-mm" or "yyyy" format.
	start_date - The *optional* start date of the range.  If this is omitted, 
	             the the end date will be taken as the entirity of the range,
	             whether it is a day, month, or year.

	Returns:
	An array of BlogEntry objects.
	*/
	function getDateRange($end_date, $start_date = '') {
		$end = split('-', $end_date);
		
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
		$month_dir = mkpath(BLOG_ROOT,BLOG_ENTRY_PATH,
		                    $year,sprintf("%02d", $month));
		#$day_list = glob($month_dir.PATH_DELIM.$fmtday."*");
		$day_list = scan_directory($month_dir, true);
		rsort($day_list);

		$match_list = array();
		$ent = NewBlogEntry();
		foreach ($day_list as $dy) {
			if ( substr($dy, 0, 2) == $fmtday && $ent->isEntry($dy) ) {
				$match_list[] = NewBlogEntry($dy);
			}
		}
		foreach ($match_list as $ent) $this->entrylist[] = $ent;
		return $match_list;
	}

	/*
	Method: getDayCount
	Get the number of posts made on a given day.

	Parameters:
	year  - The 4 digit year of the post.
	month - The month of the post.
	day   - The day of the post.

	Retruns:
	An integer representing how many posts were made that day.
	*/
	function getDayCount($year, $month, $day) {
		$fmtday = sprintf("%02d", $day);
		$month_dir = mkpath(BLOG_ROOT,BLOG_ENTRY_PATH,
		                    $year,sprintf("%02d", $month));
		#$day_list = glob($month_dir.PATH_DELIM.$fmtday."*");
		$day_list = scan_directory($month_dir, true);
		$ent = NewBlogEntry();
		$ret = 0;
		foreach ($day_list as $dy) {
			if ( substr($dy, 0, 2) == $fmtday && 
			     $ent->isEntry(mkpath($month_dir,$dy)) ) {
				$ret++;
			}
		}
		return $ret;
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
			$curr_dir = mkpath($this->home_path,BLOG_ENTRY_PATH,$year,$month);
		} elseif (sanitize(GET("month"), "/\D/") && 
		          sanitize(GET("year"), "/\D/")) {
			$curr_dir = $this->home_path.PATH_DELIM.BLOG_ENTRY_PATH.
			            PATH_DELIM.sanitize(GET("year"), "/\D/").PATH_DELIM.
							sanitize(GET("month"), "/\D/");
		}
		$ent_list = array();
		$dir_list = scan_directory($curr_dir, true);
		
		if (! $dir_list) return array();
		
		rsort($dir_list);
		
		foreach ($dir_list as $file) {
			if ( $ent->isEntry($curr_dir.PATH_DELIM.$file) ) {
				$ent_list[] = $file;
			}
		}
		rsort($ent_list);
		foreach ($ent_list as $ent) 
			$this->entrylist[] = NewBlogEntry(mkpath($curr_dir, $ent));
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
	Method: getYear
	Like <getMonth>, except gets entries for an entire year.
	
	Parameters:
	year - *Optional* year to get.  If not given, the year will be auto-detected.
	
	Returns: 
	An array of BlogEntry objects posted in the given year,
	sorted in reverse chronological order by post date.
	*/
	function getYear($year=false) {
		$ent = NewBlogEntry();
		$curr_dir = getcwd();
		if ($year) {
			$curr_dir = mkpath($this->home_path,BLOG_ENTRY_PATH,$year);
		} elseif (sanitize(GET("year"), "/\D/")) {
			$curr_dir = mkpath($this->home_path,BLOG_ENTRY_PATH,
			                   sanitize(GET("year"), "/\D/"));
		}
		$ent_list = array();
		$dir_list = scan_directory($curr_dir, true);
		rsort($dir_list);
		
		if (! $dir_list) return array();
		
		foreach ($dir_list as $dir) {
			$path = mkpath($curr_dir, $dir);
			if (is_dir($path)) {
				$ent_list = array_merge($ent_list, $this->getMonth($year, $dir));
			}
		}
		$this->entry_list = $ent_list;
		return $this->entrylist;
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
		if ($year_list) rsort($year_list);
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
		$month_list = scan_directory(mkpath($this->home_path, BLOG_ENTRY_PATH, $year), true);
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
	backward.  This is essentially a wrapper around <getMonthList>.

	Parameters:
	nummonths - *Optional* number of months to return.  The default is 12.
	            If set to zero or less, then all months will be retreived.
	
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
				if ($nummonths > 0 && ++$count >= $nummonths) break 2;
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
		return $this->uri('blog');
	}

	/* Method: uri
	   Get the URI of the designated resource.
		
		Parameters:
		type            - The type of URI to get, e.g. permalink, edit link, etc.
		data parameters - All other parameters after the first are interpreted 
		                  as additional data for the URL query string.  The 
		                  exact meaning of each parameter depends on the URL type.

		Returns:
		A string with the permalink. 
	*/
	function uri($type) {
		$uri = create_uri_object($this);
		
		$args = array();
		for ($i=1; $i < func_num_args(); $i++) {
			$args[$i] = func_get_arg($i);
		}
		
		# This is hideously ugly, but convenient based on the need for 
		# backware compatibility.
		if (func_num_args() == 1) {
			return $uri->$type();
		} elseif (func_num_args() == 2) {
			return $uri->$type($args[1]);
		} elseif (func_num_args() == 3) {
			return $uri->$type($args[1], $args[2]);
		} elseif (func_num_args() == 4) {
			return $uri->$type($args[1], $args[2], $args[3]);
		} else {
			return false;
		}
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
	number - *Optional* number of entries to return.  If set to a negative 
	         number, then returns all entries will be returned.  The default 
				value is -1.
	offset - *Optional* number of entries from the beginning of the list to 
	         skip.  The default is 0, i.e. start at the beginning.

	Returns:
	An array of BlogEntry objects.
	*/
	function getEntries($number=-1,$offset=0) {
	
		$entry = NewBlogEntry();
		$this->entrylist = array();
		if ($number == 0) return;
	
		$ent_dir = $this->home_path.PATH_DELIM.BLOG_ENTRY_PATH;
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
							if ($num_found >= $number && $number >= 0) break 3;
						}
						$num_scanned++;
					}
				}  # End month loop
			}  # End year loop
		}  # End archive loop
		return $this->entrylist;
	}

	# Method: getEntriesByTag
	# Get a list of entries tagged with a given string.  
	#
	# Parameter: 
	# taglist   - An array of tags to search for.
	# limit     - Maximum number of entries to return.  The *default* is 
	#             zero, which means return *all* matching entries.
	# match_all - Optional boolean that determines whether the entry must 
	#             have every tag in taglist to match.  The *default* is false.
	#
	# Returns:
	# An array of entry objects, in reverse chronological order by post date.

	function getEntriesByTag($taglist, $limit=0, $match_all=false) {
	
		$entry = NewBlogEntry();
		$this->entrylist = array();
	
		$ent_dir = $this->home_path.PATH_DELIM.BLOG_ENTRY_PATH;
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
						$tmp = NewBlogEntry($ent_path);
						$ent_tags = $tmp->tags();
						if (empty($ent_tags)) continue;
						if (! $match_all) {
							foreach ($taglist as $tag) {
								if (in_arrayi($tag, $ent_tags)) {
									$this->entrylist[] = $tmp;
									$num_found++;
								}
							}
						} else {
							$hit_count = 0;
							foreach ($taglist as $tag) 
								if (in_arrayi($tag, $ent_tags)) $hit_count++;
							if ($hit_count == count($taglist)) {
									$this->entrylist[] = $tmp;
									$num_found++;
							}
						}
						if ($limit > 0 && $num_found == $limit) break 3;
					}
				}  # End month loop
			}  # End year loop
		}  # End archive loop
		return $this->entrylist;
	}

	/* 
	Method: getDrafts
	Gets all the current drafts for this blog.
	
	Returns:
	An array of BlogEntry objects.
	*/
	function getDrafts() {
		$art = NewBlogEntry();
		$art_path = mkpath($this->home_path, BLOG_DRAFT_PATH);
		
		$art_list = scan_directory($art_path);
		$ret = array();
		foreach ($art_list as $dir) {
			if ($art->isEntry(mkpath($art_path,$dir)) ) {
				$ret[] = NewEntry(mkpath($art_path,$dir));
			}
		}
		return $ret;
	}

	/*
	Method: getArticles
	Returns a list of all articles, in no particular order.

	Returns:
	An array of Article objects.
	*/
	function getArticles() {
		$art = NewArticle();
		$art_path = Path::get($this->home_path, BLOG_ARTICLE_PATH);
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

	# This method is for internal use only.
	function getItemReplies($array_creator, $reply_array_creator) {
		$ent_array = $this->$array_creator();
		$ret = array();
		foreach ($ent_array as $ent) {
			$ret = array_merge($ret, $ent->$reply_array_creator());
		}
		return $ret;
	}

	# This method is also for internal use only.  It and getItemReplies are
	# generic helper functions for the publicly visible functions 
	# that follow.
	function getItemRepliesAll($array_creator) {
		$ret = $this->getItemReplies($array_creator, "getCommentArray");
		$ret = array_merge($ret, $this->getItemReplies($array_creator, "getTrackbackArray"));
		$ret = array_merge($ret, $this->getItemReplies($array_creator, "getPingbackArray"));
		return $ret;
	}

	# Method: getEntryReplies
	# Gets all the replies for all entries belonging to this blog, including
	# all comments, trackbacks, and pingbacks.
	#
	# Returns:
	# An array of objects, including BlogComment, Trackback, and Pingback
	# objects.  The sorting of this list is dependent on the data storage
	# implementation for the blog.
	function getEntryReplies() {
		return $this->getItemRepliesAll("getEntries");
	}

	# Method: getEntryComments
	# Like <getEntryReplies>, but only returns BlogComments.
	function getEntryComments() {
		return $this->getItemReplies("getEntries", "getCommentArray");
	}

	# Method: getEntryTrackbacks
	# Like <getEntryReplies>, but only returns Trackbacks.
	function getEntryTrackbacks() {
		return $this->getItemReplies("getEntries", "getTrackbackArray");
	}

	# Method: getEntryPingbacks
	# Like <getEntryReplies>, but only returns Pingbacks.
	function getEntryPingbacks() {
		return $this->getItemReplies("getEntries", "getPingbackArray");
	}

	# Method: getArticleReplies
	# Like <getEntryReplies>, but returns the replies for all Articles, instead
	# of for all BlogEntries.
	function getArticleReplies() {
		return $this->getItemRepliesAll("getArticles");
	}

	# Method: getArticleComments
	# Like <getArticleReplies>, but only returns BlogComments.
	function getArticleComments() {
		return $this->getItemReplies("getArticles", "getCommentArray");
	}

	# Method: getArticleTrackbacks
	# Like <getArticleReplies>, but only returns Trackbacks.
	function getArticleTrackbacks() {
		return $this->getItemReplies("getArticles", "getTrackbackArray");
	}

	# Method: getArticleArticlePingbacks
	# Like <getArticleReplies>, but only returns Pingbacks.
	function getArticlePingbacks() {
		return $this->getItemReplies("getArticles", "getPingbackArray");
	}

	# Method: getReplies
	# Like <getEntryReplies> and <getArticleReplies>, but combines both, 
	# returning an array of *all* replies for this blog.
	function getReplies() {
		$ret = $this->getEntryReplies();
		$ret = array_merge($ret, $this->getArticleReplies());
		return $ret;
	}

	# Method: getComments
	# Like <getReplies>, but only for comments.
	function getComments() {
		$ret = $this->getEntryComments();
		$ret = array_merge($ret, $this->getArticleComments());
		return $ret;
	}
	
	# Method: getTrackbacks
	# Like <getReplies>, but only for comments.
	function getTrackbacks() {
		$ret = $this->getEntryTrackbacks();
		$ret = array_merge($ret, $this->getArticleTrackbacks());
		return $ret;
	}
	
	# Method: getPingbacks
	# Like <getReplies>, but only for comments.
	function getPingbacks() {
		$ret = $this->getEntryPingbacks();
		$ret = array_merge($ret, $this->getArticlePingbacks());
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
		$tpl->set("BLOG_NAME", htmlspecialchars($this->name));
		$tpl->set("BLOG_DESCRIPTION", htmlspecialchars($this->description));
		$tpl->set("BLOG_IMAGE", $this->image);
		$tpl->set("BLOG_MAX_ENTRIES", $this->max_entries);
		$tpl->set("BLOG_BASE_DIR", $this->home_path);
		$tpl->set("BLOG_URL", $this->getURL() );
		$tpl->set("BLOG_URL_ROOTREL", $this->getURL(false));
		$tpl->set("BLOG_ALLOW_ENC", $this->allow_enclosure);
		$tpl->set("BLOG_GATHER_REPLIES", $this->gather_replies);
		$tpl->set("BLOG_AUTO_PINGBACK", $this->auto_pingback);
		$tpl->set("BLOG_FRONT_PAGE_ABSTRACT", $this->front_page_abstract);
	}

	/*
	Method: getWeblog
	Gets the markup to display for the front page of a weblog.

	Returns:
	A string holding the HTML to display.
	*/
	function getWeblog () {
		global $SYSTEM;
		$ret = "";
		$u = NewUser();
		if (! $this->entrylist) $this->getRecent();
		foreach ($this->entrylist as $ent) {
			$show_ctl = $SYSTEM->canModify($ent, $u) && $u->checkLogin();
			$ret .= $ent->get($show_ctl);
		}
		if (! $ret) $ret = "<p>"._("There are no entries for this weblog.")."</p>";
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
		$files = array();
		# Upgrade the base blog directory first.  All other directories will
		# get a copy of the config.php created here.
		$ret = create_directory_wrappers($this->home_path, BLOG_BASE, $inst_path);
		if (! is_array($ret)) return false;
		else $fiels = $ret;
		
		# Upgrade the articles.
		$path = $this->home_path.PATH_DELIM.BLOG_ARTICLE_PATH;
		$ret = create_directory_wrappers($path, BLOG_ARTICLES);
		$files = array_merge($files, $ret);
		
		$dir_list = scan_directory($path, true);
		foreach ($dir_list as $art) {
			$ent_path = mkpath($path, $art);
			$cmt_path = mkpath($ent_path, ENTRY_COMMENT_DIR);
			$tb_path = mkpath($ent_path, ENTRY_TRACKBACK_DIR);
			$pb_path = mkpath($ent_path, ENTRY_PINGBACK_DIR);
			$ret = create_directory_wrappers($ent_path, ARTICLE_BASE);
			$files = array_merge($files, $ret);
			$ret = create_directory_wrappers($cmt_path, ENTRY_COMMENTS, 'article');
			$files = array_merge($files, $ret);
			$ret = create_directory_wrappers($tb_path, ENTRY_TRACKBACKS, 'article');
			$files = array_merge($files, $ret);
			$ret = create_directory_wrappers($pb_path, ENTRY_PINGBACKS, 'article');
			$files = array_merge($files, $ret);
		}
		
		$path = mkpath($this->home_path, BLOG_ENTRY_PATH);
		$ret = create_directory_wrappers($path, BLOG_ENTRIES);
		$files = array_merge($files, $ret);
		$dir_list = scan_directory($path, true);
		foreach ($dir_list as $yr) {
			$year_path = $path.PATH_DELIM.$yr;
			$ret = create_directory_wrappers($year_path, YEAR_ENTRIES);
			$files = array_merge($files, $ret);
			$year_list = scan_directory($year_path, true);
			foreach ($year_list as $mn) {
				$month_path = $year_path.PATH_DELIM.$mn;
				$ret = create_directory_wrappers($month_path, MONTH_ENTRIES);
				$files = array_merge($files, $ret);
				$month_list = scan_directory($month_path, true);
				foreach ($month_list as $ent) {
					$ent_path = mkpath($month_path, $ent);
					$cmt_path = mkpath($ent_path, ENTRY_COMMENT_DIR);
					$tb_path = mkpath($ent_path, ENTRY_TRACKBACK_DIR);
					$pb_path = mkpath($ent_path, ENTRY_PINGBACK_DIR);
					$ret = create_directory_wrappers($ent_path, ENTRY_BASE);
					$files = array_merge($files, $ret);
					$ret = create_directory_wrappers($cmt_path, ENTRY_COMMENTS);
					$files = array_merge($files, $ret);
					$ret = create_directory_wrappers($tb_path, ENTRY_TRACKBACKS);
					$files = array_merge($files, $ret);
					$ret = create_directory_wrappers($pb_path, ENTRY_PINGBACKS);
					$files = array_merge($files, $ret);
				}
			}
		}
		$path = $this->home_path.PATH_DELIM.BLOG_ARTICLE_PATH;
		$ret = create_directory_wrappers($path, BLOG_ARTICLES);
		$path = $this->home_path.PATH_DELIM.BLOG_DRAFT_PATH;
		$ret = create_directory_wrappers($path, ENTRY_DRAFTS);
		$files = array_merge($files, $ret);
		$dir_list = scan_directory($path, true);
		foreach ($dir_list as $ar) {
			$ar_path = $path.PATH_DELIM.$ar;
			$cmt_path = $ar_path.PATH_DELIM.ENTRY_COMMENT_DIR;
			$ret = create_directory_wrappers($ar_path, ARTICLE_BASE);
			$files = array_merge($files, $ret);
			$ret = create_directory_wrappers($cmt_path, ENTRY_COMMENTS);
			$files = array_merge($files, $ret);
		}
		$this->sw_version = PACKAGE_VERSION;
		$this->last_upgrade = date('r');
		$ret = $this->writeBlogData();
		if (! $ret) $files[] = $this->home_path.PATH_DELIM.BLOG_CONFIG_PATH;
		$this->raiseEvent("UpgradeComplete");
		return $files;
	}

	# Method: fixDirectoryPermissions
	# A quick utility function to fix the borked permissions from not setting
	# the correct umask when creating directories.  This resulted in 
	# directories that I couldn't alter via FTP.
	#
	# Parameters:
	# start_dir - The directory to fix.  *Defaults* to the blog root.
	#
	# Returns:
	# True on success, false otherwise.

	function fixDirectoryPermissions($start_dir=false) {
		$fs = NewFS();
		if (! $start_dir) $start_dir = $this->home_path;
		$dir_list = scan_directory($start_dir, true);
		$ret = true;
		foreach ($dir_list as $dir) {
			#$ext = substr($dir, strlen($dir) - 4);
			$path = $start_dir.PATH_DELIM.$dir;
			$ret &= $fs->chmod($path, $fs->directoryMode() );
			$ret &= $this->fixDirectoryPermissions($path);
		}
		return $ret;
	}

	# Method: insert
	# Creates a new weblog.
	#
	# Parameters:
	# path - The path to the blog root.  Defaults to the current directory.
	# 
	# Returns:
	# True on success, false otherwise.

	function insert ($path=false) {
		
		$this->raiseEvent("OnInsert");
		$fs = NewFS();

		$this->name = htmlentities($this->name);
		$this->description = htmlentities($this->description);

		$p = new Path($path ? $path : $this->home_path);
		$this->home_path = $p->getCanonical();

		$ret = $this->createBlogDirectories(&$fs, INSTALL_ROOT);
		
		$this->setBlogID();
		
		if ($ret) {
			$this->sw_version = PACKAGE_VERSION;
			$this->last_upgrade = date('r');
				
			$ret = $this->writeBlogData();
			$this->raiseEvent("InsertComplete");
		}
		
		return $ret;
	}
	
	function createBlogDirectories(&$fs, $inst_path) {

		$ret = $this->createNonExistentDirectory($fs, $this->home_path);
		if ($ret) {
			$result = create_directory_wrappers($this->home_path, BLOG_BASE, $inst_path);
			# Returns an array of errors, so convert empty array to true.
			$ret &= (empty($result) ? true : false);
		}

		$p = new Path($this->home_path, BLOG_ENTRY_PATH);
		$ret &= $this->createNonExistentDirectory($fs, $p->get());

		if ($ret) {
			$result = create_directory_wrappers($p->get(), BLOG_ENTRIES);
			$ret &= (empty($result) ? true : false);
		}
		$ret &= $this->createNonExistentDirectory($fs, 
			Path::get($this->home_path, BLOG_FEED_PATH));

		return $ret;
	}
	
	function createNonExistentDirectory(&$fs, $dir) {
		if (! is_dir($dir) ) {
			$ret = $fs->mkdir_rec($dir);
			return $ret;
		} else {
			return true;
		}
	}
	
	# Method: update
	# Modify an existing weblog.
	# 
	# Returns:
	# True on success, false otherwise.
	
	function update () {
		$this->raiseEvent("OnUpdate");
		$this->name = htmlentities($this->name);
		$this->description = htmlentities($this->description);
		if (KEEP_EDIT_HISTORY) {
			$ret = $this->delete() && $this->writeBlogData();
		} else {
			$ret = $this->writeBlogData();
		}
		$this->raiseEvent("UpdateComplete");
		return $ret;
	}
	
	# Method: delete
	# Removes an existing weblog.  This only deletes the blog data file, making this a 
	# non-blog directory.  It does not totally destroy the data.
	# 
	# Returns:
	# True on success, false on failure.
	
	function delete () {
		$this->raiseEvent("OnDelete");
		$fs = NewFS();
		if (KEEP_EDIT_HISTORY) {
			$p = new Path($this->home_path, BLOG_DELETED_PATH);
			if (! is_dir($p->get()) )
				$fs->mkdir_rec($p->get());
			$p->Path($this->home_path, BLOG_DELETED_PATH,
			         BLOG_CONFIG_PATH."-".date(ENTRY_PATH_FORMAT_LONG));
			$ret = $fs->rename(Path::get($this->home_path, BLOG_CONFIG_PATH), $p->get());
		} else {
			$ret = $fs->rmdir_rec($this->home_path);
		}
		$this->raiseEvent("DeleteComplete");
		return $ret;
	}
	
	# Method: updateTagList
	# Adds any new tags to the list of tags used in the current blog.
	# 
	# Parameters:
	# tags - An array of strings holding the tags to be added.
	#        Duplicates are removed.
	#
	# Returns:
	# True on success, false on failure.
	
	function updateTagList($tags) {
		$modified = false;
		if (! $tags) return false;
		foreach ($tags as $tag) {
			if (! in_arrayi($tag, $this->tag_list)) {
				$this->tag_list[] = $tag;
				$modified = true;
			}
		}
		$new_list = array();
		foreach ($this->tag_list as $tag) {
			if (trim($tag) != "") {
				$new_list[] = $tag;
				$modified = true;
			}
		}
		if ($modified) {
			$this->tag_list = $new_list;
			return $this->writeBlogData();
		} else return false;
	}

}
?>
