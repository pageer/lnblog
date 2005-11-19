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
<?php if ( isset($USER_EMAIL) ) { ?>
	<li class="bloguser"><?php pf_('By <a href="mailto:%s">%s</a>', $USER_EMAIL, $USER_DISPLAY_NAME);?></li>
<?php } else { ?>
	<li class="bloguser"><?php pf_('By %s', $USER_DISPLAY_NAME); ?></li>
<?php } ?>
<?php if (isset($USER_HOMEPAGE)) { ?>
	<li class="bloguserurl">(<a href="<?php echo $USER_HOMEPAGE; ?>"><?php echo $USER_HOMEPAGE; ?></a>)</li>
<?php } ?>
<?php	if ($SHOW_CONTROLS) { ?>
		<li class="blogadmin">
			<a href="<?php echo $PERMALINK; ?>uploadfile.php"><?php p_('Upload File');?></a>
		</li>
		<li class="blogadmin">
			<a href="<?php echo $PERMALINK; ?>edit.php"><?php p_('Edit');?></a>
		</li>
<?php } ?>
	</ul>
</div>
</div>
