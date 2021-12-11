<?php

namespace LnBlog\Export\Translators;

use SimpleXMLElement;

interface Translator
{
    public function translate(SimpleXMLElement $root, $item, array $options = []): void;
}
