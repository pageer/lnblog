<?php
# Template: blogentry_tpl.php
# Contains the markup and display logic for blog entries.  
# Most of the tricky stuff here is for conditional display.
?>
<?php /* Display the title as a heading with a permalink to the entry. */ ?>
<div class="blogentry">
<h2 class="header"><a href="<?php echo $PERMALINK; ?>"><?php echo $SUBJECT; ?></a></h2>
<div class="body">
<?php echo $BODY; ?>
</div>
<div class="footer">
<?php
# If there is an enclosure/podcast URL for this entry, this block will display a 
# link to it with the file name, type, and size.
if (! empty($ENCLOSURE_DATA) ) { ?>
<p>
<?php 
# If the enclosure is an audio file, then refer to it as a podcast.
if (strpos($ENCLOSURE_DATA['type'], "audio") !== false) {
	p_("Download this podcast");
} else {
	p_("Download attached file");
}?>: <a href="<?php echo $ENCLOSURE_DATA['url'];?>">
<?php echo basename($ENCLOSURE_DATA['url']);?></a> 
(<?php pf_("Type: %s, size: %d bytes", $ENCLOSURE_DATA['type'], $ENCLOSURE_DATA['length']);?>)
</p>
<?php } /* End enclosure block */ ?>
<ul class="postdata">
<?php if (! empty($TAGS)) { ?>
	<li><?php p_("Topics");?>: <?php 
		$out = "";
		foreach ($TAGS as $key=>$tag) 
			$out .= ($out=="" ? "" : ", ").
			         '<a href="'.make_uri($TAG_LINK,array('tag'=>urlencode($tag))).'">'.
			         htmlspecialchars($tag).'</a>'; 
		echo $out;
?></li>
<?php } ?>
	<li class="blogdate"><?php pf_("Posted %s", $POSTDATE); ?></li>
<?php if (! isset($NO_USER_PROFILE)) { /* Display profile link or not */?>
	<li class="bloguser"><?php pf_("By %s", '<a href="'.$PROFILE_LINK.'">'.$USER_DISPLAY_NAME.'</a>');?></li>
<?php } else { /* Use old e-mail link for user name */ ?>
<?php if ( isset($USER_EMAIL) ) { ?>
	<li class="bloguser"><?php pf_("By %s", '<a href="mailto:'.$USER_EMAIL.'">'.$USER_DISPLAY_NAME.'</a>'); ?></li>
<?php } else { ?>
	<li class="bloguser"><?php pf_("By %s", $USER_DISPLAY_NAME); ?></li>
<?php } ?>
<?php if (isset($USER_HOMEPAGE)) { ?>
	<li class="bloguserurl">(<a href="<?php echo $USER_HOMEPAGE; ?>"><?php echo $USER_HOMEPAGE; ?></a>)</li>
<?php } ?>
<?php } /* End user if block */ ?>
</ul>
<?php if ($SHOW_CONTROLS) { ?>
<ul class="postadmin">
	<li><a href="<?php echo $PING_LINK; ?>"><?php p_("Send TrackBack Ping"); ?></a></li>
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
<?php } elseif ($ALLOW_COMMENTS) { ?>
<h3><a href="<?php echo $COMMENT_LINK; ?>"><?php p_("Post a comment"); ?></a></h3>
<?php } ?>
<?php if ( ! empty($TRACKBACKCOUNT) ) { ?>
<h3><a href="<?php echo $SHOW_TRACKBACK_LINK; ?>"><?php p_("View TrackBacks"); ?> (<?php echo $TRACKBACKCOUNT; ?>)</a></h3>
<?php } ?>
<?php if ( ! empty($PINGBACKCOUNT) ) { ?>
<h3><a href="<?php echo $PINGBACK_LINK; ?>"><?php p_("View Pingbacks"); ?> (<?php echo $PINGBACKCOUNT; ?>)</a></h3>
<?php } ?>
</div>
</div>
