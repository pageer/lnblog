<?php
use Prophecy\Argument;

class PublishEntryTest extends PublisherTestBase {

    public function testPublishEntry_WhenEntryDoesNotExists_SaveAsDraftAndMovesDirectory() {
        $fs = $this->fs;
        $entry = new BlogEntry(null, $fs->reveal());
        $fs->file_exists('./drafts/02_1234/entry.xml')->willReturn(false);
        $fs->is_dir(Argument::any())->willReturn(false);

        $fs->mkdir_rec('./drafts/02_1234')->willReturn(true)->shouldBeCalled();
        $fs->write_file('./drafts/02_1234/entry.xml', Argument::any())->shouldBeCalled()->will(function($args) use ($fs) {
            $fs->file_exists('./drafts/02_1234/entry.xml')->willReturn(true);
            return true; 
        });
        $fs->rename('./drafts/02_1234', './entries/2017/01/02_1234')->willReturn(true)->shouldBeCalled();

        $this->publisher->publishEntry($entry, $this->getTestTime());
    }

    public function testPublishEntry_WhenPublishTargetDirAlreadyExists_AddsSecondsToTargetPath() {
        $fs = $this->fs;
        $entry = new BlogEntry(null, $fs->reveal());
        $entry->file = './drafts/02_1234/entry.xml';
        $fs->file_exists('./drafts/02_1234/entry.xml')->willReturn(true);
        $fs->is_dir(Argument::any())->willReturn(true);
        $fs->is_dir('./entries/2017/01/02_123400')->willReturn(false);

        $fs->rename('./drafts/02_1234', './entries/2017/01/02_123400')->willReturn(true)->shouldBeCalled();

        $this->publisher->publishEntry($entry, $this->getTestTime());
    }

    /**
     * @expectedException   TargetPathExists
     */
    public function testPublishEntry_WhenBothPublishTargetDirsAlreadyExists_Throws() {
        $fs = $this->fs;
        $entry = new BlogEntry(null, $fs->reveal());
        $entry->file = './drafts/02_1234/entry.xml';
        $fs->file_exists(Argument::any())->willReturn(true);
        $fs->is_dir(Argument::any())->willReturn(true);

        $this->publisher->publishEntry($entry, $this->getTestTime());
    }
 
    public function testPublishEntry_WhenPublishSucceeds_CreatePrettyPermalink() {
        $entry = $this->setUpDraftEntryForSuccessfulPublish();
        $entry->subject = 'Some Weird Stuff';
        $entry->file = './drafts/02_1234/entry.xml';

        $this->fs->write_file('./entries/2017/01/Some_Weird_Stuff.php', Argument::any())->willReturn(true)->shouldBeCalled();

        $this->publisher->publishEntry($entry, $this->getTestTime());
    }

    /**
     * @expectedException   EntryRenameFailed
     */
    public function testPublishEntry_WhenRenameFails_Throws() {
        $fs = $this->fs;
        $entry = new BlogEntry(null, $fs->reveal());
        $entry->file = './drafts/02_1234/entry.xml';
        $fs->file_exists(Argument::any())->willReturn(false);
        $fs->file_exists('./drafts/02_1234/entry.xml')->willReturn(true);
        $fs->is_dir(Argument::any())->willReturn(true);
        $fs->is_dir('./entries/2017/01/02_1234')->willReturn(false);
        $fs->rename('./drafts/02_1234', './entries/2017/01/02_1234')->willReturn(false);

        $this->publisher->publishEntry($entry, $this->getTestTime());
    }

    public function testPublishEntry_WhenPublishDirectoryAvailable_RaisesOnInsertEvent() {
        $entry = $this->setUpDraftEntryForSuccessfulPublish();

        $event_stub = new PublisherEventTestingStub();
        EventRegister::instance()->addHandler('BlogEntry', 'OnInsert', $event_stub, 'eventHandler');

        $this->publisher->publishEntry($entry, $this->getTestTime());
    }

    public function testPublishEntry_WhenPublishSucceeds_RaisesInsertCompleteEvent() {
        $entry = $this->setUpDraftEntryForSuccessfulPublish();

        $event_stub = new PublisherEventTestingStub();
        EventRegister::instance()->addHandler('BlogEntry', 'InsertComplete', $event_stub, 'eventHandler');

        $this->publisher->publishEntry($entry, $this->getTestTime());
    }

    public function testPublishEntry_WhenPublishSucceeds_SetsUserDatesAndIpOnEntry() {
        $_SERVER['REMOTE_ADDR'] = '1.2.3.4';
        $entry = $this->setUpDraftEntryForSuccessfulPublish();
        $this->user->username()->willReturn('billybob');

        $this->publisher->publishEntry($entry, $this->getTestTime());

        $expected_time = $this->getTestTime()->getTimestamp();
        $this->assertEquals('./entries/2017/01/02_1234/entry.xml', $entry->file);
        $this->assertEquals('billybob', $entry->uid);
        $this->assertEquals($expected_time, $entry->post_ts);
        $this->assertEquals($expected_time, $entry->timestamp);
        $this->assertContains('2017-01-02 12:34', $entry->date);
        $this->assertEquals('1.2.3.4', $entry->ip);
    }

    public function testPublishEntry_WhenPostDateIsAlreadySet_UpdatesPostDateToPublicationDate() {
        $entry = $this->setUpDraftEntryForSuccessfulPublish();
        $this->user->username()->willReturn('billybob');
        $entry->post_ts = 123456789;

        $this->publisher->publishEntry($entry, $this->getTestTime());

        $expected_time = $this->getTestTime()->getTimestamp();
        $this->assertEquals($expected_time, $entry->post_ts);
    }

    public function testPublishEntry_WhenYearDirDoesNotExist_CreatesWithWrappers() {
        $entry = $this->setUpDraftEntryForSuccessfulPublish();
        $this->fs->is_dir('./entries/2017')->willReturn(false);
        $this->fs->is_dir('./entries/2017/01')->willReturn(false);

        $this->wrappers->createDirectoryWrappers('./entries/2017', YEAR_ENTRIES);
        $this->wrappers->createDirectoryWrappers('./entries/2017/01', MONTH_ENTRIES);

        $this->publisher->publishEntry($entry, $this->getTestTime());
    }

    public function testPublishEntry_WhenRenameSucceeds_CreatesWrappers() {
        $entry = $this->setUpDraftEntryForSuccessfulPublish();

        $this->wrappers->createDirectoryWrappers('./entries/2017/01/02_1234', ENTRY_BASE);
        $this->wrappers->createDirectoryWrappers('./entries/2017/01/02_1234/comments', ENTRY_COMMENTS);
        $this->wrappers->createDirectoryWrappers('./entries/2017/01/02_1234/trackbacks', ENTRY_TRACKBACKS);
        $this->wrappers->createDirectoryWrappers('./entries/2017/01/02_1234/pingbacks', ENTRY_PINGBACKS);

       $this->publisher->publishEntry($entry, $this->getTestTime());
    }

    private function setUpDraftEntryForSuccessfulPublish() {
        $entry = new BlogEntry(null, $this->fs->reveal());
        $entry->file = './drafts/02_1234/entry.xml';
        $this->fs->file_exists(Argument::any())->willReturn(false);
        $this->fs->file_exists('./drafts/02_1234/entry.xml')->willReturn(true);
        $this->fs->is_dir(Argument::any())->willReturn(true);
        $this->fs->is_dir('./entries/2017/01/02_1234')->willReturn(false);
        $this->fs->rename(Argument::any(), Argument::any())->willReturn(true);
        return $entry;
    }

}
