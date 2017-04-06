<?php
use Prophecy\Argument;

class PublisherTest extends PHPUnit_Framework_TestCase {

    /******** Create Draft ********/

    public function testCreateDraft_WhenEntryDoesNotExist_WritesEntryDataToFile() {
        $time = new DateTime('2017-01-02 12:34:00');
        $entry = new BlogEntry();
        $entry->body = "This is some text";
        $this->fs->is_dir('./drafts')->willReturn(true);
        $this->fs->is_dir('./drafts/02_1234')->willReturn(false);
        $this->fs->file_exists(Argument::any())->willReturn(false);

        $this->fs->mkdir_rec('./drafts/02_1234')->willReturn(true)->shouldBeCalled();
        $this->fs->write_file('./drafts/02_1234/entry.xml', Argument::containingString('This is some text'))->willReturn(true)->shouldBeCalled();

        $publisher = new Publisher($this->blog->reveal(), $this->fs->reveal(), $this->wrappers->reveal());
        $publisher->createDraft($entry, $time);
    }

    public function testCreateDraft_WhenDraftCreated_SetsDateAndTimestamp() {
        $time = new DateTime('2017-01-02 12:34:00');
        $entry = new BlogEntry();
        $entry->body = "This is some text";
        $this->fs->is_dir('./drafts')->willReturn(true);
        $this->fs->file_exists(Argument::any())->willReturn(false);
        $this->fs->mkdir_rec(Argument::any())->willReturn(true);
        $this->fs->write_file(Argument::any(), Argument::any())->willReturn(true);

        $publisher = new Publisher($this->blog->reveal(), $this->fs->reveal(), $this->wrappers->reveal());
        $publisher->createDraft($entry, $time);

        $this->assertEquals($time->getTimestamp(), $entry->timestamp);
        $this->assertEquals('2017-01-02 12:34 Eastern Standard Time', $entry->date);
    }

    /**
     * @expectedException   Exception
     */
    public function testCreateDraft_WhenDraftAlreadyExists_Throws() {
        $time = new DateTime('2017-01-02 12:34:00');
        $entry = new BlogEntry();
        $entry->body = "This is some text";
        $this->fs->is_dir('./drafts')->willReturn(true);
        $this->fs->file_exists('./drafts/02_1234')->willReturn(true);

        $publisher = new Publisher($this->blog->reveal(), $this->fs->reveal(), $this->wrappers->reveal());
        $publisher->createDraft($entry, $time);
    }

    public function testCreateDraft_WhenDraftDirDoesNotExist_CreatesWrappers() {
        $time = new DateTime('2017-01-02 12:34:00');
        $entry = new BlogEntry();
        $entry->body = "This is some text";
        $this->fs->is_dir('./drafts')->willReturn(false);
        $this->fs->file_exists(Argument::any())->willReturn(false);
        $this->fs->mkdir_rec(Argument::any())->willReturn(true);
        $this->fs->write_file(Argument::any(), Argument::any())->willReturn(true);
        
        $this->wrappers->createDirectoryWrappers('./drafts', BLOG_DRAFTS)->shouldBeCalled();

        $publisher = new Publisher($this->blog->reveal(), $this->fs->reveal(), $this->wrappers->reveal());
        $publisher->createDraft($entry, $time);
    }

    /**
     * @expectedException   Exception
     */
    public function testCreateDraft_WhenDraftDirDoesNotExistAndWrapperCreationFails_Throws() {
        $time = new DateTime('2017-01-02 12:34:00');
        $entry = new BlogEntry();
        $entry->body = "This is some text";
        $this->fs->is_dir('./drafts')->willReturn(false);
        $this->wrappers->createDirectoryWrappers('./drafts', BLOG_DRAFTS)->willReturn(false);

        $publisher = new Publisher($this->blog->reveal(), $this->fs->reveal(), $this->wrappers->reveal());
        $publisher->createDraft($entry, $time);
    }

    /**
     * @expectedException   Exception
     */
    public function testCreateDraft_WhenFileCreationFails_Throws() {
        $time = new DateTime('2017-01-02 12:34:00');
        $entry = new BlogEntry();
        $entry->body = "This is some text";
        $this->fs->is_dir('./drafts')->willReturn(false);
        $this->fs->mkdir_rec('./drafts/02_1234')->willReturn(true);
        $this->fs->write_file('./drafts/02_1234/entry.xml', Argument::any())->willReturn(false);

        $publisher = new Publisher($this->blog->reveal(), $this->fs->reveal(), $this->wrappers->reveal());
        $publisher->createDraft($entry, $time);
    }

