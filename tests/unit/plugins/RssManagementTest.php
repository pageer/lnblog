<?php

namespace LnBlog\Tests\Plugins;

require_once __DIR__ . "/../../../plugins/rss_management.php";

use BasePages;
use Blog;
use BlogComment;
use BlogEntry;
use FS;
use LnBlog\Export\BaseFeedExporter;
use LnBlog\Export\ExporterFactory;
use LnBlog\Tests\LnBlogBaseTestCase;
use Page;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;
use RssManagement;
use RuntimeException;
use System;
use UrlResolver;
use User;

class RssManagementTest extends LnBlogBaseTestCase
{
    private $fs;
    private $exporterFactory;
    private $urlResolver;
    private $system;
    private $logger;

    public function testCommentUpdate_CommentsEnabled_Success(): void {
        $base_path = '/path/to/comments/comments';
        $entry = $this->prophet->prophesize(BlogEntry::class);
        $entry->uri(Argument::any())->willReturn('');
        $entry->getCommentArray()->willReturn([]);
        $entry->localpath()->willReturn('/path/to/');
        $mock_entry = $entry->reveal();
        $comment = $this->prophet->prophesize(BlogComment::class);
        $comment->getParent()->willReturn($mock_entry);
        $exporter = $this->prophet->prophesize(BaseFeedExporter::class);

        $this->exporterFactory->create(Argument::any())->willReturn($exporter->reveal());

        $this->fs->write_file($base_path . '.rdf', Argument::any())->willReturn(1)->shouldBeCalled();
        $this->fs->write_file($base_path . '.xml', Argument::any())->willReturn(1)->shouldBeCalled();
        $this->fs->write_file($base_path . '_atom.xml', Argument::any())->willReturn(1)->shouldBeCalled();

        $rss = $this->getRssManagement();
        $rss->use_comment_feeds = true;
        $rss->use_rss1 = true;
        $rss->use_rss2 = true;
        $rss->use_atom = true;
        $rss->handleCommentUpdate($comment->reveal());
    }

    public function testCommentUpdate_WhenDisabled_NoOp(): void {
        $comment = $this->prophet->prophesize(BlogComment::class);

        $this->fs->write_file(Argument::any(), Argument::any())->shouldNotBeCalled();

        $rss = $this->getRssManagement();
        $rss->use_comment_feeds = false;
        $rss->use_rss1 = true;
        $rss->handleCommentUpdate($comment->reveal());
    }

    public function testCommentUpdate_WhenEnabledButNoFormats_NoOp(): void {
        $comment = $this->prophet->prophesize(BlogComment::class);

        $this->fs->write_file(Argument::any(), Argument::any())->shouldNotBeCalled();

        $rss = $this->getRssManagement();
        $rss->use_comment_feeds = true;
        $rss->use_rss1 = false;
        $rss->use_rss2 = false;
        $rss->use_atom = false;
        $rss->handleCommentUpdate($comment->reveal());
    }

    public function testEntryUpdate_EntriesEnabled_Success(): void {
        $base_path = '/path/to/feeds/news';
        $blog = $this->prophet->prophesize(Blog::class);
        $blog->uri(Argument::any())->willReturn('');
        $blog->localpath()->willReturn('/path/to/');
        $blog->getRecent(25)->willReturn([]);
        $mock_blog = $blog->reveal();
        $entry = $this->prophet->prophesize(BlogEntry::class);
        $entry->getParent()->willReturn($mock_blog);
        $exporter = $this->prophet->prophesize(BaseFeedExporter::class);

        $this->exporterFactory->create(Argument::any())->willReturn($exporter->reveal());

        $this->fs->write_file($base_path . '.rdf', Argument::any())->willReturn(1)->shouldBeCalled();
        $this->fs->write_file($base_path . '.xml', Argument::any())->willReturn(1)->shouldBeCalled();
        $this->fs->write_file($base_path . '_atom.xml', Argument::any())->willReturn(1)->shouldBeCalled();

        $rss = $this->getRssManagement();
        $rss->max_entries = 25;
        $rss->use_rss1 = true;
        $rss->use_rss2 = true;
        $rss->use_atom = true;
        $rss->handleEntryUpdate($entry->reveal());
    }

    public function testEntryUpdate_BlogFeedsDisabled_NoOp(): void {
        $entry = $this->prophet->prophesize(BlogEntry::class);

        $this->fs->write_file(Argument::any(), Argument::any())->shouldNotBeCalled();

        $rss = $this->getRssManagement();
        $rss->use_blog_feeds = false;
        $rss->use_rss1 = true;
        $rss->handleEntryUpdate($entry->reveal());
    }

    public function testEntryUpdate_BlogFeedsEnabledButNoFormats_NoOp(): void {
        $entry = $this->prophet->prophesize(BlogEntry::class);

        $this->fs->write_file(Argument::any(), Argument::any())->shouldNotBeCalled();

        $rss = $this->getRssManagement();
        $rss->use_blog_feeds = true;
        $rss->use_rss1 = false;
        $rss->use_rss2 = false;
        $rss->use_atom = false;
        $rss->handleEntryUpdate($entry->reveal());
    }

