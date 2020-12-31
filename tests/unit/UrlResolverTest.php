<?php

use Prophecy\Argument;

class UrlResolverTest extends \PHPUnit\Framework\TestCase
{
    private $prophet;
    private $blogs;
    private $userdata;
    private $install;
    private $fs;
    private $config;

    /**
     * @dataProvider localpathToUriProvider
     */
    public function testLocalpathToUri_ReturnsExpected($path, $existence_map, $use_blog, $use_entry, $expected_uri) {
        $blog = new Blog();
        $blog->blogid = 'foobar';
        $entry = new BlogEntry('', $this->fs->reveal());
        $entry->parent = $blog;
        $entry->file = '/var/www/foobar/public_html/content/foo/entry.xml';

        $this->setUpSystemUrlRegistry();
        $this->fs->realpath(Argument::any())->willReturnArgument(0);

        foreach ($existence_map as $full_path => $exists) {
            $this->fs->file_exists($full_path)->willReturn($exists);
        }

        $resolver = $this->createUrlResolver();
        $url = $resolver->localpathToUri($path, $use_blog ? $blog : null, $use_entry ? $entry : null);

        $this->assertEquals($expected_uri, $url);
    }

    public function localpathToUriProvider() {
        return [
            'relative in entry, returns in entry' => [
                'buzz.png',
                [
                    '/var/www/foobar/public_html/content/foo/buzz.png' => true,
                    '/var/www/foobar/public_html/content/foo/entry.xml' => true,
                    '/var/www/foobar/public_html/content/foo/' => true,
                ],
                true,
                true,
                'https://foobar.mysite.com/content/foo/buzz.png'
            ],
            'relative in blog with entry, returns in blog' => [
                'buzz.png',
                [
                    '/var/www/foobar/public_html/content/foo/buzz.png' => false,
                    '/var/www/foobar/public_html/content/foo/entry.xml' => true,
                    '/var/www/foobar/public_html/content/foo/' => true,
                    '/var/www/foobar/public_html/buzz.png' => true,
                ],
                true,
                true,
                'https://foobar.mysite.com/buzz.png'
            ],
            'relative in blog, returns in blog' => [
                'content/foo/buzz.png',
                [
                    '/var/www/foobar/public_html/content/foo/buzz.png' => true,
                ],
                true,
                false,
                'https://foobar.mysite.com/content/foo/buzz.png'
            ],
            'absolute in blog, returns in blog' => [
                '/var/www/foobar/public_html/content/foo/buzz.png',
                [],
                true,
                false,
                'https://foobar.mysite.com/content/foo/buzz.png'
            ],
            'absolute in blog with trailing slash, returns in blog with slash' => [
                '/var/www/foobar/public_html/content/foo/',
                [],
                true,
                false,
                'https://foobar.mysite.com/content/foo/'
            ],
            'relative in userdata, return in userdata' => [
                'bob/buzz.png',
                [
                    "/var/www/public_html/userdata/bob/buzz.png" => true,
                ],
                false,
                false,
                'https://www.mysite.com/userdata/bob/buzz.png',
            ],
            'absolute in userdata, return in userdata' => [
                '/var/www/public_html/userdata/bob/buzz.png',
                [],
                false,
                false,
                'https://www.mysite.com/userdata/bob/buzz.png'
            ],
            'relative in install root, return in install root' => [
                'buzz.png',
                [
                    "/var/www/public_html/userdata/buzz.png" => false,
                    "/var/www/public_html/lnblog/buzz.png" => true,
                ],
                false,
                false,
                'https://www.mysite.com/lnblog/buzz.png'
            ],
            'absolute in install root, return in install root' => [
                '/var/www/public_html/lnblog/buzz.png',
                [],
                false,
                false,
                'https://www.mysite.com/lnblog/buzz.png'
            ],
            'relative in none, return umodified' => [
                'buzz.png',
                [
                    "/var/www/foobar/public_html/buzz.png" => false,
                    "/var/www/public_html/userdata/buzz.png" => false,
                    "/var/www/public_html/lnblog/buzz.png" => false
                ],
                true,
                false,
                'buzz.png',
            ],
            'absolute in none, return umodified' => [
                '/var/www/public_html/buzz.png',
                [],
                false,
                false,
                '/var/www/public_html/buzz.png',
            ],
            'relative in all, return in blog' => [
                'content/foo/buzz.png',
                [
                    '/var/www/foobar/public_html/content/foo/buzz.png' => true,
                    '/var/www/public_html/userdata/content/foo/buzz.png' => true,
                    '/var/www/public_html/lnblog/content/foo/buzz.png' => true,
                ],
                true,
                false,
                'https://foobar.mysite.com/content/foo/buzz.png'
            ],
            'relative in userdata and install root, return in userdata' => [
                'content/foo/buzz.png',
                [
                    '/var/www/public_html/userdata/content/foo/buzz.png' => true,
                    '/var/www/public_html/lnblog/content/foo/buzz.png' => true,
                ],
                false,
                false,
                'https://www.mysite.com/userdata/content/foo/buzz.png'
            ],
        ];
    }

