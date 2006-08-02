<?php 

# File: ftproot_test.php
# This is a one-off script to test values of FTP_ROOT for FTPFS.
#
# This page *requires* users to enter a username, password, and host for 
# the FTP server.  It will then try to guess a value for FTP_ROOT or allow
# the user to enter a value to test.
#
# Like <docroot_test.php>, this file will test for accessibility of the 
# LnBlog ReadMe file.  It will build a local path and an FTP path using the
# given FTP_ROOT.  The idea is that if both paths exist, then the 
# FTP_ROOT is correct.

$EXCLUDE_FS = true;
require_once("blogconfig.php");
require_once("lib/creators.php");
require_once("lib/utils.php");
require_once("lib/ftpfs.php");

# Takes an FTPFS instance and tests if a given file can be reached with it.

function ftp_file_exists($file, $ftp_obj) {

	$dir_list = ftp_nlist($ftp_obj->connection, $ftp_obj->localpathToFSPath(dirname($file)));
	if (! is_array($dir_list)) $dir_list = array();
	
	foreach ($dir_list as $ent) {
		if (basename($file) == basename($ent)) {
			#echo basename($file)." == $ent<br />";
			return true;
		} #else echo basename($file)." != $ent<br />";
	}
	return false;
}

# Takes a file or directory on the local host and an FTP connection.
# Connects to the FTP server, changes to the root directory, and
# checks the directory listing.  It then goes down the local directory 
# tree until it finds a directory that contains one of the entries in the
# listing.  This directory is the FTP root.

function find_dir($dir, $conn) {
	# Change to the root directory.
	ftp_chdir($conn, "/");
	$ftp_list = ftp_nlist($conn, ".");

	# Save the drive letter (if it exists).
	$drive = substr($dir, 0, 2);

	# Get the current path into an array.
	if (PATH_DELIM != "/") {
		if (substr($dir, 1, 1) == ":") $dir = substr($dir, 3);
		$dir = str_replace(PATH_DELIM, "/", $dir);
	}

	if (substr($dir, 0, 1) == "/") $dir = substr($dir, 1);
	$dir_list = explode("/", $dir);

	# For each local directory element, loop through contents of the FTP
	# root directory.  If the current element is in FTP root, then the 
	# parent of the current element is the root.
	# $ftp_root starts at root and has the current directory appended at the
	# end of each outer loop iteration.  Thus, $ftp_root always holds the 
	# parent of the currently processing directory.
	# Note that we must account for Windows drive letters, grubmle, grumble.
	if (PATH_DELIM == "/") {
		$ftp_root = "/";
	} else {
		$ftp_root = $drive.PATH_DELIM;
	}
	foreach ($dir_list as $dir) {
		foreach ($ftp_list as $ftpdir) {
			if ($dir == $ftpdir && $ftpdir != ".." && $ftpdir != ".") {
				return $ftp_root;
			}
		}
		$ftp_root .= $dir.PATH_DELIM;
	}
	
}

$tpl = NewTemplate(FTPROOT_TEST_TEMPLATE);

$user = trim(POST("uid"));
$tpl->set("USER", $user);
$pass = trim(POST("pwd"));
$tpl->set("PASS", $pass);
$hostname = trim(POST("host"));
$tpl->set("HOSTNAME", $hostname);
$test_file = getcwd().PATH_DELIM."ReadMe.txt";
$tpl->set("TEST_FILE", $test_file);
$tpl->set("TARGETPAGE", current_file());
$ftp_root = "";
$test_status = false;
$ftp_path = "";
$error_message = "";
$curr_dir = getcwd();
$tpl->set("CURR_DIR", $curr_dir);

if ($user && $pass && $hostname) {

	$ftp = new FTPFS($hostname, $user, $pass);
	if ($ftp->status !== false)	{

		if (! POST("ftproot")) {
			$ftp_root = find_dir($test_file, $ftp->connection);
		} else {
			$ftp_root = POST("ftproot");
		}
		$ftp->ftp_root = $ftp_root;
		
		$test_status = ftp_file_exists($test_file, $ftp);
		$ftp_path = $ftp->localpathToFSPath($test_file);

		$ftp->destruct();

	} else $error_message = _("Unable to connect to FTP server.");
}

$tpl->set("FTP_ROOT", $ftp_root);
$tpl->set("FTP_PATH", $ftp_path);
$tpl->set("ERROR_MESSAGE", $error_message);
$tpl->set("TEST_STATUS", $test_status);

echo $tpl->process();
?>
