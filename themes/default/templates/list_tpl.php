<?php if (isset($LIST_TITLE)) { ?>
<h2><?php echo $LIST_TITLE; ?></h2>
<?php } 
	if (isset($LIST_HEADER)) { 
?>
<p><?php echo $LIST_HEADER; ?></p>
<?php } 
	if (! empty($LINK_LIST) || ! empty($ITEM_LIST)) { 
?>
<?php 
	if (isset($ORDERED)) $tag = "ol";
	else $tag = "ul";
	echo "<$tag".(isset($LIST_CLASS)?" class=\"$LIST_CLASS\"":"").">\n";

	# We have two options: LINK_LIST, which is an array of links and titles,
	# and ITEM_LIST, which is an array of strings.
	if (isset($LINK_LIST)) {
		foreach ($LINK_LIST as $LINK) { ?>
<li><a href="<?php echo $LINK["link"]; ?>"><?php echo $LINK["title"]; ?></a></li>
<?php 
		}
	} elseif (isset($ITEM_LIST)) {
		foreach ($ITEM_LIST as $ITEM) { ?>
<li<?php if (isset($ITEM_CLASS)) { echo " class=\"$ITEM_CLASS\""; } ?>>
<?php echo $ITEM; ?>
</li>
<?php 
		}
	}
	echo "</$tag>";
?>
<?php } 
	if (isset($LIST_FOOTER)) { 
?>
<p><?php echo $LIST_FOOTER; ?></p>
<?php } ?>