    /**
     * @dataProvider localpathToUriAbsoluteMissingProvider
     */
    public function testLocalpathToUri_MissingAbsolutePath_ReturnsUnmodified($path, $use_blog) {
        $blog = new Blog();
        $blog->blogid = 'foobar';

        $this->setUpSystemUrlRegistry();
        $this->fs->realpath($path)->willReturn(false);
        $items = array_merge($this->blogs, [$this->install, $this->userdata]);
        foreach ($items as $item) {
            $this->fs->realpath($item->path())->willReturn($item->path());
        }

        $resolver = $this->createUrlResolver();
        $url = $resolver->localpathToUri($path, $use_blog ? $blog : null);

        $this->assertEquals($path, $url);
    }

    public function localpathToUriAbsoluteMissingProvider() {
        return [
            'in blog' => [
                '/var/www/foobar/public_html/content/foo/buzz.png',
                true,
            ],
            'in install root' => [
                '/var/www/public_html/lnblog/buzz.png',
                false,
            ],
            'in userdata' => [
                '/var/www/public_html/userdata/bob/buzz.png',
                false,
            ],
        ];
    }

    /**
     * @dataProvider absoluteLocalpathProvider
     */
    public function testAbsoluteLocalpathToUri_ReturnsExpected($path, $expected_uri) {
        $blog = new Blog();
        $blog->blogid = 'foobar';

        $this->setUpSystemUrlRegistry();
        $this->fs->realpath(Argument::any())->willReturnArgument(0);

        $resolver = $this->createUrlResolver();
        $url = $resolver->absoluteLocalpathToUri($path);

        $this->assertEquals($expected_uri, $url);
    }

    public function absoluteLocalpathProvider() {
        return [
            'in blog' => [
                '/var/www/foobar/public_html/content/foo/buzz.png',
                'https://foobar.mysite.com/content/foo/buzz.png',
            ],
            'in root' => [
                '/var/www/public_html/lnblog/buzz.png',
                'https://www.mysite.com/lnblog/buzz.png',
            ],
            'in userdata' => [
                '/var/www/public_html/userdata/buzz.png',
                'https://www.mysite.com/userdata/buzz.png',
            ],
            'in unsupported' => [
                '/var/www/public_html/buzz.png',
                '/var/www/public_html/buzz.png',
            ],
        ];
    }

    public function testAbsoluteLocalpathToUri_RelativePath_Throws() {
        $blog = new Blog();
        $blog->blogid = 'foobar';
        $full_path = 'content/foo/buzz.png';

        $this->setUpSystemUrlRegistry();
        $this->fs->realpath(Argument::any())->willReturnArgument(0);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Path is not absolute (content/foo/buzz.png)");

        $resolver = $this->createUrlResolver();
        $url = $resolver->absoluteLocalpathToUri($full_path);
    }

