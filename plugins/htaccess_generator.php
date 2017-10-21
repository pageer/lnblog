<?php

# Plugin: HTAccessGenerator
# Creates Apache .htaccess files in blog root directories.
#
# When blogs are created or updated, this plugin creates a .htaccess file
# with rewrite rules to redirect attempts to directly access data files or 
# other incorrect URLs.

class HTAccessGenerator extends Plugin {

	function HTAccessGenerator() {
		$this->plugin_desc = _("Adds handy .htaccess files to blogs.");
		$this->plugin_version = "0.1.1";
		parent::__construct();
	}

	function create_file(&$param) {
		# Include the .htaccess file from the document root, if there is one.
		# We do this because .htaccess files in lower directories completely
		# over-ride ones in higher directories.
		if (file_exists(INSTALL_ROOT.PATH_DELIM.".htaccess")) {
			$lines = file(INSTALL_ROOT.PATH_DELIM.".htaccess");
			$cont = implode("\n", $lines);
		} else $cont = "";
	
		$cont .= "Options +FollowSymlinks\n";
		$cont .= "RewriteEngine on\n";
		# Redirect attempts to access the raw data files for comments, entries,
		# articles, or weblogs.
		$cont .= "RewriteRule ^(.+/".ENTRY_COMMENT_DIR."/).+\\.".COMMENT_PATH_SUFFIX."$ $1 [nc]\n";
		$cont .= "RewriteRule ^(.+/".ENTRY_TRACKBACK_DIR."/).+\\.".TRACKBACK_PATH_SUFFIX."$ $1 [nc]\n";
		$cont .= "RewriteRule ^(.+/)".ENTRY_DEFAULT_FILE."$ $1 [nc]\n";
		$cont .= "RewriteRule ^(.+/)[0-9_]+".ENTRY_PATH_SUFFIX."$ $1 [nc]\n";
		$cont .= "RewriteRule ^(.+/)".BLOG_CONFIG_PATH."$ $1 [nc]\n";
		# Redirect attempts to access "deleted" data files.
		$cont .= "RewriteRule ^(.+/)".COMMENT_DELETED_PATH.".*$ $1 [nc]\n";
		if (COMMENT_DELETED_PATH != BLOG_DELETED_PATH)
			$cont .= "RewriteRule ^(.+/)".BLOG_DELETED_PATH.".*$ $1 [nc]\n";
		write_file($param->home_path.PATH_DELIM.".htaccess", $cont);
	}

}

$gen = new HTAccessGenerator();
$gen->registerEventHandler("blog", "UpgradeComplete", "create_file");
$gen->registerEventHandler("blog", "InsertComplete", "create_file");

?>
