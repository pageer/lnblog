<?php

namespace LnBlog\Tests\Export;

use BadExport;
use BlogComment;
use FS;
use GlobalFunctions;
use InvalidOption;
use LnBlog\Export\ExportTarget;
use LnBlog\Export\BaseExporter;
use LnBlog\Export\Rss1Exporter;
use LnBlog\Storage\UserRepository;
use Path;
use Prophecy\Argument;
use UrlResolver;

class Rss1ExporterTest extends BaseExporterTest
{
    public function testExport_BlogWithNoEntries_Throws() {
        $this->setupTestUser();
        $blog = $this->getTestBlog([]);

        $this->expectException(BadExport::class);
        $this->expectExceptionMessage('No items found for feed');

        $target = new ExportTarget($this->fs->reveal());
        $target->setExportUrl('https://fooblog.com/whatever.rdf');
        $exporter = new Rss1Exporter($this->globals->reveal(), $this->user_repo->reveal());
        $exporter->export($blog, $target);
    }

    public function testExport_NoExportUrl_Throws() {
        $this->setupTestUser();
        $blog = $this->getTestBlog([]);

        $this->expectException(BadExport::class);
        $this->expectExceptionMessage('No destination URL set');

        $target = new ExportTarget($this->fs->reveal());
        $exporter = new Rss1Exporter($this->globals->reveal(), $this->user_repo->reveal());
        $exporter->export($blog, $target);
    }

    public function testExport_BlogWithEntries_MatchesXml() {
        $this->setupTestUser();
        list($entry, $entry_lines, $item_lines) = $this->getTestEntry();
        $blog = $this->getTestBlog(['entries' => [$entry]]);

        $target = new ExportTarget($this->fs->reveal());
        $target->setExportUrl('https://fooblog.com/whatever.rdf');
        $exporter = new Rss1Exporter($this->globals->reveal(), $this->user_repo->reveal());
        $exporter->export($blog, $target);

        $feed_lines = $this->getExportContent(
            $blog->name,
            $blog->getURL(),
            $blog->description,
            $item_lines,
            $entry_lines
        );
        $this->assertEquals(
            BaseExporter::prettyPrintXml($feed_lines),
            $target->getAsText()
        );
    }

    public function testExport_BlogWithOverrides_MatchesXml() {
        $this->setupTestUser();
        list($entry, $entry_lines, $item_lines) = $this->getTestEntry();
        $blog = $this->getTestBlog();

        $target = new ExportTarget($this->fs->reveal());
        $target->setExportUrl('https://fooblog.com/whatever.rdf');
        $exporter = new Rss1Exporter($this->globals->reveal(), $this->user_repo->reveal());
        $exporter->setExportOptions([
            Rss1Exporter::OPTION_CHILDREN => [$entry],
        ]);
        $exporter->setExportOptions(
            $exporter->getExportOptions() +
            [
                Rss1Exporter::OPTION_FEED_DESCRIPTION => 'A custom feed',
                Rss1Exporter::OPTION_FEED_PERMALINK => 'https://myfeed.com/',
                Rss1Exporter::OPTION_FEED_TITLE => 'Custom',
            ]
        );
        $exporter->export($blog, $target);

        $feed_lines = $this->getExportContent(
            'Custom',
            'https://myfeed.com/',
            'A custom feed',
            $item_lines,
            $entry_lines
        );
        $this->assertEquals(
            BaseExporter::prettyPrintXml($feed_lines),
            $target->getAsText()
        );
    }

    public function testExport_EntryWithNoComments_Throws() {
        $this->setupTestUser();

        $timestamp = strtotime('2000-01-01 12:13:14 GMT');
        $entry_url = 'https://fooblog.com/entries/2000/01/test1.php';
        $dir = '/tmp/foo/entries/2000/01/01_1213/';

        $entry = $this->createTestEntry(
            $dir,
            $entry_url,
            $timestamp
        );
        $entry->subject = 'Test1';
        $entry->data = 'This is some <b>HTML</b>.';
        $entry->has_html = MARKUP_HTML;
        $entry->uid = 'bob';

        $this->expectException(BadExport::class);
        $this->expectExceptionMessage('No items found for feed');

        $target = new ExportTarget($this->fs->reveal());
        $target->setExportUrl('https://fooblog.com/whatever.rdf');
        $exporter = new Rss1Exporter($this->globals->reveal(), $this->user_repo->reveal());
        $exporter->export($entry, $target);
    }

