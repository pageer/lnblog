<?php

namespace LnBlog\Import;

use FS;
use InvalidImport;
use SimpleXMLElement;

# Class: FileImportSource
#
# Represents the source for a file-based import.
#
# Currently, this is only set up for XML-based file imports, i.e. WordPress.
# Ideally we'll eventually support other types, but not yet.
class FileImportSource
{
    private $text_content = '';
    private $fs;
    private $xml;

    public function __construct(FS $fs = null) {
        $this->fs = $fs ?: NewFS();
    }

    # Method: getText
    # Gets the raw text of the import file.
    #
    # Returns:
    # String comtining the text.
    public function getText(): string {
        return $this->text_content;
    }

    # Method: setText
    # Sets the content of the import directly from a string.
    #
    # This is primarily for testing purposes.  Normally, we would
    # read the contents from the file.
    #
    # Parameters:
    # text - string containing the import data
    public function setText(string $text): void {
        $this->text_content = $text;
    }

    # Method: setFromFile
    # Sets the path to the source file for the import.
    # 
    # Parameters:
    # file - string with the path to the import file
    public function setFromFile(string $file): void {
        $this->text_content = $this->fs->read_file($file);
    }

    # Method: getAsXml
    # Gets the XML content of the import.
    #
    # This can throw an exception if the import does not contain valid XML.
    #
    # Returns:
    # A SimpleXMLElement representing the top-level node of the XML document.
    public function getAsXml(): SimpleXMLElement {
        if (!$this->xml) {
            $previous_value = libxml_use_internal_errors(true);
            $xml = simplexml_load_string($this->getText());
            $errors = libxml_get_errors();
            libxml_use_internal_errors($previous_value);
            if (!$xml) {
                throw new InvalidImport(_('Source XML is invalid'), 0, null, $errors);
            }
            $this->xml = $xml;
        }
        return $this->xml;
    }

    # Method: isValid
    # Determines if the import is valid XML.
    #
    # Returns:
    # True if the import contains valid XML data, false otherwise.
    public function isValid(): bool {
        try {
            $xml = $this->getAsXml();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
