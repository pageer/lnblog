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

# Do the standard library includes.
require_once("blogconfig.php");
require_once("template.php");
$EXCLUDE_FS = true;
require_once("utils.php");

session_start();

$ftp = "use_ftp";
$uid = "ftp_user";
$pwd = "ftp_pwd";
$conf = "ftp_conf";
$host = "ftp_host";
$root = "ftp_root";
$pref = "ftp_prefix";

if ( file_exists(INSTALL_ROOT.PATH_DELIM.FS_PLUGIN_CONFIG) ) {
	header("Location: index.php");
	exit;
}

$page_name = PACKAGE_NAME."  File Writing";
$form_title = "Configure File Writing Support";
$redir_page = "index.php";

$tpl = new PHPTemplate(FS_CONFIG_TEMPLATE);

$tpl->set("FORM_TITLE", "Configure File Writing");
$tpl->set("FORM_ACTION", basename(SERVER("PHP_SELF")) );
$tpl->set("USE_FTP_ID", $ftp);
$tpl->set("USER_ID", $uid);
$tpl->set("PASS_ID", $pwd);
$tpl->set("CONF_ID", $conf);
$tpl->set("HOST_ID", $host);
$tpl->set("ROOT_ID", $root);
$tpl->set("PREF_ID", $pref);

if ( has_post() ) {

	$tpl->set("USE_FTP", POST($ftp) );
	$tpl->set("USER", POST($uid) );
	$tpl->set("PASS", POST($pwd) );
	$tpl->set("CONF", POST($conf) );
	$tpl->set("HOST", POST($host) );
	$tpl->set("ROOT", POST($root) );
	$tpl->set("PREF", POST($pref) );

	# Check that all required fields have been specified.
	$vars = array($uid, $pwd, $conf, $host, $root);
	$has_all_data = true;
	foreach ($vars as $val) {
		$has_all_data &= ( trim(POST($val)) != "" );
		#echo "<p>'".POST($val)."'</p>";
	}

	if ( $has_all_data && trim(POST($pwd)) == trim(POST($conf)) ) {
		if ( POST($ftp) ) {
			define("FS_PLUGIN", "ftpfs");
			define("FTPFS_USER", trim(POST($uid)) );
			define("FTPFS_PASSWORD",trim( POST($pwd)) );
			define("FTPFS_HOST", trim(POST($host)) );
			define("FTP_ROOT", trim(POST($root)) );
			if (trim(POST($pref)) != '') {
				define("FTPFS_PATH_PREFIX", trim(POST($pref)) );
				#echo "<p>Found prefix</p>";
			} 
			$content = "<?php\n".
				'define("FS_PLUGIN", "'.FS_PLUGIN."\");\n".
				'define("FTPFS_USER", "'.FTPFS_USER."\");\n".
				'define("FTPFS_PASSWORD", "'.FTPFS_PASSWORD."\");\n".
				'define("FTPFS_HOST", "'.FTPFS_HOST."\");\n".
				'define("FTP_ROOT", "'.FTP_ROOT."\");\n";
			if (trim(POST($pref)) != '') {
				$content .= 'define("FTPFS_PATH_PREFIX", "'.FTPFS_PATH_PREFIX."\");\n";
			}
			$content .= '?>';
		} else {
			define("FS_PLUGIN", "nativefs");
			$content = "<?php\ndefine(\"FS_PLUGIN\", ".FS_PLUGIN."\");\n?>";
		}
	
		require_once("fs.php");
	
		$fs = CreateFS();
		$ret = $fs->write_file(INSTALL_ROOT.PATH_DELIM.FS_PLUGIN_CONFIG, $content);
		$fs->destruct();	
		if (! $ret) $tpl->set("FORM_MESSAGE", "Could not create file.");
		else {
			header("Location: index.php");
			exit;
		}
		
	} elseif ( trim(POST($pwd)) != trim(POST($conf)) ) {
		$tpl->set("FORM_MESSAGE", "Passwords do not match.");
	} else {
		$tpl->set("FORM_MESSAGE", "All fields except prefix are required.");
	}
	
} else {
	$tpl->set("ROOT", realpath(getcwd().PATH_DELIM."..".PATH_DELIM."..") );
	$tpl->set("HOST", SERVER("SERVER_NAME") );
}

$body = $tpl->process();
$tpl->reset(BASIC_LAYOUT_TEMPLATE);
$tpl->set("PAGE_CONTENT", $body);
$tpl->set("PAGE_TITLE", $page_name);
$tpl->set("STYLE_SHEETS", array("form.css") );

echo $tpl->process();
?>
