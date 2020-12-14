<?php

# Class: SimpleXMLWriter
# A class for converting objects into XML.
# The XML produced uses a simple, flat structure.  The outer element is
# named for the object's class, with each property of the object having a
# tag of the same name.  Arrays are represented as an element with a number
# of generically-named child nodes.
class SimpleXMLWriter
{

    public $object = null;
    public $exclude_list = array();
    public $cdata_list = array();

    public function __construct($object) {
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

    public function serializeArray($arr, $tag) {

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

    public function serializeObject($obj) {
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
