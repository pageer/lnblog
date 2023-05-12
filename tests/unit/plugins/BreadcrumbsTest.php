<?php

namespace LnBlog\Tests\Plugins;

require_once __DIR__ . '/../../../plugins/menubar_breadcrumbs.php';

use Blog;
use BlogEntry;
use Breadcrumbs;
use LnBlog\Tests\LnBlogBaseTestCase;

class BreadcrumbsTest extends LnBlogBaseTestCase
{
    private $blog;
    private $entry;

    public function testOutput_BlogEntry_OutputsTitle(): void {
        $blog_url = 'https://foo.com/';
        $archive_url = $blog_url . BLOG_ENTRY_PATH . '/';
        $yearlist_url = $blog_url . BLOG_ENTRY_PATH . '/2022/';
        $monthlist_url = $blog_url . BLOG_ENTRY_PATH . '/2022/02/';
        $entry_url = $blog_url . BLOG_ENTRY_PATH . '/2022/02/test.php';
        $this->blog->isBlog()->willReturn(true);
        $this->entry->isEntry()->willReturn(true);
        $this->entry->isDraft()->willReturn(false);
        $this->entry->subject = 'Test entry';
        $this->blog->uri('base')->willReturn($blog_url);
        $this->blog->uri('archives')->willReturn($archive_url);
        $this->blog->uri('listyear', ['year' => 2022])->willReturn($yearlist_url);
        $this->blog->uri('listmonth', ['year' => 2022, 'month' => '02'])->willReturn($monthlist_url);
        $this->entry->uri('permalink')->willReturn($entry_url);

        $_SERVER['PHP_SELF'] = 'https://foo.com/entries/2022/02/test.php';
        $bread = new Breadcrumbs($this->blog->reveal(), $this->entry->reveal());
        ob_start();
        $bread->output();
        $output = ob_get_clean();

        $this->assertStringContainsString("a href=\"$entry_url\"", $output);
        $this->assertStringContainsString("a href=\"$monthlist_url\"", $output);
        $this->assertStringContainsString("a href=\"$yearlist_url\"", $output);
        $this->assertStringContainsString('>Test entry<', $output);
    }

    public function testOutput_BlogEntryIsArticle_OutputsTitle(): void {
        $blog_url = 'https://foo.com/';
        $article_url = $blog_url . BLOG_ARTICLE_PATH . '/';
        $entry_url = $blog_url . BLOG_ARTICLE_PATH . '/whatever/';
        $this->blog->isBlog()->willReturn(true);
        $this->entry->isEntry()->willReturn(true);
        $this->entry->isDraft()->willReturn(false);
        $this->entry->subject = 'Test article';
        $this->blog->uri('base')->willReturn($blog_url);
        $this->blog->uri('articles')->willReturn($article_url);
        $this->entry->uri('permalink')->willReturn($entry_url);

        $_SERVER['PHP_SELF'] = 'https://foo.com/content/whatever/';
        $bread = new Breadcrumbs($this->blog->reveal(), $this->entry->reveal());
        ob_start();
        $bread->output();
        $output = ob_get_clean();

        $this->assertStringContainsString("a href=\"$entry_url\"", $output);
        $this->assertStringContainsString('>Test article<', $output);
    }

    public function testOutput_BlogEntryIsDraft_OutputsTitle(): void {
        $blog_url = 'https://foo.com/';
        $draft_url = $blog_url . BLOG_DRAFT_PATH . '/';
        $entry_url = $blog_url . BLOG_DRAFT_PATH . '/01_2345/';
        $this->blog->isBlog()->willReturn(true);
        $this->entry->isEntry()->willReturn(true);
        $this->entry->isDraft()->willReturn(true);
        $this->entry->subject = 'Test draft';
        $this->blog->uri('base')->willReturn($blog_url);
        $this->blog->uri('drafts')->willReturn($draft_url);
        $this->entry->uri('base')->willReturn($entry_url);

        $_SERVER['PHP_SELF'] = 'https://foo.com/drafts/01_2345/';
        $bread = new Breadcrumbs($this->blog->reveal(), $this->entry->reveal());
        ob_start();
        $bread->output();
        $output = ob_get_clean();

        $this->assertStringContainsString("a href=\"$entry_url\"", $output);
        $this->assertStringContainsString("Drafts", $output);
        $this->assertStringContainsString('>Test draft<', $output);
    }

