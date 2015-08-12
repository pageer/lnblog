<?php
# Template: blogcomment_tpl.php
# Template for the full markup to a comment, including the subject, body, 
# metadata (name, e-mail, and so forth).  This also includes the administrative
# links for managing the comment.
#
# This is included whenever a comment is displayed on the page.  This normally
# includes the <showcomments.php> and <showentry.php> pages.
?>
<?php if (isset($SUBJECT)) { ?>
	<h3 class="header"><a id="<?php echo $ANCHOR; ?>" href="#<?php echo $ANCHOR; ?>"><?php echo $SUBJECT; ?></a></h3>
<?php } else { ?>
<a id="<?php echo $ANCHOR; ?>" href="#<?php echo $ANCHOR; ?>"></a>
<?php } ?>
<div class="data">
<?php echo $BODY; ?>
</div>
<ul class="footer">
<?php
if (isset($USER_ID)) {
	if (! isset($NO_USER_PROFILE)) {
		$show_name = '<a href="'.$PROFILE_LINK.'"><strong>'.$USER_DISPLAY_NAME.'</strong></a>';
	} else {
		$show_name = '<a href="mailto:'.$USER_EMAIL.'"><strong>'.$USER_DISPLAY_NAME.'</strong></a>';
	}
	
	if (isset($USER_HOMEPAGE)) {
		$show_url = "(<a href=\"$USER_HOMEPAGE\">$USER_HOMEPAGE</a>)";
	} else {
		$show_url = '';
	}
} else {

	if ($EMAIL && $SHOW_MAIL) {
		$show_name = '<a href="mailto:'.$EMAIL.'">'.$NAME.'</a>';
	} else {
		$show_name = $NAME;
	}
	
	if ($URL) {
		$show_url = "(<a href=\"$URL\">$URL</a>)";
	} else {
		$show_url = '';
	}

}
?>
<li><?php pf_("Posted by %s %s at %s", $show_name, $show_url, $DATE); ?></li>
<?php
	# Show the administrative links.
	if ($SHOW_CONTROLS): ?>
	<li>
		<ul class="controlbar">
		<?php foreach ($CONTROL_BAR as $item): ?>
		<li><?php echo $item; ?></li>
		<?php endforeach; ?>
		</ul>
	</li>
	<?php endif; ?>
</ul>
