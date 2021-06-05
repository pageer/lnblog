<?php

use LnBlog\Import\FileImportSource;
use LnBlog\Import\WordPressImporter;
use LnBlog\Storage\BlogRepository;
use LnBlog\Storage\UserRepository;
use LnBlog\Tasks\TaskManager;
use Prophecy\Argument;

class WordPressImporterTest extends \PHPUnit\Framework\TestCase
{
    private $tz;
    private $blog;
    private $prophet;
    private $fs;
    private $wrappers;
    private $task_manager;
    private $user_repo;
    private $blog_repo;
    private $publisher;
    private $user;
    private $testfile;

    /**
     * @dataProvider optionsProvider
     */
    public function testImport_WhenSuccessful_AddsImportDataToBlog(array $options, string $existing_user) {
        $this->runImportTest($options, $existing_user, false);
    }

    /**
     * @dataProvider optionsProvider
     */
    public function testImportAsNewBlog_CreatesBlogWithData(array $options, string $existing_user) {
        $this->runImportTest($options, $existing_user, true);
    }

    public function optionsProvider(): array {
        return [
            'No user import, user does not exist' => [
                [],
                'administrator',
            ],
            'No user import, user exists' => [
                [],
                'testadmin',
            ],
            'Import users, user does not exist' => [
                [WordPressImporter::IMPORT_USERS => true],
                'administrator',
            ],
            'Import users, user already exists' => [
                [WordPressImporter::IMPORT_USERS => true],
                'testadmin',
            ],
        ];
    }

    public function testImport_WhenFailure_ReportsFailures() {
        $this->publisher->useBlogDefaults(false)->shouldBeCalled();
        $this->publisher->publishEntry(Argument::any(), Argument::any())->willThrow(new Exception('entry error'));
        $this->publisher->publishArticle(Argument::any(), Argument::any())->willThrow(new Exception('article error'));
        $this->publisher->createDraft(Argument::any(), Argument::any())->willThrow(new Exception('draft exception'));
        $this->publisher->publishReply(Argument::any(), Argument::any())->willThrow(new Exception('reply exception'));
        $this->user_repo->exists(Argument::any())->willReturn(true);

        $source = new FileImportSource();
        $source->setFromFile($this->testfile);
        $importer = $this->createImporter();

        $importer->import($this->blog->reveal(), $source);

        $import_report = $importer->getImportReport();

        $this->assertCount(4, $import_report->entry_errors);
        $this->assertCount(1, $import_report->article_errors);
        $this->assertCount(1, $import_report->draft_errors);
        // The entry publish throws, so we shouldn't even get to replies.
        $this->assertCount(0, $import_report->comment_errors);
        $this->assertCount(0, $import_report->ping_errors);
    }

    public function testImportAsNewBlog_WhenFailure_ReportsFailures() {
        $this->publisher->useBlogDefaults(false)->shouldBeCalled();
        $this->publisher->publishEntry(Argument::any(), Argument::any())->willThrow(new Exception('entry error'));
        $this->publisher->publishArticle(Argument::any(), Argument::any())->willThrow(new Exception('article error'));
        $this->publisher->createDraft(Argument::any(), Argument::any())->willThrow(new Exception('draft exception'));
        $this->publisher->publishReply(Argument::any(), Argument::any())->willThrow(new Exception('reply exception'));
        $this->user_repo->exists(Argument::any())->willReturn(true);
        $this->blog_repo->save(Argument::any())->shouldBeCalled();

        $source = new FileImportSource();
        $source->setFromFile($this->testfile);
        $importer = $this->createImporter();

        $urlpath = new UrlPath('/tmp/foo', 'http://foobar.com/');
        $blog = $importer->importAsNewBlog('testblog', $urlpath, $source);

        $this->assertInstanceOf(Blog::class, $blog);
        $import_report = $importer->getImportReport();

        $this->assertCount(4, $import_report->entry_errors);
        $this->assertCount(1, $import_report->article_errors);
        $this->assertCount(1, $import_report->draft_errors);
        $this->assertCount(0, $import_report->comment_errors);
        $this->assertCount(0, $import_report->ping_errors);
    }

