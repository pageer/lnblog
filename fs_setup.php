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

# Test how and if native file writing works.
function nativefs_test() {
	
	$ret = array('write'=>false, 'delete'=>false, 
	             'user'=>'', 'group'=>'', 
	             'summary'=>'');
	
	$stat_data = false;
	
	$f = @fopen("tempfile.tmp", "w");
	if ($f !== false) {

		$old = umask(0777);
		$can_write = fwrite($f, "Test");
		fclose($f);
		umask($old);
		
		$stat_data = stat("tempfile.tmp");
		if ($stat_data) {
			$ret['user'] = $stat_data['uid'];
			$ret['group'] = $stat_data['gid'];
		}
		
		$ret['delete'] = @unlink("tempfile.tmp");
	}
	
	$ret['summary'] = _("NativeFS test results:")."<br />".
		spf_("Create new files: %s", $ret['write'] ? "yes" : "no")."<br />".
		spf_("Delete files: %s", $ret['delete'] ? "yes" : "no")."<br />".
		spf_("File owner: %s", $ret['user'])."<br />".
		spf_("File group: %s", $ret['group']);

	return $ret;
}

# Test to autodetect the FTP root directory for the given account.
function ftproot_test() {
	require("lib/ftpfs.php");
	@$ftp = new FTPFS(trim(POST("ftp_host")), 
	                  trim(POST("ftp_user")), trim(POST("ftp_pwd")) );
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
				return $ftp_root;
			} 
		}
	}
	return false;
}

# Check that all required fields have been populated by the user.
function check_fields() {
	$errs = array();	
	$plugin = trim(POST('use_ftp'));
	
	if (trim(POST("docroot")) != '') $errs[] = _("No document root set.");
	
	$ret = (POST('use_ftp') == 'ftpfs' || POST('use_ftp') == 'nativefs');
	if (! $ret) $errs[] = _("Invalid file writing mode.");
	
	$ret = is_numeric(POST('permdir')) && strlen(POST('permdir')) == 4;
	$ret = $ret && is_numeric(POST('permscript')) && strlen(POST('permscript')) == 4;
	$ret = $ret && is_numeric(POST('permfile')) && strlen(POST('permfile')) == 4;
	if (! $ret) $errs[] = _("Invalid permissions specified.");
	
	
	if ($plugin == 'nativefs') {
		# Nothing to do?
	} elseif ($plugin == 'ftpfs') {
		if (trim(POST('ftp_user')) == '') $errs[] = _("Missing FTP username.");
		if (trim(POST('ftp_pwd')) == '') $errs[] = _("Missing FTP password.");
		if (trim(POST('ftp_conf')) == '') $errs[] = _("Missing FTP password confirmation.");
		if (trim(POST('ftp_host')) == '') $errs[] = _("Missing FTP hostname.");
		
		if (POST('ftp_pwd') != POST('ftp_conf')) $errs[] = _("FTP passwords do not match.");
	}
	
	if (count($errs) > 0) return $errs;
	else return true;
}

function template_set_post_data(&$tpl) {
	$tpl->set("DOC_ROOT", POST("docroot") );
	if (POST("use_ftp") == "ftpfs") $tpl->set("USE_FTP", POST("use_ftp") );
	$tpl->set("USER", POST("ftp_user") );
	$tpl->set("PASS", POST("ftp_pwd") );
	$tpl->set("CONF", POST("ftp_conf") );
	$tpl->set("HOST", POST("ftp_host") );
	$tpl->set("ROOT", POST("ftp_root") );
	$tpl->set("PREF", POST("ftp_prefix") );
	$tpl->set("HOSTTYPE", POST("hosttype"));
	$tpl->set("PERMDIR", POST('permdir'));
	$tpl->set("PERMSCRIPT", POST('permscript'));
	$tpl->set("PERMFILE", POST('permfile'));
}

function serialize_constants() {
	$ret = '';
	$consts = array("DOCUMENT_ROOT", "SUBDOMAIN_ROOT", "DOMAIN_NAME",
	                "FS_PLUGIN", "FTPFS_USER", 
	                "FTPFS_PASSWORD", "FTPFS_HOST",
	                "FTP_ROOT", "FTPFS_PATH_PREFIX",
	                "FS_DEFAULT_MODE", "FS_SCRIPT_MODE", "FS_DIRECTORY_MODE");
	foreach ($consts as $c) {
		if (defined($c)) {
			if (is_numeric(constant($c))) {
				$ret .= 'define("'.$c.'", '.constant($c).');'."\n";
			} else {
				$ret .= 'define("'.$c.'", "'.constant($c).'");'."\n";
			}
		}
	}
	if ($ret) $ret = "<?php\n$ret?>";
	return $ret;
}

global $PAGE;

if ( file_exists(USER_DATA_PATH.PATH_DELIM.FS_PLUGIN_CONFIG) ) {
	header("Location: index.php");
	exit;
}

$PAGE->title = sprintf(_("%s File Writing"), PACKAGE_NAME);
$form_title = _("Configure File Writing Support");
$redir_page = "index.php";

$tpl = NewTemplate(FS_CONFIG_TEMPLATE);

