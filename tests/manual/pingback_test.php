<?php

# This file is (loosely) based on the client.php test script that ships with 
# XML-RPC for PHP 1.2.1.

$LNBLOG_PATH = dirname(dirname(dirname($_SERVER['SCRIPT_NAME'])));

require __DIR__.'/../../vendor/autoload.php';
?>
<html>
<head><title>Pingback Server Test</title></head>
<body>
<form method="post">
<div>Source URI:<input type="text" name="source" value="<?php
echo isset($_POST['source'])?$_POST['source']:'';?>" /></div>
<div>Target URI:<input type="text" name="target" value="<?php
echo isset($_POST['target'])?$_POST['target']:'';?>" /></div>
<div>
<input type="submit" name="submit" value="Submit" />
</div>
</form>
<?php

if (! empty($_POST)) {

    $msg = 'pingback.ping';
    $arr = array(new xmlrpcval($_POST['source'], 'string'),
                 new xmlrpcval($_POST['target'], 'string'));
    
    $f = new xmlrpcmsg($msg, $arr);
         
    $c = new xmlrpc_client($LNBLOG_PATH."/xmlrpc.php", "localhost", 80);
    $c->setDebug(1);
    $r = $c->send($f);
    $v = $r->value();
    if (!$r->faultCode()) {
        echo "It worked?";
    } else {
        echo "Fault: ";
        echo "Code: " . htmlentities((string) $r->faultCode())
            . " Reason '" .htmlentities($r->faultString())."'<BR>";
    }   
}
?>
</body>
</html>
