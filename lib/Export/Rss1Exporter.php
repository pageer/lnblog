<?php

namespace LnBlog\Export;

use BadExport;
use LnBlog\Export\Translators\RSS1\ChannelTranslator;
use LnBlog\Export\Translators\RSS1\ItemTranslator;
use SimpleXMLElement;

class Rss1Exporter extends BaseFeedExporter implements Exporter
{
    const EMPTY_EXPORT_XML = '<?xml version="1.0" encoding="UTF-8" ?>
<rdf:RDF 
  xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
  xmlns:dc="http://dublincore.org/documents/dcmi-namespace/"
  xmlns="http://purl.org/rss/1.0/"
>
</rdf:RDF>
';

    const XPATH_NAMESPACES = [
        'base' => 'http://purl.org/rss/1.0/',
        'rdf' => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#',
        'dc' => 'http://dublincore.org/documents/dcmi-namespace/',
    ];

    public function export($entity, ExportTarget $target): void {
        if (!$target->getExportUrl()) {
            throw new BadExport(_('No destination URL set'));
        }

        $options = $this->getExportOptions();
        $parent_item = $this->getChannelData($entity);
        $this->applyChannelOverrides($parent_item, $options);

        if (isset($options[self::OPTION_CHILDREN])) {
            $children = $options[self::OPTION_CHILDREN];
        } else {
            $children = $this->getDefaultChildren($entity);
        }

        if (empty($children)) {
            throw new BadExport(_('No items found for feed'));
        }

        foreach ($children as $child) {
            $parent_item->children[] = $this->getItemData($child);
        }

        $xml = new SimpleXMLElement(self::EMPTY_EXPORT_XML);
        $channel = $xml->addChild('channel', '', self::XPATH_NAMESPACES['base']);
        $channel->addAttribute('rdf:about', $target->getExportUrl(), self::XPATH_NAMESPACES['rdf']);
        
        $channel_translator = new ChannelTranslator($this->globals);
        $item_translator = new ItemTranslator();

        $channel_translator->translate($channel, $parent_item);

        foreach ($parent_item->children as $child) {
            $item_translator->translate($xml, $child, $options);
        }

        $content = self::prettyPrintXml($xml->asXML());
        $target->setContent($content);
    }
}
