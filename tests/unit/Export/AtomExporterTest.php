<?php

namespace LnBlog\Tests\Export;

use BadExport;
use BlogComment;
use FS;
use GlobalFunctions;
use LnBlog\Export\BaseExporter;
use LnBlog\Export\AtomExporter;
use LnBlog\Export\ExportTarget;
use LnBlog\Storage\UserRepository;
use Prophecy\Argument;
use UrlResolver;

class AtomExporterTest extends BaseExporterTest
{
    public function testExport_NoFeedUrl_Throws() {
        $blog = $this->getTestBlog();

        $this->expectException(BadExport::class);

        $target = new ExportTarget($this->fs->reveal());
        $exporter = new AtomExporter($this->globals->reveal(), $this->user_repo->reveal());
        $exporter->export($blog, $target);
    }

    public function testExport_BlogWithNoEntries_NoItems() {
        $feedlink = 'https://fooblog.com/atom.xml';
        $blog = $this->getTestBlog();
        $this->setupTestUser();
        $this->globals->time()->willReturn(strtotime('2000-01-01 12:13:14 GMT'));
        $this->globals->constant('LANGUAGE')->willReturn('en_US');

        $target = new ExportTarget($this->fs->reveal());
        $target->setExportUrl($feedlink);
        $exporter = new AtomExporter($this->globals->reveal(), $this->user_repo->reveal());
        $exporter->setExportOptions(['children' => []]);
        $exporter->export($blog, $target);

        $xml = $this->getExportContent($blog->name, 'https://fooblog.com/', $feedlink, $blog->description, []);
        $this->assertEquals(
            BaseExporter::prettyPrintXml($xml),
            $target->getAsText()
        );
    }

    public function testExport_BlogWithEntries_MatchesXml() {
        $feedlink = 'https://fooblog.com/atom.xml';
        list($entry, $entry_lines) = $this->getTestEntry();
        $blog = $this->getTestBlog(['entries' => [$entry]]);
        $this->setupTestUser();
        $this->globals->time()->willReturn(strtotime('2000-01-01 12:13:14 GMT'));
        $this->globals->constant('LANGUAGE')->willReturn('en_US');

        $target = new ExportTarget($this->fs->reveal());
        $target->setExportUrl($feedlink);
        $exporter = new AtomExporter($this->globals->reveal(), $this->user_repo->reveal());
        $exporter->export($blog, $target);

        $xml = $this->getExportContent($blog->name, 'https://fooblog.com/', $feedlink, $blog->description, $entry_lines);

        $this->assertEquals(
            BaseExporter::prettyPrintXml($xml),
            $target->getAsText()
        );
    }

    public function testExport_BlogWithEnclosureInEntry_MatchesXml() {
        $feedlink = 'https://fooblog.com/atom.xml';
        list($entry, $entry_lines) = $this->getTestEntry([
            'url' => 'http://foo.com/test.mp3', 'size' => 12345, 'type' => 'audio/mp3'
        ]);
        $blog = $this->getTestBlog(['entries' => [$entry]]);
        $this->setupTestUser();
        $this->globals->time()->willReturn(strtotime('2000-01-01 12:13:14 GMT'));
        $this->globals->constant('LANGUAGE')->willReturn('en_US');

        $target = new ExportTarget($this->fs->reveal());
        $target->setExportUrl($feedlink);
        $exporter = new AtomExporter($this->globals->reveal(), $this->user_repo->reveal());
        $exporter->export($blog, $target);

        $xml = $this->getExportContent($blog->name, 'https://fooblog.com/', $feedlink, $blog->description, $entry_lines);
        $this->assertEquals(
            BaseExporter::prettyPrintXml($xml),
            $target->getAsText()
        );
    }

    public function testExport_EntryWithNoComments_NoItems() {
        $feedlink = 'https://fooblog.com/atom.xml';
        list($entry, $entry_lines) = $this->getTestEntry();
        $blog = $this->getTestBlog();
        $this->setupTestUser();
        $this->globals->time()->willReturn(strtotime('2000-01-01 12:13:14 GMT'));
        $this->globals->constant('LANGUAGE')->willReturn('en_US');

        $target = new ExportTarget($this->fs->reveal());
        $target->setExportUrl($feedlink);
        $exporter = new AtomExporter($this->globals->reveal(), $this->user_repo->reveal());
        $exporter->export($entry, $target);

        $xml = $this->getExportContent($entry->subject, $entry->permalink(), $feedlink, $entry->markup(), []);
        $this->assertEquals(
            BaseExporter::prettyPrintXml($xml),
            $target->getAsText()
        );
    }

    public function testExport_EntryWithComments_MatchesXml() {
        $feedlink = 'https://fooblog.com/entries/2000/01/02_34/comments_atom.xml';
        $timestamp = strtotime('2000-01-01 12:13:14 GMT');
        $entry_url = 'https://fooblog.com/entries/2000/01/test1.php';
        $comment_url = 'https://fooblog.com/entries/2000/01/01_1213/comments/';
        $dir = '/tmp/foo/entries/2000/01/01_1213/';
        $user_data = ['name' => 'Jeff', 'email' => 'jeff@jeff.com', 'uri' => 'http://jeff.com'];
        list($comment, $comment_lines) = $this->getTestComment($entry_url, $user_data);

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

        $this->setupTestUser();
        $this->globals->time()->willReturn(strtotime('2000-01-01 12:13:14 GMT'));
        $this->globals->constant('LANGUAGE')->willReturn('en_US');

        $target = new ExportTarget($this->fs->reveal());
        $target->setExportUrl($feedlink);
        $exporter = new AtomExporter($this->globals->reveal(), $this->user_repo->reveal());
        $exporter->export($entry, $target);

        $xml = $this->getExportContent($entry->subject, $entry->permalink(), $feedlink, $entry->markup(), $comment_lines);
        $this->assertEquals(
            BaseExporter::prettyPrintXml($xml),
            $target->getAsText()
        );
    }