    public function testTopicUpdate_TopicsEnabled_Success(): void {
        $base_path = '/path/to/feeds/';
        $blog = $this->prophet->prophesize(Blog::class);
        $blog->uri(Argument::any())->willReturn('');
        $blog->localpath()->willReturn('/path/to/');
        $blog->getEntriesByTag(['Foo Bar'], 25)->willReturn([]);
        $blog->getEntriesByTag(['News'], 25)->willReturn([]);
        $mock_blog = $blog->reveal();
        $entry = $this->prophet->prophesize(BlogEntry::class);
        $entry->getParent()->willReturn($mock_blog);
        $entry->tags()->willReturn(['Foo Bar', 'News']);
        $exporter = $this->prophet->prophesize(BaseFeedExporter::class);

        $this->exporterFactory->create(Argument::any())->willReturn($exporter->reveal());

        $this->fs->write_file($base_path . 'FooBar_news.rdf', Argument::any())->willReturn(1)->shouldBeCalled();
        $this->fs->write_file($base_path . 'FooBar_news.xml', Argument::any())->willReturn(1)->shouldBeCalled();
        $this->fs->write_file($base_path . 'FooBar_news_atom.xml', Argument::any())->willReturn(1)->shouldBeCalled();
        $this->fs->write_file($base_path . 'News_news.rdf', Argument::any())->willReturn(1)->shouldBeCalled();
        $this->fs->write_file($base_path . 'News_news.xml', Argument::any())->willReturn(1)->shouldBeCalled();
        $this->fs->write_file($base_path . 'News_news_atom.xml', Argument::any())->willReturn(1)->shouldBeCalled();

        $rss = $this->getRssManagement();
        $rss->use_tag_feeds = true;
        $rss->max_entries = 25;
        $rss->use_rss1 = true;
        $rss->use_rss2 = true;
        $rss->use_atom = true;
        $rss->handleTopicUpdate($entry->reveal());
    }

    public function testTopicUpdate_TopicsDisabled_NoOp(): void {
        $entry = $this->prophet->prophesize(BlogEntry::class);

        $this->fs->write_file(Argument::any(), Argument::any())->shouldNotBeCalled();

        $rss = $this->getRssManagement();
        $rss->use_tag_feeds = false;
        $rss->max_entries = 25;
        $rss->use_rss1 = true;
        $rss->handleTopicUpdate($entry->reveal());
    }

    public function testTopicUpdate_TopicsEnabledButNoFormats_NoOp(): void {
        $entry = $this->prophet->prophesize(BlogEntry::class);

        $this->fs->write_file(Argument::any(), Argument::any())->shouldNotBeCalled();

        $rss = $this->getRssManagement();
        $rss->use_tag_feeds = true;
        $rss->use_rss1 = false;
        $rss->use_rss2 = false;
        $rss->use_atom = false;
        $rss->handleTopicUpdate($entry->reveal());
    }

    public function testHandleHeaderLinks_WithBlog_AddsFeeds(): void {
        $rdf_feed = 'https://foo.com/feeds/news.rdf';
        $rss_feed = 'https://foo.com/feeds/news.xml';
        $atom_feed = 'https://foo.com/feeds/news_atom.xml';

        $blog = $this->prophet->prophesize(Blog::class);
        $blog->localpath()->willReturn('/foo/');
        $page = $this->prophet->prophesize(Page::class);
        $this->fs->file_exists(Argument::any())->willReturn(true);
        $this->setUpLocalpathToUriExpectationMap([
            '/foo/feeds/news.rdf' => $rdf_feed,
            '/foo/feeds/news.xml' => $rss_feed,
            '/foo/feeds/news_atom.xml' => $atom_feed,
        ]);

        $page->addRSSFeed($rdf_feed, 'application/rss+xml', 'RSS 1.0 feed')->shouldBeCalled();
        $page->addRSSFeed($rss_feed, 'application/rss+xml', 'RSS 2.0 feed')->shouldBeCalled();
        $page->addRSSFeed($atom_feed, 'application/atom+xml', 'Atom feed')->shouldBeCalled();

        $page_obj = $page->reveal();
        $page_obj->display_object = $blog->reveal();

        $rss = $this->getRssManagement();
        $rss->show_header_links = true;
        $rss->use_rss1 = true;
        $rss->use_rss2 = true;
        $rss->use_atom = true;
        $rss->handleHeaderLinks($page_obj);
    }

    public function testHandleHeaderLinks_WithEntry_AddsFeeds(): void {
        $rdf_feed = 'https://foo.com/feeds/news.rdf';
        $rdf_comment_feed = 'https://foo.com/entries/2020/01/02_1234/comments/comments.rdf';
        $rss_feed = 'https://foo.com/feeds/news.xml';
        $rss_comment_feed = 'https://foo.com/entries/2020/01/02_1234/comments/comments.xml';
        $atom_feed = 'https://foo.com/feeds/news_atom.xml';
        $atom_comment_feed = 'https://foo.com/entries/2020/01/02_1234/comments/comments_atom.xml';
        $entry_path = '/foo/entries/2020/01/02_1234/';

        $blog = $this->prophet->prophesize(Blog::class);
        $entry = $this->prophet->prophesize(BlogEntry::class);
        $blog->localpath()->willReturn('/foo/');
        $entry->localpath()->willReturn($entry_path);
        $entry->getParent()->willReturn($blog);
        $page = $this->prophet->prophesize(Page::class);
        $this->fs->file_exists(Argument::any())->willReturn(true);
        $this->setUpLocalpathToUriExpectationMap([
            '/foo/feeds/news.rdf' => $rdf_feed,
            "{$entry_path}comments/comments.rdf" => $rdf_comment_feed,
            '/foo/feeds/news.xml' => $rss_feed,
            "{$entry_path}comments/comments.xml" => $rss_comment_feed,
            '/foo/feeds/news_atom.xml' => $atom_feed,
            "{$entry_path}comments/comments_atom.xml" => $atom_comment_feed,
        ]);

        $page->addRSSFeed($rdf_feed, 'application/rss+xml', 'RSS 1.0 feed')->shouldBeCalled();
        $page->addRSSFeed($rss_feed, 'application/rss+xml', 'RSS 2.0 feed')->shouldBeCalled();
        $page->addRSSFeed($atom_feed, 'application/atom+xml', 'Atom feed')->shouldBeCalled();
        $page->addRSSFeed($rdf_comment_feed, 'application/rss+xml', 'Comments - RSS 1.0 feed')->shouldBeCalled();
        $page->addRSSFeed($rss_comment_feed, 'application/rss+xml', 'Comments - RSS 2.0 feed')->shouldBeCalled();
        $page->addRSSFeed($atom_comment_feed, 'application/atom+xml', 'Comments - Atom feed')->shouldBeCalled();

        $page_obj = $page->reveal();
        $page_obj->display_object = $entry->reveal();

        $rss = $this->getRssManagement();
        $rss->show_header_links = true;
        $rss->use_rss1 = true;
        $rss->use_rss2 = true;
        $rss->use_atom = true;
        $rss->handleHeaderLinks($page_obj);
    }

