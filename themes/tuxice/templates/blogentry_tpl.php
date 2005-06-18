<div class="blogentry">
<div class="blogentryheader">
<h2><a href="<?php echo $PERMALINK; ?>"><?php echo $SUBJECT; ?></a></h2>
<ul class="postdata">
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
</ul>
</div>
<div class="blogentrybody">
<?php echo $BODY; ?>
</div>
<div class="blogentryfooter">
<?php if (check_login()) { ?>
<ul class="postadmin">
	<li><a href="<?php echo $PERMALINK; ?>trackback.php?send_ping=yes">Send TrackBack Ping</a></li>
	<li><a href="<?php echo $POSTEDIT; ?>">Edit</a></li>
	<li><a href="<?php echo $POSTDELETE; ?>">Delete</a></li>
</ul>
<?php } ?>
<ul>
<li><a href="<?php echo $PERMALINK.ENTRY_COMMENT_DIR; ?>/">
<?php if ( ! empty($COMMENTCOUNT) ) { ?>View reader comments (<?php echo $COMMENTCOUNT; ?>)
<?php } else { ?>Post a comment<?php } ?></a></li>
<?php if ( ! empty($TRACKBACKCOUNT) ) { ?>
<li><a href="<?php echo $PERMALINK.ENTRY_TRACKBACK_DIR; ?>/">View TrackBacks (<?php echo $TRACKBACKCOUNT; ?>)</a></li>
<?php } ?>
<li>TrackBack <abbr title="Uniform Resource Locator">URL</abbr>: <a href="<?php echo $PERMALINK; ?>trackback.php"><?php echo $PERMALINK; ?>trackback.php</a></li>
</ul>
</div>
</div>
