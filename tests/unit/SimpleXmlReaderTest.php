<?php
class SimpleXmlReaderTest extends PHPUnit\Framework\TestCase {
    function testGetSetOption() {
		$s = new SimpleXMLReader("test");
		$s->setOption("foo");
		$s->setOption('bar', 'test');
		$this->assertEquals($s->getOption("foo"), true);
		$this->assertEquals($s->getOption("bar"), 'test');
	}
	
	function getTestXML() {
		return "<?xml version=\"1.0\" encoding=\"utf-8\"?>
<stdClass>
<foo>foo &amp; bar</foo>
<bar>1</bar>
<baz>
<elem>foo</elem>
<elem>bar</elem>
</baz>
<buzz type=\"array\">
</buzz>
<fizz type=\"int\">12</fizz>
</stdClass>\n";
	}
	
	function testPopulateObject() {
		
		$obj = new stdClass();
		$obj->foo = "foo & bar";
		$obj->bar = "1";
		$obj->baz = array('foo', 'bar');
		$obj->buzz = array();
		$obj->fizz = 12;
		
		$s = new SimpleXMLReader($this->getTestXML());
		$test = new stdClass();
		$s->parse();
		$s->populateObject($test);
		
		$this->assertEquals($test, $obj);
	}
	
	function testPopulateObjectFromBlank() {
		$obj = new stdClass();
		$obj->foo = "foo & bar";
		$obj->bar = "1";
		$obj->baz = array('foo', 'bar');
		$obj->buzz = array();
		$obj->fizz = true;
		
		$s = new SimpleXMLReader($this->getTestXML());
		
		# This should cause us to get the 12 in fizz as a boolean true.
		# The theory here is that 
		$test = new stdClass();
		$test->foo = "";
		$test->bar = "";
		$test->baz = array();
		$test->buzz = array();
		$test->fizz = false;
		
		$s->parse();
		$s->populateObject($test);
		
		$this->assertEquals($test, $obj);	
	}
	
	function testMakeObject() {

		$obj = new stdClass();
		$obj->foo = "foo & bar";
		$obj->bar = "1";
		$obj->baz = array('foo', 'bar');
		$obj->buzz = array();
		$obj->fizz = 12;
		
		$s = new SimpleXMLReader($this->getTestXML());
		$s->parse();
		$test = $s->makeObject();
		
		$this->assertEquals($test, $obj);
	}
}
