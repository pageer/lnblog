<?php
class SimpleXmlWriterTest extends PHPUnit\Framework\TestCase
{
    function testConstructor() {
        $this->fizz = array();
        $a = array('foo');
        $s = new SimpleXMLWriter($a);
        $this->assertSame($s->object, $a);
        $this->assertEquals($s->cdata_list, array());
        $this->assertEquals($s->exclude_list, array());
    }
    
    function testExclude() {
        $a = array('foo');
        $s = new SimpleXMLWriter($a);
        $s->exclude("foo", "bar");
        $this->assertEquals($s->exclude_list, array("foo", "bar"));
        $s->exclude("baz", "buzz");
        $this->assertEquals($s->exclude_list, array("foo", "bar", "baz", "buzz"));
    }
        
    function testCdata() {
        $a = array('foo');
        $s = new SimpleXMLWriter($a);
        $s->cdata("foo", "bar");
        $this->assertEquals($s->cdata_list, array("foo", "bar"));
        $s->cdata("baz", "buzz");
        $this->assertEquals($s->cdata_list, array("foo", "bar", "baz", "buzz"));
    }
    
    function testSerializeArray() {
        $a = array('a','b','c');
        $ret = SimpleXMLWriter::serializeArray($a, "mytag");
        $this->assertEquals($ret, "<mytag type=\"array\">\n<elem>a</elem>\n<elem>b</elem>\n<elem>c</elem>\n</mytag>\n");
        
        $ret = SimpleXMLWriter::serializeArray($a, "tags");
        $this->assertEquals($ret, "<tags type=\"array\">\n<tag>a</tag>\n<tag>b</tag>\n<tag>c</tag>\n</tags>\n");
        
        $a = array();
        $this->assertEquals(SimpleXMLWriter::serializeArray($a, 'foo'), "<foo type=\"array\">\n</foo>\n");
        
        $a = array('name'=>'foo','type'=>'bar','desc'=>'baz');
        $ret = SimpleXMLWriter::serializeArray($a, "item");
        $val = "<item type=\"array\">\n".
"<name>foo</name>\n".
"<type>bar</type>\n".
"<desc>baz</desc>\n".
"</item>\n";
        $this->assertEquals($ret, $val);
        
        $a = array('foo','type'=>'bar','baz'=>array('bob','name'=>'joe',array('1','2')));
        $val = "<arr type=\"array\">\n".
"<elem>foo</elem>\n".
"<type>bar</type>\n".
"<baz type=\"array\">\n".
"<elem>bob</elem>\n".
"<name>joe</name>\n".
"<elem type=\"array\">\n".
"<elem>1</elem>\n".
"<elem>2</elem>\n".
"</elem>\n".
"</baz>\n".
"</arr>\n";
        $ret = SimpleXMLWriter::serializeArray($a, "arr");
        $this->assertEquals($ret, $val);
    }
    
    function testSerializeObject() {
        $test = $this->getTestObject();
        $s = new SimpleXMLWriter($test);
        $s->exclude("fizz");
        $s->cdata("queep");
        $val = "<stdClass>\n".
"<foo>foo &amp; bar</foo>\n".
"<bar type=\"int\">7</bar>\n".
"<baz type=\"array\">\n".
"<elem>fizz</elem>\n".
"<elem>buzz</elem>\n".
"</baz>\n".
"<buzz type=\"bool\">true</buzz>\n".
"<queep><![CDATA[character > & data]]></queep>\n".
"</stdClass>\n";
        $ret = $s->serializeObject($s->object);
        $this->assertEquals(strtolower($ret), strtolower($val));
    }
    
    function testSerialize() {
        $test = $this->getTestObject();
        $s = new SimpleXMLWriter($test);
        $val = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n".
               $s->serializeObject($s->object);
        $ret = $s->serialize();
        $this->assertEquals(strtolower($ret), strtolower($val));
        
        $s->object = array('foo','type'=>'bar','baz');
        $val = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n".
               $s->serializeArray($s->object, "array");
        $ret = $s->serialize();
        $this->assertEquals($ret, $val);
    }
    
    private function getTestObject() {
        $item = new stdClass();
        $item->foo = "foo & bar";
        $item->bar = 7;
        $item->baz = array("fizz","buzz");
        $item->buzz = true;
        $item->fizz = "do not show";
        $item->queep = "character > & data";
        return $item;
    }
}
