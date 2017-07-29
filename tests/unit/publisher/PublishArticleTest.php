<?php

use Prophecy\Argument;

class PublishArticleTest extends PublisherTestBase {

    /**
     * @expectedException EntryAlreadyExists
     */
    public function testPublishArticle_WhenTargetDirAlreadyExists_Throws() {
        $entry = $this->getTestDraftEntry();
        $this->fs->file_exists('./drafts/02_1234/entry.xml')->willReturn(true);
        $this->fs->realpath('./drafts/02_1234/entry.xml')->willReturn('./drafts/02_1234/entry.xml');
        $this->fs->is_dir('./content')->willReturn(true);
        $this->fs->is_dir('./content/some_stuff')->willReturn(true);

        $this->publisher->publishArticle($entry, $this->getTestTime());
    }

    public function testPublishArticle_WhenEntryDoesNotExist_SavesAsDraft() {
        $entry = new BlogEntry(null, $this->fs->reveal());
        $entry = $this->setUpTestArticleForSuccessfulDraftSave();
        $entry->article_path = 'some_stuff';

        $this->fs->write_file('./drafts/02_1234/entry.xml', Argument::any())->willReturn(true)->shouldBeCalled();

        $this->publisher->publishArticle($entry, $this->getTestTime());
    }

    public function testPublishArticle_WhenSavingDraft_SavesUploadsOnlyOnce() {
        $entry = $this->setUpTestArticleForSuccessfulDraftSave();
        $entry->article_path = 'some_stuff';
        $event_stub = $this->setUpForMultipleUploadSuccess();
        $this->fs->write_file('./drafts/02_1234/entry.xml', Argument::any())->willReturn(true);

        $this->publisher->publishArticle($entry, $this->getTestTime());

        $this->assertEquals(1, $event_stub->call_count);
    }

    public function testPublishArticle_WhenEntryExists_RenamesToArticlePath() {
        $entry = $this->getTestDraftEntry();
        $fs = $this->fs;
        $fs->file_exists('./drafts/02_1234/entry.xml')->willReturn(true);
        $fs->realpath('./drafts/02_1234/entry.xml')->willReturn('./drafts/02_1234/entry.xml');
        $fs->file_exists('./content/some_stuff/sticky.txt')->willReturn(false);
        $fs->is_dir('./content')->willReturn(true);
        $fs->is_dir('./content/some_stuff')->willReturn(false);
        $fs->write_file('./content/some_stuff/entry.xml', Argument::any())->willReturn(true);

        $fs->rename('./drafts/02_1234', './content/some_stuff')->will(function($args) use ($fs) {
            $fs->file_exists('./content/some_stuff/entry.xml')->willReturn(true);
            $fs->realpath('./content/some_stuff/entry.xml')->willReturn('./content/some_stuff/entry.xml');
            return true;
        })->shouldBeCalled();

        $this->publisher->publishArticle($entry, $this->getTestTime());
    }

    /**
     * @expectedException   EntryRenameFailed
     */
    public function testPublishArticle_WhenDraftRenameFail_Throws() {
        $entry = $this->getTestDraftEntry();
        $this->fs->file_exists('./drafts/02_1234/entry.xml')->willReturn(true);
        $this->fs->realpath('./drafts/02_1234/entry.xml')->willReturn('./drafts/02_1234/entry.xml');
        $this->fs->is_dir('./content')->willReturn(true);
        $this->fs->is_dir('./content/some_stuff')->willReturn(false);

        $this->fs->rename('./drafts/02_1234', './content/some_stuff')->willReturn(false);

        $this->publisher->publishArticle($entry, $this->getTestTime());
    }

    public function testPublishAticle_WhenPublishSucceeds_SetsEntryWithUserFileIpAndDates() {
        $_SERVER['REMOTE_ADDR'] = '1.2.3.4';
        $entry = $this->getTestDraftEntry();
        $fs = $this->fs;
        $fs->file_exists('./drafts/02_1234/entry.xml')->willReturn(true);
        $fs->realpath('./drafts/02_1234/entry.xml')->willReturn('./drafts/02_1234/entry.xml');
        $fs->file_exists('./content/some_stuff/sticky.txt')->willReturn(false);
        $this->user->username()->willReturn('billybob');
        $fs->is_dir('./content')->willReturn(true);
        $fs->is_dir('./content/some_stuff')->willReturn(false);
        $fs->rename('./drafts/02_1234', './content/some_stuff')->will(function($args) use ($fs) {
            $fs->file_exists('./content/some_stuff/entry.xml')->willReturn(true);
            $fs->realpath('./content/some_stuff/entry.xml')->willReturn('./content/some_stuff/entry.xml');
            return true;
        });
        $fs->write_file('./content/some_stuff/entry.xml', Argument::any())->willReturn(true);

        $this->publisher->publishArticle($entry, $this->getTestTime());

        $expected_time = $this->getTestTime()->getTimestamp();
        $this->assertEquals('./content/some_stuff/entry.xml', $entry->file);
        $this->assertEquals('billybob', $entry->uid);
        $this->assertEquals($expected_time, $entry->post_ts);
        $this->assertEquals($expected_time, $entry->timestamp);
        $this->assertContains('2017-01-02 12:34', $entry->date);
        $this->assertEquals('1.2.3.4', $entry->ip);
    }

