<?php

namespace LnBlog\Tests\Plugins;

require_once __DIR__ . '/../../../plugins/banner_pageheader.php';

use Blog;
use PageHeader;
use LnBlog\Tests\LnBlogBaseTestCase;

class PageHeaderTest extends LnBlogBaseTestCase
{
    private $blog;

    public function testOutput_ShowDescription_PrintNameAndDescription(): void {
        $this->blog->isBlog()->willReturn(true);
        $this->blog->uri('blog')->willReturn('https://foo.com/');
        $this->blog->name = 'My Blog';
        $this->blog->description = 'This is a place';

        $plugin = new PageHeader($this->blog->reveal());
        $plugin->show_desc = true;
        ob_start();
        $plugin->output();
        $output = ob_get_clean();

        $this->assertStringContainsString('My Blog', $output);
        $this->assertStringContainsString('https://foo.com/', $output);
        // Two instances - one in the link title, one in a visible tag.
        $this->assertEquals(2, substr_count($output, 'This is a place', 0));
    }

    public function testOutput_NoShowDescription_PrintNameOnly(): void {
        $this->blog->isBlog()->willReturn(true);
        $this->blog->uri('blog')->willReturn('https://foo.com/');
        $this->blog->name = 'My Blog';
        $this->blog->description = 'This is a place';

        $plugin = new PageHeader($this->blog->reveal());
        $plugin->show_desc = false;
        ob_start();
        $plugin->output();
        $output = ob_get_clean();

        $this->assertStringContainsString('My Blog', $output);
        $this->assertStringContainsString('https://foo.com/', $output);
        // Only the instance in the link title
        $this->assertEquals(1, substr_count($output, 'This is a place', 0));
    }

    public function testOutput_NotBlog_ShowsPackageInfo(): void {
        $this->blog->isBlog()->willReturn(false);

        $plugin = new PageHeader($this->blog->reveal());
        $plugin->show_desc = true;
        ob_start();
        $plugin->output();
        $output = ob_get_clean();

        $this->assertStringContainsString(PACKAGE_NAME, $output);
        $this->assertStringContainsString(PACKAGE_URL, $output);
        $this->assertStringContainsString(PACKAGE_DESCRIPTION, $output);
    }

    protected function setUp(): void {
        parent::setUp();
        $this->blog = $this->prophet->prophesize(Blog::class);
    }
}
