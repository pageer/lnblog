<?php

use Prophecy\Argument;

class UnpublishTest extends PublisherTestBase
{

    public function testUnpublish_WhenNoConflictingDraft_MoveEntryToDraftWithPostTimestamp() {
        $entry = new BlogEntry('', $this->fs->reveal());
        $entry->file = './content/some_stuff/entry.xml';
        $entry->post_ts = strtotime('2017-01-02 12:34:00');
        $this->fs->file_exists('./content/some_stuff/entry.xml')->willReturn(true);
        $this->fs->realpath($entry->file)->willReturn($entry->file);
        $this->fs->is_dir('./drafts/02_123400')->willReturn(false);

        $this->fs->rename('./content/some_stuff', './drafts/02_123400')->willReturn(true)->shouldBeCalled();

        $this->publisher->unpublish($entry);
    }

    public function testUnpublish_WhenEntryDoesNotExist_Throws() {
        $entry = new BlogEntry('', $this->fs->reveal());
        $this->fs->file_exists(Argument::any())->willReturn(false);

        $this->expectException(EntryDoesNotExist::class);

        $this->publisher->unpublish($entry);
    }

    public function testUnpublish_WhenEntryIsNotPublished_Throws() {
        $entry = new BlogEntry('', $this->fs->reveal());
        $entry->file = "./drafts/02_1234/entry.xml";
        $this->fs->file_exists('./drafts/02_1234/entry.xml')->willReturn(true);
        $this->fs->realpath($entry->file)->willReturn($entry->file);

        $this->expectException(EntryIsNotPublished::class);

        $this->publisher->unpublish($entry);
    }

    public function testUnpublish_WhenRenameFails_Throws() {
        $entry = new BlogEntry('', $this->fs->reveal());
        $entry->file = './content/some_stuff/entry.xml';
        $entry->post_ts = strtotime('2017-01-02 12:34:00');
        $this->fs->file_exists($entry->file)->willReturn($entry->file);
        $this->fs->realpath($entry->file)->willReturn($entry->file);
        $this->fs->is_dir('./drafts/02_123400')->willReturn(false);
        $this->fs->rename('./content/some_stuff', './drafts/02_123400')->willReturn(false);

        $this->expectException(EntryRenameFailed::class);

        $this->publisher->unpublish($entry);
    }

    public function testUnpublish_WhenRenameSucceeds_RemovesDirectoryWrappers() {
        $entry = new BlogEntry('', $this->fs->reveal());
        $entry->file = './content/some_stuff/entry.xml';
        $entry->post_ts = strtotime('2017-01-02 12:34:00');
        $this->fs->file_exists('./content/some_stuff/entry.xml')->willReturn(true);
        $this->fs->realpath($entry->file)->willReturn($entry->file);
        $this->fs->is_dir('./drafts/02_123400')->willReturn(false);
        $this->fs->rename('./content/some_stuff', './drafts/02_123400')->willReturn(true);

        $this->wrappers->removeForEntry($entry)->shouldBeCalled();

        $this->publisher->unpublish($entry);
    }

    public function testUnpublish_WhenRenameSucceeds_RemovesPrettyPermalink() {
        $entry = new BlogEntry('', $this->fs->reveal());
        $entry->subject = 'Test Entry';
        $entry->permalink_name = 'Test_Entry.php';
        $entry->file = './entries/2017/01/02_1234/entry.xml';
        $entry->post_ts = strtotime('2017-01-02 12:34:00');
        $this->fs->scandir(Argument::any())->willReturn(array());
        $this->fs->file_exists('./entries/2017/01/02_1234/entry.xml')->willReturn(true);
        $this->fs->realpath($entry->file)->willReturn($entry->file);
        $this->fs->file_exists('./entries/2017/01/Test_Entry.php')->willReturn(true);
        $this->fs->is_dir('./entries/2017/01/Test_Entry.php')->willReturn(false);
        $this->fs->is_dir('./drafts/02_123400')->willReturn(false);
        $this->fs->rename('./entries/2017/01/02_1234', './drafts/02_123400')->willReturn(true);

        $this->fs->delete('./entries/2017/01/Test_Entry.php')->willReturn(true)->shouldBeCalled();

        $this->publisher->unpublish($entry);
    }

