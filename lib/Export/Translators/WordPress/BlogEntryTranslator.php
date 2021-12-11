<?php

namespace LnBlog\Export\Translators\WordPress;

use BlogEntry;
use LnBlog\Export\Translators\CDataHelper;
use LnBlog\Export\Translators\Translator;
use LnBlog\Export\WordPressExporter;
use DateTime;
use DateTimeInterface;
use DateTimeZone;
use SimpleXMLElement;

class BlogEntryTranslator implements Translator
{
    public function translate(SimpleXMLElement $root, $item, array $options = []): void {
        $content_ns = WordPressExporter::XPATH_NAMESPACES['content'];
        $dc_ns = WordPressExporter::XPATH_NAMESPACES['dc'];
        $wp_ns = WordPressExporter::XPATH_NAMESPACES['wp'];

        /** @var BlogEntry $item */
        $entry = $item;
        CDataHelper::addChildWithCData($root, 'title', $entry->subject);

        $root->addChild('link', $entry->permalink());
        $pub_time = DateTime::createFromFormat('U', $entry->post_ts);
        $root->addChild('pubDate', $pub_time->format(DateTimeInterface::RSS));
        $root->addChild('creator', $entry->uid, $dc_ns);
        $guid = $root->addChild('guid', $entry->permalink());
        # <description> omitted because it's always empty.
        $guid->addAttribute('isPermalink', 'false');
        CDataHelper::addChildWithCData($root, 'encoded', $entry->markup(), $content_ns);
        # <exceerpt:encoded> omitted because always empty
        $root->addChild('post_date', $pub_time->format('Y-m-d H:i:s'), $wp_ns);
        $zone = new DateTimeZone('+0000');
        $pub_time->setTimezone($zone);
        $root->addChild('post_date_gmt', $pub_time->format('Y-m-d H:i:s'), $wp_ns);
        $comment_status = $this->replyStatus($entry->allow_comment);
        $root->addChild('comment_status', $comment_status, $wp_ns);
        $ping_status = $this->replyStatus($entry->allow_pingback);
        $root->addChild('ping_status', $ping_status, $wp_ns);
        $entry_status = $entry->isDraft() ? 'draft' : 'publish';
        $root->addChild('status', $entry_status, $wp_ns);

        $post_type = $entry->isArticle() ? 'page' : 'post';
        $root->addChild('post_type', $post_type, $wp_ns);

        foreach ($entry->tags() as $tag_name) {
            $tag_nice_name = preg_replace('/\W+/', '-', strtolower($tag_name));
            $tag = CDataHelper::addChildWithCData($root, 'category', $tag_name);
            $tag->addAttribute('domain', 'post_tag');
            $tag->addAttribute('nicename', $tag_nice_name);
        }
    }

    private function replyStatus(bool $status): string {
        return $status ? 'open' : 'closed';
    }
}
