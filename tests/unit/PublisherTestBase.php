<?php

use LnBlog\Tasks\TaskManager;
use Prophecy\Argument;

abstract class PublisherTestBase extends PHPUnit\Framework\TestCase {

    protected function setUp(): void {
        $_FILES = array();
        $_SERVER = array();

        Path::$sep = '/';
        $this->prophet = new \Prophecy\Prophet();

        $this->blog = $this->prophet->prophesize(Blog::class);
        $this->blog->home_path = '.';

        $this->user = $this->prophet->prophesize(User::class);
        $this->fs = $this->prophet->prophesize(FS::class);
        $this->task_manager = $this->prophet->prophesize(TaskManager::class);

        $this->system = $this->prophet->prophesize(System::class);
        $this->sys_ini = $this->prophet->prophesize(INIParser::class);
        $this->system->reveal()->sys_ini = $this->sys_ini->reveal();
        System::$static_instance = $this->system->reveal();

        $this->wrappers = $this->prophet->prophesize(WrapperGenerator::class);

        $this->http_client = $this->prophet->prophesize(HttpClient::class);

        EventRegister::instance()->clearAll();

        $this->publisher = new TestablePublisher(
            $this->blog->reveal(),
            $this->user->reveal(),
            $this->fs->reveal(),
            $this->wrappers->reveal(),
            $this->task_manager->reveal()
        );
        $this->publisher->setHttpClient($this->http_client->reveal());
    }

    protected function tearDown(): void {
        Path::$sep = DIRECTORY_SEPARATOR;
        $this->prophet->checkPredictions();
    }

    protected function getTestTime() {
        return new DateTime('2017-01-02 12:34:00');
    }

    protected function setUpTestDraftEntryForSuccessfulSave() {
        return $this->setUpEntryForSuccessfulSave('./drafts/02_1234/entry.xml');
    }

    protected function setUpTestPublishedEntryForSuccessfulSave() {
        return $this->setUpEntryForSuccessfulSave('./entries/2017/01/02_1234/entry.xml');
    }

    protected function setUpTestArticleForSuccessfulSave() {
        $ret = $this->setUpEntryForSuccessfulSave('./content/some_stuff/entry.xml');
        $ret->is_sticky = 0;
        $this->fs->file_exists('./content/some_stuff/sticky.txt')->willReturn(false);
        return $ret;
    }

    private function setUpEntryForSuccessfulSave($path) {
        $entry = new BlogEntry(null, $this->fs->reveal());
        $entry->file = $path;
        $this->fs->file_exists($path)->willReturn(true);
        $this->fs->realpath($path)->willReturn($path);
        $this->fs->write_file($path, Argument::any())->willReturn(true);
        return $entry;
    }

    protected function setUpTestDraftEntryForSuccessfulDelete() {
        return $this->setUpEntryForSuccessfulDelete('./drafts/02_1234/entry.xml');
    }

    protected function setUpTestPublishedEntryForSuccessfulDelete() {
        return $this->setUpEntryForSuccessfulDelete('./entries/2017/01/02_1234/entry.xml');
    }

    protected function setUpTestArticleForSuccessfulDelete() {
        return $this->setUpEntryForSuccessfulDelete('./content/some_stuff/entry.xml');
    }

    private function setUpEntryForSuccessfulDelete($path) {
        $entry = new BlogEntry(null, $this->fs->reveal());
        $entry->file = $path;
        $this->fs->file_exists($path)->willReturn(true);
        $this->fs->realpath($path)->willReturn($path);
        $this->fs->file_exists(Argument::any())->willReturn(false);
        $this->fs->rmdir_rec(dirname($path))->willReturn(true);
        return $entry;
    }

    protected function setUpForSingleUploadSuccess($path = './entries/2017/01/02_1234') {
        $_FILES = array(
            'upload' => array(
                'name' => 'foo.txt',
                'tmp_name' => '/tmp/foo',
                'size' => 1234,
                'type' => 'text/plain',
                'error' => UPLOAD_ERR_OK,
            ),
        );
        $this->fs->is_file('/tmp/foo')->willReturn(true);
        $this->fs->copy('/tmp/foo', "$path/foo.txt")->willReturn(true);
        $this->fs->is_uploaded_file('/tmp/foo')->willReturn(true);
        $event_stub = new PublisherEventTestingStub();
        EventRegister::instance()->addHandler('BlogEntry', 'UploadSuccess', $event_stub, 'eventHandler');
        return $event_stub;
    }

    protected function setUpForMultipleUploadSuccess() {
        $_FILES = array(
            'upload' => array(
                'name' => array(
                    'foo.txt',
                    'bar.html',
                ),
                'tmp_name' => array(
                    '/tmp/foo',
                    '/tmp/bar',
                ),
                'size' => array(
                    1234,
                    2345,
                ),
                'type' => array(
                    'text/plain',
                    'text/html',
                ),
                'error' => array(
                    UPLOAD_ERR_OK,
                    UPLOAD_ERR_OK,
                ),
            ),
        );
        $this->fs->is_file(Argument::any())->willReturn(true);
        $this->fs->copy(Argument::any(), Argument::any())->willReturn(true);
        $this->fs->is_uploaded_file(Argument::any())->willReturn(true);
        $event_stub = new PublisherEventTestingStub();
        EventRegister::instance()->addHandler('BlogEntry', 'UploadSuccess', $event_stub, 'eventHandler');
        return $event_stub;
    }

    protected function setUpForSingleUploadMoveFail($path = './entries/2017/01/02_1234') {
        $_FILES = array(
            'upload' => array(
                'name' => 'foo.txt',
                'tmp_name' => '/tmp/foo',
                'size' => 1234,
                'type' => 'text/plain',
                'error' => UPLOAD_ERR_OK,
            ),
        );
        $this->fs->is_file('/tmp/foo')->willReturn(true);
        $this->fs->copy('/tmp/foo', "$path/foo.txt")->willReturn(false);
        $this->fs->is_uploaded_file('/tmp/foo')->willReturn(true);
        $event_stub = new PublisherEventTestingStub();
        EventRegister::instance()->addHandler('BlogEntry', 'UploadError', $event_stub, 'eventHandler');
        return $event_stub;
    }

    protected function setUpForSingleUploadStatusFail($error) {
        $_FILES = array(
            'upload' => array(
                'name' => 'foo.txt',
                'tmp_name' => '/tmp/foo',
                'size' => 1234,
                'type' => 'text/plain',
                'error' => UPLOAD_ERR_PARTIAL,
            ),
        );
        $this->fs->is_file('/tmp/foo')->willReturn(true);
        $event_stub = new PublisherEventTestingStub();
        EventRegister::instance()->addHandler('BlogEntry', 'UploadError', $event_stub, 'eventHandler');
        return $event_stub;
    }
}

class TestablePublisher extends Publisher {
    private $client = null;

    public function setHttpClient($client) {
        $this->client = $client;
    }

    protected function getHttpClient() {
        return $this->client;
    }
}

class PublisherEventTestingStub {
    public $has_been_called = false;
    public $call_count = 0;
    public $event_object = null;
    public $event_data = false;

    public function eventHandler($object, $data = false) {
        $this->has_been_called = true;
        $this->call_count += 1;
        $this->event_object = $object;
        $this->event_data = $data;
    }
}
