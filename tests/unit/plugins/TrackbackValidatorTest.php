<?php

namespace LnBlog\Tests\Plugins;

require_once __DIR__ . '/../../../plugins/trackback_validator.php';

use BlogEntry;
use HttpClient;
use Generator;
use Trackback;
use TrackbackValidator;
use LnBlog\Tests\LnBlogBaseTestCase;

class TrackbackRdfTest extends LnBlogBaseTestCase
{
    /**
     * @dataProvider schemeProvider
     */
    public function testCheckForLink_PermalinkInPage_Success(string $scheme): void {
        $permalink = 'https://foo.com/entry1/link';
        $baselink = 'https://foo.com/entry1/';
        $ping_url = $scheme . '://bar.com/';
        $content = "<p> look at $permalink</p>";

        list($client, $entry, $tb) = $this->setUpObjects($permalink, $baselink, $ping_url, $content);

        $validator = new TrackbackValidator($client->reveal());
        $result = $validator->checkForLink($tb);

        $this->assertTrue($result);
        $this->assertNotEmpty($tb->url);
    }

    public function schemeProvider(): Generator {
        yield 'HTTP' => ['http'];
        yield 'HTTPS' => ['https'];
    }

    public function testCheckForLink_AllowSelfNoPermalinkInLocalPage_Success(): void {
        $permalink = 'https://foo.com/entry1/link';
        $ping_url = 'https://foo.com/';
        $_SERVER['SERVER_NAME'] = 'foo.com';

        $client = $this->prophet->prophesize(HttpClient::class);

        $trackback = $this->prophet->prophesize(Trackback::class);
        $tb = $trackback->reveal();
        $tb->url = $ping_url;

        $validator = new TrackbackValidator($client->reveal());
        $validator->allow_self = true;
        $result = $validator->checkForLink($tb);

        $this->assertTrue($result);
        $this->assertNotEmpty($tb->url);
    }

    public function testCheckForLink_NoAllowSelfNoPermalinkInLocalPage_Failure(): void {
        $permalink = 'https://foo.com/entry1/link';
        $baselink = 'https://foo.com/entry1/';
        $ping_url = 'https://foo.com/';
        $content = 'some stuff';
        $_SERVER['SERVER_NAME'] = 'foo.com';

        list($client, $entry, $tb) = $this->setUpObjects($permalink, $baselink, $ping_url, $content);

        $validator = new TrackbackValidator($client->reveal());
        $validator->allow_self = false;
        $result = $validator->checkForLink($tb);

        $this->assertFalse($result);
        $this->assertEmpty($tb->url);
    }

    public function testCheckForLink_BaseUriBaseLinkInPage_Success(): void {
        $permalink = 'https://foo.com/entry1/link';
        $baselink = 'https://foo.com/baselink/';
        $ping_url = 'https://bar.com/';
        $content = "<p> look at $baselink</p>";

        list($client, $entry, $tb) = $this->setUpObjects($permalink, $baselink, $ping_url, $content);

        $validator = new TrackbackValidator($client->reveal());
        $validator->base_uri = true;
        $result = $validator->checkForLink($tb);

        $this->assertTrue($result);
        $this->assertNotEmpty($tb->url);
    }

    public function testCheckForLink_NoBaseUriBaseLinkInPage_Failure(): void {
        $permalink = 'https://foo.com/entry1/link';
        $baselink = 'https://foo.com/baselink/';
        $ping_url = 'https://bar.com/';
        $content = "<p> look at nothing</p>";

        list($client, $entry, $tb) = $this->setUpObjects($permalink, $baselink, $ping_url, $content);

        $validator = new TrackbackValidator($client->reveal());
        $validator->base_uri = true;
        $result = $validator->checkForLink($tb);

        $this->assertFalse($result);
        $this->assertEmpty($tb->url);
    }

    public function testCheckForLink_BadUrl_Failure(): void {
        $client = $this->prophet->prophesize(HttpClient::class);
        $tb = new Trackback();
        $tb->url = 'ssh://somejunk';

        $validator = new TrackbackValidator($client->reveal());
        $result = $validator->checkForLink($tb);

        $this->assertFalse($result);
        $this->assertEmpty($tb->url);
    }


    private function setUpObjects(
        string $permalink,
        string $baselink,
        string $ping_url,
        string $content
    ): array {

        $client = $this->prophet->prophesize(HttpClient::class);
        $client->fetchUrl($ping_url, false)->willReturn($content);

        $entry = $this->prophet->prophesize(BlogEntry::class);
        $entry->uri('base')->willReturn($baselink);
        $entry->permalink()->willReturn($permalink);

        $trackback = $this->prophet->prophesize(Trackback::class);
        $trackback->getParent()->willReturn($entry->reveal());
        $tb = $trackback->reveal();
        $tb->url = $ping_url;

        return [$client, $entry, $tb];
    }
}