    /**
     * @expectedException   Exception
     */
    public function testCreateDraft_WhenDirectoryCreationFails_Throws() {
        $time = new DateTime('2017-01-02 12:34:00');
        $entry = new BlogEntry();
        $entry->body = "This is some text";
        $this->fs->is_dir('./drafts')->willReturn(false);
        $this->fs->mkdir_rec('./drafts/02_1234')->willReturn(false);

        $publisher = new Publisher($this->blog->reveal(), $this->fs->reveal(), $this->wrappers->reveal());
        $publisher->createDraft($entry, $time);
    }

    /******** Publish Entry ********/

    public function testPublishEntry_WhenEntryDoesNotExists_SaveAsDraftAndMovesDirectory() {
        $time = new DateTime('2017-01-02 12:34:00');
        $fs = $this->fs;
        $entry = new BlogEntry(null, $fs->reveal());
        $this->file = '';
        $fs->file_exists('./drafts/02_1234/entry.xml')->willReturn(false);
        $fs->is_dir('./entries/2017/01/02_1234')->willReturn(false);
        $fs->is_dir(Argument::any())->willReturn(false);

        $fs->mkdir_rec('./drafts/02_1234')->willReturn(true)->shouldBeCalled();
        $fs->write_file('./drafts/02_1234/entry.xml', Argument::any())->shouldBeCalled()->will(function($args) use ($fs) {
            $fs->file_exists('./drafts/02_1234/entry.xml')->willReturn(true);
            return true; 
        });
        $fs->rename('./drafts/02_1234', './entries/2017/01/02_1234')->willReturn(true)->shouldBeCalled();

        $publisher = new Publisher($this->blog->reveal(), $this->fs->reveal(), $this->wrappers->reveal());
        $publisher->publishEntry($entry, $time);
    }

    /******** Update ********/

    /**
     * @expectedException EntryDoesNotExist
     */
    public function testUpdate_WhenEntryDoesNotExist_Throws() {
        $entry = new BlogEntry();
        $entry->body = "This is some text";
        $this->fs->file_exists(Argument::any())->willReturn(false);

        $publisher = new Publisher($this->blog->reveal(), $this->fs->reveal(), $this->wrappers->reveal());
        $publisher->update($entry);
    }

    public function testUpdate_WhenNotTrackingHistory_JustWritesFile() {
        $path = './drafts/02_1234/entry.xml';
        $entry = new BlogEntry();
        $entry->file = $path;
        $entry->body = "This is some text";
        $this->fs->file_exists($path)->willReturn(true);

        $this->fs->rename(Argument::any())->shouldNotBeCalled();
        $this->fs->write_file($path, Argument::containingString('This is some text'))->willReturn(true)->shouldBeCalled();

        $publisher = new Publisher($this->blog->reveal(), $this->fs->reveal(), $this->wrappers->reveal());
        $publisher->keepEditHistory(false);
        $publisher->update($entry);
    }

    public function testUpdate_WhenTrackingHistory_RenamesOldFile() {
        $time = new DateTime('2017-01-02 12:34:00');
        $path = './drafts/02_1234/entry.xml';
        $entry = new BlogEntry();
        $entry->file = $path;
        $entry->body = "This is some text";
        $this->fs->file_exists($path)->willReturn(true);
        $this->fs->write_file($path, Argument::containingString('This is some text'))->willReturn(true);

        $this->fs->rename($path, './drafts/02_1234/02_123400.xml')->willReturn(true)->shouldBeCalled();

        $publisher = new Publisher($this->blog->reveal(), $this->fs->reveal(), $this->wrappers->reveal());
        $publisher->keepEditHistory(true);
        $publisher->update($entry, $time);
    }
    
    /**
     * @expectedException EntryWriteFailed
     */
    public function testUpdate_WhenNotTrackingHistoryAndWriteFails_Throws() {
        $path = './drafts/02_1234/entry.xml';
        $entry = new BlogEntry();
        $entry->file = $path;
        $entry->body = "This is some text";
        $this->fs->file_exists($path)->willReturn(true);
        $this->fs->write_file($path, Argument::containingString('This is some text'))->willReturn(false);

        $publisher = new Publisher($this->blog->reveal(), $this->fs->reveal(), $this->wrappers->reveal());
        $publisher->keepEditHistory(false);
        $publisher->update($entry);
    }

