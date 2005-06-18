<div class="blogentry">
<div class="blogentryheader">
	<h2><a href="<?php echo $PERMALINK; ?>"><?php echo $SUBJECT; ?></a></h2>
	<ul>
		<li class="blogdate">Posted <?php echo $POSTDATE; ?></li>
<?php if ( isset($USER_EMAIL) && isset($USER_NAME) ) { ?>
		<li class="bloguser">By <a href="mailto:<?php echo $USER_EMAIL; ?>"><?php echo $USER_NAME; ?></a></li>
<?php } elseif (isset($USER_EMAIL)) { ?>
		<li class="bloguser">By <a href="mailto:<?php echo $USER_EMAIL; ?>"><?php echo $USER_ID; ?></a></li>
<?php } elseif (isset($USER_NAME)) { ?>
		<li class="bloguser">By <?php echo $USER_NAME; ?></li>
<?php } else { ?>
		<li class="bloguser">By <?php echo $USER_ID; ?></li>
<?php } ?>
<?php if (isset($USER_HOMEPAGE)) { ?>
		<li class="bloguserurl">(<a href="<?php echo $USER_HOMEPAGE; ?>"><?php echo $USER_HOMEPAGE; ?></a>)</li>
<?php } ?>
<?php if (! empty($COMMENTCOUNT)) { ?>
		<li><a href="<?php echo $PERMALINK.ENTRY_COMMENT_DIR; ?>">Comments (<?php echo $COMMENTCOUNT; ?>)</a></li>
<?php } ?>
<?php	if (check_login() ) { ?>
		<li class="blogadmin">
			<a href="<?php echo $PERMALINK; ?>edit.php">Edit</a>
			<a href="<?php echo $PERMALINK; ?>delete.php">Delete</a>
		</li>
<?php } ?>
	</ul>
</div>
<div class="blogentrybody">
<?php echo $BODY; ?>
</div>
</div>
