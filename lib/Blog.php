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

use LnBlog\Tasks\AutoPublishTask;
use LnBlog\Tasks\TaskManager;
use LnBlog\Tasks\TaskRepository;
use Psr\Log\LoggerInterface;

class Blog extends LnBlogObject implements AttachmentContainer
{

    public $blogid = '';
    public $name = '';
    public $description = '';
    public $home_path = '';
    public $image = '';
    public $theme = 'default';
    public $max_entries = 10;
    public $max_rss = 10;
    public $allow_enclosure = 1;
    public $default_markup = MARKUP_BBCODE;
    public $owner = ADMIN_USER;
    public $write_list = array();
    public $tag_list = array();

    public $auto_pingback = true;
    public $gather_replies = true;
    public $front_page_abstract = false;
    public $front_page_entry = '';

    # System configuration information.
    public $sw_version = '';
    public $last_upgrade = '';
    public $url_method = '';

    public $entrylist = array();
    public $has_more_entries = false;
    public $last_blogentry = null;
    public $last_article = null;
    public $custom_fields = array();

    # HACK: because the menubar is messed up and I'm too lazy to fix it properly.
    public $skip_root = false;

    private $fs;
    private $filemanager;
    private $task_manager;
    private $url_resolver;
    private $logger;

    private $entry_list = [];

    public function __construct(
        $path = "",
        $fs = null,
        $file_manager = null,
        TaskManager $task_manager = null,
        UrlResolver $url_resolver = null,
        LoggerInterface $logger = null
    ) {
        $this->fs = $fs ?: NewFS();
        $this->filemanager = $file_manager ?: new FileManager($this, $this->fs);
        $this->url_resolver = $url_resolver ?: new UrlResolver(SystemConfig::instance(), $this->fs);
        $this->logger = $logger ?: NewLogger();
        $this->task_manager = $task_manager;
        if (!$task_manager) {
            $task_repository = new TaskRepository($this->fs);
            $this->task_manager = new TaskManager($task_repository, $this->logger);
        }

        if ($path) {
            $this->getPathFromPassedValue($path);
        } else {
            $this->getPathFromEnvironment();
        }

        # Canonicalize the home path.
        if ($this->fs->is_dir($this->home_path)) {
            $this->home_path = $this->fs->realpath($this->home_path);
        }

        $this->raiseEvent("OnInit");

        $this->readBlogData();

        $this->raiseEvent("InitComplete");
    }

    private function getPathFromEnvironment() {
        $path = '';
        if (isset($_GET['blog']) ) {
            $path = trim(preg_replace("/[^A-Za-z0-9\-_\/\\\]/", '', $_GET["blog"]));
        }

        if ($path) {
            $this->getPathFromPassedValue($path);
        } elseif (defined("BLOG_ROOT")) {
            $this->home_path = $this->fs->realpath(BLOG_ROOT);
            $this->setBlogID();
        } else {
            $this->home_path = $this->fs->getcwd();
            $this->setBlogID();
        }
    }

    private function getPathFromPassedValue($path) {
        if ($this->fs->is_dir($path)) {
            $path = $this->fs->realpath($path);
            $this->home_path = $this->fs->realpath($path);
            $this->setBlogID();
        } else {
            $this->home_path = $this->getBlogPath($path);
            $this->blogid = $path;
        }
    }

    private function setBlogID() {
        if ($this->blogid) {
            return;
        }
        $blogs = SystemConfig::instance()->blogRegistry();
        $homepath = $this->fs->realpath($this->home_path);
        foreach ($blogs as $blogid => $urlpath) {
            $blogpath = $this->fs->realpath($urlpath->path());
            if ($homepath == $blogpath) {
                $this->blogid = $blogid;
                return;
            }
        }
    }

    #######################################################
    # Section: RSS compatibility methods
    #######################################################

    public function title($no_escape=false) {
        $ret = $this->name ? $this->name : '';
        return ( $no_escape ? $ret : htmlspecialchars($ret) );
    }

    public function description() {
        return htmlspecialchars($this->description);
    }

    private function getBlogPath($id) {
        $blogs = SystemConfig::instance()->blogRegistry();
        if (isset($blogs[$id])) {
            return $blogs[$id]->path();
        }
        return false;
    }

