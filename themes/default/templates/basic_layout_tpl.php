<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" 
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=iso-8859-1" />
<title><?php echo $PAGE_TITLE; ?></title>
<?php 
if (isset($STYLE_SHEETS)) {
	foreach ($STYLE_SHEETS as $sheet) {
?>
<link rel="stylesheet" type="text/css" href="<?php echo $sheet; ?>" />
<?php 
	}
} else { 
?>
<link rel="stylesheet" type="text/css" href="<?php echo THEME_STYLES; ?>/main.css" />
<link rel="stylesheet" type="text/css" href="<?php echo THEME_STYLES; ?>/blog.css" />
<?php } ?>
<?php 
if (isset($SCRIPTS)) { 
	foreach ($SCRIPTS as $js) {
?>
<link type="text/javascript" href="<?php echo $js; ?>" />
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
