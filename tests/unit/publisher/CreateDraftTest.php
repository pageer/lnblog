<?php
use Prophecy\Argument;

class CreateDraftTest extends PublisherTestBase {

    public function testCreateDraft_WhenEntryDoesNotExist_WritesEntryDataToFile() {
        $entry = $this->getTestEntry();
        $this->fs->is_dir('./drafts')->willReturn(true);
        $this->fs->is_dir('./drafts/02_1234')->willReturn(false);
        $this->fs->file_exists(Argument::any())->willReturn(false);

        $this->fs->mkdir_rec('./drafts/02_1234')->willReturn(true)->shouldBeCalled();
        $this->fs->write_file('./drafts/02_1234/entry.xml', Argument::containingString('This is some text'))->willReturn(true)->shouldBeCalled();

        $this->publisher->createDraft($entry, $this->getTestTime());
    }

    public function testCreateDraft_WhenDraftCreated_SetsUserDateAndTimestamp() {
        $entry = $this->getTestEntry();
        $this->user->username()->willReturn('billybob');
        $this->fs->is_dir('./drafts')->willReturn(true);
        $this->fs->file_exists(Argument::any())->willReturn(false);
        $this->fs->mkdir_rec(Argument::any())->willReturn(true);
        $this->fs->write_file(Argument::any(), Argument::any())->willReturn(true);

        $this->publisher->createDraft($entry, $this->getTestTime());

        $this->assertEquals('billybob', $entry->uid);
        $this->assertEquals($this->getTestTime()->getTimestamp(), $entry->timestamp);
        $this->assertEquals('2017-01-02 12:34 Eastern Standard Time', $entry->date);
    }

    /**
     * @expectedException   EntryAlreadyExists
     */
    public function testCreateDraft_WhenDraftAlreadyExists_Throws() {
        $entry = $this->getTestEntry();
        $entry->file = './drafts/02_1234/entry.xml';
        $this->fs->is_dir('./drafts')->willReturn(true);
        $this->fs->file_exists('./drafts/02_1234/entry.xml')->willReturn(true);
        $this->fs->realpath('./drafts/02_1234/entry.xml')->willReturn('./drafts/02_1234/entry.xml');
        $this->fs->realpath('./drafts/02_1234/entry.xml')->willReturn('./drafts/02_1234/entry.xml');

        $this->publisher->createDraft($entry, $this->getTestTime());
    }

    public function testCreateDraft_WhenDraftDirDoesNotExist_CreatesWrappers() {
        $entry = $this->getTestEntry();
        $this->fs->is_dir('./drafts')->willReturn(false);
        $this->fs->file_exists(Argument::any())->willReturn(false);
        $this->fs->mkdir_rec(Argument::any())->willReturn(true);
        $this->fs->write_file(Argument::any(), Argument::any())->willReturn(true);

        $this->wrappers->createDirectoryWrappers('./drafts', BLOG_DRAFTS)->shouldBeCalled();

        $this->publisher->createDraft($entry, $this->getTestTime());
    }

    /**
     * @expectedException   CouldNotCreateDirectory
     */
    public function testCreateDraft_WhenDraftDirDoesNotExistAndWrapperCreationFails_Throws() {
        $entry = $this->getTestEntry();
        $this->fs->is_dir('./drafts')->willReturn(false);
        $this->fs->file_exists(Argument::any())->willReturn(false);
        $this->wrappers->createDirectoryWrappers('./drafts', BLOG_DRAFTS)->willReturn(array('Error!'));

        $this->publisher->createDraft($entry, $this->getTestTime());
    }

    /**
     * @expectedException   EntryWriteFailed
     */
    public function testCreateDraft_WhenFileCreationFails_Throws() {
        $entry = $this->getTestEntry();
        $this->fs->is_dir('./drafts')->willReturn(false);
        $this->fs->file_exists(Argument::any())->willReturn(false);
        $this->fs->mkdir_rec('./drafts/02_1234')->willReturn(true);
        $this->fs->write_file('./drafts/02_1234/entry.xml', Argument::any())->willReturn(false);

        $this->publisher->createDraft($entry, $this->getTestTime());
    }

    /**
     * @expectedException   EntryWriteFailed
     */
    public function testCreateDraft_WhenDirectoryCreationFails_Throws() {
        $entry = $this->getTestEntry();
        $this->fs->is_dir('./drafts')->willReturn(false);
        $this->fs->file_exists(Argument::any())->willReturn(false);
        $this->fs->mkdir_rec('./drafts/02_1234')->willReturn(false);

        $this->publisher->createDraft($entry, $this->getTestTime());
    }