    /**
     * @expectedException EntryRenameFailed
     */
    public function testUpdate_WhenTrackingHistoryAndRenameFails_DoesNotWriteFile() {
        $time = new DateTime('2017-01-02 12:34:00');
        $path = './drafts/02_1234/entry.xml';
        $entry = new BlogEntry();
        $entry->file = $path;
        $this->fs->file_exists($path)->willReturn(true);

        $this->fs->rename($path, './drafts/02_1234/02_123400.xml')->willReturn(false)->shouldBeCalled();
        $this->fs->write_file(Argument::any(), Argument::any())->shouldNotBeCalled();

        $publisher = new Publisher($this->blog->reveal(), $this->fs->reveal(), $this->wrappers->reveal());
        $publisher->keepEditHistory(true);
        $publisher->update($entry, $time);
    }

    /**
     * @expectedException EntryWriteFailed
     */
    public function testUpdate_WhenTrackingHistoryAndWriteFails_RenamesOldFileBack() {
        $time = new DateTime('2017-01-02 12:34:00');
        $path = './drafts/02_1234/entry.xml';
        $entry = new BlogEntry();
        $entry->file = $path;
        $this->fs->file_exists($path)->willReturn(true);
        $this->fs->rename($path, './drafts/02_1234/02_123400.xml')->willReturn(true);
        $this->fs->write_file(Argument::any(), Argument::any())->willReturn(false);

        $this->fs->rename('./drafts/02_1234/02_123400.xml', $path)->shouldBeCalled();

        $publisher = new Publisher($this->blog->reveal(), $this->fs->reveal(), $this->wrappers->reveal());
        $publisher->keepEditHistory(true);
        $publisher->update($entry, $time);
    }

    public function testUpdate_WhenEntryIsPublishedAndPermalinkDoesNotExist_WritesPrettyPermalinkFile() {
        $time = new DateTime('2017-01-02 12:34:00');
        $path = './entries/2017/03/02_1234/entry.xml';
        $entry = new BlogEntry(null, $this->fs->reveal());
        $entry->subject = 'Some Stuff';
        $entry->file = $path;
        $this->fs->file_exists($path)->willReturn(true);
        $this->fs->file_exists('./entries/2017/03/Some_Stuff.php')->willReturn(false);
        $this->fs->write_file($path, Argument::any())->willReturn(true);

        $this->fs->write_file('./entries/2017/03/Some_Stuff.php', Argument::containingString('02_1234'))->shouldBeCalled();

        $publisher = new Publisher($this->blog->reveal(), $this->fs->reveal(), $this->wrappers->reveal());
        $publisher->keepEditHistory(false);
        $publisher->update($entry, $time);
    }

    public function testUpdate_WhenEntryIsNotPublished_DoesNotWritePrettyPermalinkFile() {
        $entry = $this->setUpTestDraftEntryForSuccessfulSave();
        $entry->subject = 'Some Stuff';

        $this->fs->write_file(Argument::containingString('Some_Stuff.php'), Argument::any())->shouldNotBeCalled();

        $publisher = new Publisher($this->blog->reveal(), $this->fs->reveal(), $this->wrappers->reveal());
        $publisher->keepEditHistory(false);
        $publisher->update($entry, $this->getTestTime());
    }

    public function testUpdate_WhenEntryIsPublishedAsArticle_DoesNotWritePrettyPermalinkFile() {
        $entry = $this->setUpTestArticleForSuccessfulSave();
        $entry->subject = 'Some Stuff';

        $this->fs->write_file(Argument::containingString('Some_Stuff.php'), Argument::any())->shouldNotBeCalled();

        $publisher = new Publisher($this->blog->reveal(), $this->fs->reveal(), $this->wrappers->reveal());
        $publisher->keepEditHistory(false);
        $publisher->update($entry, $this->getTestTime());
    }

