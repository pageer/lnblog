<?php

use LnBlog\Attachments\FileManager;
use LnBlog\Export\ExportTarget;
use LnBlog\Export\WordPressExporter;
use LnBlog\Storage\EntryRepository;
use LnBlog\Storage\ReplyRepository;
use LnBlog\Storage\UserRepository;
use LnBlog\Tasks\TaskManager;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;

class WordPressExporterTest extends \PHPUnit\Framework\TestCase
{
    private $prophet;

    private $fs;
    private $globals;
    private $user_repo;

    public function testExport_NoEntries_NoItems() {
        $blog = $this->getTestBlog();
        $this->globals->time()->willReturn(strtotime('2000-01-01 12:13:14 GMT'));

        $target = new ExportTarget($this->fs->reveal());
        $exporter = new WordPressExporter($this->globals->reveal(), $this->user_repo->reveal());
        $exporter->export($blog, $target);

        $this->assertEquals(
            $this->prettyPrintXml($this->getExportContent()),
            $this->prettyPrintXml($target->getAsText())
        );
    }

    public function testExport_AllEntryTypes_MatchesXml() {
        $test_entry_data = $this->getTestEntry();
        $test_article_data = $this->getTestArticle();
        $test_draft_data = $this->getTestDraft();
        $entries = [
            $test_entry_data[0],
        ];
        $articles = [
            $test_article_data[0],
        ];
        $drafts = [
            $test_draft_data[0],
        ];
        $entry_lines = array_merge(
            $test_entry_data[1],
            $test_article_data[1],
            $test_draft_data[1],
        );

        $blog = $this->getTestBlog([
            'entries' => $entries,
            'articles' => $articles,
            'drafts' => $drafts,
        ]);

        $target = new ExportTarget($this->fs->reveal());
        $exporter = new WordPressExporter($this->globals->reveal(), $this->user_repo->reveal());
        $exporter->export($blog, $target);

        $this->assertEquals(
            $this->prettyPrintXml($this->getExportContent($entry_lines)),
            $this->prettyPrintXml($target->getAsText())
        );
    }

    /**
     * @dataProvider getCommentVariations
     */
    public function testExport_EntryWithComments_MatchesXml($use_comments, $use_pingbacks) {
        $test_entry_data = $this->getTestEntry($use_comments, $use_pingbacks);
        $entries = [
            $test_entry_data[0],
        ];
        $entry_lines = $test_entry_data[1];

        $blog = $this->getTestBlog([
            'entries' => $entries,
        ]);

        $target = new ExportTarget($this->fs->reveal());
        $exporter = new WordPressExporter($this->globals->reveal(), $this->user_repo->reveal());
        $exporter->export($blog, $target);

        $this->assertEquals(
            $this->prettyPrintXml($this->getExportContent($entry_lines)),
            $this->prettyPrintXml($target->getAsText())
        );
    }

    public function getCommentVariations(): array {
        return [
            'nothing' => [false, false],
            'just comments' => [true, false],
            'just pingbacks' => [false, true],
            'both' => [true, true],
        ];
    }

    protected function setUp(): void {
        Path::$sep = Path::UNIX_SEP;
        $this->prophet = new \Prophecy\Prophet();
        $this->fs = $this->prophet->prophesize(FS::class);
        $this->globals = $this->prophet->prophesize(GlobalFunctions::class);
        $this->user_repo = $this->prophet->prophesize(UserRepository::class);
    }

    protected function tearDown(): void {
        $this->prophet->checkPredictions();
    }

    private function getTestBlog(array $data = []): Blog {
        $blog_url = 'https://fooblog.com/';
        $mock_fs = $this->prophet->prophesize(FS::class);
        $mock_file_manager = $this->prophet->prophesize(FileManager::class);
        $mock_task_manager = $this->prophet->prophesize(TaskManager::class);
        $mock_url_resolver = $this->prophet->prophesize(UrlResolver::class);
        $mock_logger = $this->prophet->prophesize(LoggerInterface::class);
        $mock_entry_repo = $this->prophet->prophesize(EntryRepository::class);

        $mock_url_resolver
            ->generateRoute('blog', Argument::any(), [])
            ->willReturn($blog_url);

        $mock_entry_repo
            ->getLimit(Argument::any(), Argument::any())
            ->willReturn($data['entries'] ?? []);
        $mock_entry_repo
            ->getArticles(Argument::any())
            ->willReturn($data['articles'] ?? []);
        $mock_entry_repo
            ->getDrafts()
            ->willReturn($data['drafts'] ?? []);

        $blog = new Blog(
            "",
            $mock_fs->reveal(),
            $mock_file_manager->reveal(),
            $mock_task_manager->reveal(),
            $mock_url_resolver->reveal(),
            $mock_logger->reveal(),
            $mock_entry_repo->reveal()
        );

        $blog->home_path = '/tmp/foo';
        $blog->blogid = 'foo';
        $blog->name = 'Foo Blog';
        $blog->description = 'Some blog';
        $blog->owner = 'bob';

        SystemConfig::instance()->registerBlog('foo', new UrlPath('/tmp/foo', $blog_url));

        $user = new User('bob');
        $user->fullname = 'Bob Smith';
        $user->email = 'bob@smith.com';
        $this->user_repo->get('bob')->willReturn($user);

        return $blog;
    }

