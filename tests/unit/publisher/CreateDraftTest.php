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

    private function getTestEntry() {
        $entry = new BlogEntry("", $this->fs->reveal());
        $entry->body = "This is some text";
        return $entry;
    }

}
