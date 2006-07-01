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
		<li class="commentdate"><?php pf_('Posted %s', $DATE); ?></li>
		<?php 
		if ($SHOW_CONTROLS) { 
			foreach ($CONTROL_BAR as $item) {
		?>
		<li class="admin"><?php echo $item; ?></li>
		<?php 
			} 
		}
		?>
	</ul>
</div>
<div class="commentdata">
<?php echo $BODY; ?>
</div>
</div>
