<div class="article">
<div class="articleheader">
<h2><a href="<?php echo $PERMALINK; ?>"><?php echo $TITLE; ?></a></h2>
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
<?php if ( isset($USER_EMAIL) ) { ?>
	<li class="bloguser">By <a href="mailto:<?php echo $USER_EMAIL; ?>"><?php echo $USER_DISPLAY_NAME; ?></a></li>
<?php } else { ?>
	<li class="bloguser">By <?php echo $USER_DISPLAY_NAME; ?></li>
<?php } ?>
<?php if (isset($USER_HOMEPAGE)) { ?>
	<li class="bloguserurl">(<a href="<?php echo $USER_HOMEPAGE; ?>"><?php echo $USER_HOMEPAGE; ?></a>)</li>
<?php } ?>
<?php	if ($SHOW_CONTROLS) { ?>
		<li class="blogadmin">
			<a href="<?php echo $PERMALINK; ?>uploadfile.php">Upload File</a>
		</li>
		<li class="blogadmin">
			<a href="<?php echo $PERMALINK; ?>edit.php">Edit</a>
		</li>
<?php } ?>
	</ul>
</div>
</div>