    public function testUriToLocalpath_UriInBlog_ReturnsPathInBlog() {
        $blog = new Blog();
        $blog->blogid = 'foobar';

        $url = 'https://foobar.mysite.com/content/foo/buzz.png';
        $path = '/var/www/foobar/public_html/content/foo/buzz.png';
        $this->setUpSystemUrlRegistry();

        $resolver = $this->createUrlResolver();
        $result = $resolver->uriToLocalpath($url, $blog);

        $this->assertEquals($path, $result);
    }

    public function testUriToLocalpath_UriInUserdata_ReturnsPathInUserdata() {
        $blog = new Blog();
        $blog->blogid = 'foobar';

        $url = 'https://www.mysite.com/userdata/bob/buzz.png';
        $path = '/var/www/public_html/userdata/bob/buzz.png';
        $this->setUpSystemUrlRegistry();

        $resolver = $this->createUrlResolver();
        $result = $resolver->uriToLocalpath($url, $blog);

        $this->assertEquals($path, $result);
    }

    public function testUriToLocalpath_UriInInstallRoot_ReturnsPathInInstallRoot() {
        $blog = new Blog();
        $blog->blogid = 'foobar';

        $url = 'https://www.mysite.com/lnblog/buzz.png';
        $path = '/var/www/public_html/lnblog/buzz.png';
        $this->setUpSystemUrlRegistry();

        $resolver = $this->createUrlResolver();
        $result = $resolver->uriToLocalpath($url, $blog);

        $this->assertEquals($path, $result);
    }

    public function testUriToLocalpath_UriNotInSupported_ReturnsUnmodified() {
        $blog = new Blog();
        $blog->blogid = 'foobar';

        $url = 'https://www.mysite.com/buzz.png';
        $this->setUpSystemUrlRegistry();

        $resolver = $this->createUrlResolver();
        $result = $resolver->uriToLocalpath($url, $blog);

        $this->assertEquals($url, $result);
    }

    /**
     * @dataProvider routeProvider
     */
    public function testGenerateRoute_ValidParams($route, $entity, $params, $expected_result) {
        $this->setUpSystemUrlRegistry();
        $this->fs->read_file(Argument::any())->willReturn('');
        $this->fs->filemtime(Argument::any())->willReturn(0);
        $this->fs->is_dir(Argument::any())->willReturn(true);
        $this->fs->file_exists(Argument::any())->willReturn(true);
        $this->fs->realpath(Argument::any())->willReturnArgument(0);

        $fs = $this->fs->reveal();

        $blog = new Blog();
        $blog->blogid = 'foobar';
        $entry = new BlogEntry('', $fs);
        $entry->parent = $blog;
        $entry->permalink_name = 'some-entry-thing.php';
        $entry->file = '/var/www/foobar/public_html/entries/2018/03/25_1143/entry.xml';
        $no_permalink_entry = new BlogEntry('', $fs);
        $no_permalink_entry->parent = $blog;
        $no_permalink_entry->file = '/var/www/foobar/public_html/entries/2018/03/25_1143/entry.xml';
        $draft_entry = new BlogEntry('', $fs);
        $draft_entry->parent = $blog;
        $draft_entry->file = '/var/www/foobar/public_html/drafts/25_1143/entry.xml';
        $article_entry = new BlogEntry('', $fs);
        $article_entry->parent = $blog;
        $article_entry->file = '/var/www/foobar/public_html/content/thing/entry.xml';
        $comment = new BlogComment('', $fs);
        $comment->parent = $entry;
        $comment->file = '/var/www/foobar/public_html/entries/2018/03/25_1143/comments/2020-11-13_114546.xml';
        $article_comment = new BlogComment('', $fs);
        $article_comment->parent = $article_entry;
        $article_comment->file = '/var/www/foobar/public_html/content/thing/comments/2020-11-13_114546.xml';
        $trackback = new Trackback('', $fs);
        $trackback->parent = $entry;
        $trackback->file = '/var/www/foobar/public_html/entries/2018/03/25_1143/trackback/2020-11-13_114546.xml';
        $pingback = new Pingback('', $fs);
        $pingback->parent = $entry;
        $pingback->file = '/var/www/foobar/public_html/entries/2018/03/25_1143/pingback/2020-11-13_114546.xml';
        $article_pingback = new Pingback('', $fs);
        $article_pingback->parent = $article_entry;
        $article_pingback->file = '/var/www/foobar/public_html/content/thing/pingback/2020-11-13_114546.xml';

        $entity_map = [
            'blog' => $blog,
            'entry' => $entry,
            'no_permalink_entry' => $no_permalink_entry,
            'draft_entry' => $draft_entry,
            'article_entry' => $article_entry,
            'comment' => $comment,
            'article_comment' => $article_comment,
            'trackback' => $trackback,
            'pingback' => $pingback,
            'article_pingback' => $article_pingback,
        ];

        $resolver = new UrlResolver($this->config->reveal(), $fs);
        $route = $resolver->generateRoute($route, $entity_map[$entity], $params);

        $this->assertEquals($expected_result, $route);
    }

