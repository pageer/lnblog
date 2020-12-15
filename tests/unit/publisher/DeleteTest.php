<?php
use Prophecy\Argument;

class DeleteTest extends PublisherTestBase
{

    public function testDelete_WhenEntryDoesNotExist_Throws() {
        $entry =  new BlogEntry(null, $this->fs->reveal());

        $this->expectException(EntryDoesNotExist::class);

        $this->publisher->delete($entry, $this->getTestTime());
    }

    public function testDelete_WhenEntryHasPrettyPermalink_DeletesLink() {
        $path = './entries/2017/03/02_1234/entry.xml';
        $link_path = './entries/2017/03/Some_Stuff.php';
        $entry = new BlogEntry(null, $this->fs->reveal());
        $entry->subject = 'Some Stuff';
        $entry->permalink_name = 'Some_Stuff.php';
        $entry->file = $path;
        $this->fs->rmdir_rec(Argument::any())->willReturn(true);
        $this->fs->scandir(Argument::any())->willReturn(array());
        $this->fs->file_exists($path)->willReturn(true);
        $this->fs->realpath($path)->willReturn($path);
        $this->fs->file_exists($link_path)->willReturn(true);

        $this->fs->delete($link_path)->shouldBeCalled();

        $this->publisher->keepEditHistory(false);
        $this->publisher->delete($entry);
    }

    public function testDelete_WhenEntryHasMultiplePermalinks_DeletesAllLinks() {
        $path = './entries/2017/03/02_1234/entry.xml';
        $link_path = './entries/2017/03/Some_Stuff.php';
        $old_link_path1 = './entries/2017/03/Other_Stuff.php';
        $old_link_path2 = './entries/2017/03/Old_Stuff.php';
        $entry = new BlogEntry(null, $this->fs->reveal());
        $entry->subject = 'Some Stuff';
        $entry->file = $path;
        $entry->permalink_name = 'Some_Stuff.php';
        $this->fs->rmdir_rec(Argument::any())->willReturn(true);
        $this->fs->file_exists($path)->willReturn(true);
        $this->fs->realpath($path)->willReturn($path);
        $this->fs->file_exists($link_path)->willReturn(true);
        $this->fs->file_exists($old_link_path1)->willReturn(true);
        $this->fs->file_exists($old_link_path2)->willReturn(true);
        $this->fs->is_file($link_path)->willReturn(true);
        $this->fs->is_file($old_link_path1)->willReturn(true);
        $this->fs->is_file($old_link_path2)->willReturn(true);
        $path = "<?php \$entrypath = dirname(__FILE__).DIRECTORY_SEPARATOR.'02_1234'.DIRECTORY_SEPARATOR; chdir(\$entrypath); include \$entrypath.'index.php';";
        $other_path = "<?php \$entrypath = dirname(__FILE__).DIRECTORY_SEPARATOR.'02_4534'.DIRECTORY_SEPARATOR; chdir(\$entrypath); include \$entrypath.'index.php';";
        $this->fs->read_file($link_path)->willReturn($path);
        $this->fs->read_file($old_link_path1)->willReturn($path);
        $this->fs->read_file($old_link_path2)->willReturn($other_path);
        $this->fs->scandir('./entries/2017/03')->willReturn(
            array(
            '.',
            '..',
            'Old_Stuff.php',
            'Other_Stuff.php',
            'Some_Stuff.php',
            '02_1234',
            )
        );

        $this->fs->delete($link_path)->shouldBeCalled();
        $this->fs->delete($old_link_path1)->shouldBeCalled();

        $this->publisher->keepEditHistory(false);
        $this->publisher->delete($entry);
    }

    public function testDelete_WhenNotTrackingHistory_DeletesEntryDirectory() {
        $path = './entries/2017/03/02_1234/entry.xml';
        $entry = new BlogEntry(null, $this->fs->reveal());
        $entry->file = $path;
        $this->fs->scandir(Argument::any())->willReturn(array());
        $this->fs->file_exists($path)->willReturn(true);
        $this->fs->realpath($path)->willReturn($path);
        $this->fs->file_exists(Argument::any())->willReturn(false);

        $this->fs->rmdir_rec('./entries/2017/03/02_1234')->willReturn(true)->shouldBeCalled();

        $this->publisher->keepEditHistory(false);
        $this->publisher->delete($entry);
    }

    public function testDelete_WhenTrackingHistory_RenamesEntryFile() {
        $time = new DateTime('2017-01-02 12:34:00');
        $path = './entries/2017/03/02_1234/entry.xml';
        $entry = new BlogEntry(null, $this->fs->reveal());
        $entry->file = $path;
        $this->fs->scandir(Argument::any())->willReturn(array());
        $this->fs->file_exists($path)->willReturn(true);
        $this->fs->realpath($path)->willReturn($path);
        $this->fs->file_exists(Argument::any())->willReturn(false);

        $this->fs->rename($path, './entries/2017/03/02_1234/02_123400.xml')->willReturn(true)->shouldBeCalled();

        $this->publisher->keepEditHistory(true);
        $this->publisher->delete($entry, $time);
    }

