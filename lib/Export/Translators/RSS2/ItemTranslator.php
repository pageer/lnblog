<?php

namespace LnBlog\Export\Translators\RSS2;

use DateTime;
use LnBlog\Export\Translators\Translator;
use LnBlog\Export\Translators\CDataHelper;
use SimpleXMLElement;

class ItemTranslator implements Translator
{
    public function translate(SimpleXMLElement $root, $item, array $options = []): void {
        $item_node = $root->addChild('item');
        CDataHelper::addChildWithCData($item_node, 'title', $item->title);
        $item_node->addChild('link', $item->permalink);
        CDataHelper::addChildWithCData($item_node, 'description', $item->description);

        if ($item->owner_email) {
            $creator_value = sprintf('%s (%s)', $item->owner_email, $item->owner_name);
            CDataHelper::addChildWithCData($item_node, 'author', $creator_value);
        }

        $item_node->addChild('pubDate', $item->publish_date->format(DateTime::RSS));

        foreach ($item->tags as $tag) {
            CDataHelper::addChildWithCData($item_node, 'category', $tag);
        }

        if ($item->enclosure) {
            $enclosure_item = $item_node->addChild('enclosure');
            $enclosure_item->addAttribute('url', $item->enclosure);
            $enclosure_item->addAttribute('length', $item->enclosure_size);
            $enclosure_item->addAttribute('type', $item->enclosure_type);
        }

        $guid_node = $item_node->addChild('guid', $item->permalink);
        $guid_node->addAttribute('isPermalink', 'true');

        if ($item->comments_url) {
            $item_node->addChild('comments', $item->comments_url);
        }
    }
}