    public function testHandleHeaderLinks_WithExternalFeed_AddsExternalFeed(): void {
        $entry_path = '/foo/entries/2020/01/02_1234/';

        $blog = $this->prophet->prophesize(Blog::class);
        $entry = $this->prophet->prophesize(BlogEntry::class);
        $blog->localpath()->willReturn('/foo/');
        $entry->localpath()->willReturn($entry_path);
        $entry->getParent()->willReturn($blog);
        $page = $this->prophet->prophesize(Page::class);
        $this->fs->file_exists(Argument::any())->willReturn(true);

        $page->addRSSFeed('https://feedplace.com/rss', 'application/rss+xml', 'My RSS Feed')->shouldBeCalled();

        $page_obj = $page->reveal();
        $page_obj->display_object = $entry->reveal();

        $rss = $this->getRssManagement();
        $rss->show_header_links = true;
        $rss->use_external_feed = true;
        $rss->feed_url = 'https://feedplace.com/rss';
        $rss->feed_description = 'My RSS Feed';
        $rss->handleHeaderLinks($page_obj);
    }

    public function testHandleHeaderLinks_WhenDisabled_NoFeedAdded(): void {
        $blog = $this->prophet->prophesize(Blog::class);
        $page = $this->prophet->prophesize(Page::class);

        $page->addRSSFeed(Argument::any(), Argument::any(), Argument::any())->shouldNotBeCalled();

        $page_obj = $page->reveal();
        $page_obj->display_object = $blog->reveal();

        $rss = $this->getRssManagement();
        $rss->show_header_links = false;
        $rss->use_rss1 = true;
        $rss->use_rss2 = true;
        $rss->use_atom = true;
        $rss->handleHeaderLinks($page_obj);
    }

    public function testHandleSidebarLinks_FeedsPresent_OutputsSidebarSection(): void {
        $blog = $this->prophet->prophesize(Blog::class);
        $page = $this->prophet->prophesize(Page::class);

        $blog->localpath()->willReturn('/foo/');
        $this->setUpLocalpathToUriExpectationMap([
            '/foo/feeds/news.rdf' => 'https://foo.com/feeds/news.rdf',
            '/foo/feeds/news.xml' => 'https://foo.com/feeds/news.xml',
            '/foo/feeds/news_atom.xml' => 'https://foo.com/feeds/news_atom.xml',
        ]);
        $this->fs->file_exists(Argument::any())->willReturn(true);

        $page_obj = $page->reveal();
        $page_obj->display_object = $blog->reveal();

        $rss = $this->getRssManagement();
        $rss->use_rss1 = true;
        $rss->use_rss2 = true;
        $rss->use_atom = true;
        $rss->sidebar_use_icons = true;
        $rss->sidebar_section_header = 'RSS Section';
        ob_start();
        $rss->handleSidebarLinks($page_obj);
        $output = ob_get_contents();
        ob_end_clean();

        $this->assertStringContainsString('https://foo.com/feeds/news.rdf', $output);
        $this->assertStringContainsString('https://foo.com/feeds/news.xml', $output);
        $this->assertStringContainsString('https://foo.com/feeds/news_atom.xml', $output);
        $this->assertStringContainsString('RSS 1.0 feed', $output);
        $this->assertStringContainsString('RSS 2.0 feed', $output);
        $this->assertStringContainsString('Atom feed', $output);
        $this->assertStringContainsString('rdf_feed.png', $output);
        $this->assertStringContainsString('xml_feed.png', $output);
        $this->assertStringContainsString('RSS Section', $output);
        $this->assertStringNotContainsString('Purge comment feeds', $output);
        $this->assertStringNotContainsString('Regenerate comment feeds', $output);
    }

    public function testHandleSidebarLinks_FeedsNotPresent_NoOutput(): void {
        $blog = $this->prophet->prophesize(Blog::class);
        $page = $this->prophet->prophesize(Page::class);

        $blog->localpath()->willReturn('/foo/');
        $this->fs->file_exists(Argument::any())->willReturn(false);

        $page_obj = $page->reveal();
        $page_obj->display_object = $blog->reveal();

        $rss = $this->getRssManagement();
        $rss->show_tag_links = true;
        $rss->use_rss1 = true;
        $rss->use_rss2 = true;
        $rss->use_atom = true;
        $rss->sidebar_use_icons = true;
        $rss->sidebar_section_header = 'RSS Section';
        ob_start();
        $rss->handleSidebarLinks($page_obj);
        $output = ob_get_contents();
        ob_end_clean();

        $this->assertEmpty($output);
    }

