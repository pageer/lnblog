<?php
use Prophecy\Argument;

class PublishEntryTest extends PublisherTestBase {

    public function testPublishEntry_WhenEntryDoesNotExists_SaveAsDraftAndMovesDirectory() {
        $fs = $this->fs;
        $entry = new BlogEntry(null, $fs->reveal());
        $fs->file_exists('./drafts/02_1234/entry.xml')->willReturn(false);
        $this->fs->file_exists("./drafts/02_1234/publish.txt")->willReturn(false);
        $fs->is_dir(Argument::any())->willReturn(false);

        $fs->mkdir_rec('./drafts/02_1234')->willReturn(true)->shouldBeCalled();
        $fs->write_file('./drafts/02_1234/entry.xml', Argument::any())->shouldBeCalled()->will(function($args) use ($fs) {
            $fs->file_exists('./drafts/02_1234/entry.xml')->willReturn(true);
            return true; 
        });
        $fs->rename('./drafts/02_1234', './entries/2017/01/02_1234')->will(function($args) use ($fs) {
            $fs->write_file('./entries/2017/01/02_1234/entry.xml', Argument::any())->willReturn(true)->shouldBeCalled();
            $fs->file_exists('./entries/2017/01/02_1234/entry.xml')->willReturn(true);
            return true;
        })->shouldBeCalled();

        $this->publisher->publishEntry($entry, $this->getTestTime());
    }

    public function testPublishEntry_WhenSavingDraft_SavesUploadOnlyOnce() {
        $fs = $this->fs;
        $entry = new BlogEntry(null, $fs->reveal());
        $event_stub = $this->setUpForMultipleUploadSuccess();
        $path = './drafts/02_1234/entry.xml';
        $fs->file_exists($path)->willReturn(false);
        $fs->realpath($path)->willReturn($path);
        $this->fs->file_exists("./drafts/02_1234/publish.txt")->willReturn(false);
        $fs->is_dir(Argument::any())->willReturn(false);

        $fs->mkdir_rec('./drafts/02_1234')->willReturn(true)->shouldBeCalled();
        $fs->write_file('./drafts/02_1234/entry.xml', Argument::any())->shouldBeCalled()->will(function($args) use ($fs) {
            $new_path = './drafts/02_1234/entry.xml';
            $fs->file_exists($new_path)->willReturn(true);
            $fs->realpath($new_path)->willReturn($new_path);
            return true; 
        });
        $fs->rename('./drafts/02_1234', './entries/2017/01/02_1234')->will(function($args) use ($fs) {
            $fs->write_file('./entries/2017/01/02_1234/entry.xml', Argument::any())->willReturn(true);
            $fs->file_exists('./entries/2017/01/02_1234/entry.xml')->willReturn(true);
            return true;
        });

        $this->publisher->publishEntry($entry, $this->getTestTime());

        $this->assertEquals(1, $event_stub->call_count);
    }

    public function testPublishEntry_WhenPublishTargetDirAlreadyExists_AddsSecondsToTargetPath() {
        $fs = $this->fs;
        $entry = new BlogEntry(null, $fs->reveal());
        $entry->file = './drafts/02_1234/entry.xml';
        $fs->file_exists('./drafts/02_1234/entry.xml')->willReturn(true);
        $fs->is_dir(Argument::any())->willReturn(true);
        $fs->is_dir('./entries/2017/01/02_123400')->willReturn(false);

        $fs->rename('./drafts/02_1234', './entries/2017/01/02_123400')->will(function($args) use ($fs) {
            $fs->write_file('./entries/2017/01/02_123400/entry.xml', Argument::any())->willReturn(true)->shouldBeCalled();
            $fs->file_exists('./entries/2017/01/02_123400/entry.xml')->willReturn(true);
            return true;
        })->shouldBeCalled();

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

        $this->assertTrue($event_stub->has_been_called);
    }

