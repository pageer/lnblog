<?php

require_once __DIR__ . "/../../../plugins/sidebar_articles.php";

class SidebarArticlesTest extends \PHPUnit\Framework\TestCase {

    private $blog;
    private $user;
    private $fs;
    private $system;

    public function testBuildOutput_WhenNormalUserWithDefaults_ShowsArticleInList() {
        $list = [
            ['link' => './content/test1', 'title' => 'Test1'],
            ['link' => './content/test2', 'title' => 'Test2'],
        ];
        $this->blog->isBlog()->willReturn(true);
        $this->blog->uri('articles')->willReturn('./content/');
        $this->blog->getArticleList()->willReturn($list);
        $blog = $this->blog->reveal();
        $user = $this->user->reveal();
        $fs = $this->fs->reveal();
        $this->system->canModify($blog, $user)->willReturn(false);
        $system = $this->system->reveal();

        $plugin = new Articles(0, $blog, $user, $fs, $system);
        $output = $plugin->buildOutput();

        $expected = [
            '<h3><a href="./content/">Articles</a></h3>',
            '<ul>',
            '<li><a href="./content/test1">Test1</a></li>',
            '<li><a href="./content/test2">Test2</a></li>',
            '<li style="margin-top: 0.5em"><a href="./content/">All static pages</a></li>',
            '</ul>',
        ];
        $this->assertEquals($expected, $this->htmlToLines($output));
    }

    public function testBuildOutput_WhenNormalUserWithCustomText_ShowsArticleInList() {
        $list = [
            ['link' => './content/test1', 'title' => 'Test1'],
            ['link' => './content/test2', 'title' => 'Test2'],
        ];
        $this->blog->isBlog()->willReturn(true);
        $this->blog->uri('articles')->willReturn('./content/');
        $this->blog->getArticleList()->willReturn($list);
        $blog = $this->blog->reveal();
        $user = $this->user->reveal();
        $fs = $this->fs->reveal();
        $this->system->canModify($blog, $user)->willReturn(false);
        $system = $this->system->reveal();

        $plugin = new Articles(0, $blog, $user, $fs, $system);
        $plugin->header = 'ALL THE CONTENT';
        $plugin->showall_text = 'EVEN MORE CONTENT';
        $output = $plugin->buildOutput();

        $expected = [
            '<h3><a href="./content/">ALL THE CONTENT</a></h3>',
            '<ul>',
            '<li><a href="./content/test1">Test1</a></li>',
            '<li><a href="./content/test2">Test2</a></li>',
            '<li style="margin-top: 0.5em"><a href="./content/">EVEN MORE CONTENT</a></li>',
            '</ul>',
        ];
        $this->assertEquals($expected, $this->htmlToLines($output));
    }

    public function testBuildOutput_WhenHeaderAndAllLinkDisabled_NotInOutput() {
        $list = [
            ['link' => './content/test1', 'title' => 'Test1'],
            ['link' => './content/test2', 'title' => 'Test2'],
        ];
        $this->blog->isBlog()->willReturn(true);
        $this->blog->uri('articles')->willReturn('./content/');
        $this->blog->getArticleList()->willReturn($list);
        $blog = $this->blog->reveal();
        $user = $this->user->reveal();
        $fs = $this->fs->reveal();
        $this->system->canModify($blog, $user)->willReturn(false);
        $system = $this->system->reveal();

        $plugin = new Articles(0, $blog, $user, $fs, $system);
        $plugin->static_link = false;
        $plugin->header = '';
        $output = $plugin->buildOutput();

        $expected = [
            '<ul>',
            '<li><a href="./content/test1">Test1</a></li>',
            '<li><a href="./content/test2">Test2</a></li>',
            '</ul>',
        ];
        $this->assertEquals($expected, $this->htmlToLines($output));
    }

    public function testBuildOutput_WhenAdminUser_ShowAddLink() {
        $list = [
            ['link' => './content/test1', 'title' => 'Test1'],
        ];
        $this->blog->isBlog()->willReturn(true);
        $this->blog->uri('articles')->willReturn('./content/');
        $this->blog->uri('editfile', ['file' => 'links.htm'])->willReturn('?action=editfile&file=links.htm');
        $this->blog->getArticleList()->willReturn($list);
        $blog = $this->blog->reveal();
        $user = $this->user->reveal();
        $fs = $this->fs->reveal();
        $this->system->canModify($blog, $user)->willReturn(true);
        $system = $this->system->reveal();

        $plugin = new Articles(0, $blog, $user, $fs, $system);
        $plugin->static_link = false;
        $plugin->header = '';
        $output = $plugin->buildOutput();

        $expected = [
            '<ul>',
            '<li><a href="./content/test1">Test1</a></li>',
            '<li style="margin-top: 0.5em"><a href="?action=editfile&file=links.htm">Add custom links</a></li>',
            '</ul>',
        ];
        $this->assertEquals($expected, $this->htmlToLines($output));
    }

    public function testBuildOutput_WhenCustomLinksFilePesent_ShowsLinks() {
        $list = [
            ['link' => './content/test1', 'title' => 'Test1'],
        ];
        $file_content = implode("\n", [
            '<a href="http://foo.com">Foo</a>',
            '<a href="http://bar.com">Bar</a>',
        ]);
        $this->blog->isBlog()->willReturn(true);
        $this->blog->uri('articles')->willReturn('./content/');
        $this->blog->uri('editfile', ['file' => 'links.htm'])->willReturn('?editfile=links.htm');
        $this->blog->getArticleList()->willReturn($list);
        $blog = $this->blog->reveal();
        $blog->home_path = '.';
        $user = $this->user->reveal();
        $this->fs->is_file('./links.htm')->willReturn(true);
        $this->fs->read_file('./links.htm')->willReturn($file_content);
        $fs = $this->fs->reveal();
        $this->system->canModify($blog, $user)->willReturn(false);
        $system = $this->system->reveal();

        $plugin = new Articles(0, $blog, $user, $fs, $system);
        $plugin->static_link = false;
        $plugin->header = '';
        $output = $plugin->buildOutput();

        $expected = [
            '<ul>',
            '<li><a href="./content/test1">Test1</a></li>',
            '<li><a href="http://foo.com">Foo</a></li>',
            '<li><a href="http://bar.com">Bar</a></li>',
            '</ul>',
        ];
        $this->assertEquals($expected, $this->htmlToLines($output));
    }

