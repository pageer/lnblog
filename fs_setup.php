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
/*
File: fs_setup.php
This file configures LnBlog's file writing and allows you to set the 
document root for your web server (it will try to auto-detect this).  
It creates the userdata/fsconfig.php file.

There are two options for file writing.

Native FS - Uses PHP's native filesystem functions.  
FTP FS    - Write files through an FTP connection (usually to localhost).

Note that there is a trade-off here.  Native FS has no special requirements
or configuration in the software, but will probably require manually changing
file permissions due to the fact that it runs as a system account.  FTP FS,
on the other hand, requires the user to enter an FTP username, password, 
host name, and the root of that user's FTP access (assuming the user cannot
access the entire filesystem).  However, with FTP FS, LnBlog can create 
files as the configured user account, rather than a system account like 
"apache" or "IUSR_whatever".  FTP FS is recommended for hosted sites where 
you don't have root access to the machine, as it makes administration through
an FTP connection much easier.
*/

require_once("blogconfig.php");
require_once("lib/creators.php");
require_once("lib/utils.php");

session_start();

global $PAGE;

$docroot = "documentroot";
$ftp = "use_ftp";
$uid = "ftp_user";
$pwd = "ftp_pwd";
$conf = "ftp_conf";
$host = "ftp_host";
$root = "ftp_root";
$pref = "ftp_prefix";

if ( file_exists(INSTALL_ROOT.PATH_DELIM.USER_DATA.PATH_DELIM.FS_PLUGIN_CONFIG) ) {
	header("Location: index.php");
	exit;
}

$PAGE->title = sprintf(_("%s File Writing"), PACKAGE_NAME);
$form_title = _("Configure File Writing Support");
$redir_page = "index.php";

$tpl = NewTemplate(FS_CONFIG_TEMPLATE);

$tpl->set("FORM_ACTION", basename(SERVER("PHP_SELF")) );
$tpl->set("DOC_ROOT_ID", $docroot);
$tpl->set("USE_FTP_ID", $ftp);
$tpl->set("USER_ID", $uid);
$tpl->set("PASS_ID", $pwd);
$tpl->set("CONF_ID", $conf);
$tpl->set("HOST_ID", $host);
$tpl->set("ROOT_ID", $root);
$tpl->set("PREF_ID", $pref);

