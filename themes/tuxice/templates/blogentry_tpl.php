<div class="blogentry">
<div class="header">
<h2><?php $this->put('SUBJECT') ?></h2>
<ul class="postdata">
	<?php
		if (! isset($NO_USER_PROFILE)) {
			$name_link = sprintf('<a href="%s">%s</a>', $PROFILE_LINK, $USER_DISPLAY_NAME);
		} elseif (isset($USER_EMAIL)) {
			$name_link = sprintf('<a href="mailto:%s">%s</a>', $USER_EMAIL, $USER_DISPLAY_NAME);
		} else {
			$name_link = $USER_DISPLAY_NAME;
		}
	?>
	<li class="blogdate"><?php pf_('Posted %1$s by %2$s', $POSTDATE, $name_link ); ?></li>
<?php if (! empty($TAGS)): ?>
	<li><?php p_("Topics");?>: 
	<?php foreach ($TAGS as $key=>$tag):?>
		<a href="<?php echo make_uri($TAG_LINK,array('tag'=>urlencode($tag)))?>">
			<?php echo $tag?>
		</a><?php echo (@$i++ < count($TAGS)-1 ? ', ' : '')?>
	<?php endforeach; ?>
	</li>
<?php endif; ?>
</ul>
</div>
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
if ( strpos($ENCLOSURE_DATA['type'], "audio") !== false || 
     strpos($ENCLOSURE_DATA['type'], "video") !== false ) {
	p_("Download this podcast");
} else {
	p_("Download attached file");
}?>: <a href="<?php echo $ENCLOSURE_DATA['url'];?>"><?php echo basename($ENCLOSURE_DATA['url']);?></a> 
<?php pf_("(Type: %s, size: %d bytes)", $ENCLOSURE_DATA['type'], $ENCLOSURE_DATA['length']);?>
</p>
<?php } /* End enclosure block */ ?>
<?php if ($SHOW_CONTROLS) { ?>
<ul class="controlbar">
	<li><a href="<?php echo $PING_LINK; ?>"><?php p_("Send TrackBack Ping"); ?></a></li>
	<li><a href="<?php echo $UPLOAD_LINK; ?>"><?php p_("Upload file"); ?></a></li>
	<li><a href="<?php echo $EDIT_LINK; ?>"><?php p_("Edit"); ?></a></li>
	<li><a href="<?php echo $DELETE_LINK; ?>"><?php p_("Delete"); ?></a></li>
	<li><a href="<?php echo $MANAGE_REPLY_LINK; ?>"><?php p_("Manage replies"); ?></a></li>
</ul>
<?php } /* End control link block */ ?>
</div>
<p>
<?php 
if ($ALLOW_COMMENTS) {
	pf_('You can reply to this entry by <a href="%s">leaving a comment</a> below.  ', $COMMENT_LINK);
}
if ($ALLOW_TRACKBACKS) {
	pf_('You can <a href="%s">send TrackBack pings to this <acronym title="Uniform Resource Locator">URL</acronym></a>.  ', $TRACKBACK_LINK);
}
if ($ALLOW_PINGBACKS) {
	pf_('This entry accepts Pingbacks from other blogs.');
}
if ($COMMENT_RSS_ENABLED) {
	pf_('You can follow comments on this entry by subscribing to the <a href="%s">RSS feed</a>.', $COMMENT_FEED_LINK);
}
?>
</p>
<?php if (! empty($LOCAL_PINGBACKS)) { ?>
<h4><?php p_("Related entries");?></h4>
<ul>
<?php foreach ($LOCAL_PINGBACKS as $p) { echo $p->get($SHOW_CONTROLS); } ?>
</ul>
<?php } /* End local pingback block */ 

if ( ! empty($TRACKBACKS) ) { ?>
<h3><?php p_("TrackBacks");?> 
<a href="<?php echo $SHOW_TRACKBACK_LINK; ?>" title="<?php p_("TrackBack page");?>">#</a></h3>
<ul class="trackbacks replies">
<?php foreach ($TRACKBACKS as $p) { echo $p->get($SHOW_CONTROLS); } ?>
</ul>
<?php } /* End TrackBack block */

if ( ! empty($PINGBACKS) ) { ?>
<h3><?php p_("Pingbacks");?> 
<a href="<?php echo $SHOW_PINGBACK_LINK; ?>" title="<?php p_("PingBack page");?>">#</a></h3>
<ul class="pingbacks replies">
<?php foreach ($PINGBACKS as $p) { echo $p->get($SHOW_CONTROLS); } ?>
</ul>
<?php } /* End Pingback block */

if ( ! empty($COMMENTS) ) { ?>
<h3><?php p_("Comments");?> 
<a href="<?php echo $COMMENT_LINK; ?>" title="<?php p_("Comment page");?>">#</a></h3>
<ul class="comments replies">
<?php foreach ($COMMENTS as $p) { echo $p->get($SHOW_CONTROLS); } ?>
</ul>
<?php } /* End comment block */ ?>
</div>