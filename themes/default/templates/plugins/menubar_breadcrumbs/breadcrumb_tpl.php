<?php if (!empty($HEADER)): ?>
<h2><?php echo $HEADER?></h2>
<?php endif ?>
<ul class="location">
<?php foreach ($ITEMS as $item): ?>
    <li><a href="<?php echo $item['url']?>"><?php echo $this->escape($item['title'])?></a></li>
<?php endforeach ?>
<ul>
