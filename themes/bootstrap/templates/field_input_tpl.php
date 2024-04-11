<?php

$this->block(
    'field.input.tag', function ($vars) {
    extract($vars); ?>
    <input type="<?php echo $TYPE?>" 
           name="<?php echo $this->escape($NAME)?>" 
           <?php if ($TYPE == 'checkbox'): ?>
               value="1"
                <?php if ($VALUE): ?>
                   checked="<?php echo $VALUE ? 'checked' : '' ?>"
                <?php endif ?>
           <?php else: ?>
               value="<?php echo $this->escape($VALUE)?>"
           <?php endif ?>
           <?php
           foreach ($ATTRIBUTES as $name => $value):
               echo $this->escape($name) . '="' . $this->escape($value) . '" ';
           endforeach; ?>
    />
    <?php
    }
);

$this->block(
    'field.input.seplabel', function ($vars) {
    extract($vars); ?>
    <?php if ($LABEL_AFTER): ?>
        <?php $this->showBlock('field.input.tag') ?>
    <?php endif ?>
    <label for="<?php echo $this->escape($ATTRIBUTES['id'])?>">
        <?php echo $this->escape($LABEL)?>
    </label>
    <?php if (!$LABEL_AFTER): ?>
        <?php $this->showBlock('field.input.tag') ?>
    <?php endif ?>
    <?php
    }
);

$this->block(
    'field.input.withlabel', function ($vars) {
    extract($vars); ?>
    <label>
        <?php if (!$LABEL_AFTER): ?>
            <span><?php echo $this->escape($LABEL)?></span>
        <?php endif ?>
        <?php $this->showBlock('field.input.tag') ?>
        <?php if ($LABEL_AFTER): ?>
            <span><?php echo $this->escape($LABEL)?></span>
        <?php endif ?>
    </label>
    <?php
    }
);

$this->block(
    'field.input.errors', function ($vars) {
    extract($vars);
    foreach ($ERRORS as $error): ?>
        <span class="error"><?php echo $this->escape($error) ?></span>
    <?php
    endforeach;
    }
);

$this->block(
    'main', function ($vars) {
        extract($vars);
        if ($LABEL) {
            if ($SEPARATE_LABEL) {
                $this->showBlock('field.input.seplabel');
            } else {
                $this->showBlock('field.input.withlabel');
            }
        } else {
            $this->showBlock('field.input.tag');
        }
        if (!$SUPPRESS_ERRORS) {
            $this->showBlock('field.input.errors');
        }
    }
);
