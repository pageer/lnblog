<?php echo $DOCTYPE; ?>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<title><?php echo $PAGE_TITLE; ?></title>
<?php foreach ($METADATA as $meta) { ?>
<meta <?php if ($meta["http-equiv"]) { ?>http-equiv="<?php echo $meta["http-equiv"]; ?>"<?php } ?> <?php if ($meta["name"]) { ?>name="<?php echo $meta["name"]; ?>"<?php } ?> content="<?php echo $meta["content"]; ?>" />
<?php } ?>
<?php foreach ($RSSFEEDS as $rssel) { ?>
<link rel="alternate" title="<?php echo $rssel["title"]; ?>" type="<?php echo $rssel["type"]; ?>" href="<?php echo $rssel["href"]; ?>" />
<?php } ?>
<?php foreach ($STYLESHEETS as $css) { ?>
<link rel="stylesheet" type="text/css" href="<?php echo getlink($css, LINK_STYLESHEET); ?>" />
<?php } ?>
<?php foreach ($SCRIPTS as $js) { ?>
<script type="<?php echo $js["type"]; ?>" src="<?php echo getlink($js["href"], LINK_SCRIPT); ?>"></script>
<?php } ?>
</head>
<?php include(BASIC_LAYOUT_TEMPLATE); ?>
</html>
