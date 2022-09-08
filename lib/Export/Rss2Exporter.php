<?php

namespace LnBlog\Export;

use SimpleXMLElement;
use LnBlog\Export\Translators\RSS2\ChannelTranslator;
use LnBlog\Export\Translators\RSS2\ItemTranslator;

class Rss2Exporter extends BaseFeedExporter implements Exporter
{
    const EMPTY_EXPORT_XML = '<?xml version="1.0"?>
<rss version="2.0">
</rss>
';

    public function export($entity, ExportTarget $target): void {
        $options = $this->getExportOptions();
        $parent_item = $this->getChannelData($entity);
        $this->applyChannelOverrides($parent_item, $options);

        if (isset($options[self::OPTION_CHILDREN])) {
            $children = $options[self::OPTION_CHILDREN];
        } else {
            $children = $this->getDefaultChildren($entity);
        }

        foreach ($children as $child) {
            $parent_item->children[] = $this->getItemData($child);
        }

        $xml = new SimpleXMLElement(self::EMPTY_EXPORT_XML);
        $channel = $xml->addChild('channel');
        
        $channel_translator = new ChannelTranslator($this->globals);
        $item_translator = new ItemTranslator();

        $channel_translator->translate($channel, $parent_item);

        foreach ($parent_item->children as $child) {
            $item_translator->translate($channel, $child, $options);
        }

        $content = self::prettyPrintXml($xml->asXML());
        $target->setContent($content);
    }
}
