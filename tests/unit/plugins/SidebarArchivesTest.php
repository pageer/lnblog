<?php

use LnBlog\Tests\LnBlogBaseTestCase;

require_once __DIR__ . "/../../../plugins/sidebar_archives.php";

class SidebarArchivesTest extends LnBlogBaseTestCase
{
    public function testOutput_HasArchives_ShowsList(): void {
        $blog = $this->prophet->prophesize(Blog::class);
        $blog->isBlog()->willReturn(true);
        $blog->uri('archives')->willReturn('https://foo.com/archiveslink/');
        $blog->uri('listall')->willReturn('https://foo.com/listalllink/');
        $blog->getRecentMonthList(3)->willReturn([
            ['year' => 2023, 'month' => 6, 'link' => 'https://foo.com/entries/2023/06/'],
            ['year' => 2023, 'month' => 5, 'link' => 'https://foo.com/entries/2023/05/'],
            ['year' => 2023, 'month' => 4, 'link' => 'https://foo.com/entries/2023/04/'],
        ]);
        $page = $this->prophet->prophesize(Page::class);
        $page->getBlog()->willReturn($blog->reveal());

        $plugin = new Archives();
        $plugin->title = 'My Blog Archives';
        $plugin->all_link = 'My Old Posts';
        $plugin->max_months = 3;

        ob_start();
        $plugin->output($page->reveal());
        $output = ob_get_clean();

        $this->assertStringContainsString("My Blog Archives", $output);
        $this->assertStringContainsString("My Old Posts", $output);
        $this->assertStringContainsString("June 2023", $output);
        $this->assertStringContainsString("May 2023", $output);
        $this->assertStringContainsString("April 2023", $output);
        $this->assertStringContainsString("archiveslink", $output);
        $this->assertStringContainsString("listalllink", $output);
    }

    public function testOutput_EmptyLinkText_NoLink(): void {
        $blog = $this->prophet->prophesize(Blog::class);
        $blog->isBlog()->willReturn(true);
        $blog->uri('archives')->willReturn('archivelink');
        $blog->uri('listall')->willReturn('listalllink');
        $blog->getRecentMonthList(3)->willReturn([
            ['year' => 2023, 'month' => 6, 'link' => 'https://foo.com/entries/2023/06/'],
            ['year' => 2023, 'month' => 5, 'link' => 'https://foo.com/entries/2023/05/'],
            ['year' => 2023, 'month' => 4, 'link' => 'https://foo.com/entries/2023/04/'],
        ]);
        $page = $this->prophet->prophesize(Page::class);
        $page->getBlog()->willReturn($blog->reveal());

        $plugin = new Archives();
        $plugin->title = '';
        $plugin->all_link = '';
        $plugin->max_months = 3;

        ob_start();
        $plugin->output($page->reveal());
        $output = ob_get_clean();

        $this->assertStringNotContainsString("archivelink", $output);
        $this->assertStringNotContainsString("listalllink", $output);
        $this->assertStringContainsString("June 2023", $output);
        $this->assertStringContainsString("May 2023", $output);
        $this->assertStringContainsString("April 2023", $output);
    }

    public function testOutput_FewerMonthsThanLimit_OutputsAllMonths(): void {
        $blog = $this->prophet->prophesize(Blog::class);
        $blog->isBlog()->willReturn(true);
        $blog->uri('archives')->willReturn('archivelink');
        $blog->uri('listall')->willReturn('listalllink');
        $blog->getRecentMonthList(6)->willReturn([
            ['year' => 2023, 'month' => 6, 'link' => 'https://foo.com/entries/2023/06/'],
        ]);
        $page = $this->prophet->prophesize(Page::class);
        $page->getBlog()->willReturn($blog->reveal());

        $plugin = new Archives();
        $plugin->title = '';
        $plugin->all_link = '';
        $plugin->max_months = 6;

        ob_start();
        $plugin->output($page->reveal());
        $output = ob_get_clean();

        $this->assertStringContainsString("June 2023", $output);
        $this->assertEquals(1, substr_count($output, "a href"));
    }

    public function testOutput_NoArchives_NoOutput(): void {
        $blog = $this->prophet->prophesize(Blog::class);
        $blog->isBlog()->willReturn(true);
        $blog->getRecentMonthList(6)->willReturn([]);
        $page = $this->prophet->prophesize(Page::class);
        $page->getBlog()->willReturn($blog->reveal());

        $plugin = new Archives();
        $plugin->max_months = 6;

        ob_start();
        $plugin->output($page->reveal());
        $output = ob_get_clean();

        $this->assertEmpty($output);
    }

    public function testOutput_NoBlog_NoOutput(): void {
        $page = $this->prophet->prophesize(Page::class);
        $page->getBlog()->willReturn(null);

        $plugin = new Archives();

        ob_start();
        $plugin->output($page->reveal());
        $output = ob_get_clean();

        $this->assertEmpty($output);
    }

    public function testOutput_NoPage_NoOutput(): void {
        $plugin = new Archives();

        ob_start();
        $plugin->output('test');
        $output = ob_get_clean();

        $this->assertEmpty($output);
    }
}
