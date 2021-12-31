<?php

# Class: SocialWebServer
# Server implementation for receiving social web notifications, e.g.
# Pingbacks and Webmentions.
class SocialWebServer
{
    private $mapper;
    private $http_client;

    public function __construct(EntryMapper $mapper, HttpClient $client) {
        $this->mapper = $mapper;
        $this->http_client = $client;
    }

    # Method: addWebmention
    # Receive a webmention.  This validates the mention and inserts a new
    # reply for the appropriate entry. See the
    # <Webmention spec: https://www.w3.org/TR/webmention/> for details.
    #
    # Parameters:
    # source - The source URL for the mention, i.e. the remote site
    # target - The target URL for the mention, i.e. the URL on our site.
    #
    # Throws:
    # A WebmentionInvalidReceive if the webmention validation fails.
    public function addWebmention($source, $target) {
        $this->assertUrlSchemeIsValid($source);
        $this->assertUrlSchemeIsValid($target);

        if ($source == $target) {
            throw new WebmentionInvalidReceive("Target is same as source");
        }

        $entry = $this->getMentionableEntry($target);

        $source_page = $this->http_client->fetchUrl($source);

        $this->assertPageContainsLink($source_page, $target);

        $pingback = new Pingback();
        $pingback->is_webmention = true;
        $pingback->source = $source;
        $pingback->target = $target;
        $pingback->title = $this->getPageTitle($source_page);

        if (!$entry->pingExists($source)) {
            $entry->addReply($pingback);
        }
    }

    public function addPingback() {
        # TODO: Move pingback implementation here
    }

    private function assertUrlSchemeIsValid($url) {
        $pieces = parse_url($url);
        $scheme = isset($pieces['scheme']) ? $pieces['scheme'] : '';

        $valid_schemes = ['http', 'https'];
        if (!in_array($scheme, $valid_schemes)) {
            throw new WebmentionInvalidReceive("Invalid URL wcheme");
        }
    }

    private function getMentionableEntry($target) {
        $entry = $this->mapper->getEntryFromUri($target);
        if ($entry && $entry->isPublished() && $entry->allow_pingback) {
            return $entry;
        }
        throw new WebmentionInvalidReceive("Target is invalid or does not accept webmentions");
    }

    private function assertPageContainsLink($source_page, $target) {
        $links = $this->extractLinks($source_page);
        if (!in_array($target, $links)) {
            throw new WebmentionInvalidReceive("Source does not mention target");
        }
    }

    private function extractLinks($source) {
        $regex = '|<a\s+[^>]*\s?href\s*=\s*[\'"]([^\'">]+)[\'"][^>]*>|is';
        $ret = preg_match_all($regex, $source, $matches);
        return isset($matches[1]) ? $matches[1] : [];
    }

    private function getPageTitle($source_page) {
        $regex = '|.*<title>(.+)</title>.*|is';
        $ret = preg_match($regex, $source_page, $matches);
        return $ret ? trim($matches[1]) : '';
    }
}
