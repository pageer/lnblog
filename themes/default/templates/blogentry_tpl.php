<div class="blogentry">
<div class="blogentryheader">
	<h2><a href="<?php echo $PERMALINK; ?>"><?php echo $SUBJECT; ?></a></h2>
</div>
<div class="blogentrybody">
<?php echo $BODY; ?>
</div>
<div class="blogentryfooter">
<ul class="postdata">
	<li class="blogdate">Posted <?php echo $POSTDATE; ?></li>
<?php if ( isset($USER_EMAIL) ) { ?>
	<li class="bloguser">By <a href="mailto:<?php echo $USER_EMAIL; ?>"><?php echo $USER_DISPLAY_NAME; ?></a></li>
<?php } else { ?>
	<li class="bloguser">By <?php echo $USER_DISPLAY_NAME; ?></li>
<?php } ?>
<?php if (isset($USER_HOMEPAGE)) { ?>
	<li class="bloguserurl">(<a href="<?php echo $USER_HOMEPAGE; ?>"><?php echo $USER_HOMEPAGE; ?></a>)</li>
<?php } ?>
</ul>
<?php if ($SHOW_CONTROLS) { ?>
<ul class="postadmin">
	<li><a onclick="javascript:window.open('<?php echo $PERMALINK; ?>trackback.php?send_ping=yes'); return false;" href="<?php echo $PERMALINK; ?>trackback.php?send_ping=yes">Send TrackBack Ping</a></li>
	<li><a href="<?php echo $PERMALINK; ?>uploadfile.php">Upload file</a></li>
	<li><a href="<?php echo $PERMALINK; ?>edit.php">Edit</a></li>
	<li><a href="<?php echo $PERMALINK; ?>delete.php">Delete</a></li>
</ul>
<?php } ?>
<p>TrackBack <abbr title="Uniform Resource Locator">URL</abbr>: <a href="<?php echo $PERMALINK; ?>trackback.php"><?php echo $PERMALINK; ?>trackback.php</a></p>
<?php if ( ! empty($COMMENTCOUNT) ) { ?>
<h3><a href="<?php echo $PERMALINK.ENTRY_COMMENT_DIR; ?>/">View reader comments (<?php echo $COMMENTCOUNT; ?>)</a></h3>
<?php } elseif ( ! empty($ALLOW_COMMENTS) ) { ?>
<h3><a href="<?php echo $PERMALINK.ENTRY_COMMENT_DIR; ?>/">Post a comment</a></h3>
<?php } ?>
<?php if ( ! empty($TRACKBACKCOUNT) ) { ?>
<h3><a href="<?php echo $PERMALINK.ENTRY_TRACKBACK_DIR; ?>/">View TrackBacks (<?php echo $TRACKBACKCOUNT; ?>)</a></h3>
<?php } ?>
</div>
</div>
