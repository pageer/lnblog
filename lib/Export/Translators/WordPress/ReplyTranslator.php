<?php

namespace LnBlog\Export\Translators\WordPress;

use BlogComment;
use DateTime;
use LnBlog\Export\Translators\CDataHelper;
use LnBlog\Export\Translators\Translator;
use LnBlog\Export\WordPressExporter;
use Pingback;
use SimpleXMLElement;

class ReplyTranslator implements Translator
{
    public function translate(SimpleXMLElement $root, $item, array $options = []): void {
        if ($item instanceof BlogComment) {
            $this->translateComment($root, $item, $options['comment_id'] ?? 1);
        } else {
            $this->translatePingback($root, $item, $options['comment_id'] ?? 1);
        }
    }

    private function translateComment(SimpleXMLElement $root, BlogComment $reply, int $id): void {
        $pub_time = DateTime::createFromFormat('U', $reply->timestamp);
        $wp_ns = WordPressExporter::XPATH_NAMESPACES['wp'];
        $comment = $root->addChild('comment', '', $wp_ns);
        $comment->addChild('comment_id', (string)$id, $wp_ns);
        CDataHelper::addChildWithCData($comment, 'comment_author', $reply->name, $wp_ns);
        CDataHelper::addChildWithCData($comment, 'comment_author_email', $reply->email, $wp_ns);
        CDataHelper::addChildWithCData($comment, 'comment_author_url', $reply->url, $wp_ns);
        CDataHelper::addChildWithCData($comment, 'comment_author_IP', $reply->ip, $wp_ns);
        $comment->addChild('comment_date', $pub_time->format('Y-m-d H:i:s'), $wp_ns);
        $comment->addChild('comment_date_gmt', $pub_time->format('Y-m-d H:i:s'), $wp_ns);
        CDataHelper::addChildWithCData($comment, 'comment_content', $reply->markup(), $wp_ns);
        $comment->addChild('comment_approved', '1', $wp_ns);
        $comment->addChild('comment_type', 'comment', $wp_ns);
    }

    private function translatePingback(SimpleXMLElement $root, Pingback $reply, int $id): void {
        $pub_time = DateTime::createFromFormat('U', $reply->timestamp);
        $wp_ns = WordPressExporter::XPATH_NAMESPACES['wp'];
        $pingback = $root->addChild('comment', '', $wp_ns);
        $pingback->addChild('comment_id', (string)$id, $wp_ns);
        CDataHelper::addChildWithCData($pingback, 'comment_author', $reply->title, $wp_ns);
        $pingback->addChild('comment_author_email', '', $wp_ns);
        CDataHelper::addChildWithCData($pingback, 'comment_author_url', $reply->source, $wp_ns);
        CDataHelper::addChildWithCData($pingback, 'comment_author_IP', $reply->ip, $wp_ns);
        $pingback->addChild('comment_date', $pub_time->format('Y-m-d H:i:s'), $wp_ns);
        $pingback->addChild('comment_date_gmt', $pub_time->format('Y-m-d H:i:s'), $wp_ns);
        CDataHelper::addChildWithCData($pingback, 'comment_content', $reply->excerpt, $wp_ns);
        $pingback->addChild('comment_approved', '1', $wp_ns);
        $pingback->addChild('comment_type', 'pingback', $wp_ns);

    }
}
