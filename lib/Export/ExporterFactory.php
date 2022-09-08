<?php

namespace LnBlog\Export;

use InvalidFormat;

class ExporterFactory
{
    const EXPORT_WORDPRESS = "wordpress";
    const EXPORT_RSS1 = "rss1";
    const EXPORT_RSS2 = "rss2";
    const EXPORT_ATOM = "atom";

    const SUPPORTED_FORMATS = [
        self::EXPORT_WORDPRESS => 'WordPress (WXR)',
        self::EXPORT_RSS1 => 'RSS 1.0',
        self::EXPORT_RSS2 => 'RSS 2.0',
        self::EXPORT_ATOM => 'Atom',
    ];

    /**
     * @codeCoverageIgnore
     */
    public function create(string $format): Exporter {
        switch ($format) {
            case self::EXPORT_RSS1:
                return new Rss1Exporter();
            case self::EXPORT_RSS2:
                return new Rss2Exporter();
            case self::EXPORT_ATOM:
                return new AtomExporter();
            case self::EXPORT_WORDPRESS:
                return new WordPressExporter();
            default:
                throw new InvalidFormat("Format is not supported");
        }
    }
}
