<div class="article">
<div class="articleheader">
<h2><a href="<?php echo $PERMALINK; ?>"><?php echo $TITLE; ?></a></h2>
</div>
<div class="articlebody">
<?php echo $BODY; ?>
</div>
<div class="articlefooter">
	<ul>
		<li class="articledate">Updated <?php echo $POSTDATE; ?></li>
		<?php
			if (check_login() ) {
		?>
		<li class="blogadmin">
			<a href="edit.php">Edit</a>
			<?php if (0) { ?><a href="delete.php">Delete</a><?php } ?>
		</li>
		<?php 
			}
		?>
	</ul>

</div>
</div>
