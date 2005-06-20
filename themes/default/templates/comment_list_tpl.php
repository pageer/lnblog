<h2>Comments on <a href="<?php echo $ENTRY_PERMALINK; ?>"><?php echo $ENTRY_SUBJECT; ?></a></h2>
<ol class="commentlist">
<?php 
if (!empty($COMMENT_LIST)) {
	echo $COMMENT_LIST; 
} else {
?><li>No comments posted yet.</li><?php } ?>
</ol>
