<?php

use Prophecy\Argument;

class SocialWebNotificationTest extends PublisherTestBase {

    public function testPublishEntry_WhenTextHasLinksAndSendPingbacks_SendsPingbacks() {
        $entry = $this->setUpDraftEntryForSuccessfulNotification("http://www.example.com/test");
        $this->http_client->fetchUrl('http://www.example.com/test', true)->willReturn("X-Pingback: http://www.example.com/ping\r\nContent-Type: text/html");
        $this->http_client->fetchUrl('http://www.example.com/test')->willReturn("<link rel=\"pingback\" href=\"http://www.example.com/ping\">");

        $this->http_client->sendXmlRpcMessage('www.example.com', '/ping', 80, Argument::any())
            ->willReturn(new xmlrpcresp(0, 0, 'success'))
            ->shouldBeCalled();

        $this->publisher->publishEntry($entry, $this->getTestTime());
    }

    public function testPublishEntry_WhenSourceHeadersSupportsWebmentionAndPingbacks_SendsOnlyWebmention() {
        $entry = $this->setUpDraftEntryForSuccessfulNotification("http://www.example.com/test");
        $page_headers= "X-Pingback: http://www.example.com/ping\r\n" .
            "Link: <http://www.example.com/ping>; rel=\"webmention\"\r\n" .
            "Content-Type: text/html";
        $this->http_client->fetchUrl('http://www.example.com/test', true)
            ->willReturn($page_headers);

        $payload = 'source=http:///./entries/2017/01/02_1234&target=http://www.example.com/test';
        $this->http_client->sendPost('http://www.example.com/ping', $payload)
            ->willReturn(new HttpResponse(''))
            ->shouldBeCalled();
        $this->http_client->sendXmlRpcMessage()->shouldNotBeCalled();

        $this->publisher->publishEntry($entry, $this->getTestTime());
    }

    public function testPublishEntry_WhenPingbackLinkInPage_SendsPingback() {
        $entry = $this->setUpDraftEntryForSuccessfulNotification("http://www.example.com/test");
        $this->http_client->fetchUrl('http://www.example.com/test', true)
            ->willReturn("Content-Type: text/html");
        $page_content = "<link rel=\"stylesheet\" href=\"foo.css\">\r\n" .
            "<link Href ='http://www.example.com/ping' Rel = 'pingback' />";
        $this->http_client->fetchUrl('http://www.example.com/test', false)
            ->willReturn($page_content);

        $this->http_client->sendXmlRpcMessage('www.example.com', '/ping', 80, Argument::any())
            ->willReturn(new xmlrpcresp(0, 0, 'success'))
            ->shouldBeCalled();
        $this->http_client->sendPost()->shouldNotBeCalled();

        $this->publisher->publishEntry($entry, $this->getTestTime());
    }

    public function testPublishEntry_WhenWebmentionLinkInPage_SendsWebmention() {
        $entry = $this->setUpDraftEntryForSuccessfulNotification("http://www.example.com/test");
        $this->http_client->fetchUrl('http://www.example.com/test', true)
            ->willReturn("Content-Type: text/html");
        $page_content = "<link rel=\"stylesheet\" href=\"foo.css\">\r\n" .
            "<link Href ='http://www.example.com/ping' Rel = 'webmention' />";
        $this->http_client->fetchUrl('http://www.example.com/test', false)
            ->willReturn($page_content);

        $this->http_client->sendXmlRpcMessage()->shouldNotBeCalled();
        $payload = 'source=http:///./entries/2017/01/02_1234&target=http://www.example.com/test';
        $this->http_client->sendPost("http://www.example.com/ping", $payload)
            ->willReturn(new HttpResponse(''))
            ->shouldBeCalled();

        $this->publisher->publishEntry($entry, $this->getTestTime());
    }

    public function testPublishEntry_WhenWebmentionAnchorInPage_SendsWebmention() {
        $entry = $this->setUpDraftEntryForSuccessfulNotification("http://www.example.com/test");
        $this->http_client->fetchUrl('http://www.example.com/test', true)
            ->willReturn("Content-Type: text/html");
        $page_content = "<a href=\"test.png\">This is a picture/a>\r\n" .
            "<A Href ='http://www.example.com/ping' Rel= 'webmention'>\r\nlink\r\n</a>";
        $this->http_client->fetchUrl('http://www.example.com/test', false)
            ->willReturn($page_content);

        $this->http_client->sendXmlRpcMessage()->shouldNotBeCalled();
        $payload = 'source=http:///./entries/2017/01/02_1234&target=http://www.example.com/test';
        $this->http_client->sendPost("http://www.example.com/ping", $payload)
            ->willReturn(new HttpResponse(''))
            ->shouldBeCalled();

        $this->publisher->publishEntry($entry, $this->getTestTime());
    }

