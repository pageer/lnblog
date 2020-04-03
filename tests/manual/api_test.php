<?php

# This file is (loosely) based on the client.php test script that ships with 
# XML-RPC for PHP 1.2.1.

# This is a simple test harness for the Blogger 1.0 API implementation.
# You can use this to try out the blogger API.  Note that this file will just
# show you the debugging output from an API call rather than attempting to 
# pretty-print it.  This is because I didn't feel like writing the 
# pretty-printing code and because most developers will be more interested in 
# the debugging output anyway.

$LNBLOG_PATH = dirname(dirname(dirname($_SERVER['SCRIPT_NAME'])));
$HOSTNAME = isset($_GET['host']) ? $_GET['host'] : 'localhost';
$PORT = isset($_GET['port']) ? $_GET['port'] : 80;

require __DIR__.'/../../vendor/autoload.php';
require '../../vendor/phpxmlrpc/phpxmlrpc/lib/xmlrpc.inc';
require '../../vendor/phpxmlrpc/phpxmlrpc/lib/xmlrpcs.inc';
require '../../vendor/phpxmlrpc/phpxmlrpc/lib/xmlrpc_wrappers.inc';
?>
<html>
<head><title>MetaWeblog API Test</title></head>
<body>
<form method="post" enctype="multipart/form-data"
    action="?port=<?php echo $PORT?>&$host=<?php echo $HOSTNAME?>">
<div>Username:<input type="text" name="username" value="<?php
echo isset($_POST['username'])?$_POST['username']:'';?>" /></div>
<div>Password:<input type="password" name="password" value="<?php
echo isset($_POST['password'])?$_POST['password']:'';?>" /></div>
<div>Post/blog ID:<input type="text" name="entid" value="<?php
echo isset($_POST['entid'])?$_POST['entid']:'';?>" /></div>
<div>Upload:<input type="file" name="fileid" /></div>
<textarea name="data"><?php echo isset($_POST['data'])?$_POST['data']:'';?></textarea>
<div>
<input type="submit" name="btn" value="blogger.newPost" />
<input type="submit" name="btn" value="blogger.deletePost" />
<input type="submit" name="btn" value="blogger.editPost" />
<input type="submit" name="btn" value="blogger.getUsersBlogs" />
<input type="submit" name="btn" value="blogger.getUserInfo" />
<input type="submit" name="btn" value="blogger.setTemplate" />
<input type="submit" name="btn" value="blogger.getTemplate" />
<br />
<input type="submit" name="btn" value="metaWeblog.newPost" />
<input type="submit" name="btn" value="metaWeblog.deletePost" />
<input type="submit" name="btn" value="metaWeblog.editPost" />
<input type="submit" name="btn" value="metaWeblog.getPost" />
<input type="submit" name="btn" value="metaWeblog.getRecentPosts" />
<input type="submit" name="btn" value="metaWeblog.getCategories" />
<input type="submit" name="btn" value="metaWeblog.newMediaObject" />
<br />
<input type="submit" name="btn" value="mt.getRecentPostTitles" />
<input type="submit" name="btn" value="mt.getCategoryList" />
<input type="submit" name="btn" value="mt.getPostCategories" />
<input type="submit" name="btn" value="mt.setPostCategories" />
<input type="submit" name="btn" value="mt.supportedMethods" />
<input type="submit" name="btn" value="mt.supportedTextFilters" />
<input type="submit" name="btn" value="mt.getTrackbackPings" />
<input type="submit" name="btn" value="mt.publishPost" />
<br />
<input type="submit" name="btn" value="slv" />
</div>
</form>
<?php

