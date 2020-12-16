<?php

use Prophecy\Argument;

class SocialWebServerTest extends \PHPUnit\Framework\TestCase
{
    private $prophet;
    private $entry;
    private $mapper;
    private $http_client;

    public function testAddWebmention_WhenMentionIsValid_SavesMention() {
        $source = 'http://www.example.com/test1';
        $target = 'http://www.mysite.com/test2';
        $page_text = 
            "<html>" . 
            "<head><title>\n\tThis is some page\t\n</title></head>" . 
            "<body>" .
            "<a href=\"http://www.yahoo.com/\">test</a>\r\ntest\r\n" .
            "<a href=\"http://www.mysite.com/test2\">link<a></body>" .
            "<a href=\"http://www.google.com/\">test 2</a>\r\ntest\r\n" .
            "</html>";
        $this->mapper->getEntryFromUri($target)->willReturn($this->entry->reveal());
        $this->http_client->fetchUrl($source)->willReturn($page_text);
        $this->entry->pingExists($source)->willReturn(false);
        $this->entry->isPublished()->willReturn(true);

        $validator = function($arg) use ($source, $target) {
            return
                $arg->source == $source && 
                $arg->target == $target &&
                $arg->title == "This is some page" &&
                $arg->is_webmention;
        };
        $this->entry->addReply(Argument::that($validator))->shouldBeCalled();

        $server = $this->createSocialWebServer();
        $server->addWebmention($source, $target);
    }

    public function testAddWebmention_WhenSourceAndTargetAreTheSame_Throws() {
        $source = 'http://www.example.com/test1';
        $target = 'http://www.example.com/test1';

        $this->expectException(WebmentionInvalidReceive::class);

        $server = $this->createSocialWebServer();
        $server->addWebmention($source, $target);
    }

    public function testAddWebmention_WhenTargetHasInvalidProtocol_Throws() {
        $source = 'https://www.example.com/test1';
        $target = 'sftp:///etc/passwd';

        $this->expectException(WebmentionInvalidReceive::class);

        $server = $this->createSocialWebServer();
        $server->addWebmention($source, $target);
    }

    public function testAddWebmention_WhenSourceHasInvalidProtocol_Throws() {
        $source = 'ftp://yoursite.com/path1';
        $target = 'https://www.example.com/test1';

        $this->expectException(WebmentionInvalidReceive::class);

        $server = $this->createSocialWebServer();
        $server->addWebmention($source, $target);
    }

    public function testAddWebmention_WhenTargetIsNotEntry_Throws() {
        $source = 'http://www.yoursite.com/path1';
        $target = 'https://www.mysite.com/test1';
        $this->mapper->getEntryFromUri($target)->willReturn(false);

        $this->expectException(WebmentionInvalidReceive::class);

        $server = $this->createSocialWebServer();
        $server->addWebmention($source, $target);
    }

    public function testAddWebmention_WhenSourceDoesNotContainTarget_Throws() {
        $source = 'http://www.yoursite.com/path1';
        $target = 'https://www.mysite.com/test1';
        $this->mapper->getEntryFromUri($target)->willReturn($this->entry);
        $this->http_client->fetchUrl($source)->willReturn('Test page');

        $this->expectException(WebmentionInvalidReceive::class);

        $server = $this->createSocialWebServer();
        $server->addWebmention($source, $target);
    }

    public function testAddWebmention_WhenPingbacksNotAccepted_Throws() {
        $source = 'http://www.yoursite.com/path1';
        $target = 'https://www.mysite.com/test1';
        $this->mapper->getEntryFromUri($target)->willReturn($this->entry);
        $this->http_client->fetchUrl($source)
            ->willReturn("<a href='$target'>this is a link</a>");
        $this->entry->allow_pingback = false;

        $this->expectException(WebmentionInvalidReceive::class);

        $server = $this->createSocialWebServer();
        $server->addWebmention($source, $target);
    }

    public function testAddWebmention_WhenEntryIsNotPublished_Throws() {
        $source = 'http://www.yoursite.com/path1';
        $target = 'https://www.mysite.com/test1';
        $this->mapper->getEntryFromUri($target)->willReturn($this->entry);
        $this->http_client->fetchUrl($source)
            ->willReturn("<a href='$target'>this is a link</a>");
        $this->entry->isPublished()->willReturn(false);

        $this->expectException(WebmentionInvalidReceive::class);

        $server = $this->createSocialWebServer();
        $server->addWebmention($source, $target);
    }

    public function testAddWebmention_WhenAlreadyReceivedMention_ShouldNotAdd() {
        $source = 'http://www.yoursite.com/path1';
        $target = 'https://www.mysite.com/test1';
        $this->mapper->getEntryFromUri($target)->willReturn($this->entry);
        $this->http_client->fetchUrl($source)
            ->willReturn("<a href='$target'>this is a link</a>");
        $this->entry->pingExists($source)->willReturn(true);
        $this->entry->isPublished()->willReturn(true);

        $server = $this->createSocialWebServer();
        $server->addWebmention($source, $target);

        $this->entry->addReply(Argument::any())->shouldNotHaveBeenCalled();
    }

    protected function setUp(): void {
        $this->prophet = new \Prophecy\Prophet();
        $this->entry = $this->prophet->prophesize(BlogEntry::class);
        $this->mapper = $this->prophet->prophesize(EntryMapper::class);
        $this->http_client = $this->prophet->prophesize(HttpClient::class);
    }

    protected function tearDown(): void {
        $this->prophet->checkPredictions();
    }

    private function createSocialWebServer() {
        return new SocialWebServer(
            $this->mapper->reveal(),
            $this->http_client->reveal()
        );
    }
}