    public function testUnpublish_WhenRenameSucceeds_RaisesOnDeleteEvent() {
        $entry = $this->setUpTestEntryForSuccessfulUnpublish();
        $event_stub = new PublisherEventTestingStub();
        EventRegister::instance()->addHandler('BlogEntry', 'OnDelete', $event_stub, 'eventHandler');

        $this->publisher->unpublish($entry);

        $this->assertTrue($event_stub->has_been_called);
    }

    public function testUnpublish_WhenRenameFails_DoesNotRaiseOnDeleteEvent() {
        $entry = $this->setUpTestEntryForEarlyFailedUnpublish();
        $event_stub = new PublisherEventTestingStub();
        EventRegister::instance()->addHandler('BlogEntry', 'OnDelete', $event_stub, 'eventHandler');

        try {
            $this->publisher->unpublish($entry);
        } catch (Exception $e) {
        }

        $this->assertFalse($event_stub->has_been_called);
    }
    
    public function testUnpublish_WhenRenameSucceeds_RaisesDeleteCompleteEvent() {
        $entry = $this->setUpTestEntryForSuccessfulUnpublish();
        $event_stub = new PublisherEventTestingStub();
        EventRegister::instance()->addHandler('BlogEntry', 'DeleteComplete', $event_stub, 'eventHandler');

        $this->publisher->unpublish($entry);

        $this->assertTrue($event_stub->has_been_called);
    }

    public function testUnpublish_WhenRenameFails_DoesNotRaiseDeleteComlpeteEvent() {
        $entry = $this->setUpTestEntryForLateFailedUnpublish();
        $event_stub = new PublisherEventTestingStub();
        EventRegister::instance()->addHandler('BlogEntry', 'DeleteComplete', $event_stub, 'eventHandler');

        try {
            $this->publisher->unpublish($entry);
        } catch (Exception $e) {
        }

        $this->assertFalse($event_stub->has_been_called);
    }

    /******** Utilities ********/

    private function setUpTestEntryForSuccessfulUnpublish() {
        $entry = new BlogEntry('', $this->fs->reveal());
        $entry->file = './content/some_stuff/entry.xml';
        $entry->post_ts = strtotime('2017-01-02 12:34:00');
        $this->fs->file_exists('./content/some_stuff/entry.xml')->willReturn(true);
        $this->fs->realpath($entry->file)->willReturn($entry->file);
        $this->fs->is_dir('./drafts/02_123400')->willReturn(false);
        $this->fs->rename('./content/some_stuff', './drafts/02_123400')->willReturn(true);
        return $entry;
    }

    private function setUpTestEntryForEarlyFailedUnpublish() {
        return $this->setUpTestEntryForFailedUnpublish(false, true);
    }

    private function setUpTestEntryForLateFailedUnpublish() {
        return $this->setUpTestEntryForFailedUnpublish(true, false);
    }

    private function setUpTestEntryForFailedUnpublish($file_exists, $rename_success) {
        $entry = new BlogEntry('', $this->fs->reveal());
        $entry->file = './content/some_stuff/entry.xml';
        $entry->post_ts = strtotime('2017-01-02 12:34:00');
        $this->fs->file_exists('./content/some_stuff/entry.xml')->willReturn($file_exists);
        $this->fs->file_exists('./content/some_stuff/current.htm')->willReturn($file_exists);
        $this->fs->realpath($entry->file)->willReturn($entry->file);
        $this->fs->rename('./content/some_stuff', './drafts/02_123400')->willReturn($rename_success);
        return $entry;
    }
}
