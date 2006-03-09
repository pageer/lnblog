<?php

class ContentBan extends Plugin {

	function ContentBan() {
		$this->plugin_desc = _("Allows you to automatically reject comments or trackbacks based on their content.");
		$this->plugin_version = "0.1.0";
		$this->addOption("re_file", 
			_("File to store list of banned regular expressions."),
			"content_ban.txt", "text");
		$this->getConfig();
	}

	function get_ban_array() {
		$blog = NewBlog();
		
		if (file_exists(USER_DATA_PATH.$this->re_file)) {
			$global_list = file(USER_DATA_PATH.$this->re_file);
		} else {
			$global_list = array();
		}
	
		if ($blog->isBlog() && 
		    file_exists($blog->home_path.PATH_DELIM.$this->re_file)) {
			$local_list = file($blog->home_path.PATH_DELIM.$this->re_file);
		} else {
			$local_list = array();
		}
		$ban_list = array_merge($global_list, $local_list);
		for ($idx=0; $idx<count($ban_list); $idx++)
			$ban_list[$idx] = trim($ban_list[$idx]);
	}

}

?>
