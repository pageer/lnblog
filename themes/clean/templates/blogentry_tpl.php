<div class="blogentry">
	<h2 class="blogentryheader"><a href="<?php echo $PERMALINK; ?>"><?php echo $SUBJECT; ?></a></h2>
	<p class="blogdate"><?php pf_("Posted %s", $POSTDATE); ?></p>
	<p class="bloguser"><?php pf_("By %s", '<a href="'.$PROFILE_LINK.'">'.$USER_DISPLAY_NAME.'</a>');?></p>
<div class="blogentrybody">
<?php echo $BODY; ?>
</div>
<div class="blogentryfooter">
<?php if (! empty($TAGS)) { ?>
<ul class="postdata">
	<li><?php p_("Filed under");?>: <?php 
		$out = "";
		foreach ($TAGS as $key=>$tag) 
			$out .= ($out=="" ? "" : ", ").'<a href="'.$TAG_LINK.'?tag='.$tag.'">'.$tag.'</a>'; 
		echo $out;
?></li>
</ul>
<?php } ?>
<?php if ($SHOW_CONTROLS) { ?>
<ul class="postadmin">
	<li><a href="<?php echo $PING_LINK; ?>"><?php p_("Send TrackBack Ping"); ?></a></li>
	<li><a href="<?php echo $UPLOAD_LINK; ?>"><?php p_("Upload file"); ?></a></li>
	<li><a href="<?php echo $EDIT_LINK; ?>"><?php p_("Edit"); ?></a></li>
	<li><a href="<?php echo $DELETE_LINK; ?>"><?php p_("Delete"); ?></a></li>
</ul>
<p>
<?php } 
if ( ! empty($COMMENTCOUNT) ) { ?>
<span><a href="<?php echo $COMMENT_LINK; ?>"><?php p_("View comments"); ?> (<?php echo $COMMENTCOUNT; ?>)</a></span>
<?php } elseif ($ALLOW_COMMENTS) { ?>
<span><a href="<?php echo $COMMENT_LINK; ?>"><?php p_("Post comment"); ?></a></span>
<?php }
if ( ! empty($TRACKBACKCOUNT) ) { ?>
<span><a href="<?php echo $SHOW_TRACKBACK_LINK; ?>"><?php p_("View TrackBacks"); ?> (<?php echo $TRACKBACKCOUNT; ?>)</a></span>
<?php } 
if (! empty($ALLOW_TRACKBACKS)) { ?>
<span><a href="<?php echo $TRACKBACK_LINK; ?>"><?php p_("TrackBack <abbr title=\"Uniform Resource Locator\">URL</abbr>");?></a></span>
<?php } ?>
</div>
</div>
