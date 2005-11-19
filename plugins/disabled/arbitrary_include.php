<?php
# This plugin just includes an arbitrary file in the sidebar.  It will try 
# to include the file name from the current blog root directory or, failing 
# that, from the global userdata directory.
#
# You may make multiple copies of this file with different names if you want
# to include multiple files in different places.  Just change the
# $file_name variable.

function include_file($arg) {
	$file_name = "links.htm";
	if (defined("BLOG_ROOT") && 
	    file_exists(BLOG_ROOT.PATH_DELIM.$file_name) ) {
		include($file_name);
	} elseif (file_exists(
	            INSTALL_ROOT.PATH_DELIM.USER_DATA.PATH_DELIM.$file_name) ) {
		include(USER_DATA.PATH_DELIM.$file_name);
	}
}

$EVENT_REGISTER->addHandler("sidebar", "OnOutput", "none", "include_file", true);
?>