    public function testImportAsNewBlog_WhenBlogCreationFailure_ThrowsReportsNoImports() {
        $this->publisher->publishEntry(Argument::any(), Argument::any())->willThrow(new Exception('entry error'));
        $this->publisher->publishArticle(Argument::any(), Argument::any())->willThrow(new Exception('article error'));
        $this->publisher->createDraft(Argument::any(), Argument::any())->willThrow(new Exception('draft exception'));
        $this->publisher->publishReply(Argument::any(), Argument::any())->willThrow(new Exception('reply exception'));
        $this->user_repo->exists(Argument::any())->willReturn(true);
        $this->blog_repo->save(Argument::any())->willThrow(new SaveFailure('error'));

        $source = new FileImportSource();
        $source->setFromFile($this->testfile);
        $importer = $this->createImporter();

        $urlpath = new UrlPath('/tmp/foo', 'http://foobar.com/');
        try {
            $blog = $importer->importAsNewBlog('testblog', $urlpath, $source);
            $this->assertTrue(false, 'Expected exception');
        } catch (SaveFailure $e) {
            $this->assertTrue(true);
        }

        $import_report = $importer->getImportReport();

        $this->assertCount(0, $import_report->entries);
        $this->assertCount(0, $import_report->articles);
        $this->assertCount(0, $import_report->drafts);
        $this->assertCount(0, $import_report->comments);
        $this->assertCount(0, $import_report->pings);
        $this->assertCount(0, $import_report->entry_errors);
        $this->assertCount(0, $import_report->article_errors);
        $this->assertCount(0, $import_report->draft_errors);
        $this->assertCount(0, $import_report->comment_errors);
        $this->assertCount(0, $import_report->ping_errors);
    }

    protected function setUp(): void {
        $this->tz = date_default_timezone_get();
        date_default_timezone_set('UTC');

        $this->prophet = new \Prophecy\Prophet();
        $this->fs = $this->prophet->prophesize(FS::class);
        $this->wrappers = $this->prophet->prophesize(WrapperGenerator::class);
        $this->task_manager = $this->prophet->prophesize(TaskManager::class);
        $this->user_repo = $this->prophet->prophesize(UserRepository::class);
        $this->blog_repo = $this->prophet->prophesize(BlogRepository::class);
        $this->publisher = $this->prophet->prophesize(Publisher::class);
        $this->blog = $this->prophet->prophesize(Blog::class);
        $this->user = new User('administrator');
        $this->testfile = __DIR__ . '/wordpress-rss-sample.xml';
    }

    protected function tearDown(): void {
        $this->prophet->checkPredictions();

        date_default_timezone_set($this->tz);
    }

    private function createImporter(): WordPressImporter {
        return new WordPressImporter(
            $this->user,
            $this->fs->reveal(),
            $this->wrappers->reveal(),
            $this->task_manager->reveal(),
            $this->user_repo->reveal(),
            $this->blog_repo->reveal(),
            $this->publisher->reveal()
        );
    }

