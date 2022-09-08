<?php

namespace LnBlog\Export;

use Blog;
use GlobalFunctions;
use LnBlog\Export\Translators\WordPress\BlogEntryTranslator;
use LnBlog\Export\Translators\WordPress\BlogTranslator;
use LnBlog\Export\Translators\WordPress\ReplyTranslator;
use LnBlog\Storage\UserRepository;
use SimpleXMLElement;

class WordPressExporter extends BaseExporter implements Exporter
{
    const EMPTY_EXPORT_XML = '<?xml version="1.0" encoding="UTF-8" ?>
<rss version="2.0"
	xmlns:excerpt="http://wordpress.org/export/1.2/excerpt/"
	xmlns:content="http://purl.org/rss/1.0/modules/content/"
	xmlns:wfw="http://wellformedweb.org/CommentAPI/"
	xmlns:dc="http://purl.org/dc/elements/1.1/"
	xmlns:wp="http://wordpress.org/export/1.2/"
></rss>
';

    const XPATH_NAMESPACES = [
        'excerpt' => "http://wordpress.org/export/1.2/excerpt/",
        'content' => "http://purl.org/rss/1.0/modules/content/",
        'wfw' => "http://wellformedweb.org/CommentAPI/",
        'dc' => "http://purl.org/dc/elements/1.1/",
        'wp' => "http://wordpress.org/export/1.2/",
    ];

    public function export($entity, ExportTarget $target): void {
        /** @var Blog $blog */
        $blog = $entity;
        $xml = new SimpleXMLElement(self::EMPTY_EXPORT_XML);
        $channel = $xml->addChild('channel');
        $blog_translator = new BlogTranslator($this->globals, $this->user_repo);

        $blog_translator->translate($channel, $blog);
        $this->translateEntries($channel, $blog->getEntries());
        $this->translateArticles($channel, $blog->getArticles());
        $this->translateDrafts($channel, $blog->getDrafts());

        $content = self::prettyPrintXml($xml->asXML());
        $target->setContent($content);
    }

    private function translateEntries(SimpleXMLElement $channel, array $entries): void {
        $entry_translator = new BlogEntryTranslator();
        foreach ($entries as $entry) {
            $item = $channel->addChild('item');
            $entry_translator->translate($item, $entry);
            $entry_replies = $entry->getReplies();
            $this->translateReplies($item, $entry_replies);
        }
    }

    private function translateDrafts(SimpleXMLElement $channel, array $entries): void {
        $entry_translator = new BlogEntryTranslator();
        foreach ($entries as $entry) {
            $item = $channel->addChild('item');
            $entry_translator->translate($item, $entry);
            $entry_replies = $entry->getReplies();
            $this->translateReplies($item, $entry_replies);
        }
    }

    private function translateArticles(SimpleXMLElement $channel, array $entries): void {
        $entry_translator = new BlogEntryTranslator();
        foreach ($entries as $entry) {
            $item = $channel->addChild('item');
            $entry_translator->translate($item, $entry);
            $entry_replies = $entry->getReplies();
            $this->translateReplies($item, $entry_replies);
        }
    }

    private function translateReplies(SimpleXMLElement $item, array $replies): void {
        $id = 1;

        $reply_translator = new ReplyTranslator();
        foreach ($replies as $reply) {
            $reply_translator->translate($item, $reply, ['comment_id' => $id++]);
        }
    }
}
