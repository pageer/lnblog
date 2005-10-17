<li class="fullcomment">
<?php if (isset($SUBJECT)) { ?>
	<h3 class="commentheader"><a id="<?php echo $ANCHOR; ?>" href="#<?php echo $ANCHOR; ?>"><?php echo $SUBJECT; ?></a></h3>
<?php } else { ?>
<a id="<?php echo $ANCHOR; ?>" href="#<?php echo $ANCHOR; ?>"></a>
<?php } ?>
<div class="commentdata">
<?php echo $BODY; ?>
</div>
<div class="commentfooter">
	<ul>
		<li class="commentdate">Comment posted <?php echo $DATE; ?></li>
<?php if (isset($USER_ID)) { ?>
	<?php if ( isset($USER_EMAIL) && isset($USER_NAME) ) { ?>
		<li class="commentname">By <a href="mailto:<?php echo $USER_EMAIL; ?>"><strong><?php echo $USER_NAME; ?></strong></a></li>
	<?php } elseif (isset($USER_EMAIL)) { ?>
		<li class="commentname">By <a href="mailto:<?php echo $USER_EMAIL; ?>"><strong><?php echo $USER_ID; ?></strong></a></li>
	<?php } elseif (isset($USER_NAME)) { ?>
		<li class="commentname">By <strong><?php echo $USER_NAME; ?></strong></li>
	<?php } else { ?>
		<li class="commentname">By <strong><?php echo $USER_ID; ?></strong></li>
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
		<li class="admin"><a href="<?php echo is_dir(ENTRY_COMMENT_DIR) ? ENTRY_COMMENT_DIR."/" : ""; ?>delete.php?comment=<?php echo $ANCHOR; ?>">Delete</a></li>
		<?php } ?>
	</ul>
</div>
</li>