function entrystruct($text) {
    $arr = array();
    $arr['title'] = new xmlrpcval(date("r"), 'string');
    $cats = array(new xmlrpcval('Test category','string'), 
                  new xmlrpcval('Post-fu','string')); 
    $arr['categories'] = new xmlrpcval($cats,'array');
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

if ( isset($_POST['btn'])) {

    $method = $_POST['btn'];
    $params = array();

    switch ($method) {
        case "blogger.newPost":
            $params = array(new xmlrpcval("1234567", "string"), 
                            new xmlrpcval($_POST['entid'], "string"),
                            new xmlrpcval($_POST['username'], 'string'),
                            new xmlrpcval($_POST['password'], 'string'),
                            new xmlrpcval($_POST['data'], 'string'),
                            new xmlrpcval(true, 'boolean'));
            break;
        case "blogger.deletePost":
        case "metaWeblog.deletePost":
            $params = array(new xmlrpcval("1234567", "string"), 
                            new xmlrpcval($_POST['entid'], "string"),
                            new xmlrpcval($_POST['username'], 'string'),
                            new xmlrpcval($_POST['password'], 'string'),
                            new xmlrpcval(true, 'boolean'));
            break;
        case "blogger.editPost":
            $params = array(new xmlrpcval("1234567", "string"), 
                            new xmlrpcval($_POST['entid'], "string"),
                            new xmlrpcval($_POST['username'], 'string'),
                            new xmlrpcval($_POST['password'], 'string'),
                            new xmlrpcval($_POST['data'], 'string'),
                            new xmlrpcval(true, 'boolean'));
            break;
        case "blogger.getUsersBlogs":
        case "blogger.getUserInfo":
            $params = array(new xmlrpcval("1234567", "string"), 
                            new xmlrpcval($_POST['username'], 'string'),
                            new xmlrpcval($_POST['password'], 'string'));
            break;
        case "blogger.setTemplate":
        case "blogger.getTemplate":
        case "metaWeblog.newPost":
            $params = array(new xmlrpcval($_POST['entid'], "string"),
                            new xmlrpcval($_POST['username'], 'string'),
                            new xmlrpcval($_POST['password'], 'string'),
                            entrystruct($_POST['data']),
                            new xmlrpcval(true, 'boolean'));
            break;
        case "metaWeblog.editPost":
            $params = array(new xmlrpcval($_POST['entid'], "string"),
                            new xmlrpcval($_POST['username'], 'string'),
                            new xmlrpcval($_POST['password'], 'string'),
                            entrystruct($_POST['data']),
                            new xmlrpcval(true, 'boolean'));
            break;
        case "metaWeblog.getPost":
        case "metaWeblog.getCategories":
            $params = array(new xmlrpcval($_POST['entid'], "string"),
                            new xmlrpcval($_POST['username'], 'string'),
                            new xmlrpcval($_POST['password'], 'string'));
            break;
        case "metaWeblog.getRecentPosts":
        case "mt.getRecentPostTitles":
            $numposts = is_numeric($_POST['data']) ? $_POST['data'] : 5;
            $params = array(new xmlrpcval($_POST['entid'], "string"), 
                            new xmlrpcval($_POST['username'], 'string'),
                            new xmlrpcval($_POST['password'], 'string'),
                            new xmlrpcval($numposts, 'int'));
            break;
        case "metaWeblog.newMediaObject":
            $params = array(new xmlrpcval($_POST['entid'], "string"),
                            new xmlrpcval($_POST['username'], 'string'),
                            new xmlrpcval($_POST['password'], 'string'),
                            uploadstruct());
            break;
        case "mt.getCategoryList":
        case "mt.getPostCategories":
            $params = array(new xmlrpcval($_POST['entid'], "string"), 
                            new xmlrpcval($_POST['username'], 'string'),
                            new xmlrpcval($_POST['password'], 'string'));
            break;
        case "mt.setPostCategories":
            $cats = explode(',', $_POST['data']);
            $catarr = array();
            foreach ($cats as $c) {
                $ent = array();
                $ent['categoryId'] = new xmlrpcval($c, 'string');
                $ent['isPrimary'] = new xmlrpcval(false, 'boolean');
                $catarr[] = new xmlrpcval($ent, 'struct');
            }
            $params = array(new xmlrpcval($_POST['entid'], "string"), 
                            new xmlrpcval($_POST['username'], 'string'),
                            new xmlrpcval($_POST['password'], 'string'),
                            new xmlrpcval($catarr, 'array'));
            break;
        case "mt.supportedMethods":
        case "mt.supportedTextFilters":
        case "mt.getTrackbackPings":
            $params = array(new xmlrpcval($_POST['entid'], "string"));
            break;
        case "mt.publishPost":
        case "slv":
            $params = array(new xmlrpcval($_POST['data'], 'string'));
    }

    $f = new xmlrpcmsg($method, $params);
    if ($method == 'slv') {
        $c = new xmlrpc_client("/slv.php", "www.linksleeve.org", $PORT);
    } else {
        $c = new xmlrpc_client($LNBLOG_PATH."/xmlrpc.php", "localhost", $PORT);
    }
    $c->setDebug(1);
    $r = $c->send($f);
    $v = $r->value();
    if (!$r->faultCode()) {
        echo "It worked?";
    } else {
        echo "Fault: ";
        echo "Code: " . htmlentities($r->faultCode())
            . " Reason '" .htmlentities($r->faultString())."'<BR>";
    }   
}
?>
</body>
</html>
