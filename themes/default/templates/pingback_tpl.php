<strong><?php p_("Pingback");?></strong>: 
<a href="<?php echo $PB_SOURCE;?>"><?php echo ($PB_TITLE?$PB_TITLE:$PB_SOURCE);?></a>
<?php if ($PB_EXCERPT) { ?>
<p><?php echo $PB_EXCERPT; ?></p>
<?php } ?>
<?php if ($SHOW_EDIT_CONTROLS) { ?>
<ul>
<?php foreach ($CONTROL_BAR as $item) { ?>
<li><?php echo $item; ?></li>
<?php } ?>
</ul>
<?php } ?>
