<?php

/*
Class: SocialWebClient
A client for sending social web notifications, e.g. pingbacks and webmentions
*/
class SocialWebClient {
    const SUPPORTS_NONE = 0;
    const SUPPORTS_PINGBACK = 1;
    const SUPPORTS_WEBMENTION = 2;

    private $http_client;
    private $fs;
    private $system;
    private $event_register;

    public function __construct(HttpClient $http_client, FS $fs) {
        $this->http_client = $http_client;
        $this->fs = $fs;
        $this->system = System::instance();
        $this->event_register = EventRegister::instance();
    }

    /*
    Method: sendReplies
    Send pingback and/or webmention replies for any links in an entry.
    Webmentions will be preferred if both are supported.  Nothing will be
    sent if pingbacks are turned off for the entry

    Parameters:
    entry - The blog entry for which to send replies.
    */
    public function sendReplies($entry) {
        if ($entry->send_pingback) {
            $local = $this->allowLocalPingback();
            $ping_results = $this->sendPings($entry, $local);
            if (!empty($ping_results)) {
                $this->raisePingbackCompleteEvent($entry, $ping_results);
            }
        }
    }

    private function sendPings($entry, $allow_local) {
        $urls = $this->extractLinks($entry->markup(), $allow_local);
        $ret = array();

        foreach ($urls as $uri) {
            $p = new Pingback(false, $this->fs, $this->http_client);
            $reply = $this->checkPingbackEnabled($uri);

            if ($reply['supports'] == self::SUPPORTS_PINGBACK) {
                $result = $this->sendPingback($entry, $reply['url'], $uri);
                $ret[] = [
                    'uri' => $uri,
                    'response' => [
                        'code' => $result->faultCode(),
                        'message' => $result->faultString(),
                    ]
                ];
            } elseif ($reply['supports'] == self::SUPPORTS_WEBMENTION) {
                $result = $this->sendWebmention($entry, $reply['url'], $uri);
                $ret[] = ['uri' => $uri, 'response' => $result];
            }
        }
        return $ret;
    }

    private function extractLinks($html, $allow_local) {
        $matches = array();

        $ret = preg_match_all('/href="([^"]+)"/i', $html, $matches);

        $url_matches = $matches[1];  # Grab only the saved subexpression.
        $ret = array();

        foreach ($url_matches as $m) {
            if ($allow_local) {
                $ret[] = $m;
            } else {
                # If we're NOT allowing local pings, filter them out.
                $url = parse_url($m);
                if (isset($url['host']) && $url['host'] != SERVER("SERVER_NAME")) {
                    $ret[] = $m;
                }
            }
        }

        return $ret;
    }

    private function sendPingback($entry, $uri, $target) {
        $linkdata = parse_url($uri);

        $host = isset($linkdata['host']) ? $linkdata['host'] : $_SERVER["SERVER_NAME"];
        $path = isset($linkdata['path']) ? $linkdata['path'] : '';
        $port = isset($linkdata['port']) ? $linkdata['port'] : 80;

        $parms = array(new xmlrpcval($entry->permalink(), 'string'),
                       new xmlrpcval($target, 'string'));
        $msg = new xmlrpcmsg('pingback.ping', $parms);

        $result = $this->http_client->sendXmlRpcMessage($host, $path, $port, $msg);

        return $result;
    }

    private function sendWebmention($entry, $uri, $target) {
        $data = "source=" . $entry->permalink() . "&target=" . $target;
        $result = $this->http_client->sendPost($uri, $data);
        $code = $result->responseCode();
        return [
            'code' => $code >= 200 && $code <= 299 ? 0 : $code,
            'message' => $result->body(),
        ];
    }

    private function checkPingbackEnabled($url) {
        # First check the page headers.
        $pageheaders = $this->http_client->fetchUrl($url, true);
        $response_support = $this->getResponseSupportFromHeaders($pageheaders);
        if ($response_support['supports'] !== self::SUPPORTS_NONE) {
            return $response_support;
        }

        $pingback_server = '';
        $response_type = self::SUPPORTS_NONE;

        if ($this->isContentTypeText($pageheaders)) {
            $pagedata = $this->http_client->fetchUrl($url, false);
            $matches = $this->getElementsWithTag('link', $pagedata);
            foreach ($matches as $match) {
                $href = $this->getHrefForRel('pingback', $match);
                if ($href) {
                    $pingback_server = $href;
                    $response_type = self::SUPPORTS_PINGBACK;
                    break;
                }
                $href = $this->getHrefForRel('webmention', $match);
                if ($href) {
                    $pingback_server = $href;
                    $response_type = self::SUPPORTS_WEBMENTION;
                    break;
                }
            }
            $matches = $this->getElementsWithTag('a', $pagedata);
            foreach ($matches as $match) {
                $href = $this->getHrefForRel('webmention', $match);
                if ($href) {
                    $pingback_server = $href;
                    $response_type = self::SUPPORTS_WEBMENTION;
                    break;
                }
            }
        }

        if ($pingback_server) {
            $pingback_server = $this->absolutizeUrl($pingback_server, $url);
        }

        return ['supports' => $response_type, 'url' => $pingback_server];
    }