    public function testHandleSidebarLinks_ExternalFeed_OutputsSidebarSection(): void {
        $blog = $this->prophet->prophesize(Blog::class);
        $page = $this->prophet->prophesize(Page::class);

        # Set up the feed files to exist so we can verify they're not added
        $blog->localpath()->willReturn('/foo/');
        $this->setUpLocalpathToUriExpectationMap([
            '/foo/feeds/news.rdf' => 'https://foo.com/feeds/news.rdf',
            '/foo/feeds/news.xml' => 'https://foo.com/feeds/news.xml',
            '/foo/feeds/news_atom.xml' => 'https://foo.com/feeds/news_atom.xml',
        ]);
        $this->fs->file_exists(Argument::any())->willReturn(true);

        $page_obj = $page->reveal();
        $page_obj->display_object = $blog->reveal();

        $rss = $this->getRssManagement();
        $rss->use_rss1 = true;
        $rss->use_rss2 = true;
        $rss->use_atom = true;
        $rss->sidebar_use_icons = true;
        $rss->use_external_feed = true;
        $rss->feed_url = 'https://foofeeds.com/whatever';
        $rss->feed_description = 'This is some feed';
        $rss->feed_format = 'atom';
        ob_start();
        $rss->handleSidebarLinks($page_obj);
        $output = ob_get_contents();
        ob_end_clean();

        $this->assertStringNotContainsString('https://foo.com/feeds/news.rdf', $output);
        $this->assertStringNotContainsString('https://foo.com/feeds/news.xml', $output);
        $this->assertStringNotContainsString('https://foo.com/feeds/news_atom.xml', $output);
        $this->assertStringContainsString($rss->feed_description, $output);
        $this->assertStringContainsString($rss->feed_url, $output);
        $this->assertStringContainsString('xml_feed.png', $output);
    }

    public function testHandleSidebarLinks_ExternalFeedWithWidget_OutputsSidebarSectionWithOnlyWidget(): void {
        $blog = $this->prophet->prophesize(Blog::class);
        $page = $this->prophet->prophesize(Page::class);
        $page_obj = $page->reveal();
        $page_obj->display_object = $blog->reveal();

        $rss = $this->getRssManagement();
        $rss->sidebar_use_icons = true;
        $rss->use_external_feed = true;
        $rss->sidebar_section_header = 'RSS Section';
        $rss->feed_url = 'https://foofeeds.com/whatever';
        $rss->feed_widget = '<object id="foo-widget"/>';
        ob_start();
        $rss->handleSidebarLinks($page_obj);
        $output = ob_get_contents();
        ob_end_clean();

        $this->assertStringContainsString($rss->feed_widget, $output);
        $this->assertStringContainsString($rss->sidebar_section_header, $output);
        $this->assertStringNotContainsString($rss->feed_url, $output);
        $this->assertStringNotContainsString('xml_feed.png', $output);
    }

    public function testHandleSidebarLinks_EntryWithFeedsPresent_OutputsSidebarSectionWithComments(): void {
        $blog = $this->prophet->prophesize(Blog::class);
        $entry = $this->prophet->prophesize(BlogEntry::class);
        $page = $this->prophet->prophesize(Page::class);

        $entry_path = '/foo/entries/2000/01/02_1234/';
        $entry_url = 'https://foo.com/entries/2000/01/02_1234/';

        $blog->localpath()->willReturn('/foo/');
        $entry->localpath()->willReturn($entry_path);
        $entry->entryID()->willReturn('2000/01/02_1234');
        $this->urlResolver->generateRoute("plugin", Argument::any(), Argument::any())->willReturn('someurl');
        $suffixes = ['.rdf', '.xml', '_atom.xml'];
        foreach ($suffixes as $suffix) {
            $this->setUpLocalpathToUriExpectationMap([
                '/foo/feeds/news' . $suffix => 'https://foo.com/feeds/news' . $suffix,
                $entry_path . 'comments/comments' . $suffix => $entry_url . 'comments/comments' . $suffix
            ]);
        }
        $this->fs->file_exists(Argument::any())->willReturn(true);

        $page_obj = $page->reveal();
        $entry->getParent()->willReturn($blog->reveal());
        $page_obj->display_object = $entry->reveal();

        $rss = $this->getRssManagement();
        $rss->use_rss1 = true;
        $rss->use_rss2 = true;
        $rss->use_atom = true;
        $rss->sidebar_use_icons = true;
        $rss->show_comment_links = true;
        $rss->sidebar_section_header = 'RSS Section';
        ob_start();
        $rss->handleSidebarLinks($page_obj);
        $output = ob_get_contents();
        ob_end_clean();

        $this->assertStringContainsString('https://foo.com/feeds/news.rdf', $output);
        $this->assertStringContainsString('https://foo.com/feeds/news.xml', $output);
        $this->assertStringContainsString('https://foo.com/feeds/news_atom.xml', $output);
        $this->assertStringContainsString($entry_url . 'comments/comments.rdf', $output);
        $this->assertStringContainsString($entry_url . 'comments/comments.xml', $output);
        $this->assertStringContainsString($entry_url . 'comments/comments_atom.xml', $output);
        $this->assertStringContainsString('RSS 1.0 feed', $output);
        $this->assertStringContainsString('RSS 2.0 feed', $output);
        $this->assertStringContainsString('Atom feed', $output);
        $this->assertStringContainsString('rdf_feed.png', $output);
        $this->assertStringContainsString('xml_feed.png', $output);
        $this->assertStringContainsString('RSS Section', $output);
        $this->assertStringContainsString('Purge comment feeds', $output);
        $this->assertStringContainsString('Regenerate comment feeds', $output);
    }

