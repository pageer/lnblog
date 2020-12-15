<?php 
# Template: list_tpl.php
# A template for HTML lists.  It does ordered or unordered lists,
# including both plain lists and lists of links.  This template is highly modular
# and includes a number of possible variabes, listed below.
#
# Variables:
# LIST_TITLE  - The title heading to put above the list body.
# LIST_HEADER - Some header text.  This goes between the title
#               and the list body.
# LIST_FOOTER - Like LIST_HEADER, but goes at the bottom.
# FORM_ACTION - If set, the list body is enclosed in a form.
#               The value in this variable is the page to which the
#               form contents will be posted on submit.
# FORM_HEADER - Some header text to go at the top of the form.
# FORM_FOOTER - Like FORM_HEADER, but goes at the bottom.
# ORDERED     - If set, the list will be ordered.  Otherwise, the 
#               template will assume an unordered list.
# LIST_CLASS  - If specified, this CSS class will be applied to each
#               list item.
# LINK_LIST   - An array of associative arrays.  Each array item is an associative
#               array with keys "link" and "title", which are a URL and the 
#               associated descriptive link text respectively.  If this is set,
#               then the template will create an array of hyperlinks.  Use this 
#               for quickly creating a list of well-formed links, e.g. for archive
#               pages or simple search results.
# ITEM_LIST   - An array containing the text for each list item.  If this is
#               specified instead of LINK_LIST, then the template will create a 
#               plain list with the items containing exactly the text given in
#               each array item.
if (isset($LIST_TITLE)) { ?>
<h2><?php echo $LIST_TITLE; ?></h2>
<?php } 
if (isset($LIST_HEADER)) { ?>
<p><?php echo $LIST_HEADER; ?></p>
<?php } 
if (! empty($LINK_LIST) || ! empty($ITEM_LIST)) { 
    
    if (isset($FORM_ACTION)) { ?>
<form method="post" action="<?php echo $FORM_ACTION;?>">
    <?php $this->outputCsrfField() ?>
    <?php 
        if (isset($FORM_HEADER)) echo $FORM_HEADER;
    }

    if (isset($ORDERED)) $tag = "ol";
    else $tag = "ul";
    echo "<$tag".(isset($LIST_CLASS)?" class=\"$LIST_CLASS\"":"").">\n";

    # We have two options: LINK_LIST, which is an array of links and titles,
    # and ITEM_LIST, which is an array of strings.
    if (isset($LINK_LIST)) {
        foreach ($LINK_LIST as $LINK) { ?>
<li><a href="<?php echo $LINK["link"];?>"><?php echo $LINK["title"];?></a></li>
<?php 
        }
    } elseif (isset($ITEM_LIST)) {
        foreach ($ITEM_LIST as $ITEM) { ?>
<li<?php echo isset($ITEM_CLASS) ? " class=\"$ITEM_CLASS\"" : '' ?>>
<?php echo $ITEM; ?>
</li>
<?php 
        }
    }

    echo "</$tag>";

    if (isset($FORM_ACTION)) { 
        if (isset($FORM_FOOTER)) echo $FORM_FOOTER; ?>
</form>
    <?php }

}

if (isset($LIST_FOOTER)) { ?>
<p><?php echo $LIST_FOOTER; ?></p>
<?php } 