    public function routeProvider() {
        return [
            # Blog URLs
            'blog permalink' => ['permalink', 'blog', [], 'https://foobar.mysite.com/'],
            'blog base' => ['base', 'blog', [], 'https://foobar.mysite.com/'],
            'blog blog' => ['blog', 'blog', [], 'https://foobar.mysite.com/'],
            'blog page' => ['page', 'blog', [], 'https://foobar.mysite.com/'],
            'blog articles' => ['articles', 'blog', [], 'https://foobar.mysite.com/content/'],
            'blog entries' => ['entries', 'blog', [], 'https://foobar.mysite.com/entries/'],
            'blog archives' => ['archives', 'blog', [], 'https://foobar.mysite.com/entries/'],
            'blog drafts' => ['drafts', 'blog', [], 'https://foobar.mysite.com/drafts/'],
            'blog list drafts' => ['listdrafts', 'blog', [], 'https://foobar.mysite.com/drafts/'],
            'blog year' => ['year', 'blog', ['year' => 2006], 'https://foobar.mysite.com/entries/2006/'],
            'blog list year' => ['listyear', 'blog', ['year' => 2006], 'https://foobar.mysite.com/entries/2006/'],
            'blog month' => ['month', 'blog', ['year' => 2006, 'month' => 11], 'https://foobar.mysite.com/entries/2006/11/'],
            'blog list month' => ['listmonth', 'blog', ['year' => 2006, 'month' => 11], 'https://foobar.mysite.com/entries/2006/11/'],
            'blog day' => ['day', 'blog', ['year' => 2006, 'month' => 11, 'day' => 27], 'https://foobar.mysite.com/entries/2006/11/?day=27'],
            'blog show day' => ['showday', 'blog', ['year' => 2006, 'month' => 11, 'day' => 27], 'https://foobar.mysite.com/entries/2006/11/?day=27'],
            'blog listall' => ['listall', 'blog', [], 'https://foobar.mysite.com/entries/?list=yes'],
            'blog addentry' => ['addentry', 'blog', [], 'https://foobar.mysite.com/?action=newentry'],
            'blog addarticle' => ['addarticle', 'blog', [], 'https://foobar.mysite.com/?action=newentry&type=article'],
            'blog delentry' => ['delentry', 'blog', ['entryid' => '2003/12/12_1230'], 'https://foobar.mysite.com/?action=delentry&entry=2003/12/12_1230'],
            'blog upload' => ['upload', 'blog', [], 'https://foobar.mysite.com/?action=upload'],
            'blog upload with profile' => ['upload', 'blog', ['profile' => 'bob'], 'https://foobar.mysite.com/?action=upload&profile=bob'],
            'blog scaleimage' => ['scaleimage', 'blog', [], 'https://foobar.mysite.com/?action=scaleimage'],
            'blog scaleimage with profile' => ['scaleimage', 'blog', ['profile' => 'bob'], 'https://foobar.mysite.com/?action=scaleimage&profile=bob'],
            'blog edit' => ['edit', 'blog', [], 'https://foobar.mysite.com/?action=edit'],
            'blog manage_reply' => ['manage_reply', 'blog', [], 'https://foobar.mysite.com/?action=managereply'],
            'blog manage_all' => ['manage_reply', 'blog', [], 'https://foobar.mysite.com/?action=managereply'],
            'blog manage_year' => ['manage_year', 'blog', ['year' => 2004], 'https://foobar.mysite.com/?action=managereply&year=2004'],
            'blog manage_month' => ['manage_month', 'blog', ['year' => 2004, 'month' => 12], 'https://foobar.mysite.com/?action=managereply&year=2004&month=12'],
            'blog login' => ['login', 'blog', [], 'https://foobar.mysite.com/?action=login'],
            'blog logout' => ['logout', 'blog', [], 'https://foobar.mysite.com/?action=logout'],
            'blog editfile' => ['editfile', 'blog', ['file' => 'foo.txt'], 'https://foobar.mysite.com/?action=editfile&file=foo.txt'],
            'blog editfile complex' => ['editfile', 'blog', ['file' => 'foo.txt', 'map' => 'yes', 'list' => 'yes', 'richedit' => 'false'], 'https://foobar.mysite.com/?action=editfile&file=foo.txt&map=yes&list=yes&richedit=false'],
            'blog edituser' => ['edituser', 'blog', [], 'https://foobar.mysite.com/?action=useredit'],
            'blog plugins' => ['pluginconfig', 'blog', [], 'https://foobar.mysite.com/?action=plugins'],
            'blog pluginload' => ['pluginload', 'blog', [], 'https://foobar.mysite.com/?action=pluginload'],
            'blog tags' => ['tags', 'blog', ['tag' => 'foo'], 'https://foobar.mysite.com/?action=tags&tag=foo'],
            'blog tags list' => ['tags', 'blog', [], 'https://foobar.mysite.com/?action=tags'],
            'blog script' => ['script', 'blog', ['script' => 'foobar.js'], 'https://foobar.mysite.com/?script=foobar.js'],
            'blog plugin' => ['plugin', 'blog', ['plugin' => 'foobar', 'val' => 123], 'https://foobar.mysite.com/?plugin=foobar&val=123'],
            # Entry URLs
            'entry dir permalink' => ['permalink', 'entry', [], 'https://foobar.mysite.com/entries/2018/03/some-entry-thing.php'],
            'entry pretty permalink' => ['permalink', 'no_permalink_entry', [], 'https://foobar.mysite.com/entries/2018/03/25_1143/'],
            'entry entry' => ['entry', 'entry', [], 'https://foobar.mysite.com/entries/2018/03/some-entry-thing.php'],
            'entry page' => ['page', 'entry', [], 'https://foobar.mysite.com/entries/2018/03/some-entry-thing.php'],
            'entry base' => ['base', 'entry', [], 'https://foobar.mysite.com/entries/2018/03/25_1143/'],
            'entry basepage' => ['basepage', 'entry', [], 'https://foobar.mysite.com/entries/2018/03/25_1143/index.php'],
            'entry comment' => ['comment', 'entry', [], 'https://foobar.mysite.com/entries/2018/03/25_1143/comments/'],
            'entry commentpage' => ['commentpage', 'entry', [], 'https://foobar.mysite.com/entries/2018/03/25_1143/comments/index.php'],
            'entry send_tb' => ['send_tb', 'entry', [], 'https://foobar.mysite.com/entries/2018/03/25_1143/trackback/?action=ping'],
            'entry get_tb' => ['get_tb', 'entry', [], 'https://foobar.mysite.com/entries/2018/03/25_1143/trackback/index.php'],
            'entry trackback' => ['trackback', 'entry', [], 'https://foobar.mysite.com/entries/2018/03/25_1143/trackback/'],
            'entry pingback' => ['pingback', 'entry', [], 'https://foobar.mysite.com/entries/2018/03/25_1143/pingback/'],
            'entry upload' => ['upload', 'entry', [], 'https://foobar.mysite.com/entries/2018/03/25_1143/?action=upload'],
            'entry scaleimage' => ['scaleimage', 'entry', [], 'https://foobar.mysite.com/entries/2018/03/25_1143/?action=scaleimage'],
            'entry edit' => ['edit', 'entry', [], 'https://foobar.mysite.com/entries/2018/03/25_1143/?action=editentry'],
            'entry editDraft' => ['editDraft', 'draft_entry', [], 'https://foobar.mysite.com/drafts/25_1143/'],
            'entry delete' => ['delete', 'entry', [], 'https://foobar.mysite.com/?action=delentry&entry=2018/03/25_1143'],
            'entry draft delete' => ['delete', 'draft_entry', [], 'https://foobar.mysite.com/?action=delentry&draft=25_1143'],
            'entry article delete' => ['delete', 'article_entry', [], 'https://foobar.mysite.com/?action=delentry&article=content/thing'],
            'entry manage_reply' => ['manage_reply', 'entry', [], 'https://foobar.mysite.com/entries/2018/03/25_1143/?action=managereplies'],
            'entry managereply' => ['managereply', 'entry', [], 'https://foobar.mysite.com/entries/2018/03/25_1143/?action=managereplies'],
            # Comment URLs
            'comment permalink' => ['permalink', 'comment', [], 'https://foobar.mysite.com/entries/2018/03/25_1143/comments/#comment2020-11-13_114546'],
            'comment comment' => ['comment', 'comment', [], 'https://foobar.mysite.com/entries/2018/03/25_1143/comments/#comment2020-11-13_114546'],
            'comment delete' => ['delete', 'comment', [], 'https://foobar.mysite.com/?action=delcomment&entry=2018/03/25_1143&delete=comment2020-11-13_114546'],
            'comment delete article' => ['delete', 'article_comment', [], 'https://foobar.mysite.com/?action=delcomment&article=content/thing&delete=comment2020-11-13_114546'],
            # Trackback and pingback URLs
            'trackback permalink' => ['permalink', 'trackback', [], 'https://foobar.mysite.com/entries/2018/03/25_1143/trackback/#trackback2020-11-13_114546'],
            'pingback permalink' => ['permalink', 'pingback', [], 'https://foobar.mysite.com/entries/2018/03/25_1143/pingback/#pingback2020-11-13_114546'],
            'trackback trackback' => ['trackback', 'trackback', [], 'https://foobar.mysite.com/entries/2018/03/25_1143/trackback/#trackback2020-11-13_114546'],
            'pingback pingback' => ['pingback', 'pingback', [], 'https://foobar.mysite.com/entries/2018/03/25_1143/pingback/#pingback2020-11-13_114546'],
            'trackback delete' => ['delete', 'trackback', [], 'https://foobar.mysite.com/?action=delcomment&entry=2018/03/25_1143&delete=trackback2020-11-13_114546'],
            'pingback delete article' => ['delete', 'article_pingback', [], 'https://foobar.mysite.com/?action=delcomment&article=content/thing&delete=pingback2020-11-13_114546'],
        ];
    }

