<?php

$this->block(
    'field.textarea.tag', function ($vars) {
    extract($vars); ?>
    <textarea name="<?php echo htmlspecialchars($NAME)?>"
    <?php
    foreach ($ATTRIBUTES as $attr => $value):
        echo "$attr=\"" . htmlspecialchars($value) . '" ';
    endforeach;
    ?>
    ><?php echo htmlspecialchars($VALUE)?></textarea>
    <?php
    }
);

$this->block(
    'field.textarea.seplabel', function ($vars) {
    extract($vars); ?>
    <?php if ($LABEL_AFTER): ?>
        <?php $this->showBlock('field.textarea.tag') ?>
    <?php endif ?>
    <label for="<?php echo $this->escape($ATTRIBUTES['id'])?>">
        <?php echo $this->escape($LABEL)?>
    </label>
    <?php if (!$LABEL_AFTER): ?>
        <?php $this->showBlock('field.textarea.tag') ?>
    <?php endif ?>
    <?php
    }
);

$this->block(
    'field.textarea.withlabel', function ($vars) {
    extract($vars); ?>
    <label>
        <?php if (!$LABEL_AFTER): ?>
            <span><?php echo $this->escape($LABEL)?></span>
        <?php endif ?>
        <?php $this->showBlock('field.textarea.tag') ?>
        <?php if ($LABEL_AFTER): ?>
            <span><?php echo $this->escape($LABEL)?></span>
        <?php endif ?>
    </label>
    <?php
    }
);

$this->block(
    'field.textarea.errors', function ($vars) {
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
                $this->showBlock('field.textarea.seplabel');
            } else {
                $this->showBlock('field.textarea.withlabel');
            }
        } else {
            $this->showBlock('field.textarea.tag');
        }
        if (!$SUPPRESS_ERRORS) {
            $this->showBlock('field.textarea.errors');
        }
    }
);
