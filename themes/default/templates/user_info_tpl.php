<h3><?php p_('User Information');?></h3>
<ul>
<?php if (0) { ?>
<li><?php p_('User ID');?>: <?php echo $USER_ID; ?></li>
<?php } ?>
<?php if (! empty($USER_NAME)) { ?>
<li><?php p_('Name');?>: <?php echo $USER_DISPLAY_NAME; ?></li>
<?php }  ?>
<?php if (! empty($USER_EMAIL) &&  empty($CUSTOM_VALUES['contacturl'])) { ?>
<li><?php p_('E-mail');?>: <a href="mailto:<?php echo $USER_EMAIL; ?>"><?php echo $USER_EMAIL; ?></a></li>
<?php } ?>
<?php if (! empty($USER_HOMEPAGE)) { ?>
<li><?php p_('Homepage');?>: <a href="<?php echo $USER_HOMEPAGE; ?>"><?php echo $USER_HOMEPAGE; ?></a></li>
<?php } ?>
<?php foreach ($CUSTOM_FIELDS as $key=>$val) { 
	if (! empty($CUSTOM_VALUES[$key])) { ?>
<li><?php echo $val; ?>: <?php echo $CUSTOM_VALUES[$key];?></li>
<?php	} 
} ?>
</ul>