    protected function setUp(): void {
        Path::$sep = '/';
        $this->prophet = new \Prophecy\Prophet();
        $this->config = $this->prophet->prophesize(SystemConfig::class);
        $this->fs = $this->prophet->prophesize(FS::class);

        $this->blogs = [
            'foobar' => new UrlPath(
                '/var/www/foobar/public_html',
                'https://foobar.mysite.com/'
            ),
            'fizzbuzz' => new UrlPath(
                '/var/web/vhosts/fizzbuzz/blog',
                'https://fizzbuzz.mysite.com/blog/'
            ),
        ];
        $this->userdata = new UrlPath(
            '/var/www/public_html/userdata',
            'https://www.mysite.com/userdata/'
        );
        $this->install = new UrlPath(
            '/var/www/public_html/lnblog',
            'https://www.mysite.com/lnblog/'
        );
    }

    protected function tearDown(): void {
        $this->prophet->checkPredictions();
    }

    private function createUrlResolver() {
        return new UrlResolver($this->config->reveal(), $this->fs->reveal());
    }

    private function setUpSystemUrlRegistry() {
        $this->config->blogRegistry()->willReturn($this->blogs);
        $this->config->userData()->willReturn($this->userdata);
        $this->config->installRoot()->willReturn($this->install);
    }
}