    private function getExportContent(array $entries = []) {
        $data = [
            '<?xml version="1.0" encoding="UTF-8"?>',
            "\n",
            '<rss xmlns:excerpt="http://wordpress.org/export/1.2/excerpt/" xmlns:content="http://purl.org/rss/1.0/modules/content/" xmlns:wfw="http://wellformedweb.org/CommentAPI/" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:wp="http://wordpress.org/export/1.2/" version="2.0">',
            '<channel>',
            '<title>Foo Blog</title>',
            '<link>https://fooblog.com/</link>',
            '<description>Some blog</description>',
            '<pubDate>Sat, 01 Jan 2000 12:13:14 +0000</pubDate>',
            '<language>en-US</language>',
            '<wp:wxr_version>1.2</wp:wxr_version>',
            '<wp:base_site_url>https://fooblog.com/</wp:base_site_url>',
            '<wp:base_blog_url>https://fooblog.com/</wp:base_blog_url>',
            '<wp:author>',
            '<wp:author_login>bob</wp:author_login>',
            '<wp:author_email>bob@smith.com</wp:author_email>',
            '<wp:author_display_name>Bob Smith</wp:author_display_name>',
            '</wp:author>',
            '<generator>https://lnblog.skepticats.com/?v='.PACKAGE_VERSION.'</generator>',
        ];

        $data = array_merge(
            $data,
            $entries,
            [
                '</channel>',
                '</rss>',
                "\n",
            ]
        );

        return implode('', $data);
    }

    private function getTestEntry(bool $include_comments = false, bool $include_pingbacks = false): array {
        $timestamp = strtotime('2000-01-01 12:13:14 GMT');
        $entry_url = 'https://fooblog.com/entries/2000/01/test1.php';
        $dir = '/tmp/foo/entries/2000/01/01_1213/';

        $test_comment = $include_comments ? $this->getTestComment() : [];
        $pingback_id = $test_comment ? 2 : 1;
        $test_pingback = $include_pingbacks ? $this->getTestPingback($pingback_id) : [];

        $entry = $this->createTestEntry(
            $dir,
            $entry_url,
            $timestamp,
            $test_comment,
            $test_pingback
        );
        $entry->subject = 'Test1';
        $entry->data = 'This is some <b>HTML</b>.';
        $entry->tags(['Foo', 'Bar']);
        $entry->has_html = MARKUP_HTML;
        $entry->uid = 'bob';
        $entry->permalink_name = 'test1.php';
        $entry->post_ts = $timestamp;
        $entry->allow_comment = true;
        $entry->allow_pingback = false;

        $entry_lines = [
            '<item>',
            '<title><![CDATA[Test1]]></title>',
            "<link>$entry_url</link>",
            "<pubDate>Sat, 01 Jan 2000 12:13:14 +0000</pubDate>",
            '<dc:creator>bob</dc:creator>',
            "<guid isPermalink=\"false\">$entry_url</guid>",
            '<content:encoded><![CDATA[This is some <b>HTML</b>.]]></content:encoded>',
            '<wp:post_date>2000-01-01 12:13:14</wp:post_date>',
            '<wp:post_date_gmt>2000-01-01 12:13:14</wp:post_date_gmt>',
            '<wp:comment_status>open</wp:comment_status>',
            '<wp:ping_status>closed</wp:ping_status>',
            '<wp:status>publish</wp:status>',
            '<wp:post_type>post</wp:post_type>',
            '<category domain="post_tag" nicename="foo"><![CDATA[Foo]]></category>',
            '<category domain="post_tag" nicename="bar"><![CDATA[Bar]]></category>',
        ];
        if ($include_comments) {
            $comment_lines = $test_comment[1];
            $entry_lines = array_merge($entry_lines, $comment_lines);
        }
        if ($include_pingbacks) {
            $pingback_lines = $test_pingback[1];
            $entry_lines = array_merge($entry_lines, $pingback_lines);
        }
        $entry_lines[] = '</item>';

        return [$entry, $entry_lines];
    }

