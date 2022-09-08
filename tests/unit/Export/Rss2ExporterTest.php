<?php

namespace LnBlog\Tests\Export;

use BlogComment;
use FS;
use GlobalFunctions;
use LnBlog\Export\ExportTarget;
use LnBlog\Export\BaseExporter;
use LnBlog\Export\Rss2Exporter;
use LnBlog\Storage\UserRepository;
use Path;
use Prophecy\Argument;
use UrlResolver;

class Rss2ExporterTest extends BaseExporterTest
{
    public function testExport_BlogWithNoEntries_NoItems() {
        $blog = $this->getTestBlog();
        $this->globals->time()->willReturn(strtotime('2000-01-01 12:13:14 GMT'));
        $this->globals->constant('LANGUAGE')->willReturn('en_US');

        $target = new ExportTarget($this->fs->reveal());
        $exporter = new Rss2Exporter($this->globals->reveal(), $this->user_repo->reveal());
        $exporter->setExportOptions(['children' => []]);
        $exporter->export($blog, $target);

        $xml = $this->getExportContent($blog->name, 'https://fooblog.com/', $blog->description, []);
        $this->assertEquals(
            BaseExporter::prettyPrintXml($xml),
            $target->getAsText()
        );
    }

    public function testExport_BlogWithEntries_MatchesXml() {
        $this->setupTestUser();
        list($entry, $entry_lines) = $this->getTestEntry();
        $blog = $this->getTestBlog(['entries' => [$entry]]);
        $this->globals->time()->willReturn(strtotime('2000-01-01 12:13:14 GMT'));
        $this->globals->constant('LANGUAGE')->willReturn('en_US');

        $target = new ExportTarget($this->fs->reveal());
        $exporter = new Rss2Exporter($this->globals->reveal(), $this->user_repo->reveal());
        $exporter->export($blog, $target);

        $xml = $this->getExportContent($blog->name, 'https://fooblog.com/', $blog->description, $entry_lines);
        $this->assertEquals(
            BaseExporter::prettyPrintXml($xml),
            $target->getAsText()
        );
    }

    public function testExport_BlogWithEnclosureInEntry_MatchesXml() {
        $this->setupTestUser();
        list($entry, $entry_lines) = $this->getTestEntry([
            'url' => 'http://foo.com/test.mp3', 'size' => 12345, 'type' => 'audio/mp3'
        ]);
        $blog = $this->getTestBlog(['entries' => [$entry]]);
        $this->globals->time()->willReturn(strtotime('2000-01-01 12:13:14 GMT'));
        $this->globals->constant('LANGUAGE')->willReturn('en_US');

        $target = new ExportTarget($this->fs->reveal());
        $exporter = new Rss2Exporter($this->globals->reveal(), $this->user_repo->reveal());
        $exporter->export($blog, $target);

        $xml = $this->getExportContent($blog->name, 'https://fooblog.com/', $blog->description, $entry_lines);
        $this->assertEquals(
            BaseExporter::prettyPrintXml($xml),
            $target->getAsText()
        );
    }

    public function testExport_EntryWithNoComments_NoItems() {
        $this->setupTestUser();
        list($entry, $entry_lines) = $this->getTestEntry();
        $this->globals->time()->willReturn(strtotime('2000-01-01 12:13:14 GMT'));
        $this->globals->constant('LANGUAGE')->willReturn('en_US');

        $target = new ExportTarget($this->fs->reveal());
        $exporter = new Rss2Exporter($this->globals->reveal(), $this->user_repo->reveal());
        $exporter->export($entry, $target);

        $xml = $this->getExportContent($entry->subject, $entry->permalink(), $entry->markup(), []);
        $this->assertEquals(
            BaseExporter::prettyPrintXml($xml),
            $target->getAsText()
        );
    }

    public function testExport_EntryWithComments_MatchesXml() {
        $this->setupTestUser();
        $timestamp = strtotime('2000-01-01 12:13:14 GMT');
        $entry_url = 'https://fooblog.com/entries/2000/01/test1.php';
        $comment_url = 'https://fooblog.com/entries/2000/01/01_1213/comments/';
        $dir = '/tmp/foo/entries/2000/01/01_1213/';
        list($comment, $comment_lines) = $this->getTestComment($entry_url, 'bob', 'bob@smith.com (Bob Smith)');

        $entry = $this->createTestEntry(
            $dir,
            $entry_url,
            $timestamp,
            [$comment]
        );
        $entry->subject = 'Test1';
        $entry->data = 'This is some <b>HTML</b>.';
        $entry->tags(['Foo', 'Bar']);
        $entry->has_html = MARKUP_HTML;
        $entry->uid = 'bob';
        $entry->permalink_name = 'test1.php';

        $blog = $this->getTestBlog();
        $this->globals->time()->willReturn(strtotime('2000-01-01 12:13:14 GMT'));
        $this->globals->constant('LANGUAGE')->willReturn('en_US');

        $target = new ExportTarget($this->fs->reveal());
        $exporter = new Rss2Exporter($this->globals->reveal(), $this->user_repo->reveal());
        $exporter->export($entry, $target);

        $xml = $this->getExportContent($entry->subject, $entry->permalink(), $entry->markup(), $comment_lines);
        $this->assertEquals(
            BaseExporter::prettyPrintXml($xml),
            $target->getAsText()
        );
    }

