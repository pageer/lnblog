<div class="article">
<div class="articleheader">
<h2><a href="<?php echo $PERMALINK; ?>"><?php echo $TITLE; ?></a></h2>
<ul class="postdata">
<?php if ( isset($USER_EMAIL) && isset($USER_NAME) ) { ?>
	<li class="bloguser">By <a href="mailto:<?php echo $USER_EMAIL; ?>"><?php echo $USER_NAME; ?></a></li>
<?php } elseif (isset($USER_EMAIL)) { ?>
	<li class="bloguser">By <a href="mailto:<?php echo $USER_EMAIL; ?>"><?php echo $USER_ID; ?></a></li>
<?php } elseif (isset($USER_NAME)) { ?>
	<li class="bloguser">By <?php echo $USER_NAME; ?></li>
<?php } else { ?>
	<li class="bloguser">By <?php echo $USER_ID; ?></li>
<?php } ?>
<?php if (isset($USER_HOMEPAGE)) { ?>
	<li class="bloguserurl">(<a href="<?php echo $USER_HOMEPAGE; ?>"><?php echo $USER_HOMEPAGE; ?></a>)</li>
<?php } ?>
</ul>
</div>
<div class="articlebody">
<?php echo $BODY; ?>
</div>
<div class="articlefooter">
	<ul>
		<li class="articledate">Published <?php echo $POSTDATE; ?></li>
<?php if ($EDITDATE != $POSTDATE) { ?>
		<li class="articledate">Last updated <?php echo $EDITDATE; ?></li>
<?php } ?>
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
