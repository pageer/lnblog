<?php
use Prophecy\Argument;

class UpdateTest extends PublisherTestBase {

    /**
     * @expectedException EntryDoesNotExist
     */
    public function testUpdate_WhenEntryDoesNotExist_Throws() {
        $entry = new BlogEntry("", $this->fs->reveal());
        $entry->body = "This is some text";
        $this->fs->file_exists(Argument::any())->willReturn(false);

        $this->publisher->update($entry);
    }

    public function testUpdate_WhenNotTrackingHistory_WritesFileButDoesNotRename() {
        $path = './drafts/02_1234/entry.xml';
        $entry = new BlogEntry("", $this->fs->reveal());
        $entry->file = $path;
        $entry->body = "This is some text";
        $this->fs->file_exists($path)->willReturn(true);
        $this->fs->realpath($path)->willReturn($path);
        $this->fs->file_exists("./drafts/02_1234/publish.txt")->willReturn(false);

        $this->fs->rename(Argument::any())->shouldNotBeCalled();
        $this->fs->write_file($path, Argument::containingString('This is some text'))->willReturn(true)->shouldBeCalled();

        $this->publisher->keepEditHistory(false);
        $this->publisher->update($entry);
    }

    public function testUpdate_WhenTrackingHistory_RenamesOldFile() {
        $path = './drafts/02_1234/entry.xml';
        $entry = new BlogEntry("", $this->fs->reveal());
        $entry->file = $path;
        $entry->body = "This is some text";
        $this->fs->file_exists($path)->willReturn(true);
        $this->fs->realpath($path)->willReturn($path);
        $this->fs->file_exists("./drafts/02_1234/publish.txt")->willReturn(false);
        $this->fs->write_file($path, Argument::containingString('This is some text'))->willReturn(true);

        $this->fs->rename($path, './drafts/02_1234/02_123400.xml')->willReturn(true)->shouldBeCalled();

        $this->publisher->keepEditHistory(true);
        $this->publisher->update($entry, $this->getTestTime());
    }
    
    /**
     * @expectedException EntryWriteFailed
     */
    public function testUpdate_WhenNotTrackingHistoryAndWriteFails_Throws() {
        $path = './drafts/02_1234/entry.xml';
        $entry = new BlogEntry("", $this->fs->reveal());
        $entry->file = $path;
        $entry->body = "This is some text";
        $this->fs->file_exists($path)->willReturn(true);
        $this->fs->realpath($path)->willReturn($path);
        $this->fs->write_file($path, Argument::containingString('This is some text'))->willReturn(false);

        $this->publisher->keepEditHistory(false);
        $this->publisher->update($entry);
    }

    /**
     * @expectedException EntryRenameFailed
     */
    public function testUpdate_WhenTrackingHistoryAndRenameFails_DoesNotWriteFile() {
        $path = './drafts/02_1234/entry.xml';
        $entry = new BlogEntry("", $this->fs->reveal());
        $entry->file = $path;
        $this->fs->file_exists($path)->willReturn(true);
        $this->fs->realpath($path)->willReturn($path);

        $this->fs->rename($path, './drafts/02_1234/02_123400.xml')->willReturn(false)->shouldBeCalled();
        $this->fs->write_file(Argument::any(), Argument::any())->shouldNotBeCalled();

        $this->publisher->keepEditHistory(true);
        $this->publisher->update($entry, $this->getTestTime());
    }

    /**
     * @expectedException EntryWriteFailed
     */
    public function testUpdate_WhenTrackingHistoryAndWriteFails_ThrowsAndRenamesOldFileBack() {
        $path = './drafts/02_1234/entry.xml';
        $entry = new BlogEntry("", $this->fs->reveal());
        $entry->file = $path;
        $this->fs->file_exists($path)->willReturn(true);
        $this->fs->realpath($path)->willReturn($path);
        $this->fs->rename($path, './drafts/02_1234/02_123400.xml')->willReturn(true);
        $this->fs->write_file(Argument::any(), Argument::any())->willReturn(false);

        $this->fs->rename('./drafts/02_1234/02_123400.xml', $path)->shouldBeCalled();

        $this->publisher->keepEditHistory(true);
        $this->publisher->update($entry, $this->getTestTime());
    }

