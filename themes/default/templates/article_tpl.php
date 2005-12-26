<div class="article">
<div class="articleheader">
<h2><a href="<?php echo $PERMALINK; ?>"><?php echo $TITLE; ?></a></h2>
</div>
<div class="articlebody">
<?php echo $BODY; ?>
</div>
<div class="articlefooter">
	<ul>
		<li class="articledate"><?php pf_("Published %s", $POSTDATE); ?></li>
<?php if ($EDITDATE != $POSTDATE) { ?>
		<li class="articledate"><?php pf_("Updated %s", $EDITDATE); ?></li>
<? } ?>
<?php if ( isset($USER_EMAIL) ) { ?>
		<li><?php pf_("By %s", '<a href=\"mailto: '.$USER_EMAIL.'">'.$USER_DISPLAY_NAME.'</a>'); ?></li>
<?php } else { ?>
		<li><?php pf_("By %s", $USER_DISPLAY_NAME); ?></li>
<?php } ?>
<?php if (! empty($TAGS)) { ?>
	<li><?php p_("Tags");?>: <?php echo implode(", ", $TAGS); ?></li>
<?php } ?>
<?php	if ($SHOW_CONTROLS) { ?>
		<li class="blogadmin">
			<a href="<?php echo $UPLOAD_LINK; ?>"><?php p_("Upload File"); ?></a>
		</li>
		<li class="blogadmin">
			<a href="<?php echo $EDIT_LINK; ?>"><?php p_("Edit"); ?></a>
		</li>
<?php } ?>
	</ul>
</div>
</div>
