<?php
# Class: WrapperGenerator
# Encapsulates the legacy functions for generating directory wrappers.
class WrapperGenerator {

    const BLOG_BASE =  0;
    const BLOG_ENTRIES =  1;
    const YEAR_ENTRIES =  2;
    const MONTH_ENTRIES =  3;
    const ENTRY_BASE =  4;
    const ENTRY_COMMENTS =  5;
    const ARTICLE_BASE =  6;
    const BLOG_ARTICLES =  7;
    const ENTRY_TRACKBACKS =  8;
    const ENTRY_DRAFTS =  9;
    const ENTRY_PINGBACKS =  10;
    const BLOG_DRAFTS =  11;
    const DRAFT_ENTRY_BASE =  12;

    private $fs;

    public function __construct(FS $fs) {
        $this->fs = $fs;
    }

    # Method: createDirectoryWrappers
    # Create the required wrapper scripts for a directory.  Note that
    # the instpath parameter is for the software installation path 
    # and is only required for the BLOG_BASE type, as it is used 
    # to create the config.php file.
    # As of version 0.7.4, this function returns an array of paths for
    # which the file operation returned an error code.
    #
    # Parameters:
    # path     - The path at which we're creating wrappers.
    # type     - The type of wrappers to create.
    # instpath - The LnBlog install path, only required for type of BLOG_BASE
    #
    # Returns:
    # List of paths for which there was an error.
    public function createDirectoryWrappers($path, $type, $instpath = "") {
        $blog_templ_dir = "BLOG_ROOT.'".PATH_DELIM.BLOG_TEMPLATE_DIR."'";
    	$sys_templ_dir = "INSTALL_ROOT.'".PATH_DELIM.BLOG_TEMPLATE_DIR."'";
    
        if (! is_dir($path)) {
            $ret = $this->fs->mkdir_rec($path);
        }
    
    	$current = $path.PATH_DELIM;
    	$parent = dirname($path).PATH_DELIM;
    	$config_level = 0;
    	$ret = 0;
    	$ret_list = array();
    
    	switch ($type) {
            case self::BLOG_BASE:
                if (!$this->fs->is_dir($instpath)) {
                    return false;
                }
    			$filelist = array("index"=>"pages/showblog");
    			$removelist = array("new", "newart", "edit", "login", "logout", 
    			                    "uploadfile", "map", "useredit", "plugins",
    			                    "tags", "pluginload", "profile");
    			$config_level = 0;
                $inst_root = $this->fs->realpath($instpath);
                $blog_root = $this->fs->realpath($path);
                $inst_url = SystemConfig::instance()->installRoot()->url();
                $blog_url = $this->findBlogRoot($blog_root);
                $config_data = pathconfig_php_string($inst_root, $inst_url, $blog_url);
                $ret = $this->fs->write_file($current."pathconfig.php", $config_data);
                if (! $ret) {
                    $ret_list[] = $current."pathconfig.php";
                }
    			break;
            case self::BLOG_ENTRIES:
    			$filelist = array("index"=>"pages/showarchive");
    			$removelist = array("all");
    			$config_level = 1;
    			break;
            case self::BLOG_DRAFTS:
    			$filelist = array("index"=>"pages/showdrafts");
    			$config_level = 1;
    			break;
            case self::YEAR_ENTRIES:
    			$filelist = array("index"=>"pages/showarchive");
    			$config_level = 2;
    			break;
            case self::MONTH_ENTRIES:
    			$filelist = array("index"=>"pages/showarchive");
    			$removelist = array("day");
    			$config_level = 3;
    			break;
            case self::ENTRY_BASE:
    			$filelist = array("index"=>"pages/showitem");
    			$removelist = array("edit", "delete", "trackback", "uploadfile");
    			$config_level = 4;
    			break;
            case self::ENTRY_COMMENTS:
    			$filelist = array("index"=>"pages/showitem");
    			$removelist = array("delete");
    			$config_level = strtolower($instpath) == 'article' ? 3 : 5;
    			break;
            case self::ENTRY_TRACKBACKS:
    			$filelist = array("index"=>"pages/showitem");
    			$config_level = strtolower($instpath) == 'article' ? 3 : 5;
    			break;
            case self::ENTRY_PINGBACKS:
    			$filelist = array("index"=>"pages/showitem");
    			$config_level = strtolower($instpath) == 'article' ? 3 : 5;
    			break;
            case self::ARTICLE_BASE:
    			# The same as for entries, but for some reason, I never added a delete.
    			$filelist = array("index"=>"pages/showitem");
    			$removelist = array("edit", "trackback", "uploadfile");
    			$config_level = 2;
    			break;
            case self::BLOG_ARTICLES:
    			$filelist = array("index"=>"pages/showarticles");
    			$config_level = 1;
    			break;
            case self::ENTRY_DRAFTS:
    			$filelist = array("index"=>"pages/showdrafts");
    			$config_level = 1;
    			break;
            case self::DRAFT_ENTRY_BASE:
    			$filelist = array("index"=>"pages/editentry");
                $config_level = 2;
                break;
    	}
    
    	foreach ($filelist as $file=>$content) {
    		$curr_file = $current.$file.".php";
    		$body = "<?php\n";
    		$body .= config_php_string($config_level);
    		$body .= "include(INSTALL_ROOT.DIRECTORY_SEPARATOR.\"$content.php\");\n";
    
    		$ret = $this->fs->write_file($curr_file, $body);
    		if (! $ret) $ret_list[] = $curr_file;
    	}
    
    	if ($this->fs->file_exists($current."config.php")) {
    		$this->fs->delete($current."config.php");
    	}
    
    	if (isset($removelist) && is_array($removelist)) {
    		foreach ($removelist as $file) {
    			$f = $current.$file.".php";
    			if ($this->fs->file_exists($f)) {
    				$this->fs->delete($f);
    			}
    		}
    	}
    
    	return $ret_list;
    }

    # Method: removeForEntry
    # Removes directory wrappers for a given entry
    #
    # Parameters:
    # entry - The BlogEntry for which to remove the wrappers.
    public function removeForEntry(BlogEntry $entry) {
    	$removeList = array("index", "edit", "delete", "trackback", "uploadfile");
        foreach ($removeList as $item) {
            $path = Path::mk($entry->localpath(), "$item.php");
            if ($this->fs->file_exists($path)) {
                $this->fs->delete($path);
            }
        }
    }

    private function findBlogRoot($path) {
        $registry = SystemConfig::instance()->blogRegistry();
        foreach ($registry as $blogid => $urlpath) {
            if ($urlpath->path() == $path) {
                return $urlpath->url();
            }
        }
        return '';
    }
}
