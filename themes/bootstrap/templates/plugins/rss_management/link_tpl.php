<?php
$description = $feed['description'];
$image = $feed['icon'] ?? '';
$class = $feed['class'] ?? 'news-feed-link';
?>
<?php if (!empty($SHOW_SIDEBAR)): ?>
<a href="<?php echo $feed['href']?>" class="<?php echo $class?>">
    <?php echo htmlspecialchars($description)?>
    <?php if ($image && $USE_ICONS): ?>
    <img class="feed-icon" alt="<?php echo htmlspecialchars($description)?>" 
         title="<?php echo htmlspecialchars($description)?>" src="<?php echo $image?>" />
    <?php endif ?>
</a>
<?php elseif (!empty($SHOW_TAG)): ?>
<a href="<?php echo $feed['href']?>" class="news-feed-link">
    <?php if ($USE_ICONS): ?>
        <img class="feed-icon" alt="<?php echo htmlspecialchars($description)?>" 
             title="<?php echo htmlspecialchars($description)?>" src="<?php echo $image?>" />
    <?php else: ?>
    (<?php echo htmlspecialchars($description)?>)
    <?php endif ?>
</a>
<?php endif ?>
