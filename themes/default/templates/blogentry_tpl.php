<div class="blogentry">
<div class="blogentryheader">
	<h2><a href="<?php echo $PERMALINK; ?>"><?php echo $SUBJECT; ?></a></h2>
</div>
<div class="blogentrybody">
<?php echo $BODY; ?>
</div>
<div class="blogentryfooter">
<ul>
	<li class="blogdate">Posted <?php echo $POSTDATE; ?></li>
<?php if (check_login()) { ?>
	<li class="blogadmin"><a href="<?php echo $POSTEDIT; ?>">Edit</a></li>
	<li class="blogadmin"><a href="<?php echo $POSTDELETE; ?>">Delete</a></li>
<?php } ?>
</ul>
<?php if (! empty($COMMENTCOUNT)) { ?>
<h3><a href="<?php echo $POSTCOMMENTS; ?>">View reader comments (<?php echo$COMMENTCOUNT; ?>)</a></h3>
<?php } ?>
</div>
</div>