    public function testHandlerTagLink_TagExists_LinkOutput(): void {
        $blog = $this->prophet->prophesize(Blog::class);

        $blog->localpath()->willReturn('/foo/');
        $this->setUpLocalpathToUriExpectationMap([
            '/foo/feeds/foo_news.rdf' => 'https://foo.com/feeds/foo_news.rdf',
            '/foo/feeds/foo_news.xml' => 'https://foo.com/feeds/foo_news.xml',
            '/foo/feeds/foo_news_atom.xml' => 'https://foo.com/feeds/foo_news_atom.xml',
        ]);
        $this->fs->file_exists(Argument::any())->willReturn(true);

        $rss = $this->getRssManagement();
        $rss->show_tag_links = true;
        $rss->use_rss1 = true;
        $rss->use_rss2 = true;
        $rss->use_atom = true;
        $rss->sidebar_use_icons = true;
        ob_start();
        $rss->handleTagLink(null, [['foo'], $blog->reveal()]);
        $output = ob_get_contents();
        ob_end_clean();

        $this->assertStringContainsString('https://foo.com/feeds/foo_news.rdf', $output);
        $this->assertStringContainsString('https://foo.com/feeds/foo_news.xml', $output);
        $this->assertStringContainsString('https://foo.com/feeds/foo_news_atom.xml', $output);
        $this->assertStringContainsString('RSS 1.0 feed', $output);
        $this->assertStringContainsString('RSS 2.0 feed', $output);
        $this->assertStringContainsString('Atom feed', $output);
        $this->assertStringContainsString('rdf_feed.png', $output);
        $this->assertStringContainsString('xml_feed.png', $output);
    }

    public function testHandlerTagLink_IconsDisabled_NoIconsInOutput(): void {
        $blog = $this->prophet->prophesize(Blog::class);

        $blog->localpath()->willReturn('/foo/');
        $this->setUpLocalpathToUriExpectationMap([
            '/foo/feeds/foo_news.rdf' => 'https://foo.com/feeds/foo_news.rdf',
            '/foo/feeds/foo_news.xml' => 'https://foo.com/feeds/foo_news.xml',
            '/foo/feeds/foo_news_atom.xml' => 'https://foo.com/feeds/foo_news_atom.xml',
        ]);
        $this->fs->file_exists(Argument::any())->willReturn(true);

        $rss = $this->getRssManagement();
        $rss->show_tag_links = true;
        $rss->use_rss1 = true;
        $rss->use_rss2 = true;
        $rss->use_atom = true;
        $rss->sidebar_use_icons = false;
        ob_start();
        $rss->handleTagLink(null, [['foo'], $blog->reveal()]);
        $output = ob_get_contents();
        ob_end_clean();

        $this->assertStringContainsString('https://foo.com/feeds/foo_news.rdf', $output);
        $this->assertStringContainsString('https://foo.com/feeds/foo_news.xml', $output);
        $this->assertStringContainsString('https://foo.com/feeds/foo_news_atom.xml', $output);
        $this->assertStringContainsString('RSS 1.0 feed', $output);
        $this->assertStringContainsString('RSS 2.0 feed', $output);
        $this->assertStringContainsString('Atom feed', $output);
        $this->assertStringNotContainsString('rdf_feed.png', $output);
        $this->assertStringNotContainsString('xml_feed.png', $output);
    }

    public function testHandleTagLink_NoFeedFiles_NoOutput(): void {
        $blog = $this->prophet->prophesize(Blog::class);

        $blog->localpath()->willReturn('/foo/');
        $this->fs->file_exists(Argument::any())->willReturn(false);

        $rss = $this->getRssManagement();
        $rss->show_tag_links = true;
        $rss->use_rss1 = true;
        $rss->use_rss2 = true;
        $rss->use_atom = true;
        ob_start();
        $rss->handleTagLink(null, [['foo'], $blog->reveal()]);
        $output = ob_get_contents();
        ob_end_clean();

        $this->assertEmpty($output);
    }

    public function testHandleTagLink_LinksDisabled_NoOutput(): void {
        $blog = $this->prophet->prophesize(Blog::class);

        $blog->localpath()->willReturn('/foo/');
        $this->fs->file_exists(Argument::any())->willReturn(true);

        $rss = $this->getRssManagement();
        $rss->show_tag_links = false;
        $rss->use_rss1 = true;
        $rss->use_rss2 = true;
        $rss->use_atom = true;
        ob_start();
        $rss->handleTagLink(null, [['foo'], $blog->reveal()]);
        $output = ob_get_contents();
        ob_end_clean();

        $this->assertEmpty($output);
    }

    public function testHandleTagLink_DisplayObjectNotBlog_NoOutput(): void {
        $this->fs->file_exists(Argument::any())->willReturn(true);

        $rss = $this->getRssManagement();
        $rss->show_tag_links = true;
        $rss->use_rss1 = true;
        $rss->use_rss2 = true;
        $rss->use_atom = true;
        ob_start();
        $rss->handleTagLink(null, [['foo'], null]);
        $output = ob_get_contents();
        ob_end_clean();

        $this->assertEmpty($output);
    }

