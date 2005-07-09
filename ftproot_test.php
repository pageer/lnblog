<?php 
$EXCLUDE_FS = true;
require_once("blogconfig.php");
require_once("utils.php");
require_once("ftpfs.php");

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

$user = trim(POST("uid"));
$pass = trim(POST("pwd"));
$hostname = trim(POST("host"));
$test_file = getcwd().PATH_DELIM."Readme.html";
$ftp_root = "";
$test_status = false;
$ftp_path = "";
$error_message = "";
$curr_dir = getcwd();

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

	} else $error_message = "Unable to connect to FTP server.";
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" 
   "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
<title>Test FTP Root</title>
</head>
<body>
<h3>FTP Root Test</h3>
<p>This page will attempt to detect your <abbr title="File Transfer Protocol">FTP</abbr> root.  This is
the root directory for <abbr title="File Transfer Protocol">FTP</abbr> use and is not necessarily the
same as the real system root.  To run the test, enter your FTP username, password, and host name and
click the "Test" button to detect the <abbr title="File Transfer Protocol">FTP</abbr> root.  You can 
also test other <abbr title="File Transfer Protocol">FTP</abbr> root values by filling in the 
"FTP Root" box.</p>
<p>To determine success, check the results section below.  If the last line indicates that the test
file was found, then you can copy the "FTP root" value into the appropriate configuration page.  If it 
indicates that the file was <em>not</em> found, then you will have to try another value.
</p>
<p>Current Directory: <?php echo $curr_dir; ?></p>
<?php if ($error_message) { ?>
<h4><?php echo $error_message; ?></h4>
<?php } ?>
<form method="post" action="<?php echo current_file(); ?>">
<div>
<label for="uid">FTP Username</label>
<input type="text" id="uid" name="uid" value="<?php echo $user; ?>" />
</div>
<div>
<label for="pwd">FTP Password</label>
<input type="password" id="pwd" name="pwd" value="<?php echo $pass; ?>" />
</div>
<div>
<label for="host">FTP host</label>
<input type="text" id="host" name="host" value="<?php echo $hostname; ?>" />
</div>
<div>
<label for="ftproot">FTP Root</label>
<input type="text" id="ftproot" name="ftproot" value="<?php echo $ftp_root; ?>" />
<span>(Optional, leave blank to auto-detect.)</span>
</div>
<div>
<input type="submit" value="Test" />
</form>
<div>
<h3>Results</h3>
<p>
FTP root: <?php echo $ftp_root; ?><br />
Test file path: <?php echo $test_file; ?><br />
FTP path: <?php echo $ftp_path; ?><br />
The test file was
<span style="color: <?php echo $test_status ? "green" : "red"; ?>">
<?php echo $test_status ? "found" : "not found.  Test failed"; ?></span>.</p>
</div>
</body>
</html>