    public function testPublishArticle_WhenPathIsValid_RaisesArticleOnInsertEvent() {
        $entry = $this->setUpTestArticleForSuccessfulPublish();
        $event_stub = new PublisherEventTestingStub();
        EventRegister::instance()->addHandler('Article', 'OnInsert', $event_stub, 'eventHandler');

        $this->publisher->publishArticle($entry, $this->getTestTime());

        $this->assertTrue($event_stub->has_been_called);
    }
    
    public function testPublishArticle_WhenPathIsValid_RaisesArticleInsertCompleteEvent() {
        $entry = $this->setUpTestArticleForSuccessfulPublish();
        $event_stub = new PublisherEventTestingStub();
        EventRegister::instance()->addHandler('Article', 'InsertComplete', $event_stub, 'eventHandler');

        $this->publisher->publishArticle($entry, $this->getTestTime());

        $this->assertTrue($event_stub->has_been_called);
    }

    public function testPublishArticle_WhenPathIsNotValid_DoesNotRaiseArticleEvents() {
        $entry = $this->getTestDraftEntry();
        $this->fs->file_exists('./drafts/02_1234/entry.xml')->willReturn(true);
        $this->fs->realpath('./drafts/02_1234/entry.xml')->willReturn('./drafts/02_1234/entry.xml');
        $this->fs->is_dir(Argument::any())->willReturn(true);
        $event_stub = new PublisherEventTestingStub();
        EventRegister::instance()->addHandler('Article', 'OnInsert', $event_stub, 'eventHandler');
        EventRegister::instance()->addHandler('Article', 'InsertComplete', $event_stub, 'eventHandler');

        try {
            $this->publisher->publishArticle($entry, $this->getTestTime());
        } catch (Exception $e) {}

        $this->assertFalse($event_stub->has_been_called);
    }

    public function testPublishArticle_WhenPublishSucceeds_CreatesDirectoryWrappers() {
        $entry = $this->setUpTestArticleForSuccessfulPublish();
        
        $this->wrappers->createDirectoryWrappers('./content/some_stuff', ARTICLE_BASE)->shouldBeCalled();
        $this->wrappers->createDirectoryWrappers('./content/some_stuff/comments', ENTRY_COMMENTS)->shouldBeCalled();
        $this->wrappers->createDirectoryWrappers('./content/some_stuff/trackback', ENTRY_TRACKBACKS)->shouldBeCalled();
        $this->wrappers->createDirectoryWrappers('./content/some_stuff/pingback', ENTRY_PINGBACKS)->shouldBeCalled();

        $this->publisher->publishArticle($entry, $this->getTestTime());
    }

    public function testPublishArticle_WhenArticlesDirDoesNotExist_CreatesArticlesDirWrappers() {
        $entry = $this->setUpTestArticleForSuccessfulPublish();
        $this->fs->is_dir('./content/some_stuff')->willReturn(false);
        $this->fs->is_dir('./content')->willReturn(false);
        $this->wrappers->createDirectoryWrappers('./content/some_stuff', ARTICLE_BASE)->willReturn(true);
        $this->wrappers->createDirectoryWrappers('./content/some_stuff/comments', ENTRY_COMMENTS)->willReturn(true);
        $this->wrappers->createDirectoryWrappers('./content/some_stuff/trackback', ENTRY_TRACKBACKS)->willReturn(true);
        $this->wrappers->createDirectoryWrappers('./content/some_stuff/pingback', ENTRY_PINGBACKS)->willReturn(true);

        $this->wrappers->createDirectoryWrappers('./content', BLOG_ARTICLES)->willReturn(true)->shouldBeCalled();

        $this->publisher->publishArticle($entry, $this->getTestTime());
    }

