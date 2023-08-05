<?php

namespace LnBlog\Tests\Plugins;

require_once __DIR__ . '/../../../plugins/trackbackrdf.php';

use BlogEntry;
use TrackbackRDF;
use LnBlog\Tests\LnBlogBaseTestCase;

class TrackbackRdfTest extends LnBlogBaseTestCase
{
    public function testAddRdf_TrackbacksAllowed_OutputsMarkup(): void {
        $entry_url = 'https://foo.com/asdf';
        $tb_url = 'https://foo.com/asdf/tb';
        $subject = 'My Entry';

        $entry = $this->prophet->prophesize(BlogEntry::class);
        $entry->permalink()->willReturn($entry_url);
        $entry->uri('trackback')->willReturn($tb_url);
        $entry->subject = $subject;
        $entry->allow_tb = true;

        $tb = new TrackbackRDF();
        ob_start();
        $tb->addRdf($entry->reveal());
        $content = ob_get_clean();

        $this->assertStringContainsString("rdf:about=\"$entry_url\"", $content);
        $this->assertStringContainsString("dc:identifier=\"$entry_url\"", $content);
        $this->assertStringContainsString("trackback:ping=\"$tb_url\"", $content);
        $this->assertStringContainsString("dc:title=\"$subject\"", $content);
    }

    public function testAddRdf_TrackbacksNotAllowed_NoOutput(): void {
        $entry = new BlogEntry();
        $entry->allow_tb = false;

        $tb = new TrackbackRDF();
        ob_start();
        $tb->addRdf($entry);
        $content = ob_get_clean();

        $this->assertEmpty($content);
    }
}
