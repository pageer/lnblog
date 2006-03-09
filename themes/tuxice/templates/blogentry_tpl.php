<div class="blogentry">
<div class="blogentryheader">
<h2><a href="<?php echo $PERMALINK; ?>"><?php echo $SUBJECT; ?></a></h2>
<ul class="postdata">
	<li class="blogdate"><?php pf_('Posted %s', $POSTDATE); ?></li>
<?php if (! isset($NO_USER_PROFILE)) { # Display profile link or not ?>
	<li class="bloguser"><?php pf_("By %s", '<a href="'.$PROFILE_LINK.'">'.$USER_DISPLAY_NAME.'</a>');?></li>
<?php } else { # Use old e-mail link for user name ?>
<?php if ( isset($USER_EMAIL) ) { ?>
	<li class="bloguser"><?php pf_('By <a href="mailto:%s">%s</a>', $USER_EMAIL, $USER_DISPLAY_NAME);?></li>
<?php } else { ?>
	<li class="bloguser"><?php pf_('By %s', $USER_DISPLAY_NAME); ?></li>
<?php } ?>
<?php if (isset($USER_HOMEPAGE)) { ?>
	<li class="bloguserurl">(<a href="<?php echo $USER_HOMEPAGE; ?>"><?php echo $USER_HOMEPAGE; ?></a>)</li>
<?php } 
}
if (! empty($TAGS)) { ?>
	<li><?php p_("Topics");?>: <?php 
		$out = "";
		foreach ($TAGS as $key=>$tag) 
			$out .= ($out=="" ? "" : ", ").'<a href="'.$TAG_LINK.'?tag='.$tag.'">'.$tag.'</a>'; 
		echo $out;
?></li>
<?php } ?>
</ul>
</div>
<div class="blogentrybody">
<?php echo $BODY; ?>
</div>
<div class="blogentryfooter">
<?php if ($SHOW_CONTROLS) { ?>
<ul class="postadmin">
	<li><a onclick="javascript:window.open('<?php echo $PING_LINK;?>'); return false;" href="<?php echo $PING_LINK; ?>"><?php p_("Send TrackBack Ping");?></a></li>
	<li><a href="<?php echo $UPLOAD_LINK; ?>"><?php p_('Upload file');?></a></li>
	<li><a href="<?php echo $EDIT_LINK; ?>"><?php p_('Edit');?></a></li>
	<li><a href="<?php echo $DELETE_LINK; ?>"><?php p_('Delete');?></a></li>
</ul>
<?php } ?>
<?php if (! empty($COMMENTCOUNT) || ! empty($ALLOW_COMMENT) || 
          ! empty($TRACKBACKCOUNT) || ! empty($ALLOW_TRACKBACKS) ) { ?>
<ul>
<?php if ( ! empty($COMMENTCOUNT) ) { ?>
<li><a href="<?php echo $COMMENT_LINK; ?>"><?php p_('View reader comments');?> (<?php echo $COMMENTCOUNT; ?>)</a></li>
<?php } elseif (! empty($ALLOW_COMMENT)) { ?> 
<li><a href="<?php echo $COMMENT_LINK; ?>"><?php p_('Post a comment');?>)</a></li>
<?php } ?>
<?php if ( ! empty($TRACKBACKCOUNT) ) { ?>
<li><a href="<?php echo $SHOW_TRACKBACK_LINK; ?>"><?php p_('View TrackBacks');?> (<?php echo $TRACKBACKCOUNT; ?>)</a></li>
<?php } 
if (! empty($ALLOW_TRACKBACKS)) { ?>
<li><?php p_('TrackBack <abbr title="Uniform Resource Locator">URL</abbr>');?>: <a href="<?php echo $TRACKBACK_LINK; ?>"><?php echo $TRACKBACK_LINK; ?></a></li>
<?php } ?>
</ul>
<?php } ?>
</div>
</div>
