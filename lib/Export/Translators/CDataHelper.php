<?php

namespace LnBlog\Export\Translators;

use SimpleXMLElement;

class CDataHelper
{

    # Method: addChildWithCData
    # Adds a child to the given SimpleXMLElement with a CDATA
    # block for the body.  This exists because SimpleXML does
    # not have a native way to write CDATA.
    #
    # Parameters:
    # element   - SimpleXMLElement to add a child to
    # tag       - The tag name to add
    # value     - The string to wrap in CDATA
    # namespace - The optional namespace of the element
    #
    # Returns:
    # The added SimpleXMLElement child node
    static public function addChildWithCData(
        SimpleXMLElement $element,
        string $tag,
        string $value,
        string $namespace = null
    ): SimpleXMLElement {
        $child = $element->addChild($tag, '', $namespace);
        $node = dom_import_simplexml($child);
        $document = $node->ownerDocument;
        $cdata = $document->createCDATASection($value);
        $node->appendChild($cdata);
        return $child;
    }
}
