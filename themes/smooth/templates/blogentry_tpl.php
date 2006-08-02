<div class="blogentry">
	<h2 class="blogentryheader"><a href="<?php echo $PERMALINK; ?>"><?php echo $SUBJECT; ?></a></h2>
<div class="blogentrybody">
<?php echo $BODY; ?>
</div>
<div class="blogentryfooter">
<p><?php p_("Posted by");?> 
<?php if (! isset($NO_USER_PROFILE)) { # Display profile link or not ?>
<?php pf_("By %s", '<a
href="'.$PROFILE_LINK.'">'.$USER_DISPLAY_NAME.'</a>');?><?php 
} elseif ( isset($USER_EMAIL) ) { ?>
<a href="mailto:<?php echo $USER_EMAIL; ?>"><?php echo $USER_DISPLAY_NAME; ?></a><?php 
} else { 
	echo $USER_DISPLAY_NAME;
}
if (isset($USER_HOMEPAGE)) { ?>
(<a href="<?php echo $USER_HOMEPAGE; ?>"><?php echo $USER_HOMEPAGE; ?></a>)
<?php } ?> at <?php echo $POSTDATE; ?></p>
<?php if ($TAGS) { ?>
<p><?php p_("Filed under:");?> <?php
$list = '';
foreach ($TAGS as $tag) {
	if ($list != '') $list .= ", ";
	$list = '<a href="'.$TAG_LINK.'?tag='.$tag.'">'.$tag.'</a>';
}
echo $list;
?></p>
<?php } ?>
<?php if ($SHOW_CONTROLS) { ?>
<ul class="postadmin">
	<li><a href="<?php echo $PING_LINK; ?>"><?php p_("Send TrackBack Ping");?></a></li>
	<li><a href="<?php echo $UPLOAD_LINK; ?>"><?php p_("Upload file");?></a></li>
	<li><a href="<?php echo $EDIT_LINK; ?>"><?php p_("Edit");?></a></li>
	<li><a href="<?php echo $DELETE_LINK; ?>"><?php p_("Delete");?></a></li>
</ul>
<?php } ?>
<ul class="comments">
<?php if ( ! empty($COMMENTCOUNT) ) { ?>
<li><a href="<?php echo $COMMENT_LINK; ?>"><?php p_("Comments");?> (<?php echo $COMMENTCOUNT; ?>)</a></li>
<?php } elseif ( ! empty($ALLOW_COMMENTS) ) { ?>
<li><a href="<?php echo $COMMENT_LINK; ?>"><?php p_("Post comment");?></a></li>
<?php } ?>
<?php if ( ! empty($TRACKBACKCOUNT) ) { ?>
<li><a href="<?php echo $SHOW_TRACKBACK_LINK; ?>"><?php p_("TrackBacks");?> (<?php echo $TRACKBACKCOUNT; ?>)</a></li>
<?php }
if ( ! empty($PINGBACKCOUNT) ) { ?>
<li><a href="<?php echo $PINGBACK_LINK; ?>"><?php p_("Pingbacks");?> (<?php echo $PINGBACKCOUNT; ?>)</a></li>
<?php }
if (! empty($ALLOW_TRACKBACKS)) {?>
<li><a href="<?php echo $TRACKBACK_LINK; ?>"><?php p_('TrackBack <abbr title="Uniform Resource Locator">URL</abbr>');?></a></li>
<?php } ?>
</ul>
</div>
</div>
