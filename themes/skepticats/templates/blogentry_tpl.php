<div class="blogentry">
<div class="blogentryheader">
	<h2><a href="<?php echo $PERMALINK; ?>"><?php echo $SUBJECT; ?></a></h2>
	<ul>
		<li class="blogdate">Posted <?php echo $POSTDATE; ?></li>
		<?php
			if (false) {
		?>
		<li class="blogadmin">
			<a href="<?php echo $POSTEDIT; ?>">Edit</a>
			<a href="<?php echo $POSTDELETE; ?>">Delete</a>
		</li>
		<?php 
			}
		?>
	</ul>
</div>
<div class="blogentrybody">
<?php echo $BODY; ?>
</div>
</div>