$tpl->set("FORM_ACTION", basename(SERVER("PHP_SELF")) );

if ( has_post() ) {

	template_set_post_data($tpl);
	$field_test = check_fields();

	# Note that DOCUMENT_ROOT is not strictly required, as all uses 
	# of it should be wrapped in a document root calculation function
	# for legacy versions.  However....

	$fields = array("docroot"=>"DOCUMENT_ROOT", "subdomroot"=>"SUBDOMAIN_ROOT",
	                "domain"=>"DOMAIN_NAME", "permfile"=>"FS_DEFAULT_MODE",
	                "permdir"=>"FS_DIRECTORY_MODE", 
	                "permscript"=>"FS_SCRIPT_MODE");
	foreach ($fields as $key=>$val) {
		if (POST($key)) {
			if (preg_match("/FS_.*_MODE/", $val)) {
				$num = trim(POST($key));
				$num = octdec((int)$num);
				define($val, $num);
			} else {
				define($val, trim(POST($key)));
			}
		}
	}
	
	if ( POST("use_ftp") == "nativefs" ) {

		define("FS_PLUGIN", "nativefs");

	} elseif ( POST("use_ftp") == "ftpfs" ) {

		# Check that all required fields have been specified.
		$vars = array("ftp_user", "ftp_pwd", "ftp_conf", "ftp_host", "ftp_root");
		$has_all_data = true;
		foreach ($vars as $val) {
			$has_all_data = $has_all_data && ( trim(POST($val)) != "" );
		}

		# Make a vain attempt to guess the FTP root.
		if ( trim(POST("ftp_user")) && trim(POST("ftp_pwd")) && trim(POST("ftp_conf")) 
		     && trim(POST("ftp_host")) && trim(POST("ftp_pwd")) == trim(POST("ftp_conf"))
			  && ! trim(POST("ftp_root")) ) {
			$ftp_root_test_result = ftproot_test();
		}

		if ($has_all_data) {

			if ( trim(POST("ftp_pwd")) == trim(POST("ftp_conf")) ) {
				
				define("FS_PLUGIN", "ftpfs");
				define("FTPFS_USER", trim(POST("ftp_user")) );
				define("FTPFS_PASSWORD",trim( POST("ftp_pwd")) );
				define("FTPFS_HOST", trim(POST("ftp_host")) );
				if (isset($ftp_root_test_result)) {
					$ftproot = $ftp_root_test_result;
				} else {
					$ftproot = trim(POST("ftp_root"));
				}
				define("FTP_ROOT", $ftproot);
				if (trim(POST("ftp_prefix")) != '') {
					define("FTPFS_PATH_PREFIX", trim(POST("ftp_prefix")) );
				} 

			} else {
				$tpl->set("FORM_MESSAGE", _("Error: Passwords do not match."));
			}
		} elseif (trim(POST("ftp_pwd")) != trim(POST("ftp_conf"))) {
			$tpl->set("FORM_MESSAGE", _("Error: Passwords do not match."));
		} elseif (isset($ftp_root)) {
			$tpl->set("FORM_MESSAGE", spf_("Error: The auto-detected FTP root directory %s was not acceptable.  You will have to set this manually.", $ftp_root));
		} else {
			$tpl->set("FORM_MESSAGE", _("Error: For FTP file writing, you must fill in the FTP login information."));
		}
		
	} else {
		$tpl->set("FORM_MESSAGE", _("Error: No file writing method selected."));
	}

	$content = serialize_constants();
	
	if ($content) {
	
		@$fs = NewFS();
		$content = str_replace('\\', '\\\\', $content);
		
		# Try to create the fsconfig file.  Suppress error messages so users
		# don't get scared by expected permissions problems.
		if (! is_dir(USER_DATA_PATH)) {
			@$ret = $fs->mkdir_rec(USER_DATA_PATH);
		}
		if (is_dir(USER_DATA_PATH)) {
			$ret = $fs->write_file(USER_DATA_PATH.PATH_DELIM.FS_PLUGIN_CONFIG, $content);
		}
		
		if (! $ret) {
			if (FS_PLUGIN == "ftpfs") {
				$tpl->set("FORM_MESSAGE", sprintf(
					_("Error: Could not create fsconfig.php file.  Make sure that the directory %s exists on the server and is writable to %s."),
					USER_DATA_PATH, FTPFS_USER));
			} else {
				$tpl->set("FORM_MESSAGE", sprintf(
					_("Error: Could not create fsconfig.php file.  Make sure that the directory %s exists on the server and is writable to the web server user."), USER_DATA_PATH));
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
	$tpl->set("HOSTTYPE", "suexec");
	$tpl->set("HOST", "localhost");  
	$tpl->set("DOC_ROOT", calculate_document_root() );
	$tpl->set("PERMDIR", '0000');
	$tpl->set("PERMSCRIPT", '0000');
	$tpl->set("PERMFILE", '0000');

}

$body = $tpl->process();
$PAGE->addStylesheet("form.css");
#$PAGE->addScript("lnblog_lib.js");
$PAGE->addScript("fs_setup.js");
$PAGE->display($body);
?>
