<?php
# Template: pager_tpl.php
# This template provides the logic and markup for the entry pager.
# The pager is displayed whenever there is an offset to a list of entries
# that supports it.  It provides forward and backward navigation with
# a given page size.

$this->block('pager.previous', function ($vars) {
    extract($vars, EXTR_OVERWRITE);
    if ($PAGE > $START_PAGE): 
        $new_offset = $PAGE - $INCREMENT;
        $offset_url = "?$PAGE_VAR=$new_offset"; ?>
        <a class="pager-prev link" href="<?php echo $offset_url?>">
            &laquo; <?php p_("Newer entries")?>
        </a><?php
    endif;
});

$this->block('pager.next', function ($vars) {
    extract($vars, EXTR_OVERWRITE);
    if ($MORE_ENTRIES): 
        $PAGE = $PAGE ?: $START_PAGE;
        $new_offset = $PAGE + $INCREMENT;
        $offset_url = "?$PAGE_VAR=$new_offset"; ?>
        <a class="pager-next link" href="<?php echo $offset_url?>">
            <?php p_("Older entries")?> &raquo;
        </a><?php
    endif;
});

$this->block('pager.basic', function ($vars) {
    extract($vars, EXTR_OVERWRITE);
    if ($PAGE > $START_PAGE || $MORE_ENTRIES): ?>
        <div class="pager">
            <?php $this->showBlock('pager.previous') ?>
            <?php $this->showBlock('pager.next') ?>
        </div>
    <?php
    endif;
});

$this->block('main', function ($vars) {
    $this->showBlock('pager.basic');
});