    private function getTestArticle(): array {
        $timestamp = strtotime('2000-01-01 12:13:14 GMT');
        $entry_url = 'https://fooblog.com/content/test2/';
        $dir = '/tmp/foo/content/test2/';

        $entry = $this->createTestEntry($dir, $entry_url);
        $entry->subject = 'Test article 2&3';
        $entry->data = 'This is some <b>HTML</b>.';
        $entry->tags(['Foo & More', 'Bar']);
        $entry->has_html = MARKUP_NONE;
        $entry->uid = 'bob';
        $entry->permalink_name = 'test2';
        $entry->post_ts = $timestamp;
        $entry->allow_comment = false;
        $entry->allow_pingback = true;

        $entry_lines = [
            '<item>',
            '<title><![CDATA[Test article 2&3]]></title>',
            "<link>$entry_url</link>",
            "<pubDate>Sat, 01 Jan 2000 12:13:14 +0000</pubDate>",
            '<dc:creator>bob</dc:creator>',
            "<guid isPermalink=\"false\">$entry_url</guid>",
            '<content:encoded><![CDATA[<p>This is some &lt;b&gt;HTML&lt;/b&gt;.</p>]]></content:encoded>',
            '<wp:post_date>2000-01-01 12:13:14</wp:post_date>',
            '<wp:post_date_gmt>2000-01-01 12:13:14</wp:post_date_gmt>',
            '<wp:comment_status>closed</wp:comment_status>',
            '<wp:ping_status>open</wp:ping_status>',
            '<wp:status>publish</wp:status>',
            '<wp:post_type>page</wp:post_type>',
            '<category domain="post_tag" nicename="foo-more"><![CDATA[Foo & More]]></category>',
            '<category domain="post_tag" nicename="bar"><![CDATA[Bar]]></category>',
            '</item>',
        ];

        return [$entry, $entry_lines];
    }

    private function getTestDraft(): array {
        $timestamp = strtotime('2000-01-01 12:13:14 GMT');
        $entry_url = 'https://fooblog.com/drafts/01_1213/';
        $dir = '/tmp/foo/drafts/01_1213/';

        $entry = $this->createTestEntry($dir, $entry_url);
        $entry->subject = 'Test draft 4';
        $entry->data = 'This is some [b]HTML[/b].';
        $entry->tags(['FooBuzz', 'Bar']);
        $entry->has_html = MARKUP_BBCODE;
        $entry->uid = 'bob';
        $entry->permalink_name = 'test4';
        $entry->post_ts = $timestamp;
        $entry->allow_comment = false;
        $entry->allow_pingback = true;

        $entry_lines = [
            '<item>',
            '<title><![CDATA[Test draft 4]]></title>',
            "<link>$entry_url</link>",
            "<pubDate>Sat, 01 Jan 2000 12:13:14 +0000</pubDate>",
            '<dc:creator>bob</dc:creator>',
            "<guid isPermalink=\"false\">$entry_url</guid>",
            '<content:encoded><![CDATA[<p>This is some <strong>HTML</strong>.</p>]]></content:encoded>',
            '<wp:post_date>2000-01-01 12:13:14</wp:post_date>',
            '<wp:post_date_gmt>2000-01-01 12:13:14</wp:post_date_gmt>',
            '<wp:comment_status>closed</wp:comment_status>',
            '<wp:ping_status>open</wp:ping_status>',
            '<wp:status>draft</wp:status>',
            '<wp:post_type>post</wp:post_type>',
            '<category domain="post_tag" nicename="foobuzz"><![CDATA[FooBuzz]]></category>',
            '<category domain="post_tag" nicename="bar"><![CDATA[Bar]]></category>',
            '</item>',
        ];

        return [$entry, $entry_lines];
    }

    private function getTestComment(int $id = 1): array {
        $file = '/tmp/foo/entries/2000/01/01_1213/comments/2000-01-01_121314.xml';
        $timestamp = strtotime('2000-01-01 12:13:14 GMT');

        $mock_fs = $this->prophet->prophesize(FS::class);
        $comment = new BlogComment($file, $mock_fs->reveal());
        $comment->name = 'Jeff';
        $comment->email = 'jeff@jeff.com';
        $comment->url = 'http://jeff.com';
        $comment->data = 'This is a comment with &lt;b&gt;escaped&lt;/b&gt; HTML.';
        $comment->ip = '1.2.3.4';
        $comment->timestamp = $timestamp;

        $lines = [
            '<wp:comment>',
            '<wp:comment_id>'.$id.'</wp:comment_id>',
            '<wp:comment_author><![CDATA[Jeff]]></wp:comment_author>',
            '<wp:comment_author_email><![CDATA[jeff@jeff.com]]></wp:comment_author_email>',
            '<wp:comment_author_url><![CDATA[http://jeff.com]]></wp:comment_author_url>',
            '<wp:comment_author_IP><![CDATA[1.2.3.4]]></wp:comment_author_IP>',
            '<wp:comment_date>2000-01-01 12:13:14</wp:comment_date>',
            '<wp:comment_date_gmt>2000-01-01 12:13:14</wp:comment_date_gmt>',
            '<wp:comment_content><![CDATA[<p>This is a comment with &lt;b&gt;escaped&lt;/b&gt; HTML.</p>]]></wp:comment_content>',
            '<wp:comment_approved>1</wp:comment_approved>',
            '<wp:comment_type>comment</wp:comment_type>',
            '</wp:comment>',
        ];

        return [$comment, $lines];
    }

