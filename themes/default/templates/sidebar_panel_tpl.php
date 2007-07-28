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

if (! function_exists('sidebar_panel_show_item')) {
	function sidebar_panel_show_item(&$item) {
		if (is_array($item)) {
			$ret = "<li";
			foreach ($item as $key=>$val) {
				if ($key != 'description') $ret .= " $key=\"$val\"";
			}
			$ret .= ">".$item['description']."</li>\n";
		} else {
			$ret = "<li>$item</li>\n";
		}
		return $ret;
	}
}

if (isset($PANEL_ID)) $id_markup = ' id="'.$PANEL_ID.'"';
else $id_markup = '';

if (isset($PANEL_CLASS)) $class_markup = ' class="'.$PANEL_CLASS.'"';
else $class_markup = '';

if (isset($PANEL_TITLE)) {
	if (isset($TITLE_LINK)) {
		echo "<h3><a href=\"$TITLE_LINK\">$PANEL_TITLE</a></h3>\n";
	} else {
		echo "<h3>$PANEL_TITLE</h3>\n";
	}
}

if (isset($PANEL_LIST)) {
	echo "<ul".$id_markup.$class_markup.">\n";
	foreach ($PANEL_LIST as $item) {
		echo sidebar_panel_show_item($item);
	}
	echo "</ul>\n";
} elseif (isset($PANEL_NUMLIST)) {
	echo "<ol".$id_markup.$class_markup.">\n";
	foreach ($PANEL_NUMLIST as $item) {
		echo sidebar_panel_show_item($item);
	}
	echo "</ol>\n";
} else {
	echo "<div".$id_markup.$class_markup.">\n";
	echo $PANEL_CONTENT;
	echo "</div>\n";
}
?>