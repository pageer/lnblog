<?php if (isset($SUBJECT)) { ?>
	<h3><a id="<?php echo $ANCHOR; ?>" href="#<?php echo $ANCHOR; ?>"><?php echo $SUBJECT; ?></a></h3>
<?php } else { ?>
<a id="<?php echo $ANCHOR; ?>" href="#<?php echo $ANCHOR; ?>"></a>
<?php } ?>
<?php echo $BODY; ?>
<ul class="footer">
		<li class="commentdate"><?php pf_('Comment posted %s', $DATE); ?></li>
<?php if (isset($USER_ID)) { ?>
	<?php if (! isset($NO_USER_PROFILE)) { /* Display profile link or not */ ?>
		<li class="bloguser"><?php pf_("By %s", '<a href="'.$PROFILE_LINK.'">'.$USER_DISPLAY_NAME.'</a>');?></li>
	<?php } else { /* Use old e-mail link for user name */ ?>
	<?php if ( isset($USER_EMAIL) && isset($USER_NAME) ) { ?>
		<li class="commentname"><?php pf_('By <a href="mailto:%s"><strong>%s</strong></a>', $USER_EMAIL, $USER_NAME);?></li>
	<?php } elseif (isset($USER_EMAIL)) { ?>
		<li class="commentname"><?php pf_('By <a href="mailto:%s"><strong>%s</strong></a>', $USER_EMAIL, $USER_ID);?></li>
	<?php } elseif (isset($USER_NAME)) { ?>
		<li class="commentname"><?php pf_('By <strong>%s</strong>', $USER_NAME);?></li>
	<?php } else { ?>
		<li class="commentname"><?php pf_('By <strong>%s</strong>', $USER_ID); ?></li>
	<?php } ?>
	<?php } /* End user if block */ ?>
	<?php if (isset($USER_HOMEPAGE)) { ?>
		<li class="commenturl">(<a href="<?php echo $USER_HOMEPAGE; ?>"><?php echo $USER_HOMEPAGE; ?></a>)</li>
	<?php } ?>
<?php } else { ?>
		<li class="commentname">By 
		<?php if ($EMAIL)	{ ?>
		<a href="mailto:<?php echo $EMAIL; ?>"><?php echo $NAME; ?></a>
		<?php } else {?>
		<?php echo $NAME; ?>
		<?php } ?>
		</li>
		<?php if ($URL) { ?>
		<li class="commenturl">(<a href="<?php echo $URL; ?>"><?php echo $URL; ?></a>)</li>
		<?php } ?>
<?php } ?>
</ul>
<ul class="admin">
		<?php if ($SHOW_CONTROLS) { 
			foreach ($CONTROL_BAR as $item) { ?>
		<li><?php echo $item; ?></li>
		<?php 
			} 
		} ?>
</ul>
