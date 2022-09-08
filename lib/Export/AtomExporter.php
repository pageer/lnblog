<?php

namespace LnBlog\Export;

use BadExport;
use SimpleXMLElement;
use LnBlog\Export\Translators\Atom\ChannelTranslator;
use LnBlog\Export\Translators\Atom\ItemTranslator;

class AtomExporter extends BaseFeedExporter implements Exporter
{
    const OPTION_FEED_URL = 'self-link';

    const EMPTY_EXPORT_XML = '<?xml version="1.0"?>
<feed xmlns="http://www.w3.org/2005/Atom">
</feed>
';

    public function export($entity, ExportTarget $target): void {
        if (!$target->getExportUrl()) {
            throw new BadExport('No destination URL set');
        }

        $options = $this->getExportOptions();
        $options[self::OPTION_FEED_URL] = $target->getExportUrl();

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
        
        $channel_translator = new ChannelTranslator($this->globals);
        $item_translator = new ItemTranslator();

        $channel_translator->translate($xml, $parent_item, $options);

        foreach ($parent_item->children as $child) {
            $item_translator->translate($xml, $child, $options);
        }

        $content = self::prettyPrintXml($xml->asXML());
        $target->setContent($content);
    }
}
