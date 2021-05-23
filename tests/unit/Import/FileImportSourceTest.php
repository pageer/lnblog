<?php

use LnBlog\Import\FileImportSource;

class FileImportSourceTest extends \PHPUnit\Framework\TestCase
{
    private $prophet;
    private $fs;

    public function testGetAsXml_WhenXmlValid_ReturnsSimpleXml() {
        $importer = $this->createImportSource();
        $importer->setText('<data><item>some data</item></data>');

        $xml = $importer->getAsXml();

        $this->assertInstanceOf(\SimpleXMLElement::class, $xml);
        $this->assertEquals('some data', $xml->item);
    }

    public function testGetAsXml_WhenXmlInvalid_Throws() {
        $importer = $this->createImportSource();
        $importer->setText('FAIL');

        try {
            $xml = $importer->getAsXml();
            $this->assertTrue(false, 'Expected an exception');
        } catch (InvalidImport $e) {
            $this->assertNotEmpty($e->getErrors());
        }
    }

    public function testIsValid_WhenHasValidXml_ReturnsTrue() {
        $this->fs->read_file('something.xml')->willReturn('<data><item>some data</item></data>');
        $importer = $this->createImportSource();
        $importer->setFromFile('something.xml');

        $is_valid = $importer->isValid();

        $this->assertTrue($is_valid);
    }

    public function testIsValid_WhenXmlIsEmpty_ReturnsTrue() {
        $importer = $this->createImportSource();

        $is_valid = $importer->isValid();

        $this->assertFalse($is_valid);
    }


    protected function setUp(): void {
        $this->prophet = new \Prophecy\Prophet();
        $this->fs = $this->prophet->prophesize(FS::class);
    }

    private function createImportSource(): FileImportSource {
        return new FileImportSource($this->fs->reveal());
    }
}
