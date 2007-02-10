<?php

# This file is (loosely) based on the client.php test script that ships with 
# XML-RPC for PHP 1.2.1.

# File: blogger1_test.php
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
<head><title>Blogger API Test</title></head>
<body>
<form method="post">
<div>Username:<input type="text" name="username" value="<?php
echo isset($_POST['username'])?$_POST['username']:'';?>" /></div>
<div>Password:<input type="password" name="password" value="<?php
echo isset($_POST['password'])?$_POST['password']:'';?>" /></div>
<div>Post/blog ID:<input type="text" name="entid" value="<?php
echo isset($_POST['entid'])?$_POST['entid']:'';?>" /></div>
<textarea name="data"></textarea>
<div>
<input type="submit" name="new" value="New Post" />
<input type="submit" name="edit" value="Edit Post" />
<input type="submit" name="getblogs" value="Get User Blogs" />
<input type="submit" name="getuser" value="Get User Info" />
</div>
</form>
<?php

if (! empty($_POST)) {
	include("xmlrpc/xmlrpc.inc");

		if (isset($_POST['new'])) {
			$arr = array(new xmlrpcval("1234567", "string"), 
			             new xmlrpcval($_POST['entid'], "string"),
							 new xmlrpcval($_POST['username'], 'string'),
							 new xmlrpcval($_POST['password'], 'string'),
							 new xmlrpcval($_POST['data'], 'string'),
							 new xmlrpcval(true, 'boolean'));
			$msg = 'blogger.newPost';
		} elseif (isset($_POST['edit'])) {
			echo "Doing editPost\n";
			$arr = array(new xmlrpcval("1234567", "string"), 
			                new xmlrpcval($_POST['entid'], "string"),
							 new xmlrpcval($_POST['username'], 'string'),
							 new xmlrpcval($_POST['password'], 'string'),
							 new xmlrpcval($_POST['data'], 'string'),
							 new xmlrpcval(true, 'boolean'));
			$msg = 'blogger.editPost';
		} elseif (isset($_POST['getblogs'])) {
			$arr = array(new xmlrpcval("1234567", "string"), 
							 new xmlrpcval($_POST['username'], 'string'),
							 new xmlrpcval($_POST['password'], 'string'));
			$msg = 'blogger.getUsersBlogs';
		} elseif (isset($_POST['getuser'])) {
			$arr = array(new xmlrpcval("1234567", "string"), 
							 new xmlrpcval($_POST['username'], 'string'),
							 new xmlrpcval($_POST['password'], 'string'));
			$msg = 'blogger.getUserInfo';
		} else {
			echo "Qua?";
			exit;
		}

		$f = new xmlrpcmsg($msg, $arr);
			 
		$c = new xmlrpc_client($LNBLOG_PATH."/blogger.php", "localhost", 80);
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
