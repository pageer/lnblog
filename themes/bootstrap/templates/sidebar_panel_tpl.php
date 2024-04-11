<?php
# Template: sidebar_panel_tpl.php
# This template is the standard markup for a panel in the sidebar.  Like
# <list_tpl.php>, this includes a number of parameters to be as general-purpose
# as possible.
#
# Parameters:
# PANEL_ID      - An ID to apply to the panel.
# PANEL_CLASS   - The CSS class to apply to the panel.
# PANEL_TITLE   - Title text to be put in a heading above the main panel.
# TITLE_LINK    - A URL which the title text should be a link to.
# PANEL_LIST    - An array of strings or arrays for the panel body.  If set,
#                 indicates that the panel should be an *unordered* list with
#                 each member of the array representing a list item.  If an item
#                 is an array, each key will be an attribute of the list item,
#                 while the content will be in the 'description' key.
# PANEL_NUMLIST - Like PANEL_LIST, except makes th panel an *ordered* list.
# PANEL_CONTENT - The main HTML content for the panel.  Only applies if niether
#                 PANEL_LIST nor PANEL_NUMLIST are set.  In this case, the main
#                 body of the panel is a DIV tag.

$this->block('sidebar.item-list', function ($vars) {
    extract($vars);
    $list_tag = isset($PANEL_NUMLIST) ? 'ol' : 'ul';
    $list = isset($PANEL_LIST) ? $PANEL_LIST : $PANEL_NUMLIST;
    echo "<$list_tag>\n";
    foreach ($list as $item) {
        $attrs = '';
        $text = '';
        if (is_array($item)) {
            $text = $item['description'];
            unset($item['description']);
            foreach ($item as $key=>$val) {
                $attrs .= ' ' . $this->escape($key) . '="' . $this->escape($val) . '"';
            }
        } else {
            $text = $item;
        }
        echo "<li $attrs>$text</li>\n";
    }
    echo "</$list_tag>\n";
});

$this->block('main', function ($vars) {
    extract($vars);
    $id = $PANEL_ID ?? '';
    if ($id) {
        $id = " id=\"$id\"";
    }
    $title = $PANEL_TITLE ?? '';
    if (isset($TITLE_LINK)) {
        $title = "<a href=\"$TITLE_LINK\">$title</a>";
    }
?>
    <div <?php echo $id?> class="card mb-3 <?php echo $PANEL_CLASS ?? ''?>">
        <div class="card-body">
            <?php if ($title):?>
            <h5 class="card-title"><?php echo $title?></h5>
            <?php endif?>

            <?php if (isset($PANEL_LIST) || isset($PANEL_NUMLIST)): ?>
                <?php $this->showBlock('sidebar.item-list'); ?>
            <?php else: ?>
                <p class="card-text"><?php echo $PANEL_CONTENT ?? ''?></p>
            <?php endif ?>
        </div>
    </div><?php
});
