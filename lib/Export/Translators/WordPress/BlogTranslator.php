<?php

namespace LnBlog\Export\Translators\WordPress;

use Blog;
use DateTime;
use DateTimeInterface;
use GlobalFunctions;
use LnBlog\Export\Translators\Translator;
use LnBlog\Export\WordPressExporter;
use LnBlog\Storage\UserRepository;
use SimpleXMLElement;

class BlogTranslator implements Translator
{
    private $globals;
    private $user_repo;

    public function __construct(
        GlobalFunctions $global = null,
        UserRepository $user_repo = null
    ) {
        $this->globals = $global ?: new GlobalFunctions();
        $this->user_repo = $user_repo ?: new UserRepository();
    }

    public function translate(SimpleXMLElement $root, $item, array $options = []): void {
        /** @var Blog $blog */
        $blog = $item;
        $now = DateTime::createFromFormat('U', $this->globals->time());
        $owner = $this->user_repo->get($blog->owner);

        $wp_ns = WordPressExporter::XPATH_NAMESPACES['wp'];

        $root->addChild('title', $blog->title());
        $root->addChild('link', $blog->getURL());
        $root->addChild('description', $blog->description());
        $root->addChild('pubDate', $now->format(DateTimeInterface::RSS));
        $root->addChild('language', str_replace('_', '-', LANGUAGE));
        $root->addChild('wxr_version', '1.2', $wp_ns);
        $root->addChild('base_site_url', $blog->getURL(), $wp_ns);
        $root->addChild('base_blog_url', $blog->getURL(), $wp_ns);
        $author = $root->addChild('author', '', $wp_ns);
        $author->addChild('author_login', $owner->username, $wp_ns);
        $author->addChild('author_email', $owner->email, $wp_ns);
        $author->addChild('author_display_name', $owner->fullname, $wp_ns);
        $root->addChild('generator', PACKAGE_URL . '?v=' . PACKAGE_VERSION);
    }
}