    public function testExport_EntryWithComments_MatchesXml() {
        $this->setupTestUser();

        $timestamp = strtotime('2000-01-01 12:13:14 GMT');
        $entry_url = 'https://fooblog.com/entries/2000/01/test1.php';
        $dir = '/tmp/foo/entries/2000/01/01_1213/';
        list($comment, $comment_lines, $item_lines) = $this->getTestComment($entry_url);

        $entry = $this->createTestEntry(
            $dir,
            $entry_url,
            $timestamp,
            [$comment]
        );
        $entry->subject = 'Test1';
        $entry->data = 'This is some <b>HTML</b>.';
        $entry->has_html = MARKUP_HTML;
        $entry->uid = 'bob';

        $this->setupTestUser();

        $target = new ExportTarget($this->fs->reveal());
        $target->setExportUrl('https://fooblog.com/whatever.rdf');
        $exporter = new Rss1Exporter($this->globals->reveal(), $this->user_repo->reveal());
        $exporter->export($entry, $target);

        $feed_lines = $this->getExportContent(
            $entry->subject,
            $entry->permalink(),
            $entry->markup(),
            $item_lines,
            $comment_lines
        );
        $this->assertEquals(
            BaseExporter::prettyPrintXml($feed_lines),
            $target->getAsText()
        );
    }

    public function testExport_EntryWithRegisteredUserComments_MatchesXml() {
        $this->setupTestUser();

        $timestamp = strtotime('2000-01-01 12:13:14 GMT');
        $entry_url = 'https://fooblog.com/entries/2000/01/test1.php';
        $dir = '/tmp/foo/entries/2000/01/01_1213/';
        list($comment, $comment_lines, $item_lines) = $this->getTestComment(
            $entry_url,
            'bob',
            'Bob Smith (mailto:bob@smith.com)'
        );

        $entry = $this->createTestEntry(
            $dir,
            $entry_url,
            $timestamp,
            [$comment]
        );
        $entry->subject = 'Test1';
        $entry->data = 'This is some <b>HTML</b>.';
        $entry->has_html = MARKUP_HTML;
        $entry->uid = 'bob';

        $this->setupTestUser();

        $target = new ExportTarget($this->fs->reveal());
        $target->setExportUrl('https://fooblog.com/whatever.rdf');
        $exporter = new Rss1Exporter($this->globals->reveal(), $this->user_repo->reveal());
        $exporter->export($entry, $target);

        $feed_lines = $this->getExportContent(
            $entry->subject,
            $entry->permalink(),
            $entry->markup(),
            $item_lines,
            $comment_lines
        );
        $this->assertEquals(
            BaseExporter::prettyPrintXml($feed_lines),
            $target->getAsText()
        );
    }

    public function testExport_WithCommentAsChannel_Throws() {
        $this->setupTestUser();
        $entry_url = 'https://fooblog.com/entries/2000/01/test1.php';
        list($comment, $comment_lines, $item_lines) = $this->getTestComment($entry_url, 'bob', '');

        $this->expectException(BadExport::class);
        $this->expectExceptionMessage('Could not get default content');

        $target = new ExportTarget($this->fs->reveal());
        $target->setExportUrl('https://fooblog.com/whatever.rdf');
        $exporter = new Rss1Exporter($this->globals->reveal(), $this->user_repo->reveal());
        $exporter->export($comment, $target);
    }

    public function testSetExportOptions_WithBadOption_Throws() {
        $this->expectException(InvalidOption::class);
        $this->expectExceptionMessage('Option "random-option" is not supported');

        $exporter = new Rss1Exporter($this->globals->reveal(), $this->user_repo->reveal());
        $exporter->setExportOptions(['random-option' => 'foo']);
    }

