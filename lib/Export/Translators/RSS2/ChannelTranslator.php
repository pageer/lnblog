<?php

namespace LnBlog\Export\Translators\RSS2;

use DateTime;
use DateTimeZone;
use GlobalFunctions;
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
        CDataHelper::addChildWithCData($root, 'title', $item->title);
        $root->addChild('link', $item->permalink);
        CDataHelper::addChildWithCData($root, 'description', $item->description);

        $date = new DateTime('now', new DateTimeZone('UTC'));
        $date->setTimestamp($this->globals->time());
        $root->addChild('lastBuildDate', $date->format(DateTime::ATOM));

        if ($item->owner_email) {
            $creator_value = sprintf('%s (%s)', $item->owner_email, $item->owner_name);
            $root->addChild('managingEditor', $creator_value);
        }

        $root->addChild('language', str_replace('_', '-', $this->globals->constant('LANGUAGE')));
        $root->addChild('generator', PACKAGE_URL . '?v=' . PACKAGE_VERSION);
    }
}
