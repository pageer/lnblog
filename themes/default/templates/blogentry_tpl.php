<div class="blogentry">
	<h2 class="blogentryheader"><a href="<?php echo $PERMALINK; ?>"><?php echo $SUBJECT; ?></a></h2>
<div class="blogentrybody">
<?php echo $BODY; ?>
</div>
<div class="blogentryfooter">
<ul class="postdata">
<?php if (! empty($TAGS)) { ?>
	<li><?php p_("Topics");?>: <?php 
		$out = "";
		foreach ($TAGS as $key=>$tag) 
			$out .= ($out=="" ? "" : ", ").'<a href="'.$TAG_LINK.'?tag='.$tag.'">'.$tag.'</a>'; 
		echo $out;
?></li>
<?php } ?>
	<li class="blogdate"><?php pf_("Posted %s", $POSTDATE); ?></li>
<?php if ( isset($USER_EMAIL) ) { ?>
	<li class="bloguser"><?php pf_("By %s", '<a href="mailto:'.$USER_EMAIL.'">'.$USER_DISPLAY_NAME.'</a>'); ?></li>
<?php } else { ?>
	<li class="bloguser"><?php pf_("By %s", $USER_DISPLAY_NAME); ?></li>
<?php } ?>
<?php if (isset($USER_HOMEPAGE)) { ?>
	<li class="bloguserurl">(<a href="<?php echo $USER_HOMEPAGE; ?>"><?php echo $USER_HOMEPAGE; ?></a>)</li>
<?php } ?>
</ul>
<?php if ($SHOW_CONTROLS) { ?>
<ul class="postadmin">
	<li><a onclick="javascript:window.open('<?php echo $PING_LINK; ?>'); return false;" href="<?php echo $PING_LINK; ?>"><?php p_("Send TrackBack Ping"); ?></a></li>
	<li><a href="<?php echo $UPLOAD_LINK; ?>"><?php p_("Upload file"); ?></a></li>
	<li><a href="<?php echo $EDIT_LINK; ?>"><?php p_("Edit"); ?></a></li>
	<li><a href="<?php echo $DELETE_LINK; ?>"><?php p_("Delete"); ?></a></li>
</ul>
<?php } 
if (! empty($ALLOW_TRACKBACKS)) { ?>
<p><?php p_("TrackBack <abbr title=\"Uniform Resource Locator\">URL</abbr>"); ?>: <a href="<?php echo $TRACKBACK_LINK; ?>"><?php echo $TRACKBACK_LINK; ?></a></p>
<?php } 
if ( ! empty($COMMENTCOUNT) ) { ?>
<h3><a href="<?php echo $COMMENT_LINK; ?>"><?php p_("View reader comments"); ?> (<?php echo $COMMENTCOUNT; ?>)</a></h3>
<?php } elseif ( ! empty($ALLOW_COMMENTS) ) { ?>
<h3><a href="<?php echo $COMMENT_LINK; ?>"><?php p_("Post a comment"); ?></a></h3>
<?php } ?>
<?php if ( ! empty($TRACKBACKCOUNT) ) { ?>
<h3><a href="<?php echo $SHOW_TRACKBACK_LINK; ?>"><?php p_("View TrackBacks"); ?> (<?php echo $TRACKBACKCOUNT; ?>)</a></h3>
<?php } ?>
</div>
</div>
