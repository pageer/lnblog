<?php

# This file is (loosely) based on the client.php test script that ships with 
# XML-RPC for PHP 1.2.1.

# File: metaweblog_test.php
# This is a simple test harness for the Blogger 1.0 API implementation.
# You can use this to try out the blogger API.  Note that this file will just
# show you the debugging output from an API call rather than attempting to 
# pretty-print it.  This is because I didn't feel like writing the 
# pretty-printing code and because most developers will be more interested in 
# the debugging output anyway.

# Variable: $LNBLOG_PATH
# Add the path to your LnBlog folder here.  Include the leading slash, but not 
# the host name.
$LNBLOG_PATH = '/LnBlog';
?>
<html>
<head><title>MetaWeblog API Test</title></head>
<body>
<form method="post" enctype="multipart/form-data">
<div>Username:<input type="text" name="username" value="<?php
echo isset($_POST['username'])?$_POST['username']:'';?>" /></div>
<div>Password:<input type="password" name="password" value="<?php
echo isset($_POST['password'])?$_POST['password']:'';?>" /></div>
<div>Post/blog ID:<input type="text" name="entid" value="<?php
echo isset($_POST['entid'])?$_POST['entid']:'';?>" /></div>
<div>Upload:<input type="file" name="fileid" /></div>
<textarea name="data"></textarea>
<div>
<input type="submit" name="new" value="New Post" />
<input type="submit" name="edit" value="Edit Post" />
<input type="submit" name="getpost" value="Get Post" />
<input type="submit" name="getcat" value="Get Categories" />
<input type="submit" name="getrec" value="Get Recent" />
<input type="submit" name="upld" value="Upload file" />
</div>
</form>
<?php

function entrystruct($text) {
	$arr = array();
	$arr['title'] = new xmlrpcval(date("r"), 'string');
	$cats = array(new xmlrpcval('Test category','string'), 
	              new xmlrpcval('Post-fu','string')); 
	$arr['category'] = new xmlrpcval($cats,'array');
	$arr['description'] = new xmlrpcval($text, 'string');
	return new xmlrpcval($arr, 'struct');
}

function uploadstruct() {
	$arr = array();
	$arr['name'] = new xmlrpcval($_FILES['fileid']['name'], 'string');
	$arr['type'] = new xmlrpcval('application/binary', 'string');
	$hnd = fopen($_FILES['fileid']['tmp_name'], 'r');
	$data = fread($hnd, filesize($_FILES['fileid']['tmp_name']));
	$arr['bits'] = new xmlrpcval(base64_encode($data), 'string');
	if ($_POST['data']) $arr['entryid'] = new xmlrpcval($_POST['data'], 'string');
	return new xmlrpcval($arr, 'struct');
}

if (! empty($_POST)) {

	include("xmlrpc/xmlrpc.inc");

		if (isset($_POST['new'])) {
			$arr = array(new xmlrpcval($_POST['entid'], "string"),
			             new xmlrpcval($_POST['username'], 'string'),
			             new xmlrpcval($_POST['password'], 'string'),
			             entrystruct($_POST['data']),
			             new xmlrpcval(true, 'boolean'));
			$msg = 'metaWeblog.newPost';
		} elseif (isset($_POST['edit'])) {
			echo "Doing editPost\n";
			$arr = array(new xmlrpcval($_POST['entid'], "string"),
			             new xmlrpcval($_POST['username'], 'string'),
			             new xmlrpcval($_POST['password'], 'string'),
			             entrystruct($_POST['data']),
			             new xmlrpcval(true, 'boolean'));
			$msg = 'metaWeblog.editPost';
		} elseif (isset($_POST['getpost'])) {
			echo "Doing getPost\n";
			$arr = array(new xmlrpcval($_POST['entid'], "string"),
			             new xmlrpcval($_POST['username'], 'string'),
			             new xmlrpcval($_POST['password'], 'string'));
			$msg = 'metaWeblog.getPost';
		} elseif (isset($_POST['getcat'])) {
			$arr = array(new xmlrpcval($_POST['entid'], "string"),
			             new xmlrpcval($_POST['username'], 'string'),
			             new xmlrpcval($_POST['password'], 'string'));
			$msg = 'metaWeblog.getCategories';
		} elseif (isset($_POST['getrec'])) {
			$arr = array(new xmlrpcval($_POST['entid'], "string"), 
			             new xmlrpcval($_POST['username'], 'string'),
			             new xmlrpcval($_POST['password'], 'string'),
			             new xmlrpcval($_POST['data'], 'string'));
			$msg = 'metaWeblog.getRecentPosts';
		} elseif (isset($_POST['upld']) && isset($_FILES['fileid']) && isset($_POST['entid'])) {
			$arr = array(new xmlrpcval($_POST['entid'], "string"),
			             new xmlrpcval($_POST['username'], 'string'),
			             new xmlrpcval($_POST['password'], 'string'),
			             uploadstruct());
			$msg = 'metaWeblog.newMediaObject';
		} else {
			echo "Qua?";
			exit;
		}

		$f = new xmlrpcmsg($msg, $arr);
			 
		$c = new xmlrpc_client($LNBLOG_PATH."/metaweblog.php", "localhost", 80);
		$c->setDebug(1);
		$r = $c->send($f);
		$v = $r->value();
		if (!$r->faultCode())
		{
			echo "It worked?";
		}
		else
		{
			echo "Fault: ";
			echo "Code: " . htmlentities($r->faultCode())
				. " Reason '" .htmlentities($r->faultString())."'<BR>";
		}	
}
?>
</body>
</html>
