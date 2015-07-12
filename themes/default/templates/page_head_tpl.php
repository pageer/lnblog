<?php
# File: page_head_tpl.php
# This template takes care of the essential structure of the HTML document.
# It includes the DOCTYPE (default is XHTML 1.0 Strict), outer html element, 
# page head section including title, and adds any link and meta elements that 
# have been set.
#
# The file also has code to reference stylesheets and scripts, both as linked
# files and as inline text.  There is also code to add links for RSS feeds (as
# opposed to general link elements).  Note that all this data is passed to the
# template through the global <Page> object, which in turn gets if from 
# individual page scripts and plugins.  Nothing but the structural HTML is 
# hard-coded in this file.
#
# Unless, for some reason, you need to make changes to the basic structure of 
# the page head, you should not need to modify this file.  The structure of the 
# page body, which you may very well want to change, is in the 
# <basic_layout_tpl.php> template.

echo $DOCTYPE."\n";
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
	if (isset($css['link'])) {
		$link = !empty($css['external']) ? $css['link'] : getlink($css['link'], LINK_STYLESHEET); 
		if ($link) { ?>
<link rel="stylesheet" type="text/css" href="<?php echo $link; ?>" />
<?php 
		}
	} elseif (isset($css['text'])) {
?>
<style type="text/css">
<?php echo $css['text']; ?>
</style>
<?php
	}
}

foreach ($SCRIPTS as $js) {
	if (isset($js['href'])) {
		$link = !empty($js['external']) ? $js["href"] : getlink($js["href"], LINK_SCRIPT);
		if ($link) { ?>
<script type="<?php echo $js["type"]; ?>" src="<?php echo $link; ?>"></script>
<?php 
		}
	} elseif (isset($js['text'])) {
?>
<script type="<?php echo $js["type"]; ?>">
<?php echo $js["text"]; ?>
</script>
<?php
	}
} 
?>
</head>
<?php include $this->getTemplatePath(BASIC_LAYOUT_TEMPLATE); ?>
</html>
