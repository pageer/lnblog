<h2><?php echo $LIST_TITLE ?></h2>
<?php if (isset($LIST_HEADER)) { ?>
<p><?php echo $LIST_HEADER; ?></p>
<?php }
	if (!empty($ITEM_LIST)) { 
?>
<ol<?php if (isset($LIST_CLASS)) { ?> class="<?php echo $LIST_CLASS; ?>"<?php } ?>>
<?php 
		foreach ($ITEM_LIST as $ITEM) {
?>
<li<?php if (isset($ITEM_CLASS)) { ?> class="<?php echo $ITEM_CLASS; ?>"<?php } ?>>
<?php echo $ITEM; ?>
</li>
<?php 
		} 
?>
</ol>
<?php } 
	if (isset($LIST_FOOTER)) { 
?>
<p><?php echo $LIST_FOOTER; ?></p>
<?php }
