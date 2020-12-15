<?php
# Template: blogentry_summary_tpl.php
# Handles the display of entry summaries, e.g. for display on the front page.
/* Display the title as a heading with a permalink to the entry. */ ?>
<div class="blogentry">
<h2 class="header"><a href="<?php echo $PERMALINK; ?>"><?php echo $SUBJECT; ?></a></h2>
<div class="body">
<?php 
if ($USE_ABSTRACT) echo $ABSTRACT; 
else echo $BODY;
?>
</div>
<div class="footer">
<ul class="postdata">
<?php if (! empty($TAGS)) { ?>
    <li><?php p_("Topics");?>: <?php 
        $out = "";
        foreach ($TAG_URLS as $tag=>$url) 
            $out .= ($out=="" ? "" : ", ").'<a href="'.$url.'">'.$tag.'</a>'; 
        echo $out;
?></li>
<?php } ?>
    <li class="blogdate"><?php pf_("Posted %s", $POSTDATE); ?></li>
<?php
    if (! isset($NO_USER_PROFILE)) {
        $name_link = sprintf('<a href="%s">%s</a>', $PROFILE_LINK, $USER_DISPLAY_NAME);
    } elseif (isset($USER_EMAIL)) {
        $name_link = sprintf('<a href="mailto:%s">%s</a>', $USER_EMAIL, $USER_DISPLAY_NAME);
    } else {
        $name_link = $USER_DISPLAY_NAME;
    }
?>
    <li class="bloguser"><?php pf_("By %s", $name_link); ?></li>
<?php if (isset($USER_HOMEPAGE)) { ?>
    <li class="bloguserurl">(<a href="<?php echo $USER_HOMEPAGE; ?>"><?php echo $USER_HOMEPAGE; ?></a>)</li>
<?php } ?>
</ul>
<?php if ($SHOW_CONTROLS) { ?>
<ul class="controlbar">
    <li class="ping"><a href="<?php echo $PING_LINK; ?>"><?php p_("Send TrackBack Ping"); ?></a></li>
    <li class="upload"><a href="<?php echo $UPLOAD_LINK; ?>"><?php p_("Upload file"); ?></a></li>
    <li class="edit"><a href="<?php echo $EDIT_LINK; ?>"><?php p_("Edit"); ?></a></li>
    <li class="delete"><a href="<?php echo $DELETE_LINK; ?>"><?php p_("Delete"); ?></a></li>
    <li class="replies"><a href="<?php echo $MANAGE_REPLY_LINK; ?>"><?php p_("Manage replies"); ?></a></li>
</ul>
<?php } 
if (! empty($ALLOW_TRACKBACKS)) { ?>
<p class="trackback-url"><?php p_("TrackBack <abbr title=\"Uniform Resource Locator\">URL</abbr>"); ?>: <a href="<?php echo $TRACKBACK_LINK; ?>"><?php echo $TRACKBACK_LINK; ?></a></p>
<?php } 
if ( ! empty($COMMENTCOUNT) ) { ?>
<h3 class="comment-count"><a href="<?php echo $COMMENT_LINK; ?>"><?php p_("View reader comments"); ?> (<?php echo $COMMENTCOUNT; ?>)</a></h3>
<?php } elseif ($ALLOW_COMMENTS) { ?>
<h3 class="comment-count zero"><a href="<?php echo $COMMENT_LINK; ?>"><?php p_("Post a comment"); ?></a></h3>
<?php } ?>
<?php if ( ! empty($TRACKBACKCOUNT) ) { ?>
<h3 class="trackback-count"><a href="<?php echo $SHOW_TRACKBACK_LINK; ?>"><?php p_("View TrackBacks"); ?> (<?php echo $TRACKBACKCOUNT; ?>)</a></h3>
<?php } ?>
<?php if ( ! empty($PINGBACKCOUNT) ) { ?>
<h3 class="pingback-count"><a href="<?php echo $PINGBACK_LINK; ?>"><?php p_("View Pingbacks"); ?> (<?php echo $PINGBACKCOUNT; ?>)</a></h3>
<?php } ?>
</div>
</div>