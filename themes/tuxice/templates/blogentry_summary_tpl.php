<div class="blogentry">
<div class="header">
<h2><a href="<?php echo $PERMALINK; ?>"><?php echo $SUBJECT; ?></a></h2>
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
    <li class="blogdate"><?php pf_('Posted %1$s by %2$s', $POSTDATE, $name_link); ?></li> 

<?php if (! empty($TAGS)): ?>
    <li><?php p_("Topics");?>: 
    <?php foreach ($TAGS as $key=>$tag):?>
        <a href="<?php echo make_uri($TAG_LINK, array('tag'=>urlencode($tag)))?>">
            <?php echo $tag?>
        </a><?php echo (@$i++ < count($TAGS)-1 ? ', ' : '')?>
    <?php endforeach; ?>
    </li>
<?php endif; ?>
</ul>
</div>
<div class="body">
<?php echo $USE_ABSTRACT ? $ABSTRACT : $BODY;?>
</div>
<div class="footer">
<?php if ($SHOW_CONTROLS) { ?>
<ul class="controlbar">
    <?php if (!empty($ALLOW_TRACKBACKS)):?>
    <li><a href="<?php echo $PING_LINK; ?>"><?php p_("Send TrackBack Ping");?></a></li>
    <?php endif ?>
    <li><a href="<?php echo $UPLOAD_LINK; ?>"><?php p_('Upload file');?></a></li>
    <li><a href="<?php echo $EDIT_LINK; ?>"><?php p_('Edit');?></a></li>
    <li><a href="<?php echo $DELETE_LINK; ?>"><?php p_('Delete');?></a></li>
    <li><a href="<?php echo $MANAGE_REPLY_LINK; ?>"><?php p_("Manage replies"); ?></a></li>
</ul>
<?php } ?>
<?php if (! empty($COMMENTCOUNT) || ! empty($ALLOW_COMMENT) || 
          ! empty($TRACKBACKCOUNT) || ! empty($ALLOW_TRACKBACKS) ): ?>
    <ul class="replies">
    <?php if ( ! empty($COMMENTCOUNT) ): ?>
        <li><a href="<?php echo $COMMENT_LINK; ?>"><?php p_('View reader comments');?> (<?php echo $COMMENTCOUNT; ?>)</a></li>
    <?php elseif (empty($ALLOW_COMMENT)): ?> 
        <li><a href="<?php echo $COMMENT_LINK; ?>"><?php p_('Post a comment');?></a></li>
    <?php endif; ?>
    <?php if ( ! empty($TRACKBACKCOUNT) ): ?>
        <li><a href="<?php echo $SHOW_TRACKBACK_LINK; ?>"><?php p_('View TrackBacks');?> (<?php echo $TRACKBACKCOUNT; ?>)</a></li>
    <?php endif; 
    if ( ! empty($PINGBACKCOUNT) ): ?>
        <li><a href="<?php echo $PINGBACK_LINK; ?>"><?php p_('View Pingbacks');?> (<?php echo $PINGBACKCOUNT; ?>)</a></li>
    <?php  endif;
    if (! empty($ALLOW_TRACKBACKS)): ?>
        <li><a href="<?php echo $TRACKBACK_LINK; ?>"><?php p_('TrackBack <abbr title="Uniform Resource Locator">URL</abbr>');?></a></li>
    <?php endif; ?>
    </ul>
<?php endif; ?>
</div>
</div>
