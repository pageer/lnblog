<?php

namespace LnBlog\Export\Translators\Atom;

use DateTime;
use DateTimeZone;
use GlobalFunctions;
use LnBlog\Export\AtomExporter;
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
        // Note: Per Atom spec, HTML content is HTML encoded, so the XML text 
        // needs to be double-encoded (or encoded wrapped in CDATA).
        $title_node = CDataHelper::addChildWithCData($root, 'title', htmlspecialchars($item->title));
        $title_node->addAttribute('type', 'html');
        $subtitle_node = CDataHelper::addChildWithCData($root, 'subtitle', htmlspecialchars($item->description));
        $subtitle_node->addAttribute('type', 'html');

        $self_link = $root->addChild('link');
        $self_link->addAttribute('rel', 'self');
        $self_link->addAttribute('href', $options[AtomExporter::OPTION_FEED_URL]);
        $alt_link = $root->addChild('link');
        $alt_link->addAttribute('rel', 'alternate');
        $alt_link->addAttribute('href', $item->permalink);

        $date = new DateTime('now', new DateTimeZone('UTC'));
        $date->setTimestamp($this->globals->time());
        $root->addChild('updated', $date->format(DateTime::ATOM));

        if ($item->owner_name) {
            $author_node = $root->addChild('author');
            $author_node->addChild('name', $item->owner_name);
            if ($item->owner_email) {
                $author_node->addChild('email', $item->owner_email);
            }
            if ($item->owner_url) {
                $author_node->addChild('uri', $item->owner_url);
            }
        }

        $root->addChild('id', $item->permalink);

        $generator_node = $root->addChild('generator', PACKAGE_NAME);
        $generator_node->addAttribute('uri', PACKAGE_URL);
        $generator_node->addAttribute('version', PACKAGE_VERSION);
    }
}
