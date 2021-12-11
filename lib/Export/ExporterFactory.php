<?php

namespace LnBlog\Export;

use InvalidFormat;

class ExporterFactory
{
    const EXPORT_WORDPRESS = "wordpress";
    const SUPPORTED_FORMATS = [self::EXPORT_WORDPRESS];

    public function create(string $format): Exporter {
        $this->validateFormat($format);
        switch ($format) {
            case self::EXPORT_WORDPRESS:
            default:
                return new WordPressExporter();
        }
    }

    private function validateFormat(string $format) {
        if (!in_array($format, self::SUPPORTED_FORMATS)) {
            throw new InvalidFormat("Format is not supported");
        }
    }
}
