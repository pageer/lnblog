<?php
require_once('../lib/xml.php');

class DummyClassForTestOfSimpleXMLReader {
	function DummyClassForTestOfSimpleXMLReader() { }
}

class TestOfSimpleXMLReader extends UnitTestCase {

	function TestOfSimpleXMLReader() {
		$this->UnitTestCase("Simple XML reader class");
	}
	
	function testGetSetOption() {
		$s = new SimpleXMLReader("test");
		$s->setOption("foo");
		$s->setOption('bar', 'test');
		$this->assertIdentical($s->getOption("foo"), true);
		$this->assertIdentical($s->getOption("bar"), 'test');
	}
	
	function getTestXML() {
		return "<?xml version=\"1.0\" encoding=\"utf-8\"?>
<DummyClassForTestOfSimpleXMLReader>
<foo>foo &amp; bar</foo>
<bar>1</bar>
<baz>
<elem>foo</elem>
<elem>bar</elem>
</baz>
<buzz type=\"array\">
</buzz>
<fizz type=\"int\">12</fizz>
</DummyClassForTestOfSimpleXMLReader>\n";
	}
	
	function testPopulateObject() {
		
		$obj = new DummyClassForTestOfSimpleXMLReader();
		$obj->foo = "foo & bar";
		$obj->bar = "1";
		$obj->baz = array('foo', 'bar');
		$obj->buzz = array();
		$obj->fizz = 12;
		
		$s = new SimpleXMLReader($this->getTestXML());
		$test = new DummyClassForTestOfSimpleXMLReader();
		$s->parse();
		$s->populateObject($test);
		
		$this->assertIdentical($test, $obj);
	}
	
	function testPopulateObjectFromBlank() {
		$obj = new DummyClassForTestOfSimpleXMLReader();
		$obj->foo = "foo & bar";
		$obj->bar = "1";
		$obj->baz = array('foo', 'bar');
		$obj->buzz = array();
		$obj->fizz = true;
		
		$s = new SimpleXMLReader($this->getTestXML());
		
		# This should cause us to get the 12 in fizz as a boolean true.
		# The theory here is that 
		$test = new DummyClassForTestOfSimpleXMLReader();
		$test->foo = "";
		$test->bar = "";
		$test->baz = array();
		$test->buzz = array();
		$test->fizz = false;
		
		$s->parse();
		$s->populateObject($test);
		
		$this->assertIdentical($test, $obj);	
	}
	
	function testMakeObject() {

		$obj = new DummyClassForTestOfSimpleXMLReader();
		$obj->foo = "foo & bar";
		$obj->bar = "1";
		$obj->baz = array('foo', 'bar');
		$obj->buzz = array();
		$obj->fizz = 12;
		
		$s = new SimpleXMLReader($this->getTestXML());
		$s->parse();
		$test = $s->makeObject();
		
		$this->assertIdentical($test, $obj);
	}

}
?>