    private function getResponseSupportFromHeaders($pageheaders) {
        # Look for an X-Pingback or Link header in the page data.
        $pingback_server = '';
        $response_type = self::SUPPORTS_NONE;
        $lines = explode("\n", $pageheaders);
        foreach ($lines as $l) {
            $s = trim($l);
            $matches = array();

            $ret = preg_match('/^Link:\s*<(.+)>;\s*rel\s*=\s*[\'"]webmention[\'"]$/i', $s, $matches);
            if ($ret) {
                $response_type = self::SUPPORTS_WEBMENTION;
                $pingback_server = $matches[1];
                break;
            }

            $ret = preg_match('/^X-Pingback:\s*(.+)$/i', $s, $matches);
            if ($ret && ! $pingback_server) {
                $response_type = self::SUPPORTS_PINGBACK;
                $pingback_server = $matches[1];
            }
        }
        return ['supports' => $response_type, 'url' => $pingback_server];
    }

    private function isContentTypeText($pageheaders) {
        $lines = explode("\n", $pageheaders);
        foreach ($lines as $l) {
            $s = trim($l);
            $matches = array();
            # Check for the content type.  We probably don't want to fetch the
            # body of the link if it's not a text type, since we could,
            # theoretically, end up linking to a 5MB MP3 file.
            # Note that I'm not sure this is a good idea.
            $ret = preg_match('|^Content-Type: ?text/(.+)$|', $s, $matches);
            if ($ret) {
                return true;
            }
        }
        return false;
    }

    private function getElementsWithTag($tag, $text) {
        $regex = "|<\s*$tag\s+([^>]+)>|i";
        $ret = preg_match_all($regex, $text, $all_matches);
        return isset($all_matches[1]) ? $all_matches[1] : [];
    }

    private function getHrefForRel($rel_value, $text) {
        $rel_regex = '|rel\s*=\s*[\'"]' . $rel_value . '[\'"]|i';
        $has_rel = preg_match($rel_regex, $text);
        if (!$has_rel) {
            return '';
        }
        $href_regex = '|href\s*=\s*[\'"]([^\'"]+)[\'"]|i';
        $has_href = preg_match($href_regex, $text, $link_match);
        if (!$has_href) {
            return '';
        }
        $href = $link_match[1];
        $search = array('&amp;', '&lt;', '&gt;', '&quot;');
        $replace = array('&', '<', '>', '"');
        return str_replace($search, $replace, $href);
    }

    private function absolutizeUrl($url, $reference_url) {
        $pieces = parse_url($url);
        $ref = parse_url($reference_url);

        if (isset($pieces['scheme']) && isset($pieces['host']) && isset($pieces['path'])) {
            return $url;
        }

        $scheme = $this->getValue('scheme', $pieces, $ref);
        $host = $this->getValue('host', $pieces, $ref);
        if (strpos($pieces['path'], '/') === 0) {
            $path = $pieces['path'];
        } else {
            $ends_with_slash = strpos($ref['path'], "/", -1) === strlen($ref['path']) - 1;
            $path = $ends_with_slash ? $ref['path'] : (dirname($ref['path']) . '/');
            $path .= $pieces['path'];
        }

        $url = "$scheme://$host";
        if (empty($pieces['host']) && $this->getValue('port', $pieces, $ref)) {
            $url .= ':' . $this->getValue('port', $pieces, $ref);
        }
        $url .= $path;
        if (!empty($pieces['query'])) {
            $url .= '?' . $pieces['query'];
        }

        return $url;
    }

    private function getValue($key, $arr1, $arr2) {
        if (!empty($arr1[$key])) {
            return $arr1[$key];
        } elseif (!empty($arr2[$key])) {
            return $arr2[$key];
        } else {
            return '';
        }
    }

    private function allowLocalPingback() {
        return $this->system->sys_ini->value("entryconfig", "AllowLocalPingback", 1);
    }

    private function raisePingbackCompleteEvent($entry, $results) {
        $this->event_register->activateEventFull($entry, 'BlogEntry', 'PingbackComplete', $results);
    }
}
