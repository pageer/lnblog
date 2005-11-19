<div class="commentheader">
<?php if (isset($SUBJECT)) { ?>
	<h3><a id="<?php echo $ANCHOR; ?>" href="#<?php echo $ANCHOR; ?>"><?php echo $SUBJECT; ?></a></h3>
<?php } else { ?>
<a id="<?php echo $ANCHOR; ?>" href="#<?php echo $ANCHOR; ?>"></a>
<?php } ?>
</div>
<div class="commentdata">
<?php echo $BODY; ?>
</div>
<div class="commentfooter">
	<ul>
		<li class="commentdate"><?php pf_('Comment posted %s', $DATE); ?></li>
<?php if (isset($USER_ID)) { ?>
	<?php if ( isset($USER_EMAIL) && isset($USER_NAME) ) { ?>
		<li class="commentname"><?php pf_('By <a href="mailto:%s"><strong>%s</strong></a>', $USER_EMAIL, $USER_NAME);?></li>
	<?php } elseif (isset($USER_EMAIL)) { ?>
		<li class="commentname"><?php pf_('By <a href="mailto:%s"><strong>%s</strong></a>', $USER_EMAIL, $USER_ID);?></li>
	<?php } elseif (isset($USER_NAME)) { ?>
		<li class="commentname"><?php pf_('By <strong>%s</strong>', $USER_NAME);?></li>
	<?php } else { ?>
		<li class="commentname"><?php pf_('By <strong>%s</strong>', $USER_ID); ?></li>
	<?php } ?>
	<?php if (isset($USER_HOMEPAGE)) { ?>
		<li class="commenturl">(<a href="<?php echo $USER_HOMEPAGE; ?>"><?php echo $USER_HOMEPAGE; ?></a>)</li>
	<?php } ?>
<?php } else { ?>
		<li class="commentname">By 
		<?php if ($EMAIL)	{ ?>
		<a href="mailto:<?php echo $EMAIL; ?>"><?php echo $NAME; ?></a>
		<?php } else {?>
		<?php echo $NAME; ?>
		<?php } ?>
		</li>
		<?php if ($URL) { ?>
		<li class="commenturl">(<a href="<?php echo $URL; ?>"><?php echo $URL; ?></a>)</li>
		<?php } ?>
<?php } ?>
		<?php if ($SHOW_CONTROLS) { ?>
		<li class="admin"><a href="<?php echo is_dir(ENTRY_COMMENT_DIR) ? ENTRY_COMMENT_DIR."/" : ""; ?>delete.php?comment=<?php echo $ANCHOR; ?>"><?php p_('Delete');?></a></li>
		<?php } ?>
	</ul>
</div>