    public function testOutput_BlogEntryComment_OutputsComments(): void {
        $blog_url = 'https://foo.com/';
        $archive_url = $blog_url . BLOG_ENTRY_PATH . '/';
        $yearlist_url = $blog_url . BLOG_ENTRY_PATH . '/2022/';
        $monthlist_url = $blog_url . BLOG_ENTRY_PATH . '/2022/02/';
        $entry_url = $blog_url . BLOG_ENTRY_PATH . '/2022/02/test.php';
        $comment_url = $blog_url . BLOG_ENTRY_PATH . '/2022/02/01_2345/comments/';
        $this->blog->isBlog()->willReturn(true);
        $this->entry->isEntry()->willReturn(true);
        $this->entry->isDraft()->willReturn(false);
        $this->entry->subject = 'Test entry';
        $this->blog->uri('base')->willReturn($blog_url);
        $this->blog->uri('archives')->willReturn($archive_url);
        $this->blog->uri('listyear', ['year' => 2022])->willReturn($yearlist_url);
        $this->blog->uri('listmonth', ['year' => 2022, 'month' => '02'])->willReturn($monthlist_url);
        $this->entry->uri('permalink')->willReturn($entry_url);
        $this->entry->uri('comment')->willReturn($comment_url);

        $_SERVER['PHP_SELF'] = 'https://foo.com/entries/2022/02/01_1234/comments/';
        $bread = new Breadcrumbs($this->blog->reveal(), $this->entry->reveal());
        ob_start();
        $bread->output();
        $output = ob_get_clean();

        $this->assertStringContainsString("a href=\"$entry_url\"", $output);
        $this->assertStringContainsString("a href=\"$monthlist_url\"", $output);
        $this->assertStringContainsString("a href=\"$yearlist_url\"", $output);
        $this->assertStringContainsString("a href=\"$comment_url\"", $output);
        $this->assertStringContainsString('Comments', $output);
    }

    public function testOutput_BlogEntryPingback_OutputsPingbacks(): void {
        $blog_url = 'https://foo.com/';
        $archive_url = $blog_url . BLOG_ENTRY_PATH . '/';
        $yearlist_url = $blog_url . BLOG_ENTRY_PATH . '/2022/';
        $monthlist_url = $blog_url . BLOG_ENTRY_PATH . '/2022/02/';
        $entry_url = $blog_url . BLOG_ENTRY_PATH . '/2022/02/test.php';
        $comment_url = $blog_url . BLOG_ENTRY_PATH . '/2022/02/01_2345/pingback/';
        $this->blog->isBlog()->willReturn(true);
        $this->entry->isEntry()->willReturn(true);
        $this->entry->isDraft()->willReturn(false);
        $this->entry->subject = 'Test entry';
        $this->blog->uri('base')->willReturn($blog_url);
        $this->blog->uri('archives')->willReturn($archive_url);
        $this->blog->uri('listyear', ['year' => 2022])->willReturn($yearlist_url);
        $this->blog->uri('listmonth', ['year' => 2022, 'month' => '02'])->willReturn($monthlist_url);
        $this->entry->uri('permalink')->willReturn($entry_url);
        $this->entry->uri('pingback')->willReturn($comment_url);

        $_SERVER['PHP_SELF'] = 'https://foo.com/entries/2022/02/01_1234/pingback/';
        $bread = new Breadcrumbs($this->blog->reveal(), $this->entry->reveal());
        ob_start();
        $bread->output();
        $output = ob_get_clean();

        $this->assertStringContainsString("a href=\"$entry_url\"", $output);
        $this->assertStringContainsString("a href=\"$monthlist_url\"", $output);
        $this->assertStringContainsString("a href=\"$yearlist_url\"", $output);
        $this->assertStringContainsString("a href=\"$comment_url\"", $output);
        $this->assertStringContainsString('Pingbacks', $output);
    }