    public function testUpdate_WhenEntryIsPublishedAndPermalinkDoesNotExist_WritesPrettyPermalinkFile() {
        $entry = $this->setUpTestPublishedEntryForSuccessfulSave();
        $entry->subject = 'Some Stuff';
        $this->fs->file_exists('./entries/2017/01/Some_Stuff.php')->willReturn(false);

        $this->fs->write_file('./entries/2017/01/Some_Stuff.php', Argument::containingString('02_1234'))->shouldBeCalled();

        $this->publisher->keepEditHistory(false);
        $this->publisher->update($entry, $this->getTestTime());
    }

    public function testUpdate_WhenEntryIsNotPublished_DoesNotWritePrettyPermalinkFile() {
        $entry = $this->setUpTestDraftEntryForSuccessfulSave();
        $this->fs->file_exists("./drafts/02_1234/publish.txt")->willReturn(false);
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

    public function testUpdate_WhenEntryIsPublishedAsArticle_SetsEntryStickiness() {
        $entry = $this->setUpTestArticleForSuccessfulSave();
        $entry->subject = 'Some Stuff';
        $entry->is_sticky = 1;

        $this->fs->write_file('./content/some_stuff/sticky.txt', Argument::any())->willReturn(true)->shouldBeCalled();

        $this->publisher->keepEditHistory(false);
        $this->publisher->update($entry, $this->getTestTime());
    }

    public function testUpdate_WhenEntryIsNotArticle_DoesNotSetStickiness() {
        $entry = $this->setUpTestPublishedEntryForSuccessfulSave();
        $entry->subject = 'Some Stuff';
        $entry->is_sticky = 1;
        $this->fs->file_exists(Argument::containingString('Some_Stuff.php'), Argument::any())->willReturn(true);

        $this->fs->write_file('./content/some_stuff/sticky.txt', Argument::any())->willReturn(true)->shouldNotBeCalled();

        $this->publisher->keepEditHistory(false);
        $this->publisher->update($entry, $this->getTestTime());
    }

    public function testUpdate_WhenSuccessfulAndPublished_UpdatesBlogTagList() {
        $entry = $this->setUpTestPublishedEntryForSuccessfulSave();
        $entry->tags = 'foo,bar';

        $this->blog->updateTagList(['foo', 'bar'])->shouldBeCalled();

        $this->publisher->update($entry, $this->getTestTime());
    }

    public function testUpdate_WhenSuccessfulAndNotPublished_DoesNotUpdateBlogTagList() {
        $entry = $this->setUpTestDraftEntryForSuccessfulSave();
        $entry->tags = 'foo,bar';
        $this->fs->file_exists("./drafts/02_1234/publish.txt")->willReturn(false);

        $this->blog->updateTagList(Argument::any())->shouldNotBeCalled();

        $this->publisher->update($entry, $this->getTestTime());
    }

    public function testUpdate_WhenEntryIsDraft_DoesNotRaiseOnUpdateEvent() {
        $entry = $this->setUpTestDraftEntryForSuccessfulSave();
        $this->fs->file_exists("./drafts/02_1234/publish.txt")->willReturn(false);
        $event_stub = new PublisherEventTestingStub();
        EventRegister::instance()->addHandler('BlogEntry', 'OnUpdate', $event_stub, 'eventHandler');

        $this->publisher->update($entry, $this->getTestTime());

        $this->assertFalse($event_stub->has_been_called);
    }

    public function testUpdate_WhenEntryIsDraft_DoesNotRaiseUpdateCompleteEvent() {
        $entry = $this->setUpTestDraftEntryForSuccessfulSave();
        $this->fs->file_exists("./drafts/02_1234/publish.txt")->willReturn(false);
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

    public function testUpdate_WhenEntryIsNotPublisehedAndAutoPublishSet_WritesAutoPublishInfo() {
        $path = './drafts/02_1234/publish.txt';
        $entry = $this->setUpTestDraftEntryForSuccessfulSave();
        $entry->autopublish = true;
        $entry->autopublish_date = "2525-01-02 12:00:00";

        $this->fs->write_file($path, "2525-01-02 12:00:00")->shouldBeCalled();

        $this->publisher->update($entry, $this->getTestTime());
    }

    public function testUpdate_WhenAutoPubishWasSetAndNowUnset_DeletesAutoPublishInfo() {
        $path = './drafts/02_1234/publish.txt';
        $entry = $this->setUpTestDraftEntryForSuccessfulSave();
        $entry->autopublish = false;
        $entry->autopublish_date = "2525-01-02 12:00:00";
        $this->fs->file_exists($path)->willReturn(true);

        $this->fs->delete($path)->shouldBeCalled();

        $this->publisher->update($entry, $this->getTestTime());
    }

    public function testUpdate_WhenNotPublishedAndTextHasLinksAndSendPingbacks_DoesNotSendPingbacks() {
        $entry = $this->setUpTestDraftEntryForSuccessfulSave();
        $entry->data = 'This is a <a href="http://www.example.com/test">link</a> thing.';
        $entry->has_html = MARKUP_HTML;
        $entry->file = './drafts/02_1234/entry.xml';
        $entry->send_pingback = true;
        $this->fs->file_exists("./drafts/02_1234/publish.txt")->willReturn(false);
        $this->http_client->fetchUrl('http://www.example.com/test', true)->willReturn("X-Pingback: http://www.example.com/ping\r\nContent-Type: text/html");
        $this->http_client->fetchUrl('http://www.example.com/test')->willReturn("<link rel=\"pingback\" href=\"http://www.example.com/ping\">");

        $this->http_client->sendXmlRpcMessage('www.example.com', '/ping', 80, Argument::any())->shouldNotBeCalled();
        
        $this->publisher->update($entry, $this->getTestTime());
    }

    public function testUpdate_WhenPublishedAndTextHasLinksAndSendPingbacks_SendsPingbacks() {
        $entry = $this->setUpTestPublishedEntryForSuccessfulSave();
        $entry->data = 'This is a <a href="http://www.example.com/test">link</a> thing.';
        $entry->has_html = MARKUP_HTML;
        $entry->file = './entries/2017/01/02_1234/entry.xml';
        $entry->send_pingback = true;
        $this->http_client->fetchUrl('http://www.example.com/test', true)->willReturn("X-Pingback: http://www.example.com/ping\r\nContent-Type: text/html");
        $this->http_client->fetchUrl('http://www.example.com/test')->willReturn("<link rel=\"pingback\" href=\"http://www.example.com/ping\">");

        $this->http_client->sendXmlRpcMessage('www.example.com', '/ping', 80, Argument::any())->shouldBeCalled();
        
        $this->publisher->update($entry, $this->getTestTime());
    }

    public function testUpdate_WhenPublishedAndTextHasLinksAndNotSendPingbacks_DoesNotSendPingbacks() {
        $entry = $this->setUpTestPublishedEntryForSuccessfulSave();
        $entry->data = 'This is a <a href="http://www.example.com/test">link</a> thing.';
        $entry->has_html = MARKUP_HTML;
        $entry->file = './entries/2017/01/02_1234/entry.xml';
        $entry->send_pingback = false;

        $this->http_client->sendXmlRpcMessage('www.example.com', '/ping', 80, Argument::any())->shouldNotBeCalled();
        
        $this->publisher->update($entry, $this->getTestTime());
    }

    public function testUpdate_WhenPublishedAndTextHasLocalLinksAndLocalEnabled_SendsLocalPingbacks() {
        $_SERVER['SERVER_NAME'] = 'www.example.com';
        $this->sys_ini->value("entryconfig", "AllowLocalPingback", 1)->willReturn(1);
        $entry = $this->setUpTestPublishedEntryForSuccessfulSave();
        $entry->data = 'This is a <a href="http://www.example.com/test">link</a> thing.';
        $entry->has_html = MARKUP_HTML;
        $entry->file = './entries/2017/01/02_1234/entry.xml';
        $entry->send_pingback = true;
        $this->http_client->fetchUrl('http://www.example.com/test', true)->willReturn("X-Pingback: http://www.example.com/ping\r\nContent-Type: text/html");
        $this->http_client->fetchUrl('http://www.example.com/test')->willReturn("<link rel=\"pingback\" href=\"http://www.example.com/ping\">");

        $this->http_client->sendXmlRpcMessage('www.example.com', '/ping', 80, Argument::any())->shouldBeCalled();
        
        $this->publisher->update($entry, $this->getTestTime());
    }

    public function testUpdate_WhenPublishedAndTextHasLocalLinksAndLocalDisabled_SendsLocalPingbacks() {
        $_SERVER['SERVER_NAME'] = 'www.example.com';
        $this->sys_ini->value("entryconfig", "AllowLocalPingback", 1)->willReturn(0);
        $entry = $this->setUpTestPublishedEntryForSuccessfulSave();
        $entry->data = 'This is a <a href="http://www.example.com/test">link</a> thing.';
        $entry->has_html = MARKUP_HTML;
        $entry->file = './entries/2017/01/02_1234/entry.xml';
        $entry->send_pingback = true;
        $this->http_client->fetchUrl('http://www.example.com/test', true)->willReturn("X-Pingback: http://www.example.com/ping\r\nContent-Type: text/html");
        $this->http_client->fetchUrl('http://www.example.com/test')->willReturn("<link rel=\"pingback\" href=\"http://www.example.com/ping\">");

        $this->http_client->sendXmlRpcMessage('www.example.com', '/ping', 80, Argument::any())->shouldNotBeCalled();
        
        $this->publisher->update($entry, $this->getTestTime());
    }

    public function testUpdate_WhenSendingPings_RaisesPingbackCompleteEvent() {
        $ping_results = array(array('uri' => 'http://example.com/test', 'response' => 'test'));
        $entry = $this->setUpTestPublishedEntryForSuccessfulSave();
        $event_stub = new PublisherEventTestingStub();
        EventRegister::instance()->addHandler('BlogEntry', 'PingbackComplete', $event_stub, 'eventHandler');
        $entry->data = 'This is a <a href="http://example.com/test">link</a> thing.';
        $entry->has_html = MARKUP_HTML;
        $entry->file = './entries/2017/01/02_1234/entry.xml';
        $entry->send_pingback = true;
        $this->http_client->fetchUrl('http://example.com/test', true)->willReturn("X-Pingback: http://example.com/ping\r\nContent-Type: text/html");
        $this->http_client->fetchUrl('http://example.com/test')->willReturn("<link rel=\"pingback\" href=\"http://example.com/ping\">");
        $this->http_client->sendXmlRpcMessage('example.com', '/ping', 80, Argument::any())->willReturn('test');

        $this->publisher->update($entry, $this->getTestTime());

        $this->assertTrue($event_stub->has_been_called);
        $this->assertEquals($ping_results, $event_stub->event_data);
    }

    public function testUpdate_WhenNotSendingPings_DoesNotRaisePingbackCompleteEvent() {
        $ping_results = array(array('uri' => 'http://example.com/test', 'response' => 'test'));
        $entry = $this->setUpTestPublishedEntryForSuccessfulSave();
        $event_stub = new PublisherEventTestingStub();
        EventRegister::instance()->addHandler('BlogEntry', 'PingbackComplete', $event_stub, 'eventHandler');
        $entry->send_pingback = false;

        $this->publisher->update($entry, $this->getTestTime());

        $this->assertFalse($event_stub->has_been_called);
    }

    public function testUpdate_NoUploadsPresent_DoesNotRaisesUploadSuccessOrErrorEvents() {
        $entry = $this->setUpTestPublishedEntryForSuccessfulSave();
        $event_stub = new PublisherEventTestingStub();
        EventRegister::instance()->addHandler('BlogEntry', 'UploadSuccess', $event_stub, 'eventHandler');
        EventRegister::instance()->addHandler('BlogEntry', 'UploadError', $event_stub, 'eventHandler');

        $this->publisher->update($entry, $this->getTestTime());

        $this->assertFalse($event_stub->has_been_called);
    }

    public function testUpdate_SingleUploadsSucceed_RaisesUploadSuccessEvent() {
        $entry = $this->setUpTestPublishedEntryForSuccessfulSave();
        $event_stub = $this->setUpForSingleUploadSuccess();

        $this->publisher->update($entry, $this->getTestTime());

        $this->assertTrue($event_stub->has_been_called);
    }

    public function testUpdate_MultipleUploadsSucceed_RaisesUploadSuccessEvent() {
        $entry = $this->setUpTestPublishedEntryForSuccessfulSave();
        $event_stub = $this->setUpForMultipleUploadSuccess();

        $this->publisher->update($entry, $this->getTestTime());

        $this->assertTrue($event_stub->has_been_called);
    }

    public function testUpdate_SingleUploadMoveFails_RaisesUploadErrorEvent() {
        $entry = $this->setUpTestPublishedEntryForSuccessfulSave();
        $event_stub = $this->setUpForSingleUploadMoveFail();

        $this->publisher->update($entry, $this->getTestTime());

        $this->assertTrue($event_stub->has_been_called);
        $this->assertEquals(array("Error moving uploaded file"), $event_stub->event_data);
    }

    public function testUpdate_SingleUploadBadStatus_RaisesUploadErrorEvent() {
        $entry = $this->setUpTestPublishedEntryForSuccessfulSave();
        $event_stub = $this->setUpForSingleUploadStatusFail(UPLOAD_ERR_PARTIAL);

        $this->publisher->update($entry, $this->getTestTime());

        $this->assertTrue($event_stub->has_been_called);
        $this->assertEquals(array("File only partially uploaded."), $event_stub->event_data);
    }
}