if ( has_post() ) {

	$tpl->set("DOC_ROOT", stripslashes_smart(POST($docroot)) );
	if (POST($ftp) == "ftpfs") $tpl->set("USE_FTP", POST($ftp) );
	$tpl->set("USER", POST($uid) );
	$tpl->set("PASS", POST($pwd) );
	$tpl->set("CONF", POST($conf) );
	$tpl->set("HOST", POST($host) );
	$tpl->set("ROOT", stripslashes_smart(POST($root)) );
	$tpl->set("PREF", POST($pref) );

	$content = '';

	# Note that DOCUMENT_ROOT is not strictly required, as all uses 
	# of it should be wrapped in a document root calculation function
	# for legacy versions.

	if ( POST($ftp) == "nativefs" ) {

		define("FS_PLUGIN", "nativefs");
		$content = "<?php\n";
		if ( POST($docroot) ) {
			$webroot = trim(POST($docroot));
			$webroot = get_magic_quotes_gpc() ? 
			           stripslashes($webroot) : $webroot;
			define("DOCUMENT_ROOT", $webroot);
			$content .= 'define("DOCUMENT_ROOT", "'.DOCUMENT_ROOT."\");\n";
		}
		$content .= 'define("FS_PLUGIN", "'.FS_PLUGIN."\");\n?>";

	} elseif ( POST($ftp) == "ftpfs" ) {

		# Check that all required fields have been specified.
		$vars = array($uid, $pwd, $conf, $host, $root);
		$has_all_data = true;
		foreach ($vars as $val) {
			$has_all_data = $has_all_data && ( trim(POST($val)) != "" );
		}

		# Make a vain attempt to guess the FTP root.
		if ( trim(POST($uid)) && trim(POST($pwd)) && trim(POST($conf)) 
		     && trim(POST($host)) && trim(POST($pwd)) == trim(POST($conf))
			  && ! trim(POST($root)) ) {
			require("lib/ftpfs.php");
			@$ftp = new FTPFS(trim(POST($host)), 
			                 trim(POST($uid)), trim(POST($pwd)) );
			if ($ftp->status !== false) {
				
				# Try to calculate the FTP root.
				ftp_chdir($ftp->connection, "/");
				$ftp_list = ftp_nlist($ftp->connection, ".");
				
				$file = getcwd().PATH_DELIM."fs_setup.php";
				$drive = substr($file, 0, 2);

				# Get the current path into an array.
				if (PATH_DELIM != "/") {
					if (substr($file, 1, 1) == ":") $file = substr($file, 3);
					$file = str_replace(PATH_DELIM, "/", $file);
				}

				if (substr($file, 0, 1) == "/") $file = substr($file, 1);
				$dir_list = explode("/", $file);

				# For each local directory element, loop through contents of 
				# the FTP root directory.  If the current element is in FTP root,
				# then the parent of the current element is the root.
				# $ftp_root starts at root and has the current directory appended
				# at the end of each outer loop iteration.  Thus, $ftp_root 
				# always holds the parent of the currently processing directory.
				# Note that we must account for Windows drive letters.
				if (PATH_DELIM == "/") {
					$ftp_root = "/";
				} else {
					$ftp_root = $drive.PATH_DELIM;
				}
				foreach ($dir_list as $dir) {
					foreach ($ftp_list as $ftpdir) {
						if ($dir == $ftpdir && $ftpdir != ".." && $ftpdir != ".") {
							break 2;
						}
					}
					$ftp_root .= $dir.PATH_DELIM;
				}
				
				# Now check that the result we got is OK.
				$ftp->ftp_root = $ftp_root;
				$dir_list = ftp_nlist($ftp->connection, 
				                      $ftp->localpathToFSPath(getcwd()));
				if (! is_array($dir_list)) $dir_list = array();

				foreach ($dir_list as $ent) {
					if ("fs_setup.php" == basename($ent)) {
						$ftp_root_test_result = $ftp_root;
						$has_all_data = true;
					} 
				}
			}
		}

		if ($has_all_data) {

			if ( trim(POST($pwd)) == trim(POST($conf)) ) {
				
				define("FS_PLUGIN", "ftpfs");
				define("FTPFS_USER", trim(POST($uid)) );
				define("FTPFS_PASSWORD",trim( POST($pwd)) );
				define("FTPFS_HOST", trim(POST($host)) );
				if (isset($ftp_root_test_result)) {
					$ftproot = $ftp_root_test_result;
				} else {
					$ftproot = trim(POST($root));
					$ftproot = get_magic_quotes_gpc() ? 
					           stripslashes($ftproot) : $ftproot;
				}
				define("FTP_ROOT", $ftproot);
				if (trim(POST($pref)) != '') {
					define("FTPFS_PATH_PREFIX", trim(POST($pref)) );
				} 
				
				$content = "<?php\n";
				if (POST($docroot)) {
					$webroot = trim(POST($docroot));
					$webroot = get_magic_quotes_gpc() ? 
					           stripslashes($webroot) : $webroot;
					
					define("DOCUMENT_ROOT", $webroot );
					$content .= 'define("DOCUMENT_ROOT", "'.DOCUMENT_ROOT."\");\n";
				}
				$content .= 'define("FS_PLUGIN", "'.FS_PLUGIN."\");\n".
					'define("FTPFS_USER", "'.FTPFS_USER."\");\n".
					'define("FTPFS_PASSWORD", "'.FTPFS_PASSWORD."\");\n".
					'define("FTPFS_HOST", "'.FTPFS_HOST."\");\n".
					'define("FTP_ROOT", "'.FTP_ROOT."\");\n";
				if ( trim(POST($pref)) != '' ) {
					$content .= 'define("FTPFS_PATH_PREFIX", "'.FTPFS_PATH_PREFIX."\");\n";
				}
				$content .= '?>';

			} else {
				$tpl->set("FORM_MESSAGE", _("Error: Passwords do not match."));
			}
		} elseif (trim(POST($pwd)) != trim(POST($conf))) {
			$tpl->set("FORM_MESSAGE", _("Error: Passwords do not match."));
		} elseif (isset($ftp_root)) {
			$tpl->set("FORM_MESSAGE", spf_("Error: The auto-detected FTP root directory %s was not acceptable.  You will have to set this manually.", $ftp_root));
		} else {
			$tpl->set("FORM_MESSAGE", _("Error: For FTP file writing, you must fill in the FTP login information."));
		}
	
	} else {
		$tpl->set("FORM_MESSAGE", _("Error: No file writing method selected."));
	}

	if ($content) {
	
		@$fs = NewFS();
		$content = str_replace('\\', '\\\\', $content);
		
		# Try to create the fsconfig file.  Suppress error messages so users
		# don't get scared by expected permissions problems.
		if (! is_dir(INSTALL_ROOT.PATH_DELIM.USER_DATA)) {
			@$ret = $fs->mkdir_rec(INSTALL_ROOT.PATH_DELIM.USER_DATA);
		}
		if (is_dir(INSTALL_ROOT.PATH_DELIM.USER_DATA)) {
			@$ret = $fs->write_file(INSTALL_ROOT.PATH_DELIM.USER_DATA.PATH_DELIM.FS_PLUGIN_CONFIG, $content);
			$fs->destruct();
		}
		
		if (! $ret) {
			if (FS_PLUGIN == "ftpfs") {
				$tpl->set("FORM_MESSAGE", sprintf(
					_("Error: Could not create fsconfig.php file.  Make sure that 
					the directory %s exists on the server and is writable to %s."),
					INSTALL_ROOT.PATH_DELIM.USER_DATA, FTPFS_USER));
			} else {
				$tpl->set("FORM_MESSAGE", sprintf(
					_("Error: Could not create fsconfig.php file.  Make sure that 
					the directory %s exists on the server and is writable to 
					the web server user."), INSTALL_ROOT.PATH_DELIM.USER_DATA));
			}
		} else {
			header("Location: index.php");
			exit;
		}
		
	} else {
		if (! $tpl->varSet("FORM_MESSAGE") ) {
			$tpl->set("FORM_MESSAGE", _("Unexpected error: missing data?"));
		}
	}
	
} else {
	$tpl->set("HOST", "localhost");  
	$tpl->set("DOC_ROOT", calculate_document_root() );
}

$body = $tpl->process();
$PAGE->addStylesheet("form.css");
$PAGE->addScript("fssetup.js");
$PAGE->display($body, &$blog);
?>
