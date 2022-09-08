<?php

namespace LnBlog\Export\Translators\RSS1;

use LnBlog\Export\Rss1Exporter;
use LnBlog\Export\Translators\CDataHelper;
use LnBlog\Export\Translators\Translator;
use SimpleXMLElement;

class ItemTranslator implements Translator
{
    public function translate(SimpleXMLElement $root, $item, array $options = []): void {
        $dc_ns = Rss1Exporter::XPATH_NAMESPACES['dc'];
        $rdf_ns = Rss1Exporter::XPATH_NAMESPACES['rdf'];
        $base_ns = Rss1Exporter::XPATH_NAMESPACES['base'];

        $item_node = $root->addChild('item', '', $base_ns);
        $item_node->addAttribute('rdf:about', $item->permalink, $rdf_ns);
        CDataHelper::addChildWithCData($item_node, 'title', $item->title);
        $item_node->addChild('link', $item->permalink);
        CDataHelper::addChildWithCData($item_node, 'description', $item->description);
        $creator_value = $item->owner_name;
        if ($item->owner_email) {
            $creator_value .= sprintf(' (mailto:%s)', $item->owner_email);
        }
        $item_node->addChild('creator', $creator_value, $dc_ns);
    }
}