    public function testSetExportOptions_WithNonArrayChildren_Throws() {
        $this->expectException(InvalidOption::class);
        $this->expectExceptionMessage('Option "children" must be an array');

        $exporter = new Rss1Exporter($this->globals->reveal(), $this->user_repo->reveal());
        $exporter->setExportOptions([Rss1Exporter::OPTION_CHILDREN => 'random-string']);
    }

    protected function setUp(): void {
        parent::setUp();
        $this->fs = $this->prophet->prophesize(FS::class);
        $this->globals = $this->prophet->prophesize(GlobalFunctions::class);
        $this->user_repo = $this->prophet->prophesize(UserRepository::class);
        $timestamp = strtotime('2000-01-01 12:13:14 GMT');
        $this->globals->time()->willReturn($timestamp);
    }

    private function getExportContent(
        string $title,
        string $permalink,
        string $description,
        array $entry_urls,
        array $entries
    ) {
        $data = array_merge(
            [
                '<?xml version="1.0" encoding="UTF-8"?>',
                "\n",
                '<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:dc="http://dublincore.org/documents/dcmi-namespace/" xmlns="http://purl.org/rss/1.0/">',
                '<channel rdf:about="https://fooblog.com/whatever.rdf">',
                "<title><![CDATA[$title]]></title>",
                "<link>$permalink</link>",
                "<description><![CDATA[$description]]></description>",
                '<dc:creator>Bob Smith (mailto:bob@smith.com)</dc:creator>',
                '<dc:date>2000-01-01T12:13:14+00:00</dc:date>',
                '<items>',
                '<rdf:Seq>',
            ],
            $entry_urls,
            [
                '</rdf:Seq>',
                '</items>',
                '</channel>',
            ],
            $entries,
            [
                '</rdf:RDF>',
                "\n",
            ]
        );

        return implode('', $data);
    }

    private function getTestEntry(): array {
        $timestamp = strtotime('2000-01-01 12:13:14 GMT');
        $entry_url = 'https://fooblog.com/entries/2000/01/test1.php';
        $dir = '/tmp/foo/entries/2000/01/01_1213/';

        $entry = $this->createTestEntry(
            $dir,
            $entry_url,
            $timestamp
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
            "<item rdf:about=\"$entry_url\">",
            '<title><![CDATA[Test1]]></title>',
            "<link>$entry_url</link>",
            "<description><![CDATA[This is some <b>HTML</b>.]]></description>",
            '<dc:creator>Bob Smith (mailto:bob@smith.com)</dc:creator>',
            '</item>',
        ];

        $item_lines = [
            '<rdf:li resource="' . $entry->permalink() . '"/>'
        ];

        return [$entry, $entry_lines, $item_lines];
    }

    private function getTestComment(string $entry_url, string $user = '' , string $user_string = ''): array {
        $entry_url .= '#comment2000-01-01_121314';
        $file = '/tmp/foo/entries/2000/01/01_1213/comments/2000-01-01_121314.xml';
        $timestamp = strtotime('2000-01-01 12:13:14 GMT');

        $mock_fs = $this->prophet->prophesize(FS::class);
        $mock_resolver = $this->prophet->prophesize(UrlResolver::class);
        $mock_resolver->generateRoute('permalink', Argument::any(), [])->willReturn($entry_url);

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

        $user_string = $user_string ?: 'Jeff (mailto:jeff@jeff.com)';
        $entry_lines = [
            "<item rdf:about=\"$entry_url\">",
            '<title><![CDATA[Test comment]]></title>',
            "<link>$entry_url</link>",
            "<description><![CDATA[<p>This is a comment.</p>]]></description>",
            "<dc:creator>$user_string</dc:creator>",
            '</item>',
        ];

        $item_lines = [
            '<rdf:li resource="' . $entry_url . '"/>'
        ];

        return [$comment, $entry_lines, $item_lines];
    }
}