    public function testPublishArticle_WhenArticlePathIsNotSet_PublishesWithPathGeneratedFromSubject() {
        $entry = $this->setUpTestArticleForSuccessfulPublish();
        $entry->subject = "Whatever THing";
        $entry->article_path = '';
        $fs = $this->fs;
        $fs->is_dir('./content/whatever_thing')->willReturn(false);
        $fs->file_exists('./content/whatever_thing/sticky.txt')->willReturn(false);

        $fs->rename('./drafts/02_1234', './content/whatever_thing')->will(function($args) use ($fs) {
            $fs->file_exists('./content/whatever_thing/entry.xml')->willReturn(true);
            $fs->realpath('./content/whatever_thing/entry.xml')->willReturn('./content/whatever_thing/entry.xml');
            return true;
        })->shouldBeCalled();
        $fs->write_file('./content/whatever_thing/entry.xml', Argument::any())->willReturn(true)->shouldBeCalled();

        $this->publisher->publishArticle($entry, $this->getTestTime());
    }

    public function testPublishArticle_WhenSuccess_UpdateBlogTagList() {
        $entry = $this->setUpTestArticleForSuccessfulPublish();
        $entry->tags = 'fizz,buzz';

        $this->blog->updateTagList(['fizz', 'buzz'])->shouldBeCalled();

        $this->publisher->publishArticle($entry, $this->getTestTime());
    }

    public function testPublishArticle_WhenSuccess_SetsStickiness() {
        $entry = $this->setUpTestArticleForSuccessfulPublish();
        $entry->is_sticky = 1;

        $this->fs->write_file('./content/some_stuff/sticky.txt', Argument::any())->willReturn(true)->shouldBeCalled();

        $this->publisher->publishArticle($entry, $this->getTestTime());
    }

    public function testPublishArticle_WhenTextHasLinksAndSendPingbacks_SendsPingbacks() {
        $entry = $this->setUpTestArticleForSuccessfulPublish();
        $entry->data = 'This is a <a href="http://www.example.com/test">link</a> thing.';
        $entry->has_html = MARKUP_HTML;
        $entry->file = './drafts/02_1234/entry.xml';
        $entry->send_pingbacks = true;
        $this->http_client->fetchUrl('http://www.example.com/test', true)->willReturn("X-Pingback: http://www.example.com/ping\r\nContent-Type: text/html");
        $this->http_client->fetchUrl('http://www.example.com/test')->willReturn("<link rel=\"pingback\" href=\"http://www.example.com/ping\">");

        $this->http_client->sendXmlRpcMessage('www.example.com', '/ping', 80, Argument::any())->shouldBeCalled();
        
        $this->publisher->publishArticle($entry, $this->getTestTime());
    }
    
    public function testPublishArticle_WhenSendingPingbacks_RaisesPingbackCompleteEventWithResponseData() {
        $ping_results = array(array('uri' => 'http://example.com/test', 'response' => 'test'));
        $entry = $this->setUpTestArticleForSuccessfulPublish();
        $event_stub = new PublisherEventTestingStub();
        EventRegister::instance()->addHandler('BlogEntry', 'PingbackComplete', $event_stub, 'eventHandler');
        $entry->data = 'This is a <a href="http://example.com/test">link</a> thing.';
        $entry->has_html = MARKUP_HTML;
        $entry->file = './drafts/02_1234/entry.xml';
        $entry->send_pingback = true;
        $this->http_client->fetchUrl('http://example.com/test', true)->willReturn("X-Pingback: http://example.com/ping\r\nContent-Type: text/html");
        $this->http_client->fetchUrl('http://example.com/test')->willReturn("<link rel=\"pingback\" href=\"http://example.com/ping\">");
        $this->http_client->sendXmlRpcMessage('example.com', '/ping', 80, Argument::any())->willReturn('test');

        $this->publisher->publishArticle($entry, $this->getTestTime());

        $this->assertTrue($event_stub->has_been_called);
        $this->assertEquals($ping_results, $event_stub->event_data);
    }

    public function testPublishArticle_WhenNoPingbacksSent_DoesNotRaisePingbackCompleteEvent() {
        $ping_results = array(array('uri' => 'http://example.com/test', 'response' => 'test'));
        $entry = $this->setUpTestArticleForSuccessfulPublish();
        $event_stub = new PublisherEventTestingStub();
        EventRegister::instance()->addHandler('BlogEntry', 'PingbackComplete', $event_stub, 'eventHandler');
        $entry->send_pingback = false;

        $this->publisher->publishArticle($entry, $this->getTestTime());

        $this->assertFalse($event_stub->has_been_called);
    }

