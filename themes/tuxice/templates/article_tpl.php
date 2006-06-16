<div class="article">
<div class="articleheader">
<h2><a href="<?php echo $PERMALINK; ?>"><?php echo $TITLE; ?></a></h2>
</div>
<div class="articlebody">
<?php echo $BODY; ?>
</div>
<div class="articlefooter">
	<ul>
		<li class="articledate"><?php pf_('Published %s', $POSTDATE); ?></li>
<?php if ($EDITDATE != $POSTDATE) { ?>
		<li class="articledate"><?php pf_('Last updated %s', $EDITDATE);?></li>
<?php } ?>
<?php if (! isset($NO_USER_PROFILE)) { # Display profile link or not ?>
		<li class="bloguser"><?php pf_("By %s", '<a href="'.$PROFILE_LINK.'">'.$USER_DISPLAY_NAME.'</a>');?></li>
<?php } else { ?>
<?php if ( isset($USER_EMAIL) ) { ?>
	<li class="bloguser"><?php pf_('By <a href="mailto:%s">%s</a>', $USER_EMAIL, $USER_DISPLAY_NAME);?></li>
<?php } else { ?>
	<li class="bloguser"><?php pf_('By %s', $USER_DISPLAY_NAME); ?></li>
<?php } ?>
<?php if (isset($USER_HOMEPAGE)) { ?>
	<li class="bloguserurl">(<a href="<?php echo $USER_HOMEPAGE; ?>"><?php echo $USER_HOMEPAGE; ?></a>)</li>
<?php } ?>
<?php } # End user profile block ?>
<?php	if ($SHOW_CONTROLS) { ?>
		<li class="blogadmin">
			<a href="<?php echo $UPLOAD_LINK; ?>uploadfile.php"><?php p_('Upload File');?></a>
		</li>
		<li class="blogadmin">
			<a href="<?php echo $EDIT_LINK; ?>edit.php"><?php p_('Edit');?></a>
		</li>
		<li class="blogadmin">
			<a href="<?php echo $DELETE_LINK; ?>"><?php p_("Delete");?></a>
		</li>
<?php } 
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
	</ul>
</div>
</div>
