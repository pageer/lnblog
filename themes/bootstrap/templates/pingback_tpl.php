<?php if ($PB_LOCAL) { /* Start local pingback template */ ?>
<a href="<?php echo $PB_SOURCE;?>"><?php echo ($PB_TITLE?$PB_TITLE:$PB_SOURCE);?></a>
<?php } else { /* Start remote pingback template */ ?>
<strong><?php p_("Pingback");?></strong>: 
<a href="<?php echo $PB_SOURCE;?>"><?php echo ($PB_TITLE?$PB_TITLE:$PB_SOURCE);?></a>
<?php if ($PB_EXCERPT) { ?>
<p><?php echo $PB_EXCERPT; ?></p>
<?php } ?>
<?php } /* End remote pingback stuff, the rest is shared */ ?>
<?php if ($SHOW_EDIT_CONTROLS) { ?>
<ul class="controlbar">
<?php foreach ($CONTROL_BAR as $item) { ?>
<li><?php echo $item; ?></li>
<?php } ?>
</ul>
<?php } 