    public function testPublishArticle_NoUploadsPresent_DoesNotRaisesUploadSuccessOrErrorEvents() {
        $entry = $this->setUpTestArticleForSuccessfulPublish();
        $event_stub = new PublisherEventTestingStub();
        EventRegister::instance()->addHandler('BlogEntry', 'UploadSuccess', $event_stub, 'eventHandler');
        EventRegister::instance()->addHandler('BlogEntry', 'UploadError', $event_stub, 'eventHandler');

        $this->publisher->publishArticle($entry, $this->getTestTime());

        $this->assertFalse($event_stub->has_been_called);
    }

    public function testPublishArticle_SingleUploadsSucceed_RaisesUploadSuccessEvent() {
        $entry = $this->setUpTestArticleForSuccessfulPublish();
        $event_stub = $this->setUpForSingleUploadSuccess('./content/some_stuff');

        $this->publisher->publishArticle($entry, $this->getTestTime());

        $this->assertTrue($event_stub->has_been_called);
    }

    public function testPublishArticle_MultipleUploadsSucceed_RaisesUploadSuccessEvent() {
        $entry = $this->setUpTestArticleForSuccessfulPublish();
        $event_stub = $this->setUpForMultipleUploadSuccess();

        $this->publisher->publishArticle($entry, $this->getTestTime());

        $this->assertTrue($event_stub->has_been_called);
    }

    public function testPublishArticle_SingleUploadMoveFails_RaisesUploadErrorEvent() {
        $entry = $this->setUpTestArticleForSuccessfulPublish();
        $event_stub = $this->setUpForSingleUploadMoveFail('./content/some_stuff');
        $this->fs->is_file('/tmp/foo')->willReturn(true);

        $this->publisher->publishArticle($entry, $this->getTestTime());

        $this->assertTrue($event_stub->has_been_called);
        $this->assertEquals(array("Error moving uploaded file"), $event_stub->event_data);
    }

    public function testPublishArticle_SingleUploadBadStatus_RaisesUploadErrorEvent() {
        $entry = $this->setUpTestArticleForSuccessfulPublish();
        $event_stub = $this->setUpForSingleUploadStatusFail(UPLOAD_ERR_PARTIAL);

        $this->publisher->publishArticle($entry, $this->getTestTime());

        $this->assertTrue($event_stub->has_been_called);
        $this->assertEquals(array("File only partially uploaded."), $event_stub->event_data);
    }

    private function getTestDraftEntry() {
        $entry = new BlogEntry(null, $this->fs->reveal());
        $entry->file = './drafts/02_1234/entry.xml';
        $entry->article_path = 'some_stuff';
        return $entry;
    }

    private function setUpTestArticleForSuccessfulPublish() {
        $entry = $this->getTestDraftEntry();
        $fs = $this->fs;
        $fs->file_exists('./drafts/02_1234/entry.xml')->willReturn(true);
        $fs->realpath('./drafts/02_1234/entry.xml')->willReturn('./drafts/02_1234/entry.xml');
        $fs->file_exists('./content/some_stuff/sticky.txt')->willReturn(false);
        $fs->is_dir('./content')->willReturn(true);
        $fs->is_dir('./content/some_stuff')->willReturn(false);
        $fs->rename('./drafts/02_1234', './content/some_stuff')->will(function($args) use ($fs) {
            $fs->file_exists('./content/some_stuff/entry.xml')->willReturn(true);
            $fs->realpath('./content/some_stuff/entry.xml')->willReturn('./content/some_stuff/entry.xml');
            return true;
        });
        $this->fs->write_file('./content/some_stuff/entry.xml', Argument::any())->willReturn(true);
        return $entry;
    } 

    private function setUpTestArticleForSuccessfulDraftSave() {
        $entry = new BlogEntry(null, $this->fs->reveal());
        $fs = $this->fs;
        $fs->is_dir('./drafts')->willReturn(true);
        $fs->mkdir_rec('./drafts/02_1234')->willReturn(true);
        $fs->is_dir('./content')->willReturn(true);
        $fs->is_dir('./content/some_stuff')->willReturn(false);
        $fs->file_exists('./drafts/02_1234/entry.xml')->willReturn(true);
        $fs->realpath('./drafts/02_1234/entry.xml')->willReturn('./drafts/02_1234/entry.xml');
        $fs->file_exists("./drafts/02_1234/publish.txt")->willReturn(false);
        $fs->file_exists('./content/some_stuff/sticky.txt')->willReturn(false);
        $fs->rename(Argument::any(), Argument::any())->will(function($args) use ($fs) {
            $fs->file_exists('./content/some_stuff/entry.xml')->willReturn(true);
            $fs->realpath('./content/some_stuff/entry.xml')->willReturn('./content/some_stuff/entry.xml');
            return true;
        });
        $fs->write_file('./content/some_stuff/entry.xml', Argument::any())->willReturn(true);
        return $entry;
    }
}