    public function testExport_EntryWithCommentsNoUser_MatchesXml() {
        $this->setupTestUser();
        $timestamp = strtotime('2000-01-01 12:13:14 GMT');
        $entry_url = 'https://fooblog.com/entries/2000/01/test1.php';
        $comment_url = 'https://fooblog.com/entries/2000/01/01_1213/comments/';
        $dir = '/tmp/foo/entries/2000/01/01_1213/';
        list($comment, $comment_lines) = $this->getTestComment($entry_url, '', '');

        $entry = $this->createTestEntry(
            $dir,
            $entry_url,
            $timestamp,
            [$comment]
        );
        $entry->subject = 'Test1';
        $entry->data = 'This is some <b>HTML</b>.';
        $entry->tags(['Foo', 'Bar']);
        $entry->has_html = MARKUP_HTML;
        $entry->permalink_name = 'test1.php';
        $entry->uid = 'bob';

        $blog = $this->getTestBlog();
        $this->globals->time()->willReturn(strtotime('2000-01-01 12:13:14 GMT'));
        $this->globals->constant('LANGUAGE')->willReturn('en_US');

        $target = new ExportTarget($this->fs->reveal());
        $exporter = new Rss2Exporter($this->globals->reveal(), $this->user_repo->reveal());
        $exporter->export($entry, $target);

        $xml = $this->getExportContent($entry->subject, $entry->permalink(), $entry->markup(), $comment_lines);
        $this->assertEquals(
            BaseExporter::prettyPrintXml($xml),
            $target->getAsText()
        );
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

    private function getExportContent(
        string $title,
        string $permalink,
        string $description,
        array $entries
    ) {
        $data = [
            '<?xml version="1.0"?>',
            "\n",
            '<rss version="2.0">',
            '<channel>',
            "<title><![CDATA[$title]]></title>",
            "<link>$permalink</link>",
            "<description><![CDATA[$description]]></description>",
            '<lastBuildDate>2000-01-01T12:13:14+00:00</lastBuildDate>',
            '<managingEditor>bob@smith.com (Bob Smith)</managingEditor>',
            '<language>en-US</language>',
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

    private function getTestEntry(array $enclosure_data = []): array {
        $timestamp = strtotime('2000-01-01 12:13:14 GMT');
        $entry_url = 'https://fooblog.com/entries/2000/01/test1.php';
        $comment_url = 'https://fooblog.com/entries/2000/01/01_1213/comments/';
        $dir = '/tmp/foo/entries/2000/01/01_1213/';

        $entry = $this->createTestEntry(
            $dir,
            $entry_url,
            $timestamp,
            [],
            [],
            $enclosure_data
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

        $enclosure_line = '';
        if ($enclosure_data) {
            $entry->enclosure = $enclosure_data['url'];
            $enclosure_line = sprintf(
                '<enclosure url="%s" length="%s" type="%s"/>',
                $enclosure_data['url'],
                $enclosure_data['size'],
                $enclosure_data['type']
            );
        }
        $entry_lines = [
            '<item>',
            '<title><![CDATA[Test1]]></title>',
            "<link>$entry_url</link>",
            '<description><![CDATA[This is some <b>HTML</b>.]]></description>',
            '<author><![CDATA[bob@smith.com (Bob Smith)]]></author>',
            "<pubDate>Sat, 01 Jan 2000 12:13:14 +0000</pubDate>",
            '<category><![CDATA[Foo]]></category>',
            '<category><![CDATA[Bar]]></category>',
            $enclosure_line,
            "<guid isPermalink=\"true\">$entry_url</guid>",
            "<comments>$comment_url</comments>",
            '</item>',
        ];

        return [$entry, $entry_lines];
    }

    private function getTestComment(string $entry_url, string $user = '' , string $user_string = ''): array {
        $url = $entry_url . '#comment2000-01-01_121314';
        $file = '/tmp/foo/entries/2000/01/01_1213/comments/2000-01-01_121314.xml';
        $timestamp = strtotime('2000-01-01 12:13:14 GMT');

        $mock_fs = $this->prophet->prophesize(FS::class);
        $mock_resolver = $this->prophet->prophesize(UrlResolver::class);
        $mock_resolver->generateRoute('permalink', Argument::any(), [])->willReturn($url);

        $comment = new BlogComment($file, $mock_fs->reveal(), $mock_resolver->reveal());
        $comment->name = 'Jeff';
        $comment->email = 'jeff@jeff.com';
        $comment->url = 'http://jeff.com';
        $comment->subject = 'Test comment';
        $comment->data = 'This is a comment.';
        $comment->ip = '1.2.3.4';
        $comment->timestamp = $timestamp;
        if ($user) {
            $comment->uid = $user;
        }

        $user_string = $user_string ?: 'jeff@jeff.com (Jeff)';
        $user_line = "<author><![CDATA[$user_string]]></author>";
        $lines = [
            '<item>',
            '<title><![CDATA[Test comment]]></title>',
            "<link>$url</link>",
            '<description><![CDATA[<p>This is a comment.</p>]]></description>',
            $user_line,
            "<pubDate>Sat, 01 Jan 2000 12:13:14 +0000</pubDate>",
            "<guid isPermalink=\"true\">$url</guid>",
            '</item>',
        ];

        return [$comment, $lines];
    }
}
