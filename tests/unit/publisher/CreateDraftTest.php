<?php
use Prophecy\Argument;

class CreateDraftTest extends PublisherTestBase {
    
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

        $this->publisher->createDraft($entry, $time);
    }

    public function testCreateDraft_WhenDraftCreated_SetsUserDateAndTimestamp() {
        $time = new DateTime('2017-01-02 12:34:00');
        $entry = new BlogEntry();
        $entry->body = "This is some text";
        $this->user->username()->willReturn('billybob');
        $this->fs->is_dir('./drafts')->willReturn(true);
        $this->fs->file_exists(Argument::any())->willReturn(false);
        $this->fs->mkdir_rec(Argument::any())->willReturn(true);
        $this->fs->write_file(Argument::any(), Argument::any())->willReturn(true);

        $this->publisher->createDraft($entry, $time);

        $this->assertEquals('billybob', $entry->uid);
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

        $this->publisher->createDraft($entry, $time);
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

        $this->publisher->createDraft($entry, $time);
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

        $this->publisher->createDraft($entry, $time);
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

        $this->publisher->createDraft($entry, $time);
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

        $this->publisher->createDraft($entry, $time);
    }

}
