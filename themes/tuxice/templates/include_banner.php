<?php if (defined("BLOG_ROOT")) { ?>
<h1><a href="<?php echo $BLOG_URL; ?>" title="<?php echo $BLOG_DESCRIPTION; ?>"><?php echo $BLOG_NAME; ?></a></h1>
<h3><?php echo $BLOG_DESCRIPTION; ?></h3>
<?php } else { ?>
<h1><a href="<?php echo PACKAGE_URL; ?>"><?php echo PACKAGE_NAME; ?></a></h1>
<?php } ?>
