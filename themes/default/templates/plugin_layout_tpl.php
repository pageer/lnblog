<?php if ( ! empty($PLUGIN_TITLE) ) { ?>
<h3><?php echo $PLUGIN_TITLE;?></h3>
<?php } /* End title if */ ?>
<?php if ($PLUGIN_TYPE == "linklist") { ?>
<ul>
<?php foreach ($PLUGIN_LINKS as $item) { ?>
<li><a href="<?php echo $item["link"];?>" <?php echo $item["attribs"];?>>
<?php echo $item["text"];?></a></li>  
<?php } /* End foreach */ ?>
</ul>
<?php } else { /* type != linklist */ ?>
<div class="panel">
<?php echo $PLUGIN_MARKUP;?>
</div>
<?php } 