    private function getTestPingback(int $id = 1): array {
        $file = '/tmp/foo/entries/2000/01/01_1213/pingbacks/2000-01-01_121314.xml';
        $timestamp = strtotime('2000-01-01 12:13:14 GMT');

        $mock_fs = $this->prophet->prophesize(FS::class);
        $pingback = new Pingback($file, $mock_fs->reveal());
        $pingback->title = "Jeff's Page";
        $pingback->source = 'http://jeff.com';
        $pingback->excerpt = 'This is a comment with &lt;b&gt;escaped&lt;/b&gt; HTML.';
        $pingback->ip = '1.2.3.4';
        $pingback->timestamp = $timestamp;
        $lines = [
            '<wp:comment>',
            '<wp:comment_id>'.$id.'</wp:comment_id>',
            "<wp:comment_author><![CDATA[Jeff's Page]]></wp:comment_author>",
            '<wp:comment_author_email></wp:comment_author_email>',
            '<wp:comment_author_url><![CDATA[http://jeff.com]]></wp:comment_author_url>',
            '<wp:comment_author_IP><![CDATA[1.2.3.4]]></wp:comment_author_IP>',
            '<wp:comment_date>2000-01-01 12:13:14</wp:comment_date>',
            '<wp:comment_date_gmt>2000-01-01 12:13:14</wp:comment_date_gmt>',
            '<wp:comment_content><![CDATA[This is a comment with &lt;b&gt;escaped&lt;/b&gt; HTML.]]></wp:comment_content>',
            '<wp:comment_approved>1</wp:comment_approved>',
            '<wp:comment_type>pingback</wp:comment_type>',
            '</wp:comment>',
        ];

        return [$pingback, $lines];
    }

    private function createTestEntry(
        string $dir,
        string $entry_url,
        int $timestamp = null,
        array $test_comment = [],
        array $test_pingback = []
    ): BlogEntry {
        if ($timestamp === null) {
            $timestamp = strtotime('2000-01-01 12:13:14 GMT');
        }

        $mock_resolver = $this->prophet->prophesize(UrlResolver::class);
        $mock_resolver->generateRoute('page', Argument::any(), [])->willReturn($entry_url);

        $comment_array = [];
        $pingback_array = [];
        $mock_repo = $this->prophet->prophesize(ReplyRepository::class);
        if ($test_comment) {
            $comment_array[] = $test_comment[0];
        }
        if ($test_pingback) {
            $pingback_array[] = $test_pingback[0];
        }
        $mock_repo->getReplyArray(
            Argument::any(),
            'comments',
            Argument::any(),
            Argument::any(),
            Argument::any(),
            Argument::any()
        )->willReturn($comment_array);
        $mock_repo->getReplyArray(
            Argument::any(),
            'pingback',
            Argument::any(),
            Argument::any(),
            Argument::any(),
            Argument::any()
        )->willReturn($pingback_array);
        $mock_repo->getReplyArray(
            Argument::any(),
            'trackback',
            Argument::any(),
            Argument::any(),
            Argument::any(),
            Argument::any()
        )->willReturn([]);

        $mock_fs = $this->prophet->prophesize(FS::class);
        $mock_fs->is_dir($dir)->willReturn(true);
        $mock_fs->is_dir("{$dir}comments")->willReturn(true);
        $mock_fs->file_exists("{$dir}entry.xml")->willReturn(true);
        $mock_fs->read_file("{$dir}entry.xml")->willReturn('data');
        $mock_fs->filemtime("{$dir}entry.xml")->willReturn(12345);
        $mock_fs->realpath("{$dir}entry.xml")->willReturn("{$dir}entry.xml");
        $this->globals->time()->willReturn($timestamp);

        $entry = new BlogEntry(
            $dir,
            $mock_fs->reveal(),
            null,
            $mock_resolver->reveal(),
            $mock_repo->reveal()
        );

        return $entry;
    }

    private function prettyPrintXml(string $xml): string {
        $dom = new \DOMDocument('1.0');
        $dom->preserveWhiteSpace = true;
        $dom->formatOutput = true;
        $dom->loadXML($xml);
        $pretty_xml = $dom->saveXML();
        return $pretty_xml;
    }
}