    public function testExport_EntryWithCommentsWithUser_MatchesXml() {
        $feedlink = 'https://fooblog.com/entries/2000/01/02_34/comments_atom.xml';
        $timestamp = strtotime('2000-01-01 12:13:14 GMT');
        $entry_url = 'https://fooblog.com/entries/2000/01/test1.php';
        $comment_url = 'https://fooblog.com/entries/2000/01/01_1213/comments/';
        $dir = '/tmp/foo/entries/2000/01/01_1213/';
        $user_data = ['user' => 'bob', 'name' => 'Bob Smith', 'email' => 'bob@smith.com', 'uri' => 'https://smith.com/'];
        list($comment, $comment_lines) = $this->getTestComment($entry_url, $user_data);

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

        $this->setupTestUser();
        $this->globals->time()->willReturn(strtotime('2000-01-01 12:13:14 GMT'));
        $this->globals->constant('LANGUAGE')->willReturn('en_US');

        $target = new ExportTarget($this->fs->reveal());
        $target->setExportUrl($feedlink);
        $exporter = new AtomExporter($this->globals->reveal(), $this->user_repo->reveal());
        $exporter->export($entry, $target);

        $xml = $this->getExportContent($entry->subject, $entry->permalink(), $feedlink, $entry->markup(), $comment_lines);
        $this->assertEquals(
            BaseExporter::prettyPrintXml($xml),
            $target->getAsText()
        );
    }

    protected function setUp(): void {
        parent::setUp();
        $this->fs = $this->prophet->prophesize(FS::class);
        $this->globals = $this->prophet->prophesize(GlobalFunctions::class);
        $this->user_repo = $this->prophet->prophesize(UserRepository::class);
    }

    private function getExportContent(
        string $title,
        string $permalink,
        string $feedlink,
        string $description,
        array $entries
    ) {
        $title = htmlspecialchars($title);
        $description = htmlspecialchars($description);
        $data = [
            '<?xml version="1.0"?>',
            "\n",
            '<feed xmlns="http://www.w3.org/2005/Atom">',
            "<title type=\"html\"><![CDATA[$title]]></title>",
            "<subtitle type=\"html\"><![CDATA[$description]]></subtitle>",
            "<link rel=\"self\" href=\"$feedlink\"/>",
            "<link rel=\"alternate\" href=\"$permalink\"/>",
            "<updated>2000-01-01T12:13:14+00:00</updated>",
            "<author>",
            "<name>Bob Smith</name>",
            "<email>bob@smith.com</email>",
            "<uri>https://smith.com/</uri>",
            "</author>",
            "<id>$permalink</id>",
            '<generator uri="https://lnblog.skepticats.com/" version="'.PACKAGE_VERSION.'">LnBlog</generator>',
        ];

        $data = array_merge(
            $data,
            $entries,
            [
                '</feed>',
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
        $entry->timestamp = $timestamp;
        $entry->allow_comment = true;
        $entry->allow_pingback = false;

        $enclosure_line = '';
        if ($enclosure_data) {
            $entry->enclosure = $enclosure_data['url'];
            $size_part = '';
            if (!empty($enclosure_data['size'])) {
                $size_part = sprintf(' length="%s"', $enclosure_data['size']);
            }
            $enclosure_line = sprintf(
                '<link rel="enclosure" href="%s" type="%s"%s/>',
                $enclosure_data['url'],
                $enclosure_data['type'],
                $size_part,
            );
        }
        $entry_lines = [
            '<entry>',
            "<id>$entry_url</id>",
            '<title><![CDATA[Test1]]></title>',
            "<updated>2000-01-01T12:13:14+00:00</updated>",
            "<author>",
            "<name>Bob Smith</name>",
            "<email>bob@smith.com</email>",
            "<uri>https://smith.com/</uri>",
            "</author>",
            '<content type="html"><![CDATA[This is some &lt;b&gt;HTML&lt;/b&gt;.]]></content>',
            "<link rel=\"alternate\" href=\"$entry_url\"/>",
            $enclosure_line,
            '<category term="Foo"/>',
            '<category term="Bar"/>',
            "<published>2000-01-01T12:13:14+00:00</published>",
            '</entry>',
        ];

        return [$entry, $entry_lines];
    }

    private function getTestComment(string $entry_url, array $user_data = []): array {
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
        if (!empty($user_data['user'])) {
            $comment->uid = $user_data['user'];
        }

        $author_section = [];
        $user_name = $user_data['name'] ?? '';
        if ($user_name) {
            $author_section[] = "<name>$user_name</name>";
        }
        $user_email = $user_data['email'] ?? '';
        if ($user_email) {
            $author_section[] = "<email>$user_email</email>";
        }
        $user_url = $user_data['uri'] ?? '';
        if ($user_url) {
            $author_section[] = "<uri>$user_url</uri>";
        }
        if (!empty($author_section)) {
            $author_section = array_merge(
                ['<author>'],
                $author_section,
                ['</author>']
            );
        }


        $lines = array_merge(
            [
                '<entry>',
                "<id>$url</id>",
                '<title><![CDATA[Test comment]]></title>',
                "<updated>2000-01-01T12:13:14+00:00</updated>",
            ],
            $author_section,
            [
                '<content type="html"><![CDATA[&lt;p&gt;This is a comment.&lt;/p&gt;]]></content>',
                "<link rel=\"alternate\" href=\"$url\"/>",
                "<published>2000-01-01T12:13:14+00:00</published>",
                '</entry>',
            ]
        );

        return [$comment, $lines];
    }
}
