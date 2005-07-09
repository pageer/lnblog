<?php if (isset($DOCTYPE)) { echo $DOCTYPE; } else { ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" 
   "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<?php } ?>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<title><?php echo $PAGE_TITLE; ?></title>
<meta http-equiv="Content-type" content="text/html; charset=iso-8859-1" />
<?php if (isset($RDF_FEED)) { # Add RSS 1.0 feed link, if set ?>
<link rel="alternate" title="<?php echo $RDF_FEED_TITLE; ?>" type="application/xml" href="<?php echo $RDF_FEED; ?>" />
<?php } ?>
<?php if (isset($XML_FEED)) { # Add RSS 2.0 feed link, if set  ?>
<link rel="alternate" title="<?php echo $XML_FEED_TITLE; ?>" type="application/rss+xml" href="<?php echo $XML_FEED; ?>" />
<?php } 
# Add in the required stylesheets.  Others are optional. ?>
<link rel="stylesheet" type="text/css" href="<?php echo getlink("main.css", LINK_STYLESHEET); ?>" />
<link rel="stylesheet" type="text/css" href="<?php echo getlink("banner.css", LINK_STYLESHEET); ?>" />
<link rel="stylesheet" type="text/css" href="<?php echo getlink("menubar.css", LINK_STYLESHEET); ?>" />
<link rel="stylesheet" type="text/css" href="<?php echo getlink("sidebar.css", LINK_STYLESHEET); ?>" />
<?php
if (isset($STYLE_SHEETS)) {
	foreach ($STYLE_SHEETS as $sheet) {
?>
<link rel="stylesheet" type="text/css" href="<?php echo getlink($sheet, LINK_STYLESHEET); ?>" />
<?php 
	}
} ?>
<?php 
if (isset($SCRIPTS)) { 
	foreach ($SCRIPTS as $js) {
?>
<script type="text/javascript" src="<?php echo getlink($js, LINK_SCRIPT); ?>"></script>
<?php 
	}
} 
?>
</head>
<body>
<!-- Site banner -->
<div id="banner"><?php include("include_banner.php"); ?></div>
<!-- A menu/navigation bar -->
<div id="menubar"><?php include("include_menubar.php"); ?></div>
<!-- A sidebar -->
<div id="sidebar"><?php include("include_sidebar.php"); ?></div>
<!-- Main page content -->
<div id="pagecontent">
<?php echo $PAGE_CONTENT; ?>
</div>
</body>
</html>
