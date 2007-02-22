<div class="blogentry">
	<h2 class="blogentryheader"><a href="<?php echo $PERMALINK; ?>"><?php echo $SUBJECT; ?></a></h2>
<div class="blogentrybody">
<?php echo $BODY; ?>
</div>
<div class="blogentryfooter">
<ul class="postdata">
	<li class="blogdate">Posted <?php echo $POSTDATE; ?></li>
<?php if (! isset($NO_USER_PROFILE)) { # Display profile link or not ?>
	<li class="bloguser"><?php pf_("By %s", '<a href="'.$PROFILE_LINK.'">'.$USER_DISPLAY_NAME.'</a>');?></li>
<?php } else { # Use old e-mail link for user name ?>
<?php if ( isset($USER_EMAIL) ) { ?>
	<li class="bloguser">By <a href="mailto:<?php echo $USER_EMAIL; ?>"><?php echo $USER_DISPLAY_NAME; ?></a></li>
<?php } else { ?>
	<li class="bloguser">By <?php echo $USER_DISPLAY_NAME; ?></li>
<?php } ?>
<?php if (isset($USER_HOMEPAGE)) { ?>
	<li class="bloguserurl">(<a href="<?php echo $USER_HOMEPAGE; ?>"><?php echo $USER_HOMEPAGE; ?></a>)</li>
<?php } ?>
<?php } # End user if block ?>
</ul>
<?php if ($SHOW_CONTROLS) { ?>
<ul class="postadmin">
	<li><a href="<?php echo $PING_LINK; ?>">Send TrackBack Ping</a></li>
	<li><a href="<?php echo $UPLOAD_LINK; ?>">Upload file</a></li>
	<li><a href="<?php echo $EDIT_LINK; ?>">Edit</a></li>
	<li><a href="<?php echo $DELETE_LINK; ?>">Delete</a></li>
	<li><a href="<?php echo $MANAGE_REPLY_LINK; ?>"><?php p_("Manage replies"); ?></a></li>
</ul>
<?php } ?>
<ul>
<?php if (! empty($ALLOW_TRACKBACKS)) { ?>
<li>TrackBack <abbr title="Uniform Resource Locator">URL</abbr>: <a href="<?php echo $TRACKBACK_LINK; ?>"><?php echo $TRACKBACK_LINK; ?></a></li>
<?php }
if ( ! empty($COMMENTCOUNT) ) { ?>
<li><a href="<?php echo $COMMENT_LINK; ?>">View comments (<?php echo $COMMENTCOUNT; ?>)</a></li>
<?php } elseif ( ! empty($ALLOW_COMMENTS) ) { ?>
<li><a href="<?php echo $COMMENT_LINK; ?>">Post comment</a></li>
<?php } ?>
<?php if ( ! empty($TRACKBACKCOUNT) ) { ?>
<li><a href="<?php echo $SHOW_TRACKBACK_LINK; ?>">TrackBacks (<?php echo $TRACKBACKCOUNT; ?>)</a></li>
<?php } ?>
<?php if ( ! empty($PINGBACKCOUNT) ) { ?>
<li><a href="<?php echo $PINGBACK_LINK; ?>">Pingbacks (<?php echo $PINGBACKCOUNT; ?>)</a></li>
<?php } ?>
</ul>
</div>
</div>
