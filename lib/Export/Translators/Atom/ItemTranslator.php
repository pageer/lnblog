<?php

namespace LnBlog\Export\Translators\Atom;

use DateTime;
use LnBlog\Export\Translators\CDataHelper;
use LnBlog\Export\Translators\Translator;
use SimpleXMLElement;

class ItemTranslator implements Translator
{
    public function translate(SimpleXMLElement $root, $item, array $options = []): void {
        $entry = $root->addChild('entry');
        $entry->addChild('id', $item->permalink);
        CDataHelper::addChildWithCData($entry, 'title', $item->title);
        $entry->addChild('updated', $item->update_date->format(DateTime::ATOM));

        if ($item->owner_name) {
            $author_node = $entry->addChild('author');
            $author_node->addChild('name', $item->owner_name);
            if ($item->owner_email) {
                $author_node->addChild('email', $item->owner_email);
            }
            if ($item->owner_url) {
                $author_node->addChild('uri', $item->owner_url);
            }
        }

        $content = CDataHelper::addChildWithCData($entry, 'content', htmlspecialchars($item->description));
        $content->addAttribute('type', 'html');

        $alt_link = $entry->addChild('link');
        $alt_link->addAttribute('rel', 'alternate');
        $alt_link->addAttribute('href', $item->permalink);

        if ($item->enclosure) {
            $enclosure_item = $entry->addChild('link');
            $enclosure_item->addAttribute('rel', 'enclosure');
            $enclosure_item->addAttribute('href', $item->enclosure);
            $enclosure_item->addAttribute('type', $item->enclosure_type);
            $enclosure_item->addAttribute('length', $item->enclosure_size);
        }

        foreach ($item->tags as $tag) {
            $cat = $entry->addChild('category');
            $cat->addAttribute('term', $tag);
        }

        $entry->addChild('published', $item->publish_date->format(DateTime::ATOM));

    }
}
