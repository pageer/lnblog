<?php
# Class: SimpleXMLReader
# A class for parsing the XML produced by the <SimpleXMLWriter> class and 
# creating or populating an object based on it.

class SimpleXMLReader
{
    
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

    public function make_array($arr) {
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
    
    public function getVal($childnode) {
        if ( isset($childnode['attributes']['TYPE']) ) {
            $type = $childnode['attributes']['TYPE'];
            switch($type) {
                case 'bool':
                    return $childnode['text'] == 'true';
                case 'int':
                    return (int)$childnode['text'];
                case 'string':
                default:
                    return $childnode['text'];
            }
        } else {
            return $childnode['text'];
        }
    }
    
    public function setVal($obj, $node) {
        $tag = $node['tag'];
        if (isset($obj->$tag) && is_bool($obj->$tag)) {
            if (in_array(strtolower($node['text']), array('true', 'false'))) {
                $obj->$tag = (strtolower($node['text']) == 'true');
            } else {
                $obj->$tag = (bool)$node['text'];
            }
        } elseif (isset($obj->$tag) && is_int($obj->$tag)) {
            $obj->$tag = (int)$node['text'];
        } elseif (isset($obj->$tag) && is_string($obj->$tag)) {
            $obj->$tag = (string)$node['text'];
        } else {
            $obj->$tag = SimpleXMLReader::getVal($node);
        }
    }

    public function populateObject($obj) {
        if (! isset($this->domtree['children'])) return;
        foreach ($this->domtree['children'] as $child) {
            $tag = $child['tag'];
            if ( isset($child['children']) ) {
                @$obj->$tag = $this->make_array($child['children']);
            } elseif ( isset($child['attributes']['TYPE']) && 
                       $child['attributes']['TYPE'] == 'array' ) {
                @$obj->$tag = array();
            } else {
                SimpleXMLReader::setVal($obj, $child);
            }
        }
    }

}
