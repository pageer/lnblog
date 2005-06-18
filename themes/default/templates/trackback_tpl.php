<li class="trackback">
<?php if (isset($TB_BLOG)) { ?><strong><?php echo $TB_BLOG; ?></strong>: <?php } ?>
<a href="<?php echo $TB_URL; ?>"><?php 
	echo (isset($TB_TITLE) ? $TB_TITLE : $TB_URL);
	?></a>
<?php if (isset($TB_DATA)) { ?>
<p><?php echo $TB_DATA; ?></p>
<?php } ?>
</li>
