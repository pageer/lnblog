<div class="article">
<div class="articleheader">
	<h2><a href="<?php echo $PERMALINK; ?>"><?php echo $TITLE; ?></a></h2>
	<ul>
		<li class="articledate">Posted <?php echo $POSTDATE; ?></li>
		<?php
			if (check_login() ) {
		?>
		<li class="blogadmin">
			<a href="edit.php">Edit</a>
			<a href="delete.php">Delete</a>
		</li>
		<?php 
			}
		?>
	</ul>
</div>
<div class="articlebody">
<?php echo $BODY; ?>
</div>
</div>
