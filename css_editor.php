<?php
require_once("blogconfig.php");

$selection_map = array();

function init_map($selector, $property, $name) {
	global $selection_map;
	if (! isset($selection_map[$selector])) {
		$selection_map[$selector] = array();
	}
}

function textbox($name, $label, $selector, $property, $default) {
	
}

function checkbox($name, $label, $selector, $property, $default) {
	
}

function radiobuttons($name, $labels, $selector, $property, $valuemap, $default) {
	
}

function selectbox($name, $label, $selector, $property, $valuemap, $default) {
	
}
?>
