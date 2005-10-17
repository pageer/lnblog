<div class="blogentry">
	<h2 class="blogentryheader"><a href="<?php echo $PERMALINK; ?>"><?php echo $SUBJECT; ?></a></h2>
<div class="blogentrybody">
<?php echo $BODY; ?>
</div>
<div class="blogentryfooter">
<p>Posted by 
<?php if ( isset($USER_EMAIL) ) { ?>
<a href="mailto:<?php echo $USER_EMAIL; ?>"><?php echo $USER_DISPLAY_NAME; ?></a>
<?php } else { ?>
<?php echo $USER_DISPLAY_NAME; ?>
<?php } ?>
<?php if (isset($USER_HOMEPAGE)) { ?>
(<a href="<?php echo $USER_HOMEPAGE; ?>"><?php echo $USER_HOMEPAGE; ?></a>)
<?php } ?>
 at <?php echo $POSTDATE; ?></p>
<?php if ($SHOW_CONTROLS) { ?>
<ul class="postadmin">
	<li><a onclick="javascript:window.open('<?php echo $PERMALINK; ?>trackback.php?send_ping=yes'); return false;" href="<?php echo $PERMALINK; ?>trackback.php?send_ping=yes">Send TrackBack Ping</a></li>
	<li><a href="<?php echo $PERMALINK; ?>uploadfile.php">Upload file</a></li>
	<li><a href="<?php echo $PERMALINK; ?>edit.php">Edit</a></li>
	<li><a href="<?php echo $PERMALINK; ?>delete.php">Delete</a></li>
</ul>
<?php } ?>
<ul class="comments">
<?php if ( ! empty($COMMENTCOUNT) ) { ?>
<li><a href="<?php echo $PERMALINK.ENTRY_COMMENT_DIR; ?>/">Comments (<?php echo $COMMENTCOUNT; ?>)</a></li>
<?php } elseif ( ! empty($ALLOW_COMMENTS) ) { ?>
<li><a href="<?php echo $PERMALINK.ENTRY_COMMENT_DIR; ?>/">Post comment</a></li>
<?php } ?>
<?php if ( ! empty($TRACKBACKCOUNT) ) { ?>
<li><a href="<?php echo $PERMALINK.ENTRY_TRACKBACK_DIR; ?>/">TrackBacks (<?php echo $TRACKBACKCOUNT; ?>)</a></li>
<?php } ?>
<li><a href="<?php echo $PERMALINK; ?>trackback.php">TrackBack <abbr title="Uniform Resource Locator">URL</abbr></a></li>
</ul>
</div>
</div>
