<?php

namespace LnBlog\Tests\Plugins;

require_once __DIR__ . '/../../../plugins/menubar_sitemap.php';

use Blog;
use FS;
use LnBlog\Storage\BlogRepository;
use LnBlog\Tests\LnBlogBaseTestCase;
use Prophecy\Argument;
use SiteMap;
use SystemConfig;
use UrlPath;

class MenubarSitemapTest extends LnBlogBaseTestCase
{
    public function testSidebarOutput_ShowsLink(): void {
        $fs = $this->prophet->prophesize(FS::class);
        $blog = $this->prophet->prophesize(Blog::class);
        $blog->uri('editfile', Argument::any())->willReturn('editfileurl');

        $sitemap = new SiteMap($blog->reveal(), $fs->reveal());
        ob_start();
        $sitemap->showLink();
        $output = ob_get_clean();

        $this->assertStringContainsString('href="editfileurl"', $output);
    }

    public function testUpdateManagedFiles_BlogUpdated(): void {
        $fs = $this->prophet->prophesize(FS::class);
        $blog = $this->prophet->prophesize(Blog::class);
        $blog->addManagedFile('testfile.html')->shouldBeCalled();

        $sitemap = new SiteMap($blog->reveal(), $fs->reveal());
        $sitemap->link_file = 'testfile.html';
        $sitemap->updateManagedFiles($blog->reveal());
    }

    public function testOutput_NoMarkupSetFileInBlog_OutputWrappedFile(): void {
        $blog = $this->prophet->prophesize(Blog::class);
        $blog->isBlog()->willReturn(true);
        $blog_obj = $blog->reveal();
        $blog_obj->home_path = '/test/';
        $fs = $this->prophet->prophesize(FS::class);
        $fs->file_exists('/test/sitemap.htm')->willReturn(true);
        $fs->is_file('/test/sitemap.htm')->willReturn(true);
        $fs->read_file('/test/sitemap.htm')->willReturn('this is a test');

        $sitemap = new SiteMap($blog_obj, $fs->reveal());
        $sitemap->no_markup = true;
        $sitemap->auto_map = false;
        ob_start();
        $sitemap->output();
        $output = ob_get_clean();

        $this->assertStringContainsString('this is a test', $output);
    }

    public function testOutput_NoMarkupSetFileGlobal_OutputWrappedFile(): void {
        $blog = $this->prophet->prophesize(Blog::class);
        $blog->isBlog()->willReturn(true);
        $blog_obj = $blog->reveal();
        $blog_obj->home_path = '/test/';
        $fs = $this->prophet->prophesize(FS::class);
        $fs->is_file('/test/sitemap.htm')->willReturn(false);
        $fs->is_file('/userdata/sitemap.htm')->willReturn(true);
        $fs->read_file('/userdata/sitemap.htm')->willReturn('this is a test');
        $sysconfig = $this->prophet->prophesize(SystemConfig::class);
        $sysconfig->userData()->willReturn(new UrlPath('/userdata/', ''));

        $sitemap = new SiteMap($blog_obj, $fs->reveal(), $sysconfig->reveal());
        $sitemap->no_markup = true;
        $sitemap->auto_map = false;
        ob_start();
        $sitemap->output();
        $output = ob_get_clean();

        $this->assertStringContainsString('this is a test', $output);
    }

    public function testOutput_NoMarkupSetNoAutomapNoFile_DefaultHomeLink(): void {
        $blog = $this->prophet->prophesize(Blog::class);
        $blog->isBlog()->willReturn(true);
        $blog_obj = $blog->reveal();
        $blog_obj->home_path = '/test/';
        $fs = $this->prophet->prophesize(FS::class);
        $fs->is_file(Argument::any())->willReturn(false);

        $sitemap = new SiteMap($blog_obj, $fs->reveal());
        $sitemap->list_header = 'Header stuff';
        $sitemap->no_markup = true;
        $sitemap->auto_map = false;
        ob_start();
        $sitemap->output();
        $output = ob_get_clean();

        $this->assertStringContainsString('Header stuff', $output);
        $this->assertStringContainsString('Site home page', $output);
    }

    public function testOutput_NoAutomapFileExists_ShowFileContent(): void {
        $blog = $this->prophet->prophesize(Blog::class);
        $blog->isBlog()->willReturn(true);
        $blog_obj = $blog->reveal();
        $blog_obj->home_path = '/test/';
        $fs = $this->prophet->prophesize(FS::class);
        $fs->is_file('/test/sitemap.htm')->willReturn(true);
        $fs->file('/test/sitemap.htm')->willReturn(['test1', 'test2', 'test3', '  ']);
        $sysconfig = $this->prophet->prophesize(SystemConfig::class);

        $sitemap = new SiteMap($blog_obj, $fs->reveal());
        $sitemap->list_header = 'Header stuff';
        $sitemap->auto_map = false;
        ob_start();
        $sitemap->output();
        $output = ob_get_clean();

        $this->assertStringContainsString('Header stuff', $output);
        $this->assertStringContainsString('test1', $output);
        $this->assertStringContainsString('test2', $output);
        $this->assertStringContainsString('test3', $output);
    }

    public function testOutput_AutomapSetFileExists_ShowFileContent(): void {
        $blog = $this->prophet->prophesize(Blog::class);
        $blog->isBlog()->willReturn(true);
        $blog_obj = $blog->reveal();
        $blog_obj->home_path = '/test/';
        $fs = $this->prophet->prophesize(FS::class);
        $fs->is_file('/test/sitemap.htm')->willReturn(true);
        $fs->file('/test/sitemap.htm')->willReturn(['myblog.com']);
        $testBlog = $this->prophet->prophesize(Blog::class);
        $testBlog->name = 'test';
        $testBlog->description = 'Testing';
        $testBlog->getUrl()->willReturn('https://testblog1.com');
        $blogRepository = $this->prophet->prophesize(BlogRepository::class);
        $blogRepository->getAll()->willReturn([$testBlog->reveal()]);

        $sitemap = new SiteMap($blog_obj, $fs->reveal(), null, $blogRepository->reveal());
        $sitemap->list_header = 'Header stuff';
        $sitemap->auto_map = true;
        ob_start();
        $sitemap->output();
        $output = ob_get_clean();

        $this->assertStringContainsString('Header stuff', $output);
        $this->assertStringContainsString('testblog1.com', $output);
        $this->assertStringContainsString('myblog.com', $output);
    }
}
