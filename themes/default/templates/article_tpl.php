<?php
# Template: article_tpl.php
# Template used to display static article text.  This is included by the
# <showentry.php> page when displaying an article.
#
# This template is very similar to the <blogentry_tpl.php> template.  This one
# is provided to allow static articles to have a different look and feel from 
# blog entries.
?>
<div class="article">
<div class="header">
<h2><a href="<?php echo $PERMALINK; ?>"><?php echo $TITLE; ?></a></h2>
</div>
<div class="body">
<?php echo $BODY; ?>
</div>
<div class="footer">
    <ul>
        <li class="articledate"><?php pf_("Published %s", $POSTDATE); ?></li>
<?php if ($EDITDATE != $POSTDATE) { /* Add an edit date, if applicable. */?>
        <li class="articledate"><?php pf_("Updated %s", $EDITDATE); ?></li>
<?php } ?>
<?php if (! isset($NO_USER_PROFILE)) { /* Display profile link or not */ ?>
        <li class="bloguser"><?php pf_("By %s", '<a href="'.$PROFILE_LINK.'">'.$USER_DISPLAY_NAME.'</a>');?></li>
<?php } elseif ( isset($USER_EMAIL) ) { ?>
        <li><?php pf_("By %s", '<a href=\"mailto: '.$USER_EMAIL.'">'.$USER_DISPLAY_NAME.'</a>'); ?></li>
<?php } else { ?>
        <li><?php pf_("By %s", $USER_DISPLAY_NAME); ?></li>
<?php } /* End user profile block */ ?>
<?php if (! empty($TAGS)) { ?>
    <li><?php p_("Tags");?>: <?php 
        $out = "";
        foreach ($TAGS as $key=>$tag) 
            $out .= ($out=="" ? "" : ", ").
                    '<a href="'.$TAG_LINK.'?tag='.urlencode($tag).'">'.
                    htmlspecialchars($tag).'</a>'; 
        echo $out;
?></li>
</ul>
<?php	}
if ($SHOW_CONTROLS) { ?>
<ul class="contolbar">
<li><a href="<?php echo $UPLOAD_LINK; ?>"><?php p_("Upload File");?></a></li>
<li><a href="<?php echo $EDIT_LINK; ?>"><?php p_("Edit");?></a></li>
<li><a href="<?php echo $DELETE_LINK; ?>"><?php p_("Delete");?></a></li>
<li><a href="<?php echo $MANAGE_REPLY_LINK; ?>"><?php p_("Manage replies"); ?></a></li>
</ul>
<?php } ?>
<ul class="inline">
<?php 
# Add links for comment, TrackBack, and Pingback stuff. 
# Only include the markup if there is something to display.
if (! empty($ALLOW_TRACKBACKS)) { ?>
<li><a href="<?php echo $TRACKBACK_LINK; ?>"><?php p_("TrackBack <abbr title=\"Uniform Resource Locator\">URL</abbr>");?></a></li>
<?php } 
if ( ! empty($COMMENTCOUNT) ) { ?>
<li><a href="<?php echo $COMMENT_LINK; ?>"><?php p_("View/post comments");?> (<?php echo $COMMENTCOUNT;?>)</a></li>
<?php } elseif ($ALLOW_COMMENTS) { ?>
<li><a href="<?php echo $COMMENT_LINK; ?>"><?php p_("Post comment");?></a></li>
<?php }
if ( ! empty($TRACKBACKCOUNT) ) { ?>
<li><a href="<?php echo $SHOW_TRACKBACK_LINK;?>"><?php p_("View TrackBacks");?> (<?php echo $TRACKBACKCOUNT;?>)</a></li>
<?php } ?>
<?php if ( ! empty($PINGBACKCOUNT) ) { ?>
<li><a href="<?php echo $PINGBACK_LINK; ?>"><?php p_("View Pingbacks"); ?> (<?php echo $PINGBACKCOUNT; ?>)</a></li>
<?php } ?>
</ul>
</div>
</div>
