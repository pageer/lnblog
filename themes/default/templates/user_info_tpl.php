<h3>User Information</h3>
<ul>
<li>User ID: <?php echo $USER_ID; ?></li>
<?php if (isset($USER_NAME)) { ?>
<li>Real Name: <?php echo $USER_NAME; ?></li>
<?php } ?>
<?php if (isset($USER_EMAIL)) { ?>
<li>E-mail: <a href="mailto:<?php echo $USER_EMAIL; ?>"><?php echo $USER_EMAIL; ?></a></li>
<?php } ?>
<?php if (isset($USER_HOEMPAGE)) { ?>
<li>Homepage: <a href="<?php echo $USER_HOMEPAGE; ?>"><?php echo $USER_HOMEPAGE; ?></a></li>
<?php } ?>
</ul>
