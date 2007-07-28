<?php
require_once('../lib/xml.php');

class DummyClassForTestOfSimpleXMLWriter {
	function DummyClassForTestOfSimpleXMLWriter() {
		$this->foo = "foo & bar";
		$this->bar = 7;
		$this->baz = array("fizz","buzz");
		$this->buzz = true;
		$this->fizz = "do not show";
		$this->queep = "character > & data";
	}
}

class TestOfSimpleXMLWriter extends UnitTestCase {
	function TestOfSimpleXMLWriter() {
		$this->UnitTestCase("Simple XML writer class");
	}
	
	function testConstructor() {		$this->fizz = array();
		$a = array('foo');
		$s = new SimpleXMLWriter($a);
		$this->assertReference($s->object, $a);
		$this->assertIdentical($s->cdata_list, array());
		$this->assertIdentical($s->exclude_list, array());
	}
	
	function testExclude() {
		$a = array('foo');
		$s = new SimpleXMLWriter($a);
		$s->exclude("foo", "bar");
		$this->assertIdentical($s->exclude_list, array("foo", "bar"));
		$s->exclude("baz", "buzz");
		$this->assertIdentical($s->exclude_list, array("foo", "bar", "baz", "buzz"));
	}
		
	function testCdata() {
		$a = array('foo');
		$s = new SimpleXMLWriter($a);
		$s->cdata("foo", "bar");
		$this->assertIdentical($s->cdata_list, array("foo", "bar"));
		$s->cdata("baz", "buzz");
		$this->assertIdentical($s->cdata_list, array("foo", "bar", "baz", "buzz"));
	}
	
	function testSerializeArray() {
		$a = array('a','b','c');
		$ret = SimpleXMLWriter::serializeArray($a,"mytag");
		$this->assertIdentical($ret, "<mytag type=\"array\">\n<elem>a</elem>\n<elem>b</elem>\n<elem>c</elem>\n</mytag>\n");
		
		$ret = SimpleXMLWriter::serializeArray($a,"tags");
		$this->assertIdentical($ret, "<tags type=\"array\">\n<tag>a</tag>\n<tag>b</tag>\n<tag>c</tag>\n</tags>\n");
		
		$a = array();
		$this->assertIdentical(SimpleXMLWriter::serializeArray($a,'foo'), "<foo type=\"array\">\n</foo>\n");
		
		$a = array('name'=>'foo','type'=>'bar','desc'=>'baz');
		$ret = SimpleXMLWriter::serializeArray($a,"item");
		$val = "<item type=\"array\">
<name>foo</name>
<type>bar</type>
<desc>baz</desc>
</item>\n";
		$this->assertIdentical($ret, $val);
		
		$a = array('foo','type'=>'bar','baz'=>array('bob','name'=>'joe',array('1','2')));
		$val = "<arr type=\"array\">
<elem>foo</elem>
<type>bar</type>
<baz type=\"array\">
<elem>bob</elem>
<name>joe</name>
<elem type=\"array\">
<elem>1</elem>
<elem>2</elem>
</elem>
</baz>
</arr>\n";
		$ret = SimpleXMLWriter::serializeArray($a,"arr");
		$this->assertIdentical($ret, $val);
	}
	
	function testSerializeObject() {
		$test = new DummyClassForTestOfSimpleXMLWriter();
		$s = new SimpleXMLWriter($test);
		$s->exclude("fizz");
		$s->cdata("queep");
		$val = "<DummyClassForTestOfSimpleXMLWriter>
<foo>foo &amp; bar</foo>
<bar type=\"int\">7</bar>
<baz type=\"array\">
<elem>fizz</elem>
<elem>buzz</elem>
</baz>
<buzz type=\"bool\">true</buzz>
<queep><![CDATA[character > & data]]></queep>
</DummyClassForTestOfSimpleXMLWriter>\n";
		$ret = $s->serializeObject($s->object);
		$this->assertIdentical(strtolower($ret), strtolower($val));
	}
	
	function testSerialize() {
		$test = new DummyClassForTestOfSimpleXMLWriter();
		$s = new SimpleXMLWriter($test);
		$val = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n".
		       $s->serializeObject($s->object);
		$ret = $s->serialize();
		$this->assertIdentical(strtolower($ret), strtolower($val));
		
		$s->object = array('foo','type'=>'bar','baz');
		$val = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n".
		       $s->serializeArray($s->object, "array");
		$ret = $s->serialize();
		$this->assertIdentical($ret, $val);
	}
}
?>