<?php if (isset($ARCHIVE_TITLE)) { ?>
<h2>Archive of <?php echo $ARCHIVE_TITLE; ?></h2>
<?php } ?>
<ul>
<?php foreach ($LINK_LIST as $LINK) { ?>
<li><a href="<?php echo $LINK["URL"]; ?>"><?php echo $LINK["DESC"]; ?></a></li>
<?php } ?>
</ul>
