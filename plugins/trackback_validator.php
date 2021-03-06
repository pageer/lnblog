<?php

# Plugin: TrackbackValidator
# Validates TrackBacks when they are posted.
#
# This plugin implements a simple method for preventing TrackBack spam.  It
# simply fetches the URL passed in the TrackBack ping and scans it for a link
# to the entry the TrackBack was posted to.  If no such link is found, then the
# conclusion is that this TrackBack is spam and it is rejected.
#
# Note that there are two options for this plugin.  The first is an option to
# exclude links to the same host as your blog from validation.  The second is
# to check the ping URL for links to the entry base directory, in addition to
# the entry permalink.  Effectively, this means that URLs that link the the
# entry permalink, the comments page, or a file uploaded to the entry will all
# pass validation.  Without this, only links to the "official" permalink will
# pass.
#
# The idea for this plugin comes from the paper
# "Taking TrackBack Back (from Spam)" by Gerecht et al. from Rice University.
# The paper is available at
# <http://seclab.cs.rice.edu/proj/trackback/papers/taking-trackback-back.pdf>

class TrackbackValidator extends Plugin
{
    public $allow_self;
    public $base_uri;

    function __construct() {
        $this->plugin_desc = _('Allow only TrackBacks that link to your URL in the page body.');
        $this->plugin_version = '0.1.0';

        $this->addOption(
            'allow_self',
            _('Allow TrackBacks from your own blog'),
            true, 'checkbox'
        );
        $this->addOption(
            'base_uri',
            _('Allow pings from pages that link to anything under the entry, not just the permalink.'),
            true, 'checkbox'
        );
        parent::__construct();

        $this->registerEventHandler("trackback", "POSTRetreived", "check_for_link");
    }

    function check_for_link(&$param) {
        $url = parse_url($param->url);

        # The trackback is not to a valid URL.

        if ( !$url ||
             ! isset($url['host']) ||
             strpos($param->url, "http://") !== 0 ) {
            $param->url = '';
            return false;
        }


        if ( $this->allow_self && $url['host'] == SERVER("SERVER_NAME") ) {
            return true;
        }

        $ent = $param->getParent();
        $client = new HttpClient();
        $data = $client->fetchUrl($param->url, false);
        # If the permalink is in the page, it's legitimate, so return true.
        if (strpos($data, $ent->permalink()) > 0) {
            return true;
        }

        # If we're also checking the base URI and that's in the page, then
        # we can also return true.
        # Note that this should return true even if the link is actually to the
        # comments page or a file under the entry rather than the permalink
        # wrapper script.  This may or may not be a good thing, hence the
        # option.
        if ($this->base_uri && strpos($data, $ent->uri('base')) > 0) {
            return true;
        } else {
            $param->url = '';
            return false;
        }
    }

}

$plug = new TrackbackValidator();