    public function testDelete_WhenNotTrackingHistoryAndDeleteFails_Throws() {
        $path = './entries/2017/03/02_1234/entry.xml';
        $entry = new BlogEntry(null, $this->fs->reveal());
        $entry->file = $path;
        $this->fs->scandir(Argument::any())->willReturn(array());
        $this->fs->file_exists($path)->willReturn(true);
        $this->fs->realpath($path)->willReturn($path);
        $this->fs->file_exists(Argument::any())->willReturn(false);
        $this->fs->rmdir_rec('./entries/2017/03/02_1234')->willReturn(false);

        $this->expectException(EntryDeleteFailed::class);

        $this->publisher->keepEditHistory(false);
        $this->publisher->delete($entry);
    }

    public function testDelete_WhenTrackingHistoryAndRenameFails_Throws() {
        $path = './entries/2017/03/02_1234/entry.xml';
        $new_path = './entries/2017/03/02_1234/02_123400.xml';
        $entry = new BlogEntry(null, $this->fs->reveal());
        $entry->file = $path;
        $this->fs->scandir(Argument::any())->willReturn(array());
        $this->fs->file_exists($path)->willReturn(true);
        $this->fs->realpath($path)->willReturn($path);
        $this->fs->file_exists(Argument::any())->willReturn(false);
        $this->fs->rename($path, $new_path)->willReturn(false);

        $this->expectException(EntryDeleteFailed::class);

        $this->publisher->keepEditHistory(true);
        $this->publisher->delete($entry, $this->getTestTime());
    }

    public function testDelete_WhenEntryIsDraft_DoesNotRaiseOnDeleteEvent() {
        $entry = $this->setUpTestDraftEntryForSuccessfulDelete();
        $this->fs->scandir(Argument::any())->willReturn(array());
        $event_stub = new PublisherEventTestingStub();
        EventRegister::instance()->addHandler('BlogEntry', 'OnDelete', $event_stub, 'eventHandler');

        $this->publisher->delete($entry, $this->getTestTime());

        $this->assertFalse($event_stub->has_been_called);
    }

    public function testDelete_WhenEntryIsDraft_DoesNotRaiseDeleteCompleteEvent() {
        $entry = $this->setUpTestDraftEntryForSuccessfulDelete();
        $this->fs->scandir(Argument::any())->willReturn(array());
        $event_stub = new PublisherEventTestingStub();
        EventRegister::instance()->addHandler('BlogEntry', 'DeleteComplete', $event_stub, 'eventHandler');

        $this->publisher->delete($entry, $this->getTestTime());

        $this->assertFalse($event_stub->has_been_called);
    }

    public function testDelete_WhenEntryIsPublished_RaisesOnDeleteEvent() {
        $entry = $this->setUpTestPublishedEntryForSuccessfulDelete();
        $this->fs->scandir(Argument::any())->willReturn(array());
        $event_stub = new PublisherEventTestingStub();
        EventRegister::instance()->addHandler('BlogEntry', 'OnDelete', $event_stub, 'eventHandler');

        $this->publisher->delete($entry, $this->getTestTime());

        $this->assertTrue($event_stub->has_been_called);
    }

    public function testDelete_WhenEntryIsPublished_RaisesDeleteCompleteEvent() {
        $entry = $this->setUpTestPublishedEntryForSuccessfulDelete();
        $this->fs->scandir(Argument::any())->willReturn(array());
        $event_stub = new PublisherEventTestingStub();
        EventRegister::instance()->addHandler('BlogEntry', 'DeleteComplete', $event_stub, 'eventHandler');

        $this->publisher->delete($entry, $this->getTestTime());

        $this->assertTrue($event_stub->has_been_called);
    }

    public function testDelete_WhenEntryIsPublishedAsArticle_RaisesArticleOnDeleteEvent() {
        $entry = $this->setUpTestArticleForSuccessfulDelete();
        $this->fs->scandir(Argument::any())->willReturn(array());
        $event_stub = new PublisherEventTestingStub();
        EventRegister::instance()->addHandler('Article', 'OnDelete', $event_stub, 'eventHandler');

        $this->publisher->delete($entry, $this->getTestTime());

        $this->assertTrue($event_stub->has_been_called);
    }

    public function testDelete_WhenEntryIsPublisehedAsArticle_RaisesArticleDeleteCompleteEvent() {
        $entry = $this->setUpTestArticleForSuccessfulDelete();
        $this->fs->scandir(Argument::any())->willReturn(array());
        $event_stub = new PublisherEventTestingStub();
        EventRegister::instance()->addHandler('Article', 'DeleteComplete', $event_stub, 'eventHandler');

        $this->publisher->delete($entry, $this->getTestTime());

        $this->assertTrue($event_stub->has_been_called);
    }
}
