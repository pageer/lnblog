<?php
use Prophecy\Argument;

class XMLINITest extends PHPUnit\Framework\TestCase
{

    public function testWriteFileWithSimpleSettingWritesSimpleXmlNode() {
        $this->config->setValue('foo', 'bar', 'baz');

        $xml = '<?xml version="1.0" encoding="utf-8"?>' . "\n" .
            "<LnBlogXMLConfig>\n" .
            '<foo type="array">' . "\n" .
            "<bar>baz</bar>\n" .
            "</foo>\n" .
            "</LnBlogXMLConfig>\n";
        $this->fs->write_file('foo.xml', $xml)->willReturn(true)->shouldBeCalled();

        $this->config->writeFile('foo.xml');
    }

    public function testWriteFileWithMultipleFieldsWritesMultipleNodes() {
        $this->config->setValue('foo', 'bar', 'baz');
        $this->config->setValue('foo', 'fizz', 'fuzz');

        $xml = '<?xml version="1.0" encoding="utf-8"?>' . "\n" .
            "<LnBlogXMLConfig>\n" .
            '<foo type="array">' . "\n" .
            "<bar>baz</bar>\n" .
            "<fizz>fuzz</fizz>\n" .
            "</foo>\n" .
            "</LnBlogXMLConfig>\n";
        $this->fs->write_file('foo.xml', $xml)->willReturn(true)->shouldBeCalled();

        $this->config->writeFile('foo.xml');
    }

    public function testReadFileWithSimpleSettingReturnsSimpleObject() {
        $xml = '<?xml version="1.0" encoding="utf-8"?>' . "\n" .
            "<LnBlogXMLConfig>\n" .
            '<foo type="array">' . "\n" .
            "<bar>baz</bar>\n" .
            "</foo>\n" .
            "</LnBlogXMLConfig>\n";
        $this->fs->read_file('foo.xml')->willReturn($xml);
        $this->fs->file_exists('foo.xml')->willReturn(true);
        $config = new XMLINI('foo.xml', $this->fs->reveal());

        $config->readFile('foo.xml');
        $value = $config->value('foo', 'bar');

        $this->assertEquals('baz', $value);
    }

    public function testReadFileWithMultipleNodesReturnsMultipleFields() {
        $xml = '<?xml version="1.0" encoding="utf-8"?>' . "\n" .
            "<LnBlogXMLConfig>\n" .
            '<foo type="array">' . "\n" .
            "<bar>baz</bar>\n" .
            "<fizz>fuzz</fizz>\n" .
            "</foo>\n" .
            "</LnBlogXMLConfig>\n";
        $this->fs->read_file('foo.xml')->willReturn(true);
        $this->fs->file_exists('foo.xml')->willReturn(true);
        $config = new XMLINI('foo.xml', $this->fs->reveal());

        $config->readFile('foo.xml');
        $baz = $config->setValue('foo', 'bar', 'baz');
        $fuzz = $config->setValue('foo', 'fizz', 'fuzz');

        $this->assertEquals('baz', $baz);
        $this->assertEquals('fuzz', $fuzz);
    }

    public function testMergeWhenFilesDoNotOverlapResultContainsBothDataSets() {
        $xml1 = '<?xml version="1.0" encoding="utf-8"?>' . "\n" .
            "<LnBlogXMLConfig>\n" .
            '<foo type="array">' . "\n" .
            "<bar>baz</bar>\n" .
            "</foo>\n" .
            "</LnBlogXMLConfig>\n";
        $xml2 = '<?xml version="1.0" encoding="utf-8"?>' . "\n" .
            "<LnBlogXMLConfig>\n" .
            '<foo type="array">' . "\n" .
            "<fizz>fuzz</fizz>\n" .
            "</foo>\n" .
            "</LnBlogXMLConfig>\n";
        $this->fs->file_exists(Argument::any())->willReturn(true);
        $this->fs->read_file("file1.xml")->willReturn($xml1);
        $this->fs->read_file("file2.xml")->willReturn($xml2);
        $config1 = new XMLINI("file1.xml", $this->fs->reveal());
        $config2 = new XMLINI("file2.xml", $this->fs->reveal());

        $config1->merge($config2);
        $baz = $config1->value("foo", "bar", "default");
        $fuzz = $config1->value("foo", "fizz", "default");

        $this->assertEquals("baz", $baz);
        $this->assertEquals("fuzz", $fuzz);
    }

    protected function setUp(): void {
        $this->prophet = new \Prophecy\Prophet();
        $this->fs = $this->prophet->prophesize('FS');
        $this->config = new XMLINI(false, $this->fs->reveal());
    }

    protected function tearDown(): void {
        $this->prophet->checkPredictions();
    }
}
