<?php

# Class: SimpleXMLWriter
# A class for converting objects into XML.
# The XML produced uses a simple, flat structure.  The outer element is 
# named for the object's class, with each property of the object having a 
# tag of the same name.  Arrays are represented as an element with a number 
# of generically-named child nodes.
class SimpleXMLWriter {

	public $object = null;
	public $exclude_list = array();
	public $cdata_list = array();

	public function __construct(&$object) {
		$this->object = $object;
	}

	# Method:
	# Adds to or sets the list of excluded properties.  These are object property
	# fields that will not be added to the XML file.
	#
	# Parameters:
	# An arbitrary number of strings, each matching the name of a property.

	public function exclude() {
		$list = func_get_args();
		$this->exclude_list = array_merge($this->exclude_list, $list);
	}

	# Method:
	# Adds to or sets the list of CDATA properties.  These are the properties
	# that will be marked as character data (CDATA) in the XML file.  CDATA is
	# not parsed by the XML parser and so can contain characters that are not 
	# valid in XML.  The only exception is the ']]>' character sequence, which 
	# ends a CDATA block.
	#
	# Parameters:
	# An arbitrary number of strings, each matching the name of a property.

	public function cdata() {
		$list = func_get_args();
		$this->cdata_list = array_merge($this->cdata_list, $list);
	}

	public function serializeArray(&$arr, $tag) {
		
		$ret = "<$tag type=\"array\">\n";
		
		if (substr($tag, strlen($tag) - 1, 1) == 's') {
			$newtag = substr($tag, 0, strlen($tag) - 1);
		} else {
			$newtag = "elem";
		}
		foreach ($arr as $key=>$val) {
			if (! is_numeric($key)) $currtag = $key;
			else $currtag = $newtag;
			if (is_array($val)) {
				$ret .= SimpleXMLWriter::serializeArray($val, $currtag);
			} else {
				$ret .= "<$currtag>".htmlspecialchars($val)."</$currtag>\n";
			}
		}
		$ret .= "</$tag>\n";
		return $ret;
	}

	public function serializeObject(&$obj) {
		$ret = '<'.get_class($obj).">\n";
		foreach ($obj as $field=>$value) {
			if (! in_array($field, $this->exclude_list) &&
			      (! is_string($value) || $value !== '') ) {
				if (is_bool($value)) {
					$ret .= "<$field type=\"bool\">".($value ? "true" : "false")."</$field>\n";
				} elseif (is_int($value)) {
					$ret .= "<$field type=\"int\">$value</$field>\n";
				} elseif (is_array($value)) {
					$ret .= SimpleXMLWriter::serializeArray($value, $field);
				} elseif (in_array($field, $this->cdata_list)) {
					$ret .="<$field><![CDATA[$value]]></$field>\n";
				} else {
					$ret .= "<$field>".htmlspecialchars($value)."</$field>\n";
				}
			}
		}
		$ret .= '</'.get_class($this->object).">\n";
		return $ret;
	}

	public function serialize() {
		$ret = '<?xml version="1.0" encoding="utf-8"?>'."\n";
		if (is_object($this->object)) {
			$ret .= $this->serializeObject($this->object);
		} elseif (is_array($this->object)) {
			$ret .= $this->serializeArray($this->object, "array");
		} else {
			$ret .= "<item>".$this->object."</item>\n";
		}
		return $ret;
	}
}

# Class: SimpleXMLReader
# A class for parsing the XML produced by the <SimpleXMLWriter> class and 
# creating or populating an object based on it.

class SimpleXMLReader {
	
	public $file = '';
	public $domtree = false;
	protected $stack = array();
	protected $stack_length = 0;
	protected $options = array();
	
	public function __construct($file) {
		$this->file = $file;
	}

	public function setOption($name, $value=true) {
		$this->options[$name] = $value;
	}

	public function getOption($name) {
		if (isset($this->options[$name])) {
			return $this->options[$name];
		} else {
			return false;
		}
	}

	public function do_open($parser, $tag, $attributes) {
		$new = array('tag'=>strtolower($tag), 'attributes'=>$attributes, 'text'=>'');
		$this->stack_length = array_push($this->stack, $new);
	}

	public function do_close($parser, $tag) {
		$full = array_pop($this->stack);
		$this->stack_length--;
		if ($this->stack_length > 0) {
			$this->stack[$this->stack_length-1]['children'][] = $full;	
		} else {
			$this->domtree = $full;
		}
	}

	public function do_cdata($parser, $data) {
		if ($this->getOption('no_blank_cdata')) {
			if (! trim($data)) return false;
		}
		$this->stack[$this->stack_length-1]['text'] .= $data;	
	}
	
	public function parse() {
		if (is_file($this->file)) {
			$data = file_get_contents($this->file);
		} else {
			$data = $this->file;
		}

		$this->parser = xml_parser_create();
		
		xml_set_object($this->parser, $this);
		xml_set_element_handler($this->parser, "do_open", "do_close");
		xml_set_character_data_handler($this->parser, "do_cdata");

		$ret = xml_parse($this->parser, $data);
		xml_parser_free($this->parser);
	}

	public function make_array(&$arr) {
		$ret = array();
		foreach ($arr as $a) {
			if ( isset($a['children']) ) {
				$ret[] = $this->make_array($a['children']);
			} else {
				if ($this->getOption('assoc_arrays')) {
					$ret[$a['tag']] = $a['text'];
				} else {
					$ret[] = SimpleXMLReader::getVal($a);
				}
			}
		}
		return $ret;
	}

	public function makeObject($class=false) {
		if (! $class) $class = $this->domtree['tag'];
		$obj = new $class;
		$this->populateObject($obj);
		return $obj;
	}
	
	public function getVal(&$childnode) {
		if ( isset($childnode['attributes']['TYPE']) ) {
			$type = $childnode['attributes']['TYPE'];
			switch($type) {
				case 'int':
					return (int)$childnode['text'];
					break;
				case 'string':
				default:
					return $childnode['text'];
			}
		} else {
			return $childnode['text'];
		}
	}
	
	public function setVal(&$obj, $node) {
		$tag = $node['tag'];
		if (isset($obj->$tag) && is_bool($obj->$tag)) {
			$obj->$tag = (bool)$node['text'];
		} elseif (isset($obj->$tag) && is_int($obj->$tag)) {
			$obj->$tag = (int)$node['text'];
		} elseif (isset($obj->$tag) && is_string($obj->$tag)) {
			$obj->$tag = (string)$node['text'];
		} else {
			$obj->$tag = SimpleXMLReader::getVal($node);
		}
	}

	public function populateObject(&$obj) {
		if (! isset($this->domtree['children'])) return;
		foreach ($this->domtree['children'] as $child) {
			if ( isset($child['children']) ) {
				$obj->$child['tag'] = $this->make_array($child['children']);
			} elseif ( isset($child['attributes']['TYPE']) && 
			           $child['attributes']['TYPE'] == 'array' ) {
				$obj->$child['tag'] = array();
			} else {
				SimpleXMLReader::setVal($obj, $child);
			}
		}
	}

}
