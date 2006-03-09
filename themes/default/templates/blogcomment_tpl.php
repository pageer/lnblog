<?php if (isset($SUBJECT)) { ?>
	<h3 class="commentheader"><a id="<?php echo $ANCHOR; ?>" href="#<?php echo $ANCHOR; ?>"><?php echo $SUBJECT; ?></a></h3>
<?php } else { ?>
<a id="<?php echo $ANCHOR; ?>" href="#<?php echo $ANCHOR; ?>"></a>
<?php } ?>
<div class="commentdata">
<?php echo $BODY; ?>
</div>
<div class="commentfooter">
	<ul>
		<li class="commentdate"><?php pf_("Comment posted %s", $DATE); ?></li>
<?php if (isset($USER_ID)) { ?>
	<?php if (! isset($NO_USER_PROFILE)) { # Display profile link or not ?>
			<li class="bloguser"><?php pf_("By %s", '<a href="'.$PROFILE_LINK.'">'.$USER_DISPLAY_NAME.'</a>');?></li>
	<?php } else { ?>
	<?php if ( isset($USER_EMAIL) && isset($USER_NAME) ) { ?>
		<li class="commentname"><?php pf_("By %s", '<a href="mailto:'.$USER_EMAIL.'"><strong>'.$USER_NAME.'</strong></a>'); ?></li>
	<?php } elseif (isset($USER_EMAIL)) { ?>
		<li class="commentname"><?php pf_("By %s", '<a href="mailto:'.$USER_EMAIL.'"><strong>'.$USER_ID.'</strong></a>'); ?></li>
	<?php } elseif (isset($USER_NAME)) { ?>
		<li class="commentname"><?php pf_("By %s", '<strong>'.$USER_NAME.'</strong>'); ?></li>
	<?php } else { ?>
		<li class="commentname"><?php pf_("By %s", '<strong>'.$USER_ID.'</strong>'); ?></li>
	<?php } ?>
	<?php } # End user profile block ?>
	<?php if (isset($USER_HOMEPAGE)) { ?>
		<li class="commenturl">(<a href="<?php echo $USER_HOMEPAGE; ?>"><?php echo $USER_HOMEPAGE; ?></a>)</li>
	<?php } ?>
<?php } else { ?>
		<li class="commentname">
		<?php if ($EMAIL && $SHOW_MAIL) { ?>
		<?php pf_('By %s', '<a href="mailto:'.$EMAIL.'">'.$NAME.'</a>'); ?>
		<?php } else {?>
		<?php pf_('By %s', $NAME); ?>
		<?php } ?>
		</li>
		<?php if ($URL) { ?>
		<li class="commenturl">(<a href="<?php echo $URL; ?>"><?php echo $URL; ?></a>)</li>
		<?php } ?>
<?php } ?>
		<?php 
		if ($SHOW_CONTROLS) { 
			foreach ($CONTROL_BAR as $item) {
		?>
		<li class="admin"><?php echo $item; ?></li>
		<?php 
			}
		} 
		?>
	</ul>
</div>
