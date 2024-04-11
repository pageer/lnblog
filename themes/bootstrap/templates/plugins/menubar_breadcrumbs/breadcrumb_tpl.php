<nav>
<ol class="breadcrumb">
<?php if (!empty($HEADER)): ?>
    <li class="breadcrumb-item">
        <a href="<?php echo $HOMEURL?>"><?php echo $this->escape($HEADER)?></a>
    </li>
<?php endif ?>
<?php foreach ($ITEMS as $item): ?>
    <li class="breadcrumb-item">
        <a href="<?php echo $item['url']?>"><?php echo $this->escape($item['title'])?></a>
    </li>
<?php endforeach ?>
<ol>
</nav>
