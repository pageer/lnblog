<?php
# Template: blogcomment_tpl.php
# Template for the full markup to a comment, including the subject, body, 
# metadata (name, e-mail, and so forth).  This also includes the administrative
# links for managing the comment.
#
# This is included whenever a comment is displayed on the page.  This normally
# includes the <showcomments.php> and <showentry.php> pages.
?>
<?php if (isset($SUBJECT)) { ?>
	<h3 class="header"><a id="<?php echo $ANCHOR; ?>" href="#<?php echo $ANCHOR; ?>"><?php echo $SUBJECT; ?></a></h3>
<?php } else { ?>
<a id="<?php echo $ANCHOR; ?>" href="#<?php echo $ANCHOR; ?>"></a>
<?php } ?>
<div class="data">
<?php echo $BODY; ?>
</div>
<ul class="footer">
<li class="commentdate"><?php pf_("Comment posted %s", $DATE); ?></li>
<?php 
# Here is where we display the user name and contact links.
# Note that the display is different for logged-in users than for anonymous users.
if (isset($USER_ID)) { 
	# Display profile link or not.
	if (! isset($NO_USER_PROFILE)) { ?>
<li class="user"><?php pf_("By %s", '<a href="'.$PROFILE_LINK.'">'.$USER_DISPLAY_NAME.'</a>');?></li>
<?php
	# No profile link, so display the user's e-mail address, if present.
	} else { ?>
<li class="commentname">
		<?php	if ( isset($USER_EMAIL) ) echo '<a href="mailto:'.$USER_EMAIL.'">'; ?>
		<?php pf_("By <strong>%s</strong>", $USER_DISPLAY_NAME); ?>
		<?php if ( isset($USER_EMAIL) ) echo '</a>';	?>
</li>
	<?php 
	} # End user profile block
	# Now display the user's homepage, if set.
	if (isset($USER_HOMEPAGE)) { ?>
<li class="commenturl">(<a href="<?php echo $USER_HOMEPAGE; ?>"><?php echo $USER_HOMEPAGE; ?></a>)</li>
	<?php 
	} 
} else { 
	/* If the user isn't logged in, display what was entered in the boxes. */ ?>
	<li class="commentname">
	<?php if ($EMAIL && $SHOW_MAIL) echo '<a href="mailto:'.$EMAIL.'">'; ?>
	<?php pf_('By %s', $NAME); ?>
	<?php if ($EMAIL && $SHOW_MAIL) echo '</a>'; ?>
	</li>
	<?php if ($URL) { ?>
	<li class="commenturl">(<a href="<?php echo $URL; ?>"><?php echo $URL; ?></a>)</li>
	<?php 
	} 
} # End user info display 
# Show the administrative links.
if ($SHOW_CONTROLS) { 
	foreach ($CONTROL_BAR as $item) {
?>
<li class="admin"><?php echo $item; ?></li>
<?php 
	}
} 
?>
</ul>
