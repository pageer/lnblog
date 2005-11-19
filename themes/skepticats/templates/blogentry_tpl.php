<div class="blogentry">
<div class="blogentryheader">
<h2><a href="<?php echo $PERMALINK; ?>"><?php echo $SUBJECT; ?></a></h2>
<ul>
	<li class="blogdate"><?php pf_('Posted %s', $POSTDATE); ?></li>
<?php if ( isset($USER_EMAIL) ) { ?>
	<li class="bloguser"><?php pf_('By <a href="mailto:%s">%s</a>', $USER_EMAIL, $USER_DISPLAY_NAME); ?></li>
<?php } else { ?>
	<li class="bloguser"><?php pf_('By %s', $USER_DISPLAY_NAME); ?></li>
<?php } ?>
<?php if (isset($USER_HOMEPAGE)) { ?>
	<li class="bloguserurl">(<a href="<?php echo $USER_HOMEPAGE; ?>"><?php echo $USER_HOMEPAGE; ?></a>)</li>
<?php } ?>
<?php if ( ! empty($COMMENTCOUNT) ) { ?>
<li><a href="<?php echo $PERMALINK.ENTRY_COMMENT_DIR; ?>/"><?php p_('Comments ');?>(<?php echo $COMMENTCOUNT; ?>)</a></li>
<?php } elseif ( ! empty($ALLOW_COMMENTS) ) { ?>
<li><a href="<?php echo $PERMALINK.ENTRY_COMMENT_DIR; ?>/"><?php p_('Post comment'); ?></a></li>
<?php } ?>
<?php if ( ! empty($TRACKBACKCOUNT) ) { ?>
<li><a href="<?php echo $PERMALINK.ENTRY_TRACKBACK_DIR; ?>/"><?php p_('View TrackBacks');?> (<?php echo $TRACKBACKCOUNT; ?>)</a></li>
<?php } ?>
<li><?php p_('TrackBack <abbr title="Uniform Resource Locator">URL</abbr>');?>: <a href="<?php echo $PERMALINK; ?>trackback.php"><?php echo $PERMALINK; ?>trackback.php</a></li>
</ul>
<?php if ($SHOW_CONTROLS) { ?>
<ul class="postadmin">
	<li><a onclick="javascript:window.open('<?php echo $PERMALINK; ?>trackback.php?send_ping=yes'); return false;" href="<?php echo $PERMALINK; ?>trackback.php?send_ping=yes"><?php p_('Send TrackBack Ping');?></a></li>
	<li><a href="<?php echo $PERMALINK; ?>uploadfile.php"><?php p_('Upload file');?></a></li>
	<li><a href="<?php echo $PERMALINK; ?>edit.php"><?php p_('Edit');?></a></li>
	<li><a href="<?php echo $PERMALINK; ?>delete.php"><?php p_('Delete');?></a></li>
</ul>
<?php } ?>
</div>
<div class="blogentrybody">
<?php echo $BODY; ?>
</div>
</div>