    public function testUpdate_WhenEntryIsDraft_DoesNotRaiseOnUpdateEvent() {
        $entry = $this->setUpTestDraftEntryForSuccessfulSave();
        $event_stub = new PublisherEventTestingStub();
        EventRegister::instance()->addHandler('BlogEntry', 'OnUpdate', $event_stub, 'eventHandler');

        $publisher = new Publisher($this->blog->reveal(), $this->fs->reveal(), $this->wrappers->reveal());
        $publisher->update($entry, $this->getTestTime());

        $this->assertFalse($event_stub->has_been_called);
    }

    public function testUpdate_WhenEntryIsDraft_DoesNotRaiseUpdateCompleteEvent() {
        $entry = $this->setUpTestDraftEntryForSuccessfulSave();
        $event_stub = new PublisherEventTestingStub();
        EventRegister::instance()->addHandler('BlogEntry', 'UpdateComplete', $event_stub, 'eventHandler');

        $publisher = new Publisher($this->blog->reveal(), $this->fs->reveal(), $this->wrappers->reveal());
        $publisher->update($entry, $this->getTestTime());

        $this->assertFalse($event_stub->has_been_called);
    }

    public function testUpdate_WhenEntryIsPublished_RaisesOnUpdateEvent() {
        $entry = $this->setUpTestPublishedEntryForSuccessfulSave();
        $event_stub = new PublisherEventTestingStub();
        EventRegister::instance()->addHandler('BlogEntry', 'OnUpdate', $event_stub, 'eventHandler');

        $publisher = new Publisher($this->blog->reveal(), $this->fs->reveal(), $this->wrappers->reveal());
        $publisher->update($entry, $this->getTestTime());

        $this->assertTrue($event_stub->has_been_called);
    }

    public function testUpdate_WhenEntryIsPublished_RaisesUpdateCompleteEvent() {
        $entry = $this->setUpTestPublishedEntryForSuccessfulSave();
        $event_stub = new PublisherEventTestingStub();
        EventRegister::instance()->addHandler('BlogEntry', 'UpdateComplete', $event_stub, 'eventHandler');

        $publisher = new Publisher($this->blog->reveal(), $this->fs->reveal(), $this->wrappers->reveal());
        $publisher->update($entry, $this->getTestTime());

        $this->assertTrue($event_stub->has_been_called);
    }

    public function testUpdate_WhenEntryIsPublishedAsArticle_RaisesArticleOnUpdateEvent() {
        $entry = $this->setUpTestArticleForSuccessfulSave();
        $event_stub = new PublisherEventTestingStub();
        EventRegister::instance()->addHandler('Article', 'OnUpdate', $event_stub, 'eventHandler');

        $publisher = new Publisher($this->blog->reveal(), $this->fs->reveal(), $this->wrappers->reveal());
        $publisher->update($entry, $this->getTestTime());

        $this->assertTrue($event_stub->has_been_called);

    }

    public function testUpdate_WhenEntryIsPublisehedAsArticle_RaisesArticleUpdateCompleteEvent() {
        $entry = $this->setUpTestArticleForSuccessfulSave();
        $event_stub = new PublisherEventTestingStub();
        EventRegister::instance()->addHandler('Article', 'UpdateComplete', $event_stub, 'eventHandler');

        $publisher = new Publisher($this->blog->reveal(), $this->fs->reveal(), $this->wrappers->reveal());
        $publisher->update($entry, $this->getTestTime());

        $this->assertTrue($event_stub->has_been_called);
    }

    /**
     * @expectedException EntryDoesNotExist
     */
    public function testDelete_WhenEntryDoesNotExist_Throws() {
        $entry =  new BlogEntry(null, $this->fs->reveal());

        $publisher = new Publisher($this->blog->reveal(), $this->fs->reveal(), $this->wrappers->reveal());
        $publisher->delete($entry, $this->getTestTime());
    }

    public function testDelete_WhenEntryHasPrettyPermalink_DeletesLink() {
        $path = './entries/2017/03/02_1234/entry.xml';
        $link_path = './entries/2017/03/Some_Stuff.php';
        $entry = new BlogEntry(null, $this->fs->reveal());
        $entry->subject = 'Some Stuff';
        $entry->file = $path;
        $this->fs->rmdir_rec(Argument::any())->willReturn(true);
        $this->fs->file_exists($path)->willReturn(true);
        $this->fs->file_exists($link_path)->willReturn(true);

        $this->fs->delete($link_path)->shouldBeCalled();

        $publisher = new Publisher($this->blog->reveal(), $this->fs->reveal(), $this->wrappers->reveal());
        $publisher->keepEditHistory(false);
        $publisher->delete($entry);
    }

