<?php
use Prophecy\Argument;

abstract class PublisherTestBase extends PHPUnit_Framework_TestCase {

    protected function setUp() {
        Path::$sep = '/';
        $this->prophet = new \Prophecy\Prophet();

        $this->blog = $this->prophet->prophesize('Blog');
        $this->blog->home_path = '.';

        $this->user = $this->prophet->prophesize('User');
        
        $this->fs = $this->prophet->prophesize('FS');

        $this->wrappers = $this->prophet->prophesize('WrapperGenerator');

        EventRegister::instance()->clearAll();

        $this->publisher = new Publisher($this->blog->reveal(), $this->user->reveal(), $this->fs->reveal(), $this->wrappers->reveal());
    }

    protected function tearDown() {
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
        return $this->setUpEntryForSuccessfulSave('./content/some_stuff/entry.xml');
    }

    private function setUpEntryForSuccessfulSave($path) {
        $entry = new BlogEntry(null, $this->fs->reveal());
        $entry->file = $path;
        $this->fs->file_exists($path)->willReturn(true);
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
        $this->fs->file_exists(Argument::any())->willReturn(false);
        $this->fs->rmdir_rec(dirname($path))->willReturn(true);
        return $entry;
    }

}

class PublisherEventTestingStub {
    public $has_been_called = false;

    public function eventHandler() {
        $this->has_been_called = true;
    }
}