    public function testCreateDraft_WhenAutoPublishSet_WriteAutoPublishFile() {
        $entry = $this->getTestEntry();
        $entry->autopublish = true;
        $entry->autopublish_date = '2525-01-02 12:00:00';
        $fs = $this->fs;
        $fs->is_dir('./drafts')->willReturn(true);
        $fs->is_dir('./drafts/02_1234')->willReturn(false);
        $fs->file_exists(Argument::any())->willReturn(false);
        $fs->mkdir_rec('./drafts/02_1234')->willReturn(true);
        $fs->write_file('./drafts/02_1234/entry.xml', Argument::any())->will(function($args) use ($fs) {
            $fs->file_exists('./drafts/02_1234/entry.xml')->willReturn(true);
            $fs->realpath('./drafts/02_1234/entry.xml')->willReturn('./drafts/02_1234/entry.xml');
            return true;
        });

        $this->fs->write_file('./drafts/02_1234/publish.txt', '2525-01-02 12:00:00')->shouldBeCalled();

        $this->publisher->createDraft($entry, $this->getTestTime());
    }

    public function testCreateDraft_NoUploadsPresent_DoesNotRaisesUploadSuccessOrErrorEvents() {
        $entry = $this->getTestEntryForUpload();
        $event_stub = new PublisherEventTestingStub();
        EventRegister::instance()->addHandler('BlogEntry', 'UploadSuccess', $event_stub, 'eventHandler');
        EventRegister::instance()->addHandler('BlogEntry', 'UploadError', $event_stub, 'eventHandler');

        $this->publisher->createDraft($entry, $this->getTestTime());

        $this->assertFalse($event_stub->has_been_called);
    }

    public function testCreateDraft_SingleUploadsSucceed_RaisesUploadSuccessEvent() {
        $entry = $this->getTestEntryForUpload();
        $event_stub = $this->setUpForSingleUploadSuccess('./drafts/02_1234');

        $this->publisher->createDraft($entry, $this->getTestTime());

        $this->assertTrue($event_stub->has_been_called);
    }

    public function testCreateDraft_MultipleUploadsSucceed_RaisesUploadSuccessEvent() {
        $entry = $this->getTestEntryForUpload();
        $event_stub = $this->setUpForMultipleUploadSuccess();

        $this->publisher->createDraft($entry, $this->getTestTime());

        $this->assertTrue($event_stub->has_been_called);
    }

    public function testCreateDraft_SingleUploadMoveFails_RaisesUploadErrorEvent() {
        $entry = $this->getTestEntryForUpload();
        $event_stub = $this->setUpForSingleUploadMoveFail('./drafts/02_1234');
        $this->fs->is_file('/tmp/foo')->willReturn(true);

        $this->publisher->createDraft($entry, $this->getTestTime());

        $this->assertTrue($event_stub->has_been_called);
        $this->assertEquals(array("Error moving uploaded file"), $event_stub->event_data);
    }

    public function testCreateDraft_SingleUploadBadStatus_RaisesUploadErrorEvent() {
        $entry = $this->getTestEntryForUpload();
        $event_stub = $this->setUpForSingleUploadStatusFail(UPLOAD_ERR_PARTIAL);

        $this->publisher->createDraft($entry, $this->getTestTime());

        $this->assertTrue($event_stub->has_been_called);
        $this->assertEquals(array("File only partially uploaded."), $event_stub->event_data);
    }

    private function getTestEntry() {
        $entry = new BlogEntry("", $this->fs->reveal());
        $entry->body = "This is some text";
        return $entry;
    }

    private function getTestEntryForUpload() {
        $fs = $this->fs;
        $fs->is_dir('./drafts')->willReturn(true);
        $fs->is_dir('./drafts/02_1234')->willReturn(false);
        $fs->file_exists(Argument::any())->willReturn(false);

        $fs->mkdir_rec('./drafts/02_1234')->willReturn(true)->shouldBeCalled();
        $fs->write_file('./drafts/02_1234/entry.xml', Argument::any())->will(function($args) use ($fs) {
            $fs->file_exists('./drafts/02_1234/entry.xml')->willReturn(true);
            $fs->realpath('./drafts/02_1234/entry.xml')->willReturn('./drafts/02_1234/entry.xml');
            return true;
        });

        $entry = new BlogEntry(null, $this->fs->reveal());
        $entry->body = "This is some text";
        return $entry;
    }

}
