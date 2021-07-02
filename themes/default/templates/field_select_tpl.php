<?php

$this->block(
    'field.select.tag', function ($vars) {
    extract($vars); ?>
    <select name="<?php echo htmlspecialchars($NAME)?>" 
        <?php
        foreach ($ATTRIBUTES as $attr => $value):
            echo "$attr=\"" . htmlspecialchars($value) . '" ';
        endforeach;
        ?>
    />
        <?php foreach ($OPTIONS as $value => $label):?>
        <option value="<?php echo htmlspecialchars($value)?>">
            <?php echo htmlspecialchars($label)?>
        </option>
        <?php endforeach ?>
    </select>
    <?php
    }
);

$this->block(
    'field.select.seplabel', function ($vars) {
    extract($vars); ?>
    <?php if ($LABEL_AFTER): ?>
        <?php $this->showBlock('field.select.tag') ?>
    <?php endif ?>
    <label for="<?php echo $this->escape($ATTRIBUTES['id'])?>">
        <?php echo $this->escape($LABEL)?>
    </label>
    <?php if (!$LABEL_AFTER): ?>
        <?php $this->showBlock('field.select.tag') ?>
    <?php endif ?>
    <?php
    }
);

$this->block(
    'field.select.withlabel', function ($vars) {
    extract($vars); ?>
    <label>
        <?php if (!$LABEL_AFTER): ?>
            <span><?php echo $this->escape($LABEL)?></span>
        <?php endif ?>
        <?php $this->showBlock('field.select.tag') ?>
        <?php if ($LABEL_AFTER): ?>
            <span><?php echo $this->escape($LABEL)?></span>
        <?php endif ?>
    </label>
    <?php
    }
);

$this->block(
    'field.select.errors', function ($vars) {
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
                $this->showBlock('field.select.seplabel');
            } else {
                $this->showBlock('field.select.withlabel');
            }
        } else {
            $this->showBlock('field.select.tag');
        }
        if (!$SUPPRESS_ERRORS) {
            $this->showBlock('field.select.errors');
        }
    }
);