    public function testOutputPage_PurgeBlog_RemovesFiles(): void {
        $rdf_files = ['./feeds/foo.rdf', './feeds/bar.rdf', './feeds/news.rdf'];
        $xml_files = ['./feeds/foo.xml', './feeds/bar.xml', './feeds/news.xml',
            './feeds/foo_atom.xml', './feeds/bar_atom.xml', './feeds/news_atom.xml'
        ];

        $this->fs->glob('./feeds/*.rdf')->willReturn($rdf_files);
        $this->fs->glob('./feeds/*.xml')->willReturn($xml_files);

        $this->fs->delete('./feeds/foo.rdf')->shouldBeCalled();
        $this->fs->delete('./feeds/bar.rdf')->shouldBeCalled();
        $this->fs->delete('./feeds/news.rdf')->shouldBeCalled();
        $this->fs->delete('./feeds/foo.xml')->shouldBeCalled();
        $this->fs->delete('./feeds/bar.xml')->shouldBeCalled();
        $this->fs->delete('./feeds/news.xml')->shouldBeCalled();
        $this->fs->delete('./feeds/foo_atom.xml')->shouldBeCalled();
        $this->fs->delete('./feeds/bar_atom.xml')->shouldBeCalled();
        $this->fs->delete('./feeds/news_atom.xml')->shouldBeCalled();

        $user = $this->prophet->prophesize(User::class);
        $user->checkLogin()->willReturn(true);
        $page = $this->setUpForAuthorizedRequest();
        $blog = $this->prophet->prophesize(Blog::class);
        $blog->localpath()->willReturn('.');
        $rss = $this->getRssManagement();
        $rss->test_blog = $blog->reveal();
        $rss->outputPage($page->reveal(), 'purgeblog');
    }

    /**
     * @dataProvider actionProvider
     */
    public function testOutputPage_NotLoggedIn_Error403(string $action): void {
        $blog = $this->prophet->prophesize(Blog::class);
        $entry = $this->prophet->prophesize(BlogEntry::class);
        $user = $this->prophet->prophesize(User::class);
        $page = $this->prophet->prophesize(Page::class);
        $webpage = $this->prophet->prophesize(BasePages::class);
        $webpage->getCurrentUser()->willReturn($user->reveal());

        $user->checkLogin()->willReturn(false);
        $page->error(403)->shouldBeCalled();
        $webpage->getPage()->willReturn($page->reveal());
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('User not logged in');

        $rss = $this->getRssManagement();
        $rss->test_blog = $blog->reveal();
        $rss->test_entry = $entry->reveal();

        $rss->outputPage($webpage->reveal(), $action);
    }

    /**
     * @dataProvider actionProvider
     */
    public function testOutputPage_NotAuthorized_Error403(string $action): void {
        $blog = $this->prophet->prophesize(Blog::class);
        $entry = $this->prophet->prophesize(BlogEntry::class);
        $user = $this->prophet->prophesize(User::class);
        $page = $this->prophet->prophesize(Page::class);
        $webpage = $this->prophet->prophesize(BasePages::class);

        $user->checkLogin()->willReturn(true);
        $mock_user = $user->reveal();
        $webpage->getCurrentUser()->willReturn($mock_user);
        $page->error(403)->shouldBeCalled();
        $webpage->getPage()->willReturn($page->reveal());
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('User not authorized');

        $rss = $this->getRssManagement();
        $rss->test_blog = $blog->reveal();
        $rss->test_entry = $entry->reveal();

        $this->system->canModify($rss->test_blog, $mock_user)->willReturn(false);
        $this->system->canModify($rss->test_entry, $mock_user)->willReturn(false);

        $rss->outputPage($webpage->reveal(), $action);
    }

    /**
     * @return array<string[]>
     */
    public function actionProvider(): array {
        return [
            ['purgeblog'],
            ['regenblog'],
            ['purgecomment'],
            ['regencomment'],
            ['purgeallcomment'],
            ['regenallcomment'],
        ];
    }

    public function testOutputPage_PurgeEntry_RemovesFiles(): void {
        $entry_path = './entries/2000/01/02_1234/';
        $feed_files = [
            "{$entry_path}comments/comments.rdf",
            "{$entry_path}comments/comments.xml",
            "{$entry_path}comments/comments_atom.xml",
        ];

        foreach ($feed_files as $file) {
            $this->fs->file_exists($file)->willReturn(true);
            $this->fs->delete($file)->shouldBeCalled();
        }

        $page = $this->setUpForAuthorizedRequest();
        $entry = $this->prophet->prophesize(BlogEntry::class);
        $entry->localpath()->willReturn($entry_path);
        $rss = $this->getRssManagement();
        $rss->test_entry = $entry->reveal();
        $rss->outputPage($page->reveal(), 'purgecomment');
    }

