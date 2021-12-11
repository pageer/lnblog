<?php

namespace LnBlog\Export;

use Blog;
use GlobalFunctions;
use LnBlog\Export\Translators\WordPress\BlogEntryTranslator;
use LnBlog\Export\Translators\WordPress\BlogTranslator;
use LnBlog\Export\Translators\WordPress\ReplyTranslator;
use LnBlog\Storage\UserRepository;
use SimpleXMLElement;

class WordPressExporter implements Exporter
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

    private $globals;
    private $user_repo;

    private $options = [];

    public function __construct(
        GlobalFunctions $globals = null,
        UserRepository $user_repo = null
    ) {
        $this->globals = $globals ?: new GlobalFunctions();
        $this->user_repo = $user_repo ?: new UserRepository();
    }

    public function setExportOptions(array $options): void {
        $this->options = $options;
    }

    public function getExportOptions(): array {
        return $this->options;
    }

    public function export(Blog $blog, ExportTarget $target): void {
        $xml = new SimpleXMLElement(self::EMPTY_EXPORT_XML);
        $channel = $xml->addChild('channel');
        $blog_translator = new BlogTranslator($this->globals, $this->user_repo);

        $blog_translator->translate($channel, $blog);
        $this->translateEntries($channel, $blog->getEntries());
        $this->translateArticles($channel, $blog->getArticles());
        $this->translateDrafts($channel, $blog->getDrafts());

        $target->setContent($xml->asXML());
    }

    public function translateEntries(SimpleXMLElement $channel, array $entries): void {
        $entry_translator = new BlogEntryTranslator();
        foreach ($entries as $entry) {
            $item = $channel->addChild('item');
            $entry_translator->translate($item, $entry);
            $entry_replies = $entry->getReplies();
            $this->translateReplies($item, $entry_replies);
        }
    }

    public function translateDrafts(SimpleXMLElement $channel, array $entries): void {
        $entry_translator = new BlogEntryTranslator();
        foreach ($entries as $entry) {
            $item = $channel->addChild('item');
            $entry_translator->translate($item, $entry);
            $entry_replies = $entry->getReplies();
            $this->translateReplies($item, $entry_replies);
        }
    }

    public function translateArticles(SimpleXMLElement $channel, array $entries): void {
        $entry_translator = new BlogEntryTranslator();
        foreach ($entries as $entry) {
            $item = $channel->addChild('item');
            $entry_translator->translate($item, $entry);
            $entry_replies = $entry->getReplies();
            $this->translateReplies($item, $entry_replies);
        }
    }

    public function translateReplies(SimpleXMLElement $item, array $replies): void {
        $id = 1;

        $reply_translator = new ReplyTranslator();
        foreach ($replies as $reply) {
            $reply_translator->translate($item, $reply, ['comment_id' => $id++]);
        }
    }
}