    /*
    Method: isBlog
    Determines whether the object represents an existing blog.

    Returns:
    True if the blog metadata exists, false otherwise.
    */
    public function isBlog($blog=false) {

        return $this->fs->file_exists(Path::mk($this->home_path, BLOG_CONFIG_PATH)) ||
               $this->fs->file_exists(Path::mk($this->home_path, 'blogdata.txt'));
    }

    public function getParent() {
 return false; 
    }

    /*
    Method: writers
    Set and return the list of users who can add posts to the blog.

    Parameters:
    list - an arrays or comma-delimited stringof user names.

    Returns:
    An array of user names.
    */

    public function writers($list=false) {
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
    private function readBlogData() {
        $path = Path::mk($this->home_path, BLOG_CONFIG_PATH);
        if (!$this->fs->is_file($path)) {
            return;
        }
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

        $this->sw_version = $ini->value('system', 'SoftwareVersion', '0.7pre');
        $this->last_upgrade = $ini->value('system', 'LastUpgrade', '');
        $this->url_method = $ini->value('system', 'URLMethod', 'wrapper');
    }

    /*
    Method: writeBlogData
    Save the blog data to disk.  This is for internal use only.

    Returns:
    False on failure, something else on success.
    */
    private function writeBlogData() {
        $path = $this->home_path.PATH_DELIM.BLOG_CONFIG_PATH;
        $ini = NewINIParser($path);
        $props = array("name", "description", "image", "max_entries",
                       "max_rss", "allow_enclosure", "theme", "owner",
                       "default_markup", "write_list", "tag_list",
                       "gather_replies", "auto_pingback", "front_page_abstract",
                       "front_page_entry");
        foreach ($props as $key) {
            if (is_array($this->$key)) {
                $ini->setValue("blog", $key, implode(",", $this->$key));
            } else {
                $ini->setValue("blog", $key, $this->$key);
            }
        }

        $ini->setValue('system', 'SoftwareVersion', $this->sw_version);
        $ini->setValue('system', 'LastUpgrade', $this->last_upgrade);
        $ini->setValue('system', 'URLMethod', $this->url_method);

        $ret = $ini->writeFile();
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
    public function getDay($year, $month, $day) {
        $fmtday = sprintf("%02d", $day);
        $month_dir = Path::mk(
            $this->home_path, BLOG_ENTRY_PATH,
            $year, sprintf("%02d", $month)
        );
        $day_list = $this->fs->scan_directory($month_dir, true);
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
    public function getDayCount($year, $month, $day) {
        $fmtday = sprintf("%02d", $day);
        $month_dir = Path::mk(
            $this->home_path, BLOG_ENTRY_PATH,
            $year, sprintf("%02d", $month)
        );
        #$day_list = glob($month_dir.PATH_DELIM.$fmtday."*");
        $day_list = $this->fs->scan_directory($month_dir, true);
        $ent = NewBlogEntry();
        $ret = 0;
        foreach ($day_list as $dy) {
            if ( substr($dy, 0, 2) == $fmtday &&
                 $ent->isEntry(Path::mk($month_dir, $dy)) ) {
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
    year  - The year you want.
    month - The month you want.

    Returns:
    An array of BlogEntry objects posted in the given month,
    sorted in reverse chronological order by post date.
    */
    public function getMonth($year, $month) {
        $ent = NewBlogEntry();
        $curr_dir = Path::mk($this->home_path, BLOG_ENTRY_PATH, $year, $month);

        $ent_list = array();
        $dir_list = $this->fs->scan_directory($curr_dir, true);

        if (! $dir_list) return array();

        rsort($dir_list);

        foreach ($dir_list as $file) {
            if ( $ent->isEntry(Path::mk($curr_dir, $file)) ) {
                $ent_list[] = $file;
            }
        }
        rsort($ent_list);
        foreach ($ent_list as $ent)
            $this->entrylist[] = NewBlogEntry(Path::mk($curr_dir, $ent));
        return $this->entrylist;
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
    public function getYear($year=false) {
        $ent = NewBlogEntry();
        $curr_dir = $this->fs->getcwd();
        if ($year) {
            $curr_dir = Path::mk($this->home_path, BLOG_ENTRY_PATH, $year);
        } elseif (sanitize(GET("year"), "/\D/")) {
            $curr_dir = Path::mk(
                $this->home_path, BLOG_ENTRY_PATH,
                sanitize(GET("year"), "/\D/")
            );
        }
        $ent_list = array();
        $dir_list = $this->fs->scan_directory($curr_dir, true);
        rsort($dir_list);

        if (! $dir_list) return array();

        foreach ($dir_list as $dir) {
            $path = Path::mk($curr_dir, $dir);
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
    public function getYearList() {
        $year_list = $this->fs->scan_directory(Path::mk($this->home_path, BLOG_ENTRY_PATH), true);
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
    public function getMonthList($year=false) {
        if (! $year) {
            if (sanitize(GET("year"), "/\D/")) {
                $year = sanitize(GET("year"), "/\D/");
            } else $year = basename(getcwd());
        }
        $month_list = $this->fs->scan_directory(Path::mk($this->home_path, BLOG_ENTRY_PATH, $year), true);
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
    public function getRecentMonthList($nummonths=12) {
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
    public function getURL($full_uri=true) {
        return $this->uri('blog');
    }

    /* Method: uri
       Get the URI of the designated resource.

        Parameters:
        type   - The type of URI to get, e.g. permalink, edit link, etc.
        params - All other parameters after the first are interpreted
                 as additional data for the URL query string.  The
                 exact meaning of each parameter depends on the URL type.

        Returns:
        A string with the permalink.
    */
    public function uri($type, $params = []) {
        return $this->url_resolver->generateRoute($type, $this, $params);
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
    public function getRecent($num_entries=false) {

        $show_max = $num_entries ? $num_entries : $this->max_entries;
        if (! $num_entries) $show_max = $this->max_entries;
        else $show_max = $num_entries;

        $this->getEntries($show_max);
        return $this->entrylist;
    }

    /*
    Method: getNextMax
    Convenience function to get "previous entries".     Returns a list of
    entries starting after the the end of the blog's <max_entires> property.

    Parameters:
    num_entries - The *optional* number or entries to return.  The default is
                  to use the blog's <max_entries> property.

    Returns:
    An array of BlogEntry objects.
    */
    public function getNextMax($num_entries=false) {

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
    public function getEntries($number = -1, $offset = 0) {
        $this->getEntriesRecursive($number, $offset);
        #$this->getEntriesByGlob($number, $offset);
        return $this->entrylist;
    }

    private function getEntriesRecursive($number=-1,$offset=0) {

        $entry = NewBlogEntry();
        $this->entrylist = array();
        if ($number == 0) {
            return [];
        }

        $ent_dir = Path::mk($this->home_path, BLOG_ENTRY_PATH);
        $num_scanned = 0;
        $num_found = 0;

        $this->has_more_entries = false;

        $year_list = $this->fs->scan_directory($ent_dir, true);
        rsort($year_list);

        foreach ($year_list as $year) {
            $month_list = $this->fs->scan_directory(Path::mk($ent_dir, $year), true);
            rsort($month_list);
            foreach ($month_list as $month) {
                $path = Path::mk($ent_dir, $year, $month);
                $ents = $this->fs->scan_directory($path, true);
                rsort($ents);
                foreach ($ents as $e) {
                    $ent_path = Path::mk($path, $e);
                    if ( $entry->isEntry($ent_path) ) {
                        if ($num_scanned >= $offset) {
                            $num_found++;
                            # If we've hit the max, then break out of all 3 loops.
                            if ($num_found > $number && $number >= 0) {
                                $this->has_more_entries = true;
                                break 3;
                            }
                            $this->entrylist[] = NewBlogEntry($ent_path);
                        }
                        $num_scanned++;
                    }
                }  # End month loop
            }  # End year loop
        }  # End archive loop
        return $this->entrylist;
    }

    private function getEntriesByGlob($number = -1, $offset = 0) {
        if ($number == 0) {
            return [];
        }

        $this->entrylist = [];
        $num_scanned = 0;
        $num_found = 0;

        # This glob should find all of the entry.xml files, which are required
        # for valid entries, in a single step.
        $entry_file_glob = Path::mk($this->home_path, BLOG_ENTRY_PATH, '*', '*', '*', ENTRY_DEFAULT_FILE);
        $files = $this->fs->glob($entry_file_glob);
        /*
        $entry_list = array_reverse($files);

        if ($number > 0) {
            $entry_list = array_slice($entry_list, $offset, $number);
        }
         */
        if ($number > 0) {
            $offset = -1 * ($offset + $number);
            $files2 = array_slice($files, $offset, $number);
        }

        $entry_list = array_reverse(isset($files2) ? $files2 : $files);

        $instantiator = function ($entry_path) {
            return NewBlogEntry(dirname($entry_path));
        };
        
        $this->entrylist = array_map($instantiator, $entry_list);
        $this->has_more_entries = count($entry_list) - $offset - $number > 0;
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

    public function getEntriesByTag($taglist, $limit=0, $match_all=false) {

        $entry = NewBlogEntry();
        $this->entrylist = array();

        $ent_dir = Path::mk($this->home_path, BLOG_ENTRY_PATH);
        $num_found = 0;

        $year_list = $this->fs->scan_directory($ent_dir, true);
        rsort($year_list);

        foreach ($year_list as $year) {
            $month_list = $this->fs->scan_directory(Path::mk($ent_dir, $year), true);
            rsort($month_list);
            foreach ($month_list as $month) {
                $path = Path::mk($ent_dir, $year, $month);
                $ents = $this->fs->scan_directory($path, true);
                rsort($ents);
                foreach ($ents as $e) {
                    $ent_path = Path::mk($path, $e);
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
    public function getDrafts() {
        $art = NewBlogEntry();
        $art_path = Path::mk($this->home_path, BLOG_DRAFT_PATH);

        $art_list = $this->fs->scan_directory($art_path);
        $ret = array();
        foreach ($art_list as $dir) {
            if ($art->isEntry(Path::mk($art_path, $dir)) ) {
                $ret[] = NewEntry(Path::mk($art_path, $dir));
            }
        }
        usort($ret, array($this, '_sort_by_date'));
        return $ret;
    }

    protected function _sort_by_date($e1, $e2) {
        if ($e1->post_ts == $e2->post_ts) {
            return 0;
        } else {
            return $e1->post_ts < $e2->post_ts ? -1 : 1;
        }
    }

    /*
    Method: getArticles
    Returns a list of all articles, in no particular order.

    Parameters:
    number      - *Optional* number of articles to return.  Default is all.

    Returns:
    An array of Article objects.
    */
    public function getArticles($number = null) {
        $art = NewEntry();
        $art_path = Path::get($this->home_path, BLOG_ARTICLE_PATH);
        $art_list = $this->fs->scan_directory($art_path);
        $ret = array();
        $count = 0;
        foreach ($art_list as $dir) {
            $path = Path::mk($art_path, $dir);
            if ($art->checkArticlePath($path)) {
                $ret[] = NewEntry($path);
                $count++;
            }
            if ($number && $count >= $number) {
                break;
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
    public function getArticleList($number = null, $sticky_only = true) {
        $ret = array();
        $art_list = $this->getArticles($number);
        foreach ($art_list as $art) {
            if ($art->isSticky() ) {
                $ret[] = $art->readSticky();
            } elseif (!$sticky_only) {
                $ret[] = array("title"=>$art->subject, "link"=>$art->permalink());
            }
        }
        return $ret;
    }

    # This method is for internal use only.
    private function getItemReplies($array_creator, $reply_array_creator) {
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
    private function getItemRepliesAll($array_creator) {
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
    public function getEntryReplies() {
        return $this->getItemRepliesAll("getEntries");
    }

    # Method: getEntryComments
    # Like <getEntryReplies>, but only returns BlogComments.
    public function getEntryComments() {
        return $this->getItemReplies("getEntries", "getCommentArray");
    }

    # Method: getEntryTrackbacks
    # Like <getEntryReplies>, but only returns Trackbacks.
    public function getEntryTrackbacks() {
        return $this->getItemReplies("getEntries", "getTrackbackArray");
    }

    # Method: getEntryPingbacks
    # Like <getEntryReplies>, but only returns Pingbacks.
    public function getEntryPingbacks() {
        return $this->getItemReplies("getEntries", "getPingbackArray");
    }

    # Method: getArticleReplies
    # Like <getEntryReplies>, but returns the replies for all Articles, instead
    # of for all BlogEntries.
    public function getArticleReplies() {
        return $this->getItemRepliesAll("getArticles");
    }

    # Method: getArticleComments
    # Like <getArticleReplies>, but only returns BlogComments.
    public function getArticleComments() {
        return $this->getItemReplies("getArticles", "getCommentArray");
    }

    # Method: getArticleTrackbacks
    # Like <getArticleReplies>, but only returns Trackbacks.
    public function getArticleTrackbacks() {
        return $this->getItemReplies("getArticles", "getTrackbackArray");
    }

    # Method: getArticleArticlePingbacks
    # Like <getArticleReplies>, but only returns Pingbacks.
    public function getArticlePingbacks() {
        return $this->getItemReplies("getArticles", "getPingbackArray");
    }

    # Method: getReplies
    # Like <getEntryReplies> and <getArticleReplies>, but combines both,
    # returning an array of *all* replies for this blog.
    public function getReplies() {
        $ret = $this->getEntryReplies();
        $ret = array_merge($ret, $this->getArticleReplies());
        return $ret;
    }

    # Method: getComments
    # Like <getReplies>, but only for comments.
    public function getComments() {
        $ret = $this->getEntryComments();
        $ret = array_merge($ret, $this->getArticleComments());
        return $ret;
    }

    # Method: getTrackbacks
    # Like <getReplies>, but only for trackbacks.
    public function getTrackbacks() {
        $ret = $this->getEntryTrackbacks();
        $ret = array_merge($ret, $this->getArticleTrackbacks());
        return $ret;
    }

    # Method: getPingbacks
    # Like <getReplies>, but only for pingbacks.
    public function getPingbacks() {
        $ret = $this->getEntryPingbacks();
        $ret = array_merge($ret, $this->getArticlePingbacks());
        return $ret;
    }

    # Method: autoPingbackEnabled
    # Determine if pingbacks are on by default.
    public function autoPingbackEnabled() {
        return $this->auto_pingback ? $this->auto_pingback != 'none' : false;
    }

    # Method: getAttachments
    # Get a list of files attached to this blog.
    #
    # Returns: Array of AttachedFile objects
    public function getAttachments() {
        return $this->filemanager->getAll();
    }

    # Method: addAttachment
    # Add an attached file to the blog.
    #
    # Parameters:
    # path - The local file path to the file to attach.
    # name - Optional name of the file.  Default is the existing name.
    public function addAttachment($path, $name = '') {
        $this->filemanager->attach($path, $name);
    }

    # Method: removeAttachment
    # Remove a file that is attached to the blog.
    #
    # Parameter:
    # name - The name of the file to remove.
    public function removeAttachment($name) {
        $this->filemanager->remove($name);
    }

    # Method: getManagedFiles
    # Get a list of system files that are managed by the blog and
    # are therefore excluded from uploads.
    #
    # Returns: An array of file names.
    public function getManagedFiles() {
        return [
            'index.php',
            'pathconfig.php',
            'blogdata.ini',
            'ip_ban.txt',
            're_ban.txt',
            'plugins.xml',
        ];
    }

    /*
    Method: exportVars
    Export blog variables to a PHPTemplate class.
    This is for internal use only.

    Parameters:
    tpl - The PHPTemplate to populate.
    */
    public function exportVars(&$tpl) {
        $tpl->set("BLOG_NAME", htmlspecialchars($this->name));
        $tpl->set("BLOG_DESCRIPTION", htmlspecialchars($this->description));
        $tpl->set("BLOG_IMAGE", $this->image);
        $tpl->set("BLOG_MAX_ENTRIES", $this->max_entries);
        $tpl->set("BLOG_BASE_DIR", $this->home_path);
        $tpl->set("BLOG_URL", $this->getURL());
        $tpl->set("BLOG_URL_ROOTREL", $this->getURL(false));
        $tpl->set("BLOG_ALLOW_ENC", $this->allow_enclosure);
        $tpl->set("BLOG_GATHER_REPLIES", $this->gather_replies);
        $tpl->set("BLOG_AUTO_PINGBACK", $this->autoPingbackEnabled());
        $tpl->set("BLOG_FRONT_PAGE_ABSTRACT", $this->front_page_abstract);
    }

    /*
    Method: upgradeWrappers
    This is an upgrade function that will create new config and wrapper
    scripts to upgrade a directory of blog data to the current version.
    The data files will only be modified if required.  Copies of the old

    Precondition:
    It is assumed that this function will only be run from the package
    installation directory.

    Returns:
    An array of failed files or false if the upgrade fails completely.
    */
    public function upgradeWrappers () {
        $this->raiseEvent("OnUpgrade");
        $inst_path = $this->fs->getcwd();
        $files = array();

        # Upgrade the base blog directory first.  All other directories will
        # get a copy of the config.php created here.
        $wrappers = new WrapperGenerator($this->fs);
        $ret = $wrappers->createDirectoryWrappers($this->home_path, WrapperGenerator::BLOG_BASE, $inst_path);
        if (! is_array($ret)) {
            return false;
        } else {
            $files = $ret;
        }

        # Upgrade the articles.
        $path = Path::mk($this->home_path, BLOG_ARTICLE_PATH);
        $ret = $wrappers->createDirectoryWrappers($path, WrapperGenerator::BLOG_ARTICLES);
        $files = array_merge($files, $ret);

        $dir_list = $this->fs->scan_directory($path, true);
        foreach ($dir_list as $art) {
            $ent_path = Path::mk($path, $art);
            $cmt_path = Path::mk($ent_path, ENTRY_COMMENT_DIR);
            $tb_path = Path::mk($ent_path, ENTRY_TRACKBACK_DIR);
            $pb_path = Path::mk($ent_path, ENTRY_PINGBACK_DIR);
            $ret = $this->writeEntryFileIfNeeded($ent_path);
            $files = array_merge($files, $ret);
            $ret = $wrappers->createDirectoryWrappers($ent_path, WrapperGenerator::ARTICLE_BASE);
            $files = array_merge($files, $ret);
            $ret = $wrappers->createDirectoryWrappers($cmt_path, WrapperGenerator::ENTRY_COMMENTS, 'article');
            $files = array_merge($files, $ret);
            $ret = $wrappers->createDirectoryWrappers($tb_path, WrapperGenerator::ENTRY_TRACKBACKS, 'article');
            $files = array_merge($files, $ret);
            $ret = $wrappers->createDirectoryWrappers($pb_path, WrapperGenerator::ENTRY_PINGBACKS, 'article');
            $files = array_merge($files, $ret);
        }

        # Upgrade the regular blog entries.
        $path = Path::mk($this->home_path, BLOG_ENTRY_PATH);
        $ret = $wrappers->createDirectoryWrappers($path, WrapperGenerator::BLOG_ENTRIES);
        $files = array_merge($files, $ret);
        $dir_list = $this->fs->scan_directory($path, true);
        foreach ($dir_list as $yr) {
            $year_path = Path::mk($path, $yr);
            $ret = $wrappers->createDirectoryWrappers($year_path, WrapperGenerator::YEAR_ENTRIES);
            $files = array_merge($files, $ret);
            $year_list = $this->fs->scan_directory($year_path, true);
            foreach ($year_list as $mn) {
                $month_path = $year_path.PATH_DELIM.$mn;
                $ret = $wrappers->createDirectoryWrappers($month_path, WrapperGenerator::MONTH_ENTRIES);
                $files = array_merge($files, $ret);
                $month_list = $this->fs->scan_directory($month_path, true);
                foreach ($month_list as $ent) {
                    $ent_path = Path::mk($month_path, $ent);
                    $cmt_path = Path::mk($ent_path, ENTRY_COMMENT_DIR);
                    $tb_path = Path::mk($ent_path, ENTRY_TRACKBACK_DIR);
                    $pb_path = Path::mk($ent_path, ENTRY_PINGBACK_DIR);
                    $ret = $this->writeEntryFileIfNeeded($ent_path);
                    $files = array_merge($files, $ret);
                    $ret = $wrappers->createDirectoryWrappers($ent_path, WrapperGenerator::ENTRY_BASE);
                    $files = array_merge($files, $ret);
                    $ret = $wrappers->createDirectoryWrappers($cmt_path, WrapperGenerator::ENTRY_COMMENTS);
                    $files = array_merge($files, $ret);
                    $ret = $wrappers->createDirectoryWrappers($tb_path, WrapperGenerator::ENTRY_TRACKBACKS);
                    $files = array_merge($files, $ret);
                    $ret = $wrappers->createDirectoryWrappers($pb_path, WrapperGenerator::ENTRY_PINGBACKS);
                    $files = array_merge($files, $ret);

                    # Update the "pretty permalink" wrapper scripts
                    $ppl_files = glob(Path::mk($month_path, '*.php'));
                    foreach ($ppl_files as $ppl) {
                        $content = $this->fs->read_file($ppl);
                        $matches = array();
                        if (preg_match("/chdir\('([\d_]+)'\)/", $content, $matches)) {
                            $content = '<?php $path = dirname(__FILE__).DIRECTORY_SEPARATOR."'.
                                $matches[1].'".DIRECTORY_SEPARATOR; chdir($path); include "$path/index.php";';
                            $this->fs->write_file($ppl, $content);
                        }
                    }
                }
            }
        }

        # Upgrade the drafts.
        $path = Path::mk($this->home_path, BLOG_DRAFT_PATH);
        $ret = $wrappers->createDirectoryWrappers($path, WrapperGenerator::ENTRY_DRAFTS);
        $files = array_merge($files, $ret);

        $path = Path::mk($this->home_path, BLOG_DRAFT_PATH);
        $dir_list = $this->fs->scan_directory($path, true);
        foreach ($dir_list as $draft) {
            $ent_path = Path::mk($path, $draft);
            $ret = $this->writeEntryFileIfNeeded($ent_path);
            $files = array_merge($files, $ret);
            $ret = $this->migrateAutoPublishIfNeeded($ent_path);
            $files = array_merge($files, $ret);
            $ret = $wrappers->createDirectoryWrappers($ent_path, WrapperGenerator::DRAFT_ENTRY_BASE);
            $files = array_merge($files, $ret);
        }

        # Make sure third-part scripts are present
        $ret = $this->createThirdPartyClientLibraries();
        if (!$ret) {
            $files[] = Path::mk($this->home_path, 'third-party');
        }

        $this->sw_version = PACKAGE_VERSION;
        $this->last_upgrade = date('r');
        $ret = $this->writeBlogData();
        if (! $ret) {
            $files[] = Path::mk($this->home_path, BLOG_CONFIG_PATH);
        }
        $this->raiseEvent("UpgradeComplete");
        return $files;
    }

    public function createThirdPartyClientLibraries() {
        $ret = true;
        $third_party_path = Path::mk($this->home_path, 'third-party');
        $src_path = Path::mk(INSTALL_ROOT, 'third-party');
        $root_domain = parse_url(INSTALL_ROOT_URL, PHP_URL_HOST);
        $blog_domain = parse_url($this->getURL(), PHP_URL_HOST);
        if ($root_domain != $blog_domain && !$this->fs->is_link($third_party_path)) {
            if ($this->fs->is_dir($third_party_path)) {
                $ret = $this->fs->copy_rec($src_path, $third_party_path);
            } else {
                $ret = $this->fs->symlink($src_path, $third_party_path);
                # If the symlinking fails (maybe we're running on Windows and lack permissions),
                # then try to fall back to copying the directory.
                if (!$ret) {
                    $ret = $this->fs->copy_rec($src_path, $third_party_path);
                }
            }
        }
        return $ret;
    }


    private function shouldWriteEntryDataFile($entry_path) {
        $path = Path::mk($entry_path, ENTRY_DEFAULT_FILE);
        $entry = new BlogEntry($entry_path, $this->fs);
        if (!$this->fs->file_exists($path) || !$entry->uid || !$entry->permalink_name) {
            return $entry;
        }
        return false;
    }

    private function migrateAutoPublishIfNeeded($entry_path) {
        $entry = new BlogEntry($entry_path, $this->fs);
        $auto_publish_date = $entry->getAutoPublishDate();
        if (!$auto_publish_date) {
            return [];
        }

        $file = Path::mk($entry_path, BlogEntry::AUTO_PUBLISH_FILE);
        if (!$this->fs->file_exists($file)) {
            return [];
        }

        $generator = new WrapperGenerator($this->fs);
        $user = NewUser();
        $publisher = new Publisher($this, $user, $this->fs, $generator, $this->task_manager);

        $contents = $this->fs->read_file($file);
        $time = new DateTime(trim($contents));
        $task = new AutoPublishTask($this->logger, $publisher);
        $task->runAfterTime($time);
        $task->data($entry);

        try {
            $this->task_manager->add($task);
            $this->fs->delete($file);
            return [];
        } catch (Exception $e) {
            $this->logger->error(
                "Unable to migrate auto-publish setting to task", [
                    'exception' => $e,
                    'draftid' => $entry->entryID(),
                ]
            );
            return [$entry->file];
        }
    }

    private function backupOldFileData($entry_path) {
        // Nothing to do for now.  May need in the future.
    }

    private function writeEntryFileIfNeeded($entry_path) {
        if ($entry = $this->shouldWriteEntryDataFile($entry_path)) {
            $this->backupOldFileData($entry);
            if (!$entry->uid) {
                $entry->uid = $this->owner;
            }
            # Update entries that are missing a permalink in the metadata
            if (!$entry->permalink_name) {
                $base_path = dirname($entry->file);
                $permalink = $entry->calcLegacyPermalink();
                $old_permalink = $entry->calcLegacyPermalink(true);
                if ($this->fs->file_exists(Path::mk($base_path, $permalink))) {
                    $entry->permalink_name = $permalink;
                } elseif ($this->fs->file_exists(Path::mk($base_path, $old_permalink))) {
                    $entry->permalink_name = $old_permalink;
                }
            }
            $ret = $entry->writeFileData();
            return $ret ? [] : [$entry->file];
        }
        return [];
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
    public function fixDirectoryPermissions($start_dir=false) {
        if (! $start_dir) $start_dir = $this->home_path;
        $dir_list = $this->fs->scan_directory($start_dir, true);
        $ret = true;
        foreach ($dir_list as $dir) {
            $path = Path::mk($start_dir, $dir);
            $ret = $ret && $this->fs->chmod($path, $this->fs->directoryMode());
            $ret = $ret && $this->fixDirectoryPermissions($path);
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
    public function insert($path=false) {

        $this->raiseEvent("OnInsert");

        $this->name = htmlentities($this->name);
        $this->description = htmlentities($this->description);

        $p = new Path($path ? $path : $this->home_path);
        $this->home_path = $p->getCanonical();

        $ret = $this->createBlogDirectories(INSTALL_ROOT);

        $this->setBlogID();

        if ($ret) {
            $ret = $this->createThirdPartyClientLibraries();
        }

        if ($ret) {
            $this->sw_version = PACKAGE_VERSION;
            $this->last_upgrade = date('r');

            $ret = $this->writeBlogData();
            $this->raiseEvent("InsertComplete");
        }

        return (bool)$ret;
    }

    private function createBlogDirectories($inst_path) {
        $wrappers = new WrapperGenerator($this->fs);
        $ret = $this->createDirectoryIfDoesNotExist($this->home_path);
        if ($ret) {
            $result = $wrappers->createDirectoryWrappers($this->home_path, WrapperGenerator::BLOG_BASE, $inst_path);
            # Returns an array of errors, so convert empty array to true.
            $ret = $ret && empty($result);
        }

        $path = Path::get($this->home_path, BLOG_ENTRY_PATH);
        $ret = $ret && $this->createDirectoryIfDoesNotExist($path);

        if ($ret) {
            $result = $wrappers->createDirectoryWrappers($path, WrapperGenerator::BLOG_ENTRIES);
            $ret = $ret && empty($result);
        }
        $path = Path::get($this->home_path, BLOG_FEED_PATH);
        $ret = $ret && $this->createDirectoryIfDoesNotExist($path);

        return $ret;
    }

    private function createDirectoryIfDoesNotExist($dir) {
        if (is_dir($dir) ) {
            return true; 
        }
        return $this->fs->mkdir_rec($dir);
    }

    # Method: update
    # Modify an existing weblog.
    #
    # Returns:
    # True on success, false otherwise.

    public function update() {
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
    # Removes an existing weblog.
    #
    # Parameters:
    # keep_history - If set to true, only delete the blog data file, making this a
    #                non-blog directory, rather than totally destroy the data.  If not
    #                set, defaults to KEEP_EDIT_HISTORY constant.
    #
    # Returns:
    # True on success, false on failure.

    public function delete($keep_history = null) {
        if ($keep_history === null) {
            $keep_history = KEEP_EDIT_HISTORY;
        }
        $this->raiseEvent("OnDelete");
        if ($keep_history) {
            $p = Path::get($this->home_path, BLOG_DELETED_PATH);
            if (! is_dir($p)) {
                $this->fs->mkdir_rec($p->get());
            }
            $p = Path::get(
                $this->home_path, BLOG_DELETED_PATH,
                BLOG_CONFIG_PATH."-".date(ENTRY_PATH_FORMAT_LONG)
            );
            $ret = $this->fs->rename(Path::get($this->home_path, BLOG_CONFIG_PATH), $p);
        } else {
            $ret = $this->fs->rmdir_rec($this->home_path);
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

    public function updateTagList($tags) {
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
