<?php

namespace LnBlog\Tests\Plugins;

require_once __DIR__ . '/../../../plugins/content_ban.php';

use Blog;
use BlogComment;
use ContentBan;
use FS;
use Generator;
use LnBlog\Tests\LnBlogBaseTestCase;
use Page;
use Prophecy\Argument;
use Trackback;

class ContentBanTest extends LnBlogBaseTestCase
{
    private $fs;

    public function testUpdateManagedFiles_AddsBanListFile(): void {
        $blog = new Blog();

        $plugin = new ContentBan();
        $plugin->ban_list = 'somefile.txt';
        $plugin->updateManagedFiles($blog);

        $this->assertContains('somefile.txt', $blog->getManagedFiles());
    }

    public function testClearData_ContentBanned_BlanksComment(): void {
        $rules = [
            '/something/',
            '/wha+tever/',
        ];
        $blog = $this->prophet->prophesize(Blog::class);
        $blog->isBlog()->willReturn(true);
        $comment = new BlogComment();
        $comment->parent = $blog->reveal();
        $comment->subject = 'thing';
        $comment->data = 'whaaatever';

        $this->fs->file_exists(Argument::any())->willReturn(true);
        $this->fs->file(Argument::any())->willReturn($rules);

        $plugin = new ContentBan($this->fs->reveal());
        $plugin->clearData($comment);

        $this->assertEmpty($comment->data);
        $this->assertEmpty($comment->subject);
    }

    public function testClearData_ContentOk_NoCommentChange(): void {
        $rules = [
            '/something/',
            '/wha+tever/',
        ];
        $blog = $this->prophet->prophesize(Blog::class);
        $blog->isBlog()->willReturn(true);
        $comment = new BlogComment();
        $comment->parent = $blog->reveal();
        $comment->subject = 'thing';
        $comment->data = 'stuff';

        $this->fs->file_exists(Argument::any())->willReturn(true);
        $this->fs->file(Argument::any())->willReturn($rules);

        $plugin = new ContentBan($this->fs->reveal());
        $plugin->clearData($comment);

        $this->assertEquals('thing', $comment->subject);
        $this->assertEquals('stuff', $comment->data);
    }

    public function testClearTbData_TrackbackContentBanned_BlanksTrackback(): void {
        $rules = [
            '/something/',
            '/wha+tever/',
        ];
        $blog = $this->prophet->prophesize(Blog::class);
        $blog->isBlog()->willReturn(true);
        $comment = new Trackback();
        $comment->parent = $blog->reveal();
        $comment->title = 'thing';
        $comment->data = 'whaaatever';

        $this->fs->file_exists(Argument::any())->willReturn(true);
        $this->fs->file(Argument::any())->willReturn($rules);

        $plugin = new ContentBan($this->fs->reveal());
        $plugin->clearTBData($comment);

        $this->assertEmpty($comment->data);
        $this->assertEmpty($comment->title);
    }

    public function testClearData_TrackbackContentOk_NoTrackbackChange(): void {
        $rules = [
            '/something/',
            '/wha+tever/',
        ];
        $blog = $this->prophet->prophesize(Blog::class);
        $blog->isBlog()->willReturn(true);
        $comment = new Trackback();
        $comment->parent = $blog->reveal();
        $comment->title = 'thing';
        $comment->data = 'stuff';

        $this->fs->file_exists(Argument::any())->willReturn(true);
        $this->fs->file(Argument::any())->willReturn($rules);

        $plugin = new ContentBan($this->fs->reveal());
        $plugin->clearTBData($comment);

        $this->assertEquals('thing', $comment->title);
        $this->assertEquals('stuff', $comment->data);
    }

    public function testSidebarLink_Blog_OutputsOneLink(): void {
        $plugin = new ContentBan($this->fs->reveal());
        ob_start();
        $plugin->sidebarLink();
        $output = ob_get_clean();
    }

    protected function setUp(): void {
        parent::setup();
        $this->fs = $this->prophet->prophesize(FS::class);
    }
}
