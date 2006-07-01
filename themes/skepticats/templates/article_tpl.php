<div class="article">
<div class="articleheader">
<h2><a href="<?php echo $PERMALINK; ?>"><?php echo $TITLE; ?></a></h2>
</div>
<div class="articlebody">
<?php echo $BODY; ?>
</div>
<div class="articlefooter">
		<ul>
<?php if ( isset($USER_EMAIL) ) { ?>
		<li><?php pf_('By <a href="mailto:%">%s</a>', $USER_EMAIL, $USER_DISPLAY_NAME); ?></li>
<?php } else { ?>
		<li><?php pf_('By %s', $USER_DISPLAY_NAME); ?></li>
<?php } ?>
		<li class="articledate"><?php pf_("Posted %s", $POSTDATE); ?></li>
<?php if ($EDITDATE != $POSTDATE) { ?>
		<li class="articledate"><?php pf_("Updated %s", $EDITDATE); ?></li>
<? } ?>
<?php	if ($SHOW_CONTROLS) { ?>
		<li class="blogadmin">
			<a href="<?php echo $PERMALINK; ?>uploadfile.php"><?php p_('Upload File'); ?></a>
		</li>
		<li class="blogadmin">
			<a href="<?php echo $PERMALINK; ?>edit.php"><?php p_('Edit');?></a>
		</li>
<?php } ?>
	</ul>
</div>
</div>