    public function testPublishEntry_WhenWebmentionLinkIsRelative_CanonicalizesLinkRelativeToTarget() {
        $entry = $this->setUpDraftEntryForSuccessfulNotification("http://www.example.com/test/foo");
        $target_url = 'http://www.example.com/test/foo';
        $page_content = '<link href="ping" rel="webmention" />';
        $this->http_client->fetchUrl($target_url, true)->willReturn("Content-Type: text/html");
        $this->http_client->fetchUrl($target_url, false)->willReturn($page_content);

        $payload = 'source=http:///./entries/2017/01/02_1234&target=http://www.example.com/test/foo';
        $this->http_client->sendPost("http://www.example.com/test/ping", $payload)
            ->willReturn(new HttpResponse(''))
            ->shouldBeCalled();

        $this->publisher->publishEntry($entry, $this->getTestTime());
    }

    public function testPublishEntry_WhenWebmentionLinkIsRootRelative_CanonicalizesLinkRelativeToTarget() {
        $entry = $this->setUpDraftEntryForSuccessfulNotification("http://www.example.com/test");
        $target_url = 'http://www.example.com/test';
        $page_content = '<link href="/ping" rel="webmention" />';
        $this->http_client->fetchUrl($target_url, true)->willReturn("Content-Type: text/html");
        $this->http_client->fetchUrl($target_url, false)->willReturn($page_content);

        $payload = 'source=http:///./entries/2017/01/02_1234&target=http://www.example.com/test';
        $this->http_client->sendPost("http://www.example.com/ping", $payload)
            ->willReturn(new HttpResponse(''))
            ->shouldBeCalled();

        $this->publisher->publishEntry($entry, $this->getTestTime());
    }

    public function testPublishEntry_WhenWebmentionLinkIsProtocolRelative_CanonicalizesLinkRelativeToTarget() {
        $target_url = 'https://www.example.com/test';
        $entry = $this->setUpDraftEntryForSuccessfulNotification($target_url);
        $page_content = '<link href="//www.example.com/ping?test=yes" rel="webmention" />';
        $this->http_client->fetchUrl($target_url, true)->willReturn("Content-Type: text/html");
        $this->http_client->fetchUrl($target_url, false)->willReturn($page_content);

        $payload = 'source=http:///./entries/2017/01/02_1234&target=https://www.example.com/test';
        $this->http_client->sendPost("https://www.example.com/ping?test=yes", $payload)
            ->willReturn(new HttpResponse(''))
            ->shouldBeCalled();

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

    public function testPublishEntry_WhenNoPingUrlFound_DoesNotSendPingbackOrWebmention() {
        $entry = $this->setUpDraftEntryForSuccessfulNotification("http://www.example.com/test");
        $this->http_client->fetchUrl('http://www.example.com/test', true)->willReturn("Content-Type: text/html\r\n\r\n");
        $this->http_client->fetchUrl('http://www.example.com/test', false)->willReturn("This is a random webpage");

        $this->http_client->sendXmlRpcMessage()->shouldNotBeCalled();
        $this->http_client->sendPost()->shouldNotHaveBeenCalled();

        $this->publisher->publishEntry($entry, $this->getTestTime());
    }

    public function testPublishEntry_WhenContentTypeIsNotText_DoesNotFetchBody() {
        $entry = $this->setUpDraftEntryForSuccessfulNotification("http://www.example.com/test");
        $this->http_client->fetchUrl('http://www.example.com/test', true)->willReturn("Content-Type: audio/mp3\r\n\r\n");

        $this->http_client->fetchUrl('http://www.example.com/test', false)->shouldNotBeCalled();

        $this->publisher->publishEntry($entry, $this->getTestTime());
    }

    public function testPublishEntry_WhenSendingPingbacks_RaisesPingbackCompleteEventWithResponseData() {
        $ping_results = [
            [
                'uri' => 'http://example.com/test',
                'response' => ['code' => 0, 'message' => '' ],
            ]
        ];
        $event_stub = new PublisherEventTestingStub();
        EventRegister::instance()->addHandler('BlogEntry', 'PingbackComplete', $event_stub, 'eventHandler');
        $entry = $this->setUpDraftEntryForSuccessfulNotification("http://example.com/test");
        $this->http_client->fetchUrl('http://example.com/test', true)->willReturn("X-Pingback: http://example.com/ping\r\nContent-Type: text/html");
        $this->http_client->fetchUrl('http://example.com/test')->willReturn("<link rel=\"pingback\" href=\"http://example.com/ping\">");
        $this->http_client->sendXmlRpcMessage('example.com', '/ping', 80, Argument::any())
            ->willReturn(new xmlrpcresp(0, 0, 'success'));

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

    private function setUpDraftEntryForSuccessfulNotification($target_link) {
        $entry = $this->setUpDraftEntryForSuccessfulPublish();
        $entry->data = 'This is a <a href="' . $target_link . '">link</a> thing.';
        $entry->has_html = MARKUP_HTML;
        $entry->file = './drafts/02_1234/entry.xml';
        $entry->send_pingback = true;
        return $entry;
    }
}