    public function testPublishEntry_WhenPublishSucceeds_RaisesInsertCompleteEvent() {
        $entry = $this->setUpDraftEntryForSuccessfulPublish();
        $event_stub = new PublisherEventTestingStub();
        EventRegister::instance()->addHandler('BlogEntry', 'InsertComplete', $event_stub, 'eventHandler');

        $this->publisher->publishEntry($entry, $this->getTestTime());

        $this->assertTrue($event_stub->has_been_called);
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

    public function testPublishEntry_WhenSuccessful_UpdateBlogTagList() {
        $entry = $this->setUpDraftEntryForSuccessfulPublish();
        $entry->tags = 'foo,bar';
        $this->user->username()->willReturn('billybob');

        $this->blog->updateTagList(['foo', 'bar'])->shouldBeCalled();
        $this->publisher->publishEntry($entry, $this->getTestTime());
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

    public function testPublishEntry_WhenTextHasLinksAndSendPingbacks_SendsPingbacks() {
        $entry = $this->setUpDraftEntryForSuccessfulPublish();
        $entry->data = 'This is a <a href="http://www.example.com/test">link</a> thing.';
        $entry->has_html = MARKUP_HTML;
        $entry->file = './drafts/02_1234/entry.xml';
        $entry->send_pingback = true;
        $this->http_client->fetchUrl('http://www.example.com/test', true)->willReturn("X-Pingback: http://www.example.com/ping\r\nContent-Type: text/html");
        $this->http_client->fetchUrl('http://www.example.com/test')->willReturn("<link rel=\"pingback\" href=\"http://www.example.com/ping\">");

        $this->http_client->sendXmlRpcMessage('www.example.com', '/ping', 80, Argument::any())->shouldBeCalled();
        
        $this->publisher->publishEntry($entry, $this->getTestTime());
    }

    public function testPublishEntry_WhenSendPingbacksOff_DoesNotSendPingbacks() {
        $entry = $this->setUpDraftEntryForSuccessfulPublish();
        $entry->data = 'This is a <a href="http://www.example.com/test">link</a> thing.';
        $entry->has_html = MARKUP_HTML;
        $entry->file = './drafts/02_1234/entry.xml';
        $entry->send_pingback = false;
        $this->http_client->fetchUrl('http://www.example.com/test', true)->willReturn("X-Pingback: http://www.example.com/ping\r\nContent-Type: text/html");

        $this->http_client->sendXmlRpcMessage('www.example.com', '/ping', 80, Argument::any())->shouldNotBeCalled();
        
        $this->publisher->publishEntry($entry, $this->getTestTime());
    }

    public function testPublishEntry_WhenSendingPingbacks_RaisesPingbackCompleteEventWithResponseData() {
        $ping_results = array(array('uri' => 'http://example.com/test', 'response' => 'test'));
        $entry = $this->setUpDraftEntryForSuccessfulPublish();
        $event_stub = new PublisherEventTestingStub();
        EventRegister::instance()->addHandler('BlogEntry', 'PingbackComplete', $event_stub, 'eventHandler');
        $entry->data = 'This is a <a href="http://example.com/test">link</a> thing.';
        $entry->has_html = MARKUP_HTML;
        $entry->file = './drafts/02_1234/entry.xml';
        $entry->send_pingback = true;
        $this->http_client->fetchUrl('http://example.com/test', true)->willReturn("X-Pingback: http://example.com/ping\r\nContent-Type: text/html");
        $this->http_client->fetchUrl('http://example.com/test')->willReturn("<link rel=\"pingback\" href=\"http://example.com/ping\">");
        $this->http_client->sendXmlRpcMessage('example.com', '/ping', 80, Argument::any())->willReturn('test');

        $this->publisher->publishEntry($entry, $this->getTestTime());

        $this->assertTrue($event_stub->has_been_called);
        $this->assertEquals($ping_results, $event_stub->event_data);
    }

    public function testPublishEntry_WhenNoPingbacksSent_DoesNotRaisePingbackCompleteEvent() {
        $ping_results = array(array('uri' => 'http://example.com/test', 'response' => 'test'));
        $entry = $this->setUpDraftEntryForSuccessfulPublish();
        $event_stub = new PublisherEventTestingStub();
        EventRegister::instance()->addHandler('BlogEntry', 'PingbackComplete', $event_stub, 'eventHandler');
        $entry->send_pingback = false;

        $this->publisher->publishEntry($entry, $this->getTestTime());

        $this->assertFalse($event_stub->has_been_called);
    }

    public function testPublishEntry_NoUploadsPresent_DoesNotRaisesUploadSuccessOrErrorEvents() {
        $entry = $this->setUpDraftEntryForSuccessfulPublish();
        $event_stub = new PublisherEventTestingStub();
        EventRegister::instance()->addHandler('BlogEntry', 'UploadSuccess', $event_stub, 'eventHandler');
        EventRegister::instance()->addHandler('BlogEntry', 'UploadError', $event_stub, 'eventHandler');

        $this->publisher->publishEntry($entry, $this->getTestTime());

        $this->assertFalse($event_stub->has_been_called);
    }

    public function testPublishEntry_SingleUploadsSucceed_RaisesUploadSuccessEvent() {
        $entry = $this->setUpDraftEntryForSuccessfulPublish();
        $event_stub = $this->setUpForSingleUploadSuccess();

        $this->publisher->publishEntry($entry, $this->getTestTime());

        $this->assertTrue($event_stub->has_been_called);
    }

    public function testPublishEntry_MultipleUploadsSucceed_RaisesUploadSuccessEvent() {
        $entry = $this->setUpDraftEntryForSuccessfulPublish();
        $event_stub = $this->setUpForMultipleUploadSuccess();

        $this->publisher->publishEntry($entry, $this->getTestTime());

        $this->assertTrue($event_stub->has_been_called);
    }

    public function testPublishEntry_SingleUploadMoveFails_RaisesUploadErrorEvent() {
        $entry = $this->setUpDraftEntryForSuccessfulPublish();
        $event_stub = $this->setUpForSingleUploadMoveFail(); $this->fs->is_file('/tmp/foo')->willReturn(true);

        $this->publisher->publishEntry($entry, $this->getTestTime());

        $this->assertTrue($event_stub->has_been_called);
        $this->assertEquals(array("Error moving uploaded file"), $event_stub->event_data);
    }

    public function testPublishEntry_SingleUploadBadStatus_RaisesUploadErrorEvent() {
        $entry = $this->setUpDraftEntryForSuccessfulPublish();
        $event_stub = $this->setUpForSingleUploadStatusFail(UPLOAD_ERR_PARTIAL);

        $this->publisher->publishEntry($entry, $this->getTestTime());

        $this->assertTrue($event_stub->has_been_called);
        $this->assertEquals(array("File only partially uploaded."), $event_stub->event_data);
    }

    private function setUpDraftEntryForSuccessfulPublish() {
        $entry = new BlogEntry(null, $this->fs->reveal());
        $entry->file = './drafts/02_1234/entry.xml';
        $fs = $this->fs;
        $fs->file_exists(Argument::any())->willReturn(false);
        $fs->file_exists('./drafts/02_1234/entry.xml')->willReturn(true);
        $fs->realpath($entry->file)->willReturn($entry->file);
        $fs->is_dir(Argument::any())->willReturn(true);
        $fs->is_dir('./entries/2017/01/02_1234')->willReturn(false);
        $fs->rename(Argument::any(), Argument::any())->will(function($args) use ($fs) {
            $fs->write_file(Argument::any(), Argument::any())->willReturn(true)->shouldBeCalled();
            $published_path = './entries/2017/01/02_1234/entry.xml';
            $fs->file_exists($published_path)->willReturn(true);
            $fs->realpath($published_path)->willReturn($published_path);
            return true;
        })->shouldBeCalled();
        return $entry;
    }

}
