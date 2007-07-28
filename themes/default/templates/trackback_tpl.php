<?php 
# Template: trackback_tpl.php
if (isset($TB_BLOG)) { ?><strong><?php echo $TB_BLOG; ?></strong>: <?php } ?>
<a href="<?php echo $TB_URL; ?>"><?php 
	echo (isset($TB_TITLE) ? $TB_TITLE : $TB_URL);
	?></a>
<?php if (isset($TB_DATA)) { ?>
<p><?php echo $TB_DATA; ?></p>
<?php } ?>
<?php if ($SHOW_EDIT_CONTROLS) { ?>
<ul class="controlbar">
<?php foreach ($CONTROL_BAR as $item) { ?>
<li><?php echo $item; ?></li>
<?php } ?>
</ul>
<?php } ?>