    public function testOutputPage_RegenerateBlogFeeds_CreateMainAndTags(): void {

        $base_path = '/path/to/feeds/';
        $page = $this->setUpForAuthorizedRequest();
        $blog = $this->prophet->prophesize(Blog::class);
        $blog->uri(Argument::any())->willReturn('');
        $blog->localpath()->willReturn('/path/to/');
        $blog->getRecent(25)->willReturn([]);
        $blog->getEntriesByTag(['Foo Bar'], 25)->willReturn([]);
        $mock_blog = $blog->reveal();
        $mock_blog->tag_list = ['Foo Bar'];
        $exporter = $this->prophet->prophesize(BaseFeedExporter::class);

        $this->exporterFactory->create(Argument::any())->willReturn($exporter->reveal());

        $this->fs->write_file($base_path . 'news.xml', Argument::any())->willReturn(1)->shouldBeCalled();
        $this->fs->write_file($base_path . 'FooBar_news.xml', Argument::any())->willReturn(1)->shouldBeCalled();

        $rss = $this->getRssManagement();
        $rss->max_entries = 25;
        $rss->use_rss2 = true;
        $rss->use_tag_feeds = true;
        $rss->test_blog = $mock_blog;
        $rss->test_blog = $blog->reveal();
        $rss->outputPage($page->reveal(), 'regenblog');
    }

    public function testOutputPage_PurgeEntryComments_RemovesFiles(): void {
        $this->fs->file_exists('./entries/2000/01/02_1234/comments/comments.rdf')->willReturn(true);
        $this->fs->file_exists('./entries/2000/01/02_1234/comments/comments.xml')->willReturn(true);
        $this->fs->file_exists('./entries/2000/01/02_1234/comments/comments_atom.xml')->willReturn(false);

        $this->fs->delete('./entries/2000/01/02_1234/comments/comments.rdf')->shouldBeCalled();
        $this->fs->delete('./entries/2000/01/02_1234/comments/comments.xml')->shouldBeCalled();
        $this->fs->delete('./entries/2000/01/02_1234/comments/comments_atom.xml')->shouldNotBeCalled();

        $page = $this->setUpForAuthorizedRequest();
        $entry = $this->prophet->prophesize(BlogEntry::class);
        $entry->localpath()->willReturn('./entries/2000/01/02_1234/');
        $rss = $this->getRssManagement();
        $rss->test_entry = $entry->reveal();
        $rss->outputPage($page->reveal(), 'purgecomment');
    }

    public function testOutputPage_RegenerateEntryComments_CreatesFiles(): void {
        $entry = $this->prophet->prophesize(BlogEntry::class);
        $entry->localpath()->willReturn('./entries/2000/01/02_1234/');
        $entry->uri('comment')->willReturn('https://localhost/foo/entries/2000/01/02_1234/comments/');
        $entry->getCommentArray()->willReturn([]);
        $exporter = $this->prophet->prophesize(BaseFeedExporter::class);

        $this->exporterFactory->create(Argument::any())->willReturn($exporter->reveal());

        $this->fs->write_file('./entries/2000/01/02_1234/comments/comments.xml', Argument::any())->willReturn(1)->shouldBeCalled();

        $page = $this->setUpForAuthorizedRequest();
        $rss = $this->getRssManagement();
        $rss->use_rss2 = true;
        $rss->test_entry = $entry->reveal();
        $rss->outputPage($page->reveal(), 'regencomment');
    }

    public function testOutputPage_PurgeAllComments_RemovesFiles(): void {
        $this->fs->file_exists('./entries/2000/01/02_1234/comments/comments.rdf')->willReturn(true);
        $this->fs->file_exists('./entries/2000/01/02_1234/comments/comments.xml')->willReturn(true);
        $this->fs->file_exists('./entries/2000/01/02_1234/comments/comments_atom.xml')->willReturn(false);

        $this->fs->delete('./entries/2000/01/02_1234/comments/comments.rdf')->shouldBeCalled();
        $this->fs->delete('./entries/2000/01/02_1234/comments/comments.xml')->shouldBeCalled();
        $this->fs->delete('./entries/2000/01/02_1234/comments/comments_atom.xml')->shouldNotBeCalled();

        $page = $this->setUpForAuthorizedRequest();
        $entry = $this->prophet->prophesize(BlogEntry::class);
        $entry->localpath()->willReturn('./entries/2000/01/02_1234/');
        $blog = $this->prophet->prophesize(Blog::class);
        $blog->getEntries()->willReturn([$entry->reveal()]);
        $rss = $this->getRssManagement();
        $rss->test_blog = $blog->reveal();
        $rss->outputPage($page->reveal(), 'purgeallcomment');
    }

    public function testOutputPage_RegenerateAllComments_CreatesFiles(): void {
        $entry = $this->prophet->prophesize(BlogEntry::class);
        $entry->localpath()->willReturn('./entries/2000/01/02_1234/');
        $entry->uri('comment')->willReturn('https://localhost/foo/entries/2000/01/02_1234/comments/');
        $entry->getCommentArray()->willReturn([]);
        $exporter = $this->prophet->prophesize(BaseFeedExporter::class);

        $this->exporterFactory->create(Argument::any())->willReturn($exporter->reveal());

        $this->fs->write_file('./entries/2000/01/02_1234/comments/comments.xml', Argument::any())->willReturn(1)->shouldBeCalled();

        $page = $this->setUpForAuthorizedRequest();
        $blog = $this->prophet->prophesize(Blog::class);
        $blog->getEntries()->willReturn([$entry->reveal()]);
        $rss = $this->getRssManagement();
        $rss->use_rss2 = true;
        $rss->test_blog = $blog->reveal();
        $rss->outputPage($page->reveal(), 'regenallcomment');
    }

    public function testOutputPage_BadAction_BadRequest(): void {
        $base_page = $this->prophet->prophesize(BasePages::class);
        $page = $this->prophet->prophesize(Page::class);

        $page->error(400)->shouldBeCalled();
        $base_page->getPage()->willReturn($page->reveal());

        $rss = $this->getRssManagement();
        $rss->outputPage($base_page->reveal(), 'this_is_garbage');
    }

