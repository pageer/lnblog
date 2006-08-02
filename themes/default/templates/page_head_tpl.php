<?php
# File: page_head_tpl.php
# This template takes care of the essential structure of the HTML document.
# It includes the DOCTYPE (default is XHTML 1.0 Strict), outer html element, 
# page head section including title, and adds any link and meta elements that 
# have been set.
#
# The file also has code to reference stylesheets and scripts, both as linked
# files and as inline text.  There is also code to add links for RSS feeds (as
# opposed to general link elements).
#
# Unless, for some reason, you need to make changes to the basic structure of 
# the page head, you should not need to modify this file.  The structure of the 
# page body, which you may very well want to change, is in the 
# <basic_layout_tpl.php> template.

echo $DOCTYPE;
$xml_lang = str_replace("_", "-", LANGUAGE); ?>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $xml_lang; ?>" lang="<?php echo $xml_lang; ?>">
<head>
<title><?php echo $PAGE_TITLE; ?></title>
<?php foreach ($METADATA as $meta) { ?>
<meta <?php if ($meta["http-equiv"]) { ?>http-equiv="<?php echo $meta["http-equiv"]; ?>"<?php } 
?> <?php if ($meta["name"]) { ?>name="<?php echo $meta["name"]; ?>"<?php } ?> content="<?php echo $meta["content"]; ?>" />
<?php } ?>
<?php foreach ($LINKS as $link) { ?>
<link <?php foreach ($link as $attrib=>$val) echo $attrib.'="'.$val.'" ';?>/>
<?php } ?>
<?php foreach ($RSSFEEDS as $rssel) { ?>
<link rel="alternate" title="<?php echo $rssel["title"]; ?>" type="<?php echo $rssel["type"]; ?>" href="<?php echo $rssel["href"]; ?>" />
<?php } ?>
<?php 
foreach ($STYLESHEETS as $css) { 
	$link = getlink($css, LINK_STYLESHEET); 
	if ($link) { ?>
<link rel="stylesheet" type="text/css" href="<?php echo $link; ?>" />
<?php 
	} 
}
foreach ($INLINE_STYLESHEETS as $css) { ?>
<style type="text/css">
<?php echo $css; ?>
</style>
<?php 
}
foreach ($SCRIPTS as $js) {
	$link = getlink($js["href"], LINK_SCRIPT); 
	if ($link) { ?>
<script type="<?php echo $js["type"]; ?>" src="<?php echo $link; ?>"></script>
<?php 
	} 
} 
foreach ($INLINE_SCRIPTS as $js) { ?>
<script type="<?php echo $js["type"]; ?>">
<?php echo $js["text"]; ?>
</script>
<?php } ?>
</head>
<?php include(BASIC_LAYOUT_TEMPLATE); ?>
</html>
