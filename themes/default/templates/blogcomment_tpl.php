<div class="fullcomment">
<div class="commentheader">
<?php if (isset($SUBJECT)) { ?>
	<h3><a id="<?php echo $ANCHOR; ?>" href="#<?php echo $ANCHOR; ?>"><?php echo $SUBJECT; ?></a></h3>
<?php } else { ?>
<a id="<?php echo $ANCHOR; ?>" href="#<?php echo $ANCHOR; ?>"></a>
<?php } ?>
</div>
<div class="commentdata">
<?php echo $BODY; ?>
</div>
<div class="commentfooter">
	<ul>
		<li class="commentdate">Comment posted <?php echo $DATE; ?></li>
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
		<?php if ( check_login() ) { ?>
		<li class="admin"><a href="<?php file_exists("delete.php") ? "" : ENTRY_COMMENT_PATH; ?>delete.php?comment=<?php echo $ANCHOR; ?>">Delete</a></li>
		<?php } ?>
	</ul>
</div>
</div>
