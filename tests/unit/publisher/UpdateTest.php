<?php
use Prophecy\Argument;

class UpdateTest extends PublisherTestBase {

    /******** Update ********/

    /**
     * @expectedException EntryDoesNotExist
     */
    public function testUpdate_WhenEntryDoesNotExist_Throws() {
        $entry = new BlogEntry();
        $entry->body = "This is some text";
        $this->fs->file_exists(Argument::any())->willReturn(false);

        $this->publisher->update($entry);
    }

    public function testUpdate_WhenNotTrackingHistory_JustWritesFile() {
        $path = './drafts/02_1234/entry.xml';
        $entry = new BlogEntry();
        $entry->file = $path;
        $entry->body = "This is some text";
        $this->fs->file_exists($path)->willReturn(true);

        $this->fs->rename(Argument::any())->shouldNotBeCalled();
        $this->fs->write_file($path, Argument::containingString('This is some text'))->willReturn(true)->shouldBeCalled();

        $this->publisher->keepEditHistory(false);
        $this->publisher->update($entry);
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

        $this->publisher->keepEditHistory(true);
        $this->publisher->update($entry, $time);
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

        $this->publisher->keepEditHistory(false);
        $this->publisher->update($entry);
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

        $this->publisher->keepEditHistory(true);
        $this->publisher->update($entry, $time);
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

        $this->publisher->keepEditHistory(true);
        $this->publisher->update($entry, $time);
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

        $this->publisher->keepEditHistory(false);
        $this->publisher->update($entry, $time);
    }

    public function testUpdate_WhenEntryIsNotPublished_DoesNotWritePrettyPermalinkFile() {
        $entry = $this->setUpTestDraftEntryForSuccessfulSave();
        $entry->subject = 'Some Stuff';

        $this->fs->write_file(Argument::containingString('Some_Stuff.php'), Argument::any())->shouldNotBeCalled();

        $this->publisher->keepEditHistory(false);
        $this->publisher->update($entry, $this->getTestTime());
    }

    public function testUpdate_WhenEntryIsPublishedAsArticle_DoesNotWritePrettyPermalinkFile() {
        $entry = $this->setUpTestArticleForSuccessfulSave();
        $entry->subject = 'Some Stuff';

        $this->fs->write_file(Argument::containingString('Some_Stuff.php'), Argument::any())->shouldNotBeCalled();

        $this->publisher->keepEditHistory(false);
        $this->publisher->update($entry, $this->getTestTime());
    }

    public function testUpdate_WhenEntryIsDraft_DoesNotRaiseOnUpdateEvent() {
        $entry = $this->setUpTestDraftEntryForSuccessfulSave();
        $event_stub = new PublisherEventTestingStub();
        EventRegister::instance()->addHandler('BlogEntry', 'OnUpdate', $event_stub, 'eventHandler');

        $this->publisher->update($entry, $this->getTestTime());

        $this->assertFalse($event_stub->has_been_called);
    }

    public function testUpdate_WhenEntryIsDraft_DoesNotRaiseUpdateCompleteEvent() {
        $entry = $this->setUpTestDraftEntryForSuccessfulSave();
        $event_stub = new PublisherEventTestingStub();
        EventRegister::instance()->addHandler('BlogEntry', 'UpdateComplete', $event_stub, 'eventHandler');

        $this->publisher->update($entry, $this->getTestTime());

        $this->assertFalse($event_stub->has_been_called);
    }

    public function testUpdate_WhenEntryIsPublished_RaisesOnUpdateEvent() {
        $entry = $this->setUpTestPublishedEntryForSuccessfulSave();
        $event_stub = new PublisherEventTestingStub();
        EventRegister::instance()->addHandler('BlogEntry', 'OnUpdate', $event_stub, 'eventHandler');

        $this->publisher->update($entry, $this->getTestTime());

        $this->assertTrue($event_stub->has_been_called);
    }

    public function testUpdate_WhenEntryIsPublished_RaisesUpdateCompleteEvent() {
        $entry = $this->setUpTestPublishedEntryForSuccessfulSave();
        $event_stub = new PublisherEventTestingStub();
        EventRegister::instance()->addHandler('BlogEntry', 'UpdateComplete', $event_stub, 'eventHandler');

        $this->publisher->update($entry, $this->getTestTime());

        $this->assertTrue($event_stub->has_been_called);
    }

    public function testUpdate_WhenEntryIsPublishedAsArticle_RaisesArticleOnUpdateEvent() {
        $entry = $this->setUpTestArticleForSuccessfulSave();
        $event_stub = new PublisherEventTestingStub();
        EventRegister::instance()->addHandler('Article', 'OnUpdate', $event_stub, 'eventHandler');

        $this->publisher->update($entry, $this->getTestTime());

        $this->assertTrue($event_stub->has_been_called);

    }

    public function testUpdate_WhenEntryIsPublisehedAsArticle_RaisesArticleUpdateCompleteEvent() {
        $entry = $this->setUpTestArticleForSuccessfulSave();
        $event_stub = new PublisherEventTestingStub();
        EventRegister::instance()->addHandler('Article', 'UpdateComplete', $event_stub, 'eventHandler');

        $this->publisher->update($entry, $this->getTestTime());

        $this->assertTrue($event_stub->has_been_called);
    }

}
