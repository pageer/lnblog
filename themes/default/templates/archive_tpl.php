<?php if (isset($ARCHIVE_TITLE)) { ?>
<h2>Archive of <?php echo $ARCHIVE_TITLE; ?></h2>
<?php } ?>
<ul>
<?php foreach ($LINK_LIST as $LINK) { ?>
<li><a href="<?php echo $LINK["link"]; ?>"><?php echo $LINK["title"]; ?></a></li>
<?php } ?>
</ul>
<?php if (isset($SHOW_TEXT)) { ?>
<p><a href="?show=all">Show all entries at once</a></p>
<?php } ?>
