<?php

namespace LnBlog\Tests\Export;

use Blog;
use BlogEntry;
use FS;
use GlobalFunctions;
use LnBlog\Attachments\FileManager;
use LnBlog\Storage\EntryRepository;
use LnBlog\Storage\ReplyRepository;
use LnBlog\Tasks\TaskManager;
use LnBlog\Tests\LnBlogBaseTestCase;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;
use SystemConfig;
use UrlPath;
use UrlResolver;
use User;

abstract class BaseExporterTest extends LnBlogBaseTestCase
{
    protected $prophet;

    protected $fs;
    protected $globals;
    protected $user_repo;

    protected function getTestBlog(array $data = []): Blog {
        $blog_url = 'https://fooblog.com/';
        $mock_fs = $this->prophet->prophesize(FS::class);
        $mock_file_manager = $this->prophet->prophesize(FileManager::class);
        $mock_task_manager = $this->prophet->prophesize(TaskManager::class);
        $mock_url_resolver = $this->prophet->prophesize(UrlResolver::class);
        $mock_logger = $this->prophet->prophesize(LoggerInterface::class);
        $mock_entry_repo = $this->prophet->prophesize(EntryRepository::class);

        $mock_url_resolver
            ->generateRoute('blog', Argument::any(), [])
            ->willReturn($blog_url);

        $mock_entry_repo
            ->getLimit(Argument::any(), Argument::any())
            ->willReturn($data['entries'] ?? []);
        $mock_entry_repo
            ->getArticles(Argument::any())
            ->willReturn($data['articles'] ?? []);
        $mock_entry_repo
            ->getDrafts()
            ->willReturn($data['drafts'] ?? []);

        $blog = new Blog(
            "",
            $mock_fs->reveal(),
            $mock_file_manager->reveal(),
            $mock_task_manager->reveal(),
            $mock_url_resolver->reveal(),
            $mock_logger->reveal(),
            $mock_entry_repo->reveal()
        );

        $blog->home_path = '/tmp/foo';
        $blog->blogid = 'foo';
        $blog->name = 'Foo Blog';
        $blog->description = 'Some blog';
        $blog->owner = 'bob';

        SystemConfig::instance()->registerBlog('foo', new UrlPath('/tmp/foo', $blog_url));

        $user = new User('bob');
        $user->fullname = 'Bob Smith';
        $user->email = 'bob@smith.com';
        $this->user_repo->get('bob')->willReturn($user);

        return $blog;
    }

    protected function createTestEntry(
        string $dir,
        string $entry_url,
        int $timestamp = null,
        array $test_comment = [],
        array $test_pingback = [],
        array $enclosure_data = []
    ): BlogEntry {
        if ($timestamp === null) {
            $timestamp = strtotime('2000-01-01 12:13:14 GMT');
        }

        $comment_url = dirname($entry_url) . '/' . basename($dir) . '/comments/';
        $mock_resolver = $this->prophet->prophesize(UrlResolver::class);
        $mock_resolver->generateRoute('page', Argument::any(), [])->willReturn($entry_url);
        $mock_resolver->generateRoute('comment', Argument::any(), [])->willReturn($comment_url);

        $comment_array = [];
        $pingback_array = [];
        $mock_repo = $this->prophet->prophesize(ReplyRepository::class);
        if ($test_comment) {
            $comment_array[] = $test_comment[0];
        }
        if ($test_pingback) {
            $pingback_array[] = $test_pingback[0];
        }
        $mock_repo->getReplyArray(
            Argument::any(),
            'comments',
            Argument::any(),
            Argument::any(),
            Argument::any(),
            Argument::any()
        )->willReturn($comment_array);

        $mock_fs = $this->prophet->prophesize(FS::class);
        $mock_fs->is_dir($dir)->willReturn(true);
        $mock_fs->is_dir("{$dir}comments")->willReturn(true);
        $mock_fs->file_exists("{$dir}entry.xml")->willReturn(true);
        $mock_fs->read_file("{$dir}entry.xml")->willReturn('data');
        $mock_fs->filemtime("{$dir}entry.xml")->willReturn(12345);
        $mock_fs->realpath("{$dir}entry.xml")->willReturn("{$dir}entry.xml");

        $mock_globals = $this->prophet->prophesize(GlobalFunctions::class);

        if ($enclosure_data) {
            $url = $enclosure_data['url'];
            $file = basename($url);
            $mock_resolver->uriToLocalpath($url, Argument::any())->willReturn($file);
            $mock_resolver->localpathToUri($file, Argument::any())->willReturn($url);
            $mock_fs->file_exists($file)->willReturn(true);
            $mock_fs->filesize($file)->willReturn($enclosure_data['size']);
            $mock_globals->getMimeType($file)->willReturn($enclosure_data['type']);
        }

        $entry = new BlogEntry(
            $dir,
            $mock_fs->reveal(),
            null,
            $mock_resolver->reveal(),
            $mock_repo->reveal(),
            $mock_globals->reveal()
        );

        return $entry;
    }

    protected function setupTestUser(): void {
        $user = new User('bob');
        $user->fullname = 'Bob Smith';
        $user->email = 'bob@smith.com';
        $user->homepage = 'https://smith.com/';
        $this->user_repo->get('bob')->willReturn($user);
    }
}