    public function testOutput_BlogEntryTrackBack_OutputsTrackBacks(): void {
        $blog_url = 'https://foo.com/';
        $archive_url = $blog_url . BLOG_ENTRY_PATH . '/';
        $yearlist_url = $blog_url . BLOG_ENTRY_PATH . '/2022/';
        $monthlist_url = $blog_url . BLOG_ENTRY_PATH . '/2022/02/';
        $entry_url = $blog_url . BLOG_ENTRY_PATH . '/2022/02/test.php';
        $comment_url = $blog_url . BLOG_ENTRY_PATH . '/2022/02/01_2345/trackback/';
        $this->blog->isBlog()->willReturn(true);
        $this->entry->isEntry()->willReturn(true);
        $this->entry->isDraft()->willReturn(false);
        $this->entry->subject = 'Test entry';
        $this->blog->uri('base')->willReturn($blog_url);
        $this->blog->uri('archives')->willReturn($archive_url);
        $this->blog->uri('listyear', ['year' => 2022])->willReturn($yearlist_url);
        $this->blog->uri('listmonth', ['year' => 2022, 'month' => '02'])->willReturn($monthlist_url);
        $this->entry->uri('permalink')->willReturn($entry_url);
        $this->entry->uri('trackback')->willReturn($comment_url);

        $_SERVER['PHP_SELF'] = 'https://foo.com/entries/2022/02/01_1234/trackback/';
        $bread = new Breadcrumbs($this->blog->reveal(), $this->entry->reveal());
        ob_start();
        $bread->output();
        $output = ob_get_clean();

        $this->assertStringContainsString("a href=\"$entry_url\"", $output);
        $this->assertStringContainsString("a href=\"$monthlist_url\"", $output);
        $this->assertStringContainsString("a href=\"$yearlist_url\"", $output);
        $this->assertStringContainsString("a href=\"$comment_url\"", $output);
        $this->assertStringContainsString('TrackBacks', $output);
    }

    public function testOutput_Tags_OutputsTags(): void {
        $blog_url = 'https://foo.com/';
        $tags_url = $blog_url . '?action=tags';
        $this->blog->isBlog()->willReturn(true);
        $this->entry->isEntry()->willReturn(false);
        $this->entry->isDraft()->willReturn(false);
        $this->blog->uri('base')->willReturn($blog_url);
        $this->blog->uri('tags')->willReturn($tags_url);

        $_SERVER['PHP_SELF'] = 'https://foo.com/?action=tags';
        $_GET['action'] = 'tags';
        $bread = new Breadcrumbs($this->blog->reveal(), $this->entry->reveal());
        ob_start();
        $bread->output();
        $output = ob_get_clean();

        $this->assertStringContainsString("a href=\"$tags_url\"", $output);
        $this->assertStringContainsString('Tags', $output);
    }

    public function testOutput_SingleTag_OutputsTags(): void {
        $blog_url = 'https://foo.com/';
        $tags_url = $blog_url . '?action=tags';
        $tag_url = $blog_url . '?action=tags&tag=foo';
        $this->blog->isBlog()->willReturn(true);
        $this->entry->isEntry()->willReturn(false);
        $this->entry->isDraft()->willReturn(false);
        $this->blog->uri('base')->willReturn($blog_url);
        $this->blog->uri('tags')->willReturn($tags_url);
        $this->blog->uri('tags', ['tag' => 'foo'])->willReturn($tag_url);

        $_SERVER['PHP_SELF'] = 'https://foo.com/?action=tags';
        $_GET['action'] = 'tags';
        $_GET['tag'] = 'foo';
        $bread = new Breadcrumbs($this->blog->reveal(), $this->entry->reveal());
        ob_start();
        $bread->output();
        $output = ob_get_clean();

        $this->assertStringContainsString("a href=\"$tags_url\"", $output);
        $this->assertStringContainsString("a href=\"$tag_url\"", $output);
        $this->assertStringContainsString('foo', $output);
    }

    public function testOutput_NotBlog_NoOutput(): void {
        $this->blog->isBlog()->willReturn(false);

        $_SERVER['PHP_SELF'] = 'https://foo.com/';
        $bread = new Breadcrumbs($this->blog->reveal(), $this->entry->reveal());
        ob_start();
        $bread->output();
        $output = ob_get_clean();

        $this->assertEmpty($output);
    }

    public function testOutput_NoBreadcrumbs_NoOutput(): void {
        $blog_url = 'https://foo.com/';
        $this->blog->isBlog()->willReturn(true);
        $this->entry->isEntry()->willReturn(false);
        $this->blog->uri('base')->willReturn($blog_url);

        $_SERVER['PHP_SELF'] = 'https://foo.com/';
        $bread = new Breadcrumbs($this->blog->reveal(), $this->entry->reveal());
        ob_start();
        $bread->output();
        $output = ob_get_clean();

        $this->assertEmpty($output);
    }

    protected function setUp(): void {
        parent::setUp();
        $this->blog = $this->prophet->prophesize(Blog::class);
        $this->entry = $this->prophet->prophesize(BlogEntry::class);
    }
}
