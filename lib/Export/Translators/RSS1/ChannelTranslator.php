<?php

namespace LnBlog\Export\Translators\RSS1;

use DateTime;
use DateTimeZone;
use GlobalFunctions;
use LnBlog\Export\Rss1Exporter;
use LnBlog\Export\Translators\CDataHelper;
use LnBlog\Export\Translators\Translator;
use SimpleXMLElement;

class ChannelTranslator implements Translator
{
    private GlobalFunctions $globals;

    public function __construct(GlobalFunctions $globals) {
        $this->globals = $globals;
    }

    public function translate(SimpleXMLElement $root, $item, array $options = []): void {
        $dc_ns = Rss1Exporter::XPATH_NAMESPACES['dc'];
        $rdf_ns = Rss1Exporter::XPATH_NAMESPACES['rdf'];

        CDataHelper::addChildWithCData($root, 'title', $item->title);
        $root->addChild('link', $item->permalink);
        CDataHelper::addChildWithCData($root, 'description', $item->description);

        $creator_value = $item->owner_name;
        if ($item->owner_email) {
            $creator_value .= sprintf(' (mailto:%s)', $item->owner_email);
        }
        $root->addChild('creator', $creator_value, $dc_ns);

        $date = new DateTime('now', new DateTimeZone('UTC'));
        $date->setTimestamp($this->globals->time());
        $root->addChild('date', $date->format(DateTime::ATOM), $dc_ns);

        $items = $root->addChild('items');
        $seq = $items->addChild('Seq', '', $rdf_ns);

        foreach ($item->children as $child) {
            $li = $seq->addChild('li', '', $rdf_ns);
            $li->addAttribute('resource', $child->permalink);
        }
    }
}