    public function testDelete_WhenNotTrackingHistory_DeletesEntryDirectory() {
        $path = './entries/2017/03/02_1234/entry.xml';
        $entry = new BlogEntry(null, $this->fs->reveal());
        $entry->file = $path;
        $this->fs->file_exists($path)->willReturn(true);
        $this->fs->file_exists(Argument::any())->willReturn(false);

        $this->fs->rmdir_rec('./entries/2017/03/02_1234')->willReturn(true)->shouldBeCalled();

        $publisher = new Publisher($this->blog->reveal(), $this->fs->reveal(), $this->wrappers->reveal());
        $publisher->keepEditHistory(false);
        $publisher->delete($entry);
    }

    public function testDelete_WhenTrackingHistory_RenamesEntryFile() {
        $time = new DateTime('2017-01-02 12:34:00');
        $path = './entries/2017/03/02_1234/entry.xml';
        $entry = new BlogEntry(null, $this->fs->reveal());
        $entry->file = $path;
        $this->fs->file_exists($path)->willReturn(true);
        $this->fs->file_exists(Argument::any())->willReturn(false);

        $this->fs->rename($path, './entries/2017/03/02_1234/02_123400.xml')->willReturn(true)->shouldBeCalled();

        $publisher = new Publisher($this->blog->reveal(), $this->fs->reveal(), $this->wrappers->reveal());
        $publisher->keepEditHistory(true);
        $publisher->delete($entry, $time);
    }

    /**
     * @expectedException   EntryDeleteFailed
     */
    public function testDelete_WhenNotTrackingHistoryAndDeleteFails_Throws() {
        $path = './entries/2017/03/02_1234/entry.xml';
        $entry = new BlogEntry(null, $this->fs->reveal());
        $entry->file = $path;
        $this->fs->file_exists($path)->willReturn(true);
        $this->fs->file_exists(Argument::any())->willReturn(false);
        $this->fs->rmdir_rec('./entries/2017/03/02_1234')->willReturn(false);

        $publisher = new Publisher($this->blog->reveal(), $this->fs->reveal(), $this->wrappers->reveal());
        $publisher->keepEditHistory(false);
        $publisher->delete($entry);
    }

    /**
     * @expectedException   EntryDeleteFailed
     */
    public function testDelete_WhenTrackingHistoryAndRenameFails_Throws() {
        $path = './entries/2017/03/02_1234/entry.xml';
        $new_path = './entries/2017/03/02_1234/02_123400.xml';
        $entry = new BlogEntry(null, $this->fs->reveal());
        $entry->file = $path;
        $this->fs->file_exists($path)->willReturn(true);
        $this->fs->file_exists(Argument::any())->willReturn(false);
        $this->fs->rename($path, $new_path)->willReturn(false);

        $publisher = new Publisher($this->blog->reveal(), $this->fs->reveal(), $this->wrappers->reveal());
        $publisher->keepEditHistory(true);
        $publisher->delete($entry, $this->getTestTime());
    }
    
    public function testDelete_WhenEntryIsDraft_DoesNotRaiseOnDeleteEvent() {
        $entry = $this->setUpTestDraftEntryForSuccessfulDelete();
        $event_stub = new PublisherEventTestingStub();
        EventRegister::instance()->addHandler('BlogEntry', 'OnDelete', $event_stub, 'eventHandler');

        $publisher = new Publisher($this->blog->reveal(), $this->fs->reveal(), $this->wrappers->reveal());
        $publisher->delete($entry, $this->getTestTime());

        $this->assertFalse($event_stub->has_been_called);
    }

    public function testDelete_WhenEntryIsDraft_DoesNotRaiseDeleteCompleteEvent() {
        $entry = $this->setUpTestDraftEntryForSuccessfulDelete();
        $event_stub = new PublisherEventTestingStub();
        EventRegister::instance()->addHandler('BlogEntry', 'DeleteComplete', $event_stub, 'eventHandler');

        $publisher = new Publisher($this->blog->reveal(), $this->fs->reveal(), $this->wrappers->reveal());
        $publisher->delete($entry, $this->getTestTime());

        $this->assertFalse($event_stub->has_been_called);
    }