    public function testHandleEntryFeedListingRequest_GetCommentFeeds(): void {
        $rdf_feed = 'https://foo.com/feeds/news.rdf';
        $rdf_comment_feed = 'https://foo.com/entries/2020/01/02_1234/comments/comments.rdf';
        $rss_feed = 'https://foo.com/feeds/news.xml';
        $rss_comment_feed = 'https://foo.com/entries/2020/01/02_1234/comments/comments.xml';
        $atom_feed = 'https://foo.com/feeds/news_atom.xml';
        $atom_comment_feed = 'https://foo.com/entries/2020/01/02_1234/comments/comments_atom.xml';
        $entry_path = '/foo/entries/2020/01/02_1234/';

        $blog = $this->prophet->prophesize(Blog::class);
        $entry = $this->prophet->prophesize(BlogEntry::class);
        $blog->localpath()->willReturn('/foo/');
        $entry->localpath()->willReturn($entry_path);
        $entry->getParent()->willReturn($blog);
        $this->fs->file_exists(Argument::any())->willReturn(true);
        $this->setUpLocalpathToUriExpectationMap([
            '/foo/feeds/news.rdf' => $rdf_feed,
            "{$entry_path}comments/comments.rdf" => $rdf_comment_feed,
            '/foo/feeds/news.xml' => $rss_feed,
            "{$entry_path}comments/comments.xml" => $rss_comment_feed,
            '/foo/feeds/news_atom.xml' => $atom_feed,
            "{$entry_path}comments/comments_atom.xml" => $atom_comment_feed,
        ]);

        $rss = $this->getRssManagement();
        $rss->use_rss1 = true;
        $rss->use_rss2 = true;
        $rss->use_atom = true;
        $result = $rss->handleEntryFeedListingRequest($entry->reveal());

        $this->assertCount(3, $result);
    }

    public function testHandleBlogFeedListingRequest_WithoutTags_ReturnsMainFeeds(): void {
        $rdf_feed = 'https://foo.com/feeds/news.rdf';
        $rss_feed = 'https://foo.com/feeds/news.xml';
        $atom_feed = 'https://foo.com/feeds/news_atom.xml';

        $blog = $this->prophet->prophesize(Blog::class);
        $blog->localpath()->willReturn('/foo/');
        $this->fs->file_exists(Argument::any())->willReturn(true);
        $this->setUpLocalpathToUriExpectationMap([
            '/foo/feeds/news.rdf' => $rdf_feed,
            '/foo/feeds/news.xml' => $rss_feed,
            '/foo/feeds/news_atom.xml' => $atom_feed,
        ]);

        $rss = $this->getRssManagement();
        $rss->use_rss1 = true;
        $rss->use_rss2 = true;
        $rss->use_atom = true;
        $result = $rss->handleBlogFeedListingRequest($blog->reveal(), []);

        $this->assertCount(3, $result);
        $this->assertEquals($rdf_feed, $result[0]['href']);
    }

    public function testHandleBlogFeedListingRequest_WithTags_ReturnsTagFeeds(): void {
        $blog = $this->prophet->prophesize(Blog::class);

        $blog->localpath()->willReturn('/foo/');
        $this->setUpLocalpathToUriExpectationMap([
            '/foo/feeds/foo_news.rdf' => 'https://foo.com/feeds/foo_news.rdf',
            '/foo/feeds/foo_news.xml' => 'https://foo.com/feeds/foo_news.xml',
            '/foo/feeds/foo_news_atom.xml' => 'https://foo.com/feeds/foo_news_atom.xml',
        ]);
        $this->fs->file_exists(Argument::any())->willReturn(true);

        $rss = $this->getRssManagement();
        $rss->use_rss1 = true;
        $rss->use_rss2 = true;
        $rss->use_atom = true;
        $result = $rss->handleBlogFeedListingRequest($blog->reveal(), ['foo']);

        $this->assertCount(3, $result);
        $this->assertEquals('https://foo.com/feeds/foo_news.rdf', $result[0]['href']);
    }

    protected function setUp(): void {
        parent::setUp();
        $this->fs = $this->prophet->prophesize(FS::class);
        $this->exporterFactory = $this->prophet->prophesize(ExporterFactory::class);
        $this->urlResolver = $this->prophet->prophesize(UrlResolver::class);
        $this->system = $this->prophet->prophesize(System::class);
        $this->logger = $this->prophet->prophesize(LoggerInterface::class);
    }

    private function getRssManagement(): RssManagement {
        $rss = new RssManagement(
            $this->fs->reveal(),
            $this->exporterFactory->reveal(),
            $this->urlResolver->reveal(),
            $this->system->reveal(),
            $this->logger->reveal()
        );
        $rss->setTestMode(true);

        return $rss;
    }

    private function setUpForAuthorizedRequest() {
        $user = $this->prophet->prophesize(User::class);
        $user->checkLogin()->willReturn(true);
        $mock_user = $user->reveal();
        $page = $this->prophet->prophesize(BasePages::class);
        $page->getCurrentUser()->willReturn($mock_user);
        $this->system->canModify(Argument::any(), Argument::any())->willReturn(true);

        return $page;
    }

    private function setUpLocalpathToUriExpectationMap(array $map): void {
        foreach ($map as $path => $url) {
            $this->urlResolver
                 ->localpathToUri($path, Argument::any(), Argument::any())
                 ->willReturn($url);
        }
    }
}
