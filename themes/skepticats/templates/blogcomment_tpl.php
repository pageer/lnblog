<div class="fullcomment">
<div class="commentheader">
	<h3><a id="<?php echo $ANCHOR; ?>" href="#<?php echo $ANCHOR; ?>"><?php echo $SUBJECT; ?></a></h3>
	<ul>
		<li class="commentname">By 
		<?php if ($EMAIL)	{ ?>
		<a href="mailto:<?php echo $EMAIL; ?>"><?php echo $NAME; ?></a>
		<?php } else {?>
		<?php echo $NAME; ?>
		<?php } ?>
		</li>
		<?php if ($URL) { ?>
		<li class="commenturl"><a href="<?php echo $URL; ?>"><?php echo $URL; ?></a></li>
		<?php } ?>
		<li class="commentdate">Posted <?php echo $DATE; ?></li>
		<?php if ( check_login() ) { ?>
		<li class="admin"><a href="<?php file_exists("delcomment.php") ? "" : ENTRY_COMMENT_PATH; ?>"></a></li>
		<?php } ?>
	</ul>
</div>
<div class="commentdata">
<?php echo $BODY; ?>
</div>
</div>