    public function testDelete_WhenEntryIsPublished_RaisesOnDeleteEvent() {
        $entry = $this->setUpTestPublishedEntryForSuccessfulDelete();
        $event_stub = new PublisherEventTestingStub();
        EventRegister::instance()->addHandler('BlogEntry', 'OnDelete', $event_stub, 'eventHandler');

        $publisher = new Publisher($this->blog->reveal(), $this->fs->reveal(), $this->wrappers->reveal());
        $publisher->delete($entry, $this->getTestTime());

        $this->assertTrue($event_stub->has_been_called);
    }

    public function testDelete_WhenEntryIsPublished_RaisesDeleteCompleteEvent() {
        $entry = $this->setUpTestPublishedEntryForSuccessfulDelete();
        $event_stub = new PublisherEventTestingStub();
        EventRegister::instance()->addHandler('BlogEntry', 'DeleteComplete', $event_stub, 'eventHandler');

        $publisher = new Publisher($this->blog->reveal(), $this->fs->reveal(), $this->wrappers->reveal());
        $publisher->delete($entry, $this->getTestTime());

        $this->assertTrue($event_stub->has_been_called);
    }

    public function testDelete_WhenEntryIsPublishedAsArticle_RaisesArticleOnDeleteEvent() {
        $entry = $this->setUpTestArticleForSuccessfulDelete();
        $event_stub = new PublisherEventTestingStub();
        EventRegister::instance()->addHandler('Article', 'OnDelete', $event_stub, 'eventHandler');

        $publisher = new Publisher($this->blog->reveal(), $this->fs->reveal(), $this->wrappers->reveal());
        $publisher->delete($entry, $this->getTestTime());

        $this->assertTrue($event_stub->has_been_called);

    }

    public function testDelete_WhenEntryIsPublisehedAsArticle_RaisesArticleDeleteCompleteEvent() {
        $entry = $this->setUpTestArticleForSuccessfulDelete();
        $event_stub = new PublisherEventTestingStub();
        EventRegister::instance()->addHandler('Article', 'DeleteComplete', $event_stub, 'eventHandler');

        $publisher = new Publisher($this->blog->reveal(), $this->fs->reveal(), $this->wrappers->reveal());
        $publisher->delete($entry, $this->getTestTime());

        $this->assertTrue($event_stub->has_been_called);
    }
     
    /******** Test Fixtures ********/

    protected function setUp() {
        Path::$sep = '/';
        $this->prophet = new \Prophecy\Prophet();

        $this->blog = $this->prophet->prophesize('Blog');
        $this->blog->home_path = '.';

        $this->fs = $this->prophet->prophesize('FS');

        $this->wrappers = $this->prophet->prophesize('WrapperGenerator');

        EventRegister::instance()->clearAll();
    }

    protected function tearDown() {
        Path::$sep = DIRECTORY_SEPARATOR;
        $this->prophet->checkPredictions();
    }

    private function getTestTime() {
        return new DateTime('2017-01-02 12:34:00'); 
    }
    
    private function setUpTestDraftEntryForSuccessfulSave() {
        return $this->setUpEntryForSuccessfulSave('./drafts/02_1234/entry.xml');
    }

    private function setUpTestPublishedEntryForSuccessfulSave() {
        return $this->setUpEntryForSuccessfulSave('./entries/2017/01/02_1234/entry.xml');
    }

    private function setUpTestArticleForSuccessfulSave() {
        return $this->setUpEntryForSuccessfulSave('./content/some_stuff/entry.xml');
    }

    private function setUpEntryForSuccessfulSave($path) {
        $entry = new BlogEntry(null, $this->fs->reveal());
        $entry->file = $path;
        $this->fs->file_exists($path)->willReturn(true);
        $this->fs->write_file($path, Argument::any())->willReturn(true);
        return $entry;
    }

    private function setUpTestDraftEntryForSuccessfulDelete() {
        return $this->setUpEntryForSuccessfulDelete('./drafts/02_1234/entry.xml');
    }

    private function setUpTestPublishedEntryForSuccessfulDelete() {
        return $this->setUpEntryForSuccessfulDelete('./entries/2017/01/02_1234/entry.xml');
    }

    private function setUpTestArticleForSuccessfulDelete() {
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
