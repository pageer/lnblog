<div class="article">
<div class="articleheader">
<h2><a href="<?php echo $PERMALINK; ?>"><?php echo $TITLE; ?></a></h2>
<ul>

</ul>
</div>
<div class="articlebody">
<?php echo $BODY; ?>
</div>
<div class="articlefooter">
	<ul>
		<li class="articledate">Published <?php echo $POSTDATE; ?></li>
<?php if ($EDITDATE != $POSTDATE) { ?>
		<li class="articledate">Updated <?php echo $EDITDATE; ?></li>
<?php } ?>
<?php if (! isset($NO_USER_PROFILE)) { # Display profile link or not ?>
		<li class="bloguser"><?php pf_("By %s", '<a href="'.$PROFILE_LINK.'">'.$USER_DISPLAY_NAME.'</a>');?></li>
<?php } elseif ( isset($USER_EMAIL) ) { ?>
		<li>By <a href="mailto:<?php echo $USER_EMAIL; ?>"><?php echo $USER_DISPLAY_NAME; ?></a></li>
<?php } else { ?>
		<li>By <?php echo $USER_DISPLAY_NAME; ?></li>
<?php } ?>
<?php	if ($SHOW_CONTROLS) { ?>
		<li class="blogadmin">
			<a href="<?php echo $PERMALINK; ?>uploadfile.php">Upload File</a>
		</li>
		<li class="blogadmin">
			<a href="<?php echo $PERMALINK; ?>edit.php">Edit</a>
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
<?php }
if ( ! empty($PINGBACKCOUNT) ) { ?>
<li><a href="<?php echo $PINGBACK_LINK;?>"><?php p_("View Pingbacks");?> (<?php echo $PINGBACKCOUNT;?>)</a></li>
<?php } ?>
	</ul>
</div>
</div>