    public function testBuildOutput_WhenCustomLinksFileContainsMangledHtml_ShowsOnlyLinks() {
        $list = [
            ['link' => './content/test1', 'title' => 'Test1'],
        ];
        $file_content = '<p><a href="http://foo.com">Foo</a><br>' .
            '<a href="http://bar.com">Bar</a></p><p><BR />' .
            '<a href="http://bazz.com">Bazz</a><DIV>&NBsp; </DIV></p>';
        $this->blog->isBlog()->willReturn(true);
        $this->blog->uri('articles')->willReturn('./content/');
        $this->blog->uri('editfile', ['file' => 'links.htm'])->willReturn('?editfile=links.htm');
        $this->blog->getArticleList()->willReturn($list);
        $blog = $this->blog->reveal();
        $blog->home_path = '.';
        $user = $this->user->reveal();
        $this->fs->is_file('./links.htm')->willReturn(true);
        $this->fs->read_file('./links.htm')->willReturn($file_content);
        $fs = $this->fs->reveal();
        $this->system->canModify($blog, $user)->willReturn(false);
        $system = $this->system->reveal();

        $plugin = new Articles(0, $blog, $user, $fs, $system);
        $plugin->static_link = false;
        $plugin->header = '';
        $output = $plugin->buildOutput();

        $expected = [
            '<ul>',
            '<li><a href="./content/test1">Test1</a></li>',
            '<li><a href="http://foo.com">Foo</a></li>',
            '<li><a href="http://bar.com">Bar</a></li>',
            '<li><a href="http://bazz.com">Bazz</a></li>',
            '</ul>',
        ];
        $this->assertEquals($expected, $this->htmlToLines($output));
    }

    public function testBuildOutput_WhenCustomLinksFileButNoStickyArticles_ShowsOnlyLinks() {
        $list = [];
        $file_content = '<p><a href="http://foo.com">Foo</a><br>' .
            '<a href="http://bar.com">Bar</a><br />' .
            '<a href="http://bazz.com">Bazz</a></p>';
        $this->blog->isBlog()->willReturn(true);
        $this->blog->uri('articles')->willReturn('./content/');
        $this->blog->uri('editfile', ['file' => 'links.htm'])->willReturn('?editfile=links.htm');
        $this->blog->getArticleList()->willReturn($list);
        $blog = $this->blog->reveal();
        $blog->home_path = '.';
        $user = $this->user->reveal();
        $this->fs->is_file('./links.htm')->willReturn(true);
        $this->fs->read_file('./links.htm')->willReturn($file_content);
        $fs = $this->fs->reveal();
        $this->system->canModify($blog, $user)->willReturn(false);
        $system = $this->system->reveal();

        $plugin = new Articles(0, $blog, $user, $fs, $system);
        $plugin->static_link = false;
        $plugin->header = '';
        $output = $plugin->buildOutput();

        $expected = [
            '<ul>',
            '<li><a href="http://foo.com">Foo</a></li>',
            '<li><a href="http://bar.com">Bar</a></li>',
            '<li><a href="http://bazz.com">Bazz</a></li>',
            '</ul>',
        ];
        $this->assertEquals($expected, $this->htmlToLines($output));
    }

    public function testBuildOutput_WhenNoStickyArticles_NoOutput() {
        $list = [];
        $this->blog->isBlog()->willReturn(true);
        $this->blog->getArticleList()->willReturn($list);
        $blog = $this->blog->reveal();
        $user = $this->user->reveal();
        $fs = $this->fs->reveal();
        $system = $this->system->reveal();

        $plugin = new Articles(0, $blog, $user, $fs, $system);
        $plugin->static_link = false;
        $output = $plugin->buildOutput();

        $this->assertEquals('', $output);
    }

    public function testBuildOutput_WhenNoActiveBlog_NoOutput() {
        $this->blog->isBlog()->willReturn(false);
        $blog = $this->blog->reveal();
        $user = $this->user->reveal();
        $fs = $this->fs->reveal();
        $system = $this->system->reveal();

        $plugin = new Articles(0, $blog, $user, $fs, $system);
        $plugin->static_link = false;
        $output = $plugin->buildOutput();

        $this->assertEquals('', $output);
    }

    protected function setUp(): void {
        Path::$sep = '/';
        $this->prophet = new \Prophecy\Prophet();

        $this->fs = $this->prophet->prophesize(FS::class);
        $this->blog = $this->prophet->prophesize(Blog::class);
        $this->user = $this->prophet->prophesize(User::class);
        $this->system = $this->prophet->prophesize(System::class);
    }

    protected function tearDown(): void {
        $this->prophet->checkPredictions();
    }

    private function htmlToLines($output) {
        $lines = explode("\n", trim($output));
        return array_map(function ($line) {
            return trim($line);
        }, $lines);
    }

    private function createArticle($path, $sticky_title = '') {
        $article = $this->prophet->prophesize(BlogEntry::class);
        $article->isSticky($path)->willReturn($sticky_title === '');
        $article->readSticky($path)->willReturn($sticky_title);
        return $article->reveal();
    }
}
