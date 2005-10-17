<?php 
class HTAccessGenerator extends Plugin {

	function HTAccessGenerator() {
		$this->plugin_desc = "Adds handy .htaccess files to blogs.";
		$this->plugin_version = "0.1.0";
	}

	function create_file(&$param) {
		$cont  = "Options +FollowSymlinks\n";
		$cont .= "rewriteengine on\n";
		# Redirect attempts to access the raw data files for comments, entries,
		# articles, or weblogs.
		$cont .= "rewriterule ^(.+/".ENTRY_COMMENT_DIR."/).+\\.".COMMENT_PATH_SUFFIX."$ $1 [nc]\n";
		$cont .= "rewriterule ^(.+/".ENTRY_TRACKBACK_DIR."/).+\\.".TRACKBACK_PATH_SUFFIX."$ $1 [nc]\n";
		$cont .= "rewriterule ^(.+/)".ENTRY_DEFAULT_FILE."$ $1 [nc]\n";
		$cont .= "rewriterule ^(.+/)[0-9_]+".ENTRY_PATH_SUFFIX."$ $1 [nc]\n";
		$cont .= "rewriterule ^(.+/)".BLOG_CONFIG_PATH."$ $1 [nc]\n";
		# Redirect attempts to access "deleted" data files.
		$cont .= "rewriterule ^(.+/)".COMMENT_DELETED_PATH.".*$ $1 [nc]\n";
		if (COMMENT_DELETED_PATH != BLOG_DELETED_PATH)
			$cont .= "rewriterule ^(.+/)".BLOG_DELETED_PATH.".*$ $1 [nc]\n";
		write_file($param->home_path.PATH_DELIM.".htaccess", $cont);
	}

}

$gen = new HTAccessGenerator();
$gen->registerEventHandler("blog", "UpgradeComplete", "create_file");
$gen->registerEventHandler("blog", "InsertComplete", "create_file");

?>
