<h3><?php p_('User Information');?></h3>
<ul>
<?php if (0) { ?>
<li><?php p_('User ID');?>: <?php echo $USER_ID; ?></li>
<?php } ?>
<?php if (isset($USER_NAME)) { ?>
<li><?php p_('Real Name');?>: <?php echo $USER_NAME; ?></li>
<?php } ?>
<?php if (isset($USER_EMAIL)) { ?>
<li><?php p_('E-mail');?>: <a href="mailto:<?php echo $USER_EMAIL; ?>"><?php echo $USER_EMAIL; ?></a></li>
<?php } ?>
<?php if (isset($USER_HOEMPAGE)) { ?>
<li><?php p_('Homepage');?>: <a href="<?php echo $USER_HOMEPAGE; ?>"><?php echo $USER_HOMEPAGE; ?></a></li>
<?php } ?>
<?php foreach ($CUSTOM_FIELDS as $key=>$val) { 
	if (! empty($CUSTOM_VALUES[$key])) { ?>
<li><?php echo $val;?>: <?php echo $CUSTOM_VALUES[$key];?></li>
<?php	} 
} ?>
</ul>
