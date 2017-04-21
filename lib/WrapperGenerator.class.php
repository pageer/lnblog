<?php
/**
 * Encapsulates the legacy functions for generating directory wrappers.
 */
class WrapperGenerator {

    private $fs;

    public function __construct(FS $fs) {
        $this->fs = $fs;
    }

    /**
     * Legacy function to create/remove directory wrapper scripts.
     */
    public function createDirectoryWrappers($path, $type, $instpath = "") {
        $blog_templ_dir = "BLOG_ROOT.'".PATH_DELIM.BLOG_TEMPLATE_DIR."'";
    	$sys_templ_dir = "INSTALL_ROOT.'".PATH_DELIM.BLOG_TEMPLATE_DIR."'";
    
        if (! is_dir($path)) {
            $this->fs->$ret = $this->fs->mkdir_rec($path);
        }
    
    	$current = $path.PATH_DELIM;
    	$parent = dirname($path).PATH_DELIM;
    	$config_level = 0;
    	$ret = 0;
    	$ret_list = array();
    
    	switch ($type) {
    		case BLOG_BASE:
                if (!$this->fs->is_dir($instpath)) {
                    return false;
                }
    			$filelist = array("index"=>"pages/showblog");
    			$removelist = array("new", "newart", "edit", "login", "logout", 
    			                    "uploadfile", "map", "useredit", "plugins",
    			                    "tags", "pluginload", "profile");
    			$config_level = 0;
    			if (! $this->fs->file_exists($current."pathconfig.php")) {
    				$inst_root = $this->fs->realpath($instpath);
    				$blog_root = $this->fs->realpath($path);
    				$inst_url = localpath_to_uri($inst_root);
    				$blog_url = localpath_to_uri($blog_root);
    				$config_data = pathconfig_php_string($inst_root, $inst_url, $blog_url);
    				$ret = $this->fs->write_file($current."pathconfig.php", $config_data);
                    if (! $ret) {
                        $ret_list[] = $current."pathconfig.php";
                    }
    			}
    			break;
    		case BLOG_ENTRIES:
    			$filelist = array("index"=>"pages/showarchive");
    			$removelist = array("all");
    			$config_level = 1;
    			break;
    		case BLOG_DRAFTS:
    			$filelist = array("index"=>"pages/showdrafts");
    			$config_level = 1;
    			break;
    		case YEAR_ENTRIES:
    			$filelist = array("index"=>"pages/showarchive");
    			$config_level = 2;
    			break;
    		case MONTH_ENTRIES:
    			$filelist = array("index"=>"pages/showarchive");
    			$removelist = array("day");
    			$config_level = 3;
    			break;
    		case ENTRY_BASE:
    			$filelist = array("index"=>"pages/showitem");
    			$removelist = array("edit", "delete", "trackback", "uploadfile");
    			$config_level = 4;
    			break;
    		case ENTRY_COMMENTS:
    			$filelist = array("index"=>"pages/showitem");
    			$removelist = array("delete");
    			$config_level = strtolower($instpath) == 'article' ? 3 : 5;
    			break;
    		case ENTRY_TRACKBACKS:
    			$filelist = array("index"=>"pages/showitem");
    			$config_level = strtolower($instpath) == 'article' ? 3 : 5;
    			break;
    		case ENTRY_PINGBACKS:
    			$filelist = array("index"=>"pages/showitem");
    			$config_level = strtolower($instpath) == 'article' ? 3 : 5;
    			break;
    		case ARTICLE_BASE:
    			# The same as for entries, but for some reason, I never added a delete.
    			$filelist = array("index"=>"pages/showitem");
    			$removelist = array("edit", "trackback", "uploadfile");
    			$config_level = 2;
    			break;
    		case BLOG_ARTICLES:
    			$filelist = array("index"=>"pages/showarticles");
    			$config_level = 1;
    			break;
    		case ENTRY_DRAFTS:
    			$filelist = array("index"=>"pages/showdrafts");
    			$config_level = 1;
    			break;
    	}
    
    	foreach ($filelist as $file=>$content) {
    		$curr_file = $current.$file.".php";
    		$body = "<?php\n";
    		$body .= config_php_string($config_level);
    		$body .= "include(INSTALL_ROOT.DIRECTORY_SEPARATOR.\"".
    			str_replace('/', DIRECTORY_SEPARATOR, $content).".php\");\n";
    
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

    public function removeForEntry(BlogEntry $entry) {
    	$removelist = array("index", "edit", "delete", "trackback", "uploadfile");
        foreach ($removeList as $item) {
            $path = Path::mk($entry->localpath(), "$item.php");
            if ($this->fs->file_exists($path)) {
                $this->fs->delete($path);
            }
        }
    }

}