    private function runImportTest(array $options, string $existing_user, bool $import_as_new) {

        $do_user_import = !empty($options[WordPressImporter::IMPORT_USERS]);
        $should_create_user = $do_user_import && $existing_user != 'testadmin';
        $owner = $do_user_import ? 'testadmin' : $existing_user;
        $comment_owner =
            ($do_user_import || $existing_user == 'testadmin') ?
            'testadmin' :
            '';
        $existing_users = [$existing_user];

        $count = [
            'published' => 0,
            'draft' => 0,
            'page' => 0,
            'comment' => 0,
            'pingback' => 0,
            'user' => 0,
        ];
        $found = [
            'published' => false,
            'draft' => false,
            'page' => false,
            'comment' => false,
            'pingback' => false,
            'user' => false,
        ];

        $post_validation = function ($args) use (&$count, &$found, $owner) {
            $count['published']++;

            $matches = 
                $args[0]->subject == 'Including some media' &&
                strpos($args[0]->data, 'This is a test post') &&
                $args[0]->allow_comment === true &&
                $args[0]->post_ts == 1605227404 &&
                $args[0]->timestamp == 1605227404 &&
                $args[0]->allow_pingback === true && 
                $args[0]->uid == $owner && 
                in_array('blah', $args[0]->tags());

            if ($matches) {
                $found['published'] = true;
            }
        };
        $page_validation = function ($args) use (&$count, &$found, $owner) {
            $count['page']++;

            $matches = 
                $args[0]->subject == 'Sample Page' &&
                strpos($args[0]->data, 'This is an example page') &&
                $args[0]->allow_comment === false &&
                $args[0]->post_ts == 1605227032 &&
                $args[0]->timestamp == 1605227032 &&
                $args[0]->uid == $owner && 
                $args[0]->allow_pingback === true;

            if ($matches) {
                $found['page'] = true;
            }
        };
        $draft_validation = function ($args) use (&$count, &$found, $owner) {
            $count['draft']++;

            $matches = 
                $args[0]->subject == 'Privacy Policy' &&
                strpos($args[0]->data, 'Who we are') &&
                $args[0]->allow_comment === false &&
                $args[0]->post_ts == 1605227032 &&
                $args[0]->timestamp == 1605227032 &&
                $args[0]->allow_pingback === true && 
                $args[0]->uid == $owner && 
                $args[0]->article_path == 'privacy-policy';

            if ($matches) {
                $found['draft'] = true;
            }
        };
        $pingback_validation = function ($args) use (&$count, &$found) {
            $count['pingback']++;

            $matches = 
                strpos($args[0]->title, 'Test link') !== false &&
                strpos($args[0]->data, 'This is a test link') !== false &&
                $args[0]->ip == '192.252.154.17' &&
                $args[0]->source == 'https://www.skepticats.com/testwp/2021/01/17/test-link/' &&
                $args[0]->ping_date == '2021-01-17 22:46:15' &&
                $args[0]->timestamp == 1610923575;

            if ($matches) {
                $found['pingback'] = true;
            }
        };
        $comment_validation = function ($args) use (&$count, &$found, $comment_owner) {
            $count['comment']++;

            $matches = 
                $args[0]->data == 'This is a test comment.' &&
                $args[0]->ip == '67.240.217.238' &&
                $args[0]->uid == $comment_owner &&
                $args[0]->name == 'Pete' && 
                $args[0]->email == 'pageer@skepticats.com' &&
                $args[0]->url == 'https://skepticats.com' &&
                $args[0]->post_ts == 1610922656 &&
                $args[0]->timestamp == 1610922656;

            if ($matches) {
                $found['comment'] = true;
            }
        };
        $reply_validation = function ($args) use ($pingback_validation, $comment_validation) {
            if ($args[0] instanceof BlogComment) {
                $comment_validation($args);
            } else {
                $pingback_validation($args);
            }
        };
        $user_validation = function ($args) use (&$count, &$found, &$existing_users) {
            $count['user']++;

            $found['user'] =
                $args[0]->username() == 'testadmin' && 
                !empty($args[0]->password());
            $existing_users[] = $args[0]->username();
        };
        $user_exists = function ($args) use (&$existing_users) {
            return in_array($args[0], $existing_users);
        };

        $this->publisher->useBlogDefaults(false)->shouldBeCalled();
        $this->publisher->publishEntry(Argument::any(), Argument::any())->will($post_validation);
        $this->publisher->publishArticle(Argument::any(), Argument::any())->will($page_validation);
        $this->publisher->createDraft(Argument::any(), Argument::any())->will($draft_validation);
        $this->publisher->publishReply(Argument::any(), Argument::any())->will($reply_validation);
        $this->user_repo->createUser(Argument::any())->will($user_validation);
        $this->user_repo->exists(Argument::any())->will($user_exists);

        $source = new FileImportSource();
        $source->setFromFile($this->testfile);
        $importer = $this->createImporter();
        $importer->setImportOptions($options);

        if ($import_as_new) {
            $this->blog_repo->save(Argument::any())->shouldBeCalled();

            $urlpath = new UrlPath('/tmp/foo', 'http://foobar.com/');
            $blog = $importer->importAsNewBlog('testblog', $urlpath, $source);

            $this->assertInstanceOf(Blog::class, $blog);
            $this->assertEquals('testblog', $blog->blogid);
            $this->assertEquals('Test WP', $blog->name);
            $this->assertEquals('Just another WordPress site', $blog->description);
        } else {
            $importer->import($this->blog->reveal(), $source);
        }

        if ($should_create_user) {
            $this->assertEquals(1, $count['user']);
            $this->assertTrue($found['user']);
        }

        $this->assertEquals(4, $count['published']);
        $this->assertTrue($found['published']);
        $this->assertEquals(1, $count['page']);
        $this->assertTrue($found['page']);
        $this->assertEquals(1, $count['draft']);
        $this->assertTrue($found['draft']);
        $this->assertEquals(1, $count['comment']);
        $this->assertTrue($found['comment']);
        $this->assertEquals(1, $count['pingback']);
        $this->assertTrue($found['pingback']);

        $import_report = $importer->getImportReport();

        $this->assertCount(4, $import_report->entries);
        $this->assertCount(1, $import_report->articles);
        $this->assertCount(1, $import_report->drafts);
        $this->assertCount(1, $import_report->comments);
        $this->assertCount(1, $import_report->pings);
    }
}
