<?php
# Plugin: Linksleeve
# Runs replies through the LinkSleeve servers to check it for dubious URLs.
#
# LinkSleeve is a  distributed system for link spam management.  It works on a
# throttling principle.  The idea is to maintain a big list of links that have
# recently been posted to web sites and use that to calculate a spamminess
# threshold.  People can't post the same link to thousands of sites an hour -
# only robots can do that, and only spambots would *want* to do it.  Thus if the
# same link is showing up with high frequency in a short period of time, it must
# be link spam.
#
# The LinkSleeve interface is a simple XML-RPC call.  Replies are serialized and
# sent to the LinkSleeve server, which extracts the URLs from the text and does
# its magic.  The return value of this call is a simple 1 or 0 - yes or no to the
# question of whether the reply should be accepted.
#
# The check_reply() method is derived from the reference code at
# http://www.linksleeve.org/xml-rpc.php
# It as been modified to fit LnBlog.

class Linksleeve extends Plugin {

    function __construct() {
        $this->plugin_version = "0.1.0";
        $this->plugin_desc = _("Submits reply data to Linksleeve anti-spam service.");
        $this->addOption("check_comm", _("Check comment data"), true, "checkbox");
        $this->addOption("exempt_users",
                         _("Don't check comments by logged-in users."),
                         true, 'checkbox');
        $this->addOption("check_tb", _("Check TrackBack data"), false, "checkbox");
        $this->addOption("check_pb", _("Checl Pingback data"), false, "checkbox");
        $this->addOption("mail_conf",
                         _("Send e-mail confirmation of errors and deleted replies (for testing/debugging)."),
                         false, "select",
                         array(0=>_("No notification"),
                               1=>_("Notify on rejection"),
                               2=>_("Notify for all")) );
        $this->addOption("service_uri", _("LinkSleeve service URI"),
                         "http://www.linksleeve.org/slv.php");
        parent::__construct();

        if ($this->check_comm) {
            $this->registerEventHandler("blogcomment", "OnInsert", "check_reply");
        }
        if ($this->check_comm) {
            $this->registerEventHandler("trackback", "OnReceive", "check_reply");
        }
        if ($this->check_comm) {
            $this->registerEventHandler("pingback", "OnInsert", "check_reply");
        }

    }

    function user_post_serialize(&$repl) {
        $ret = '';
        if ( strtolower(get_class($repl)) == "blogcomment" ) {
            $ret = $repl->name.' '.
                   $repl->email.' '.
                   $repl->url.' '.
                   $repl->subject.' '.
                   $repl->data;
            foreach ($repl->custom_fields as $fld=>$val) {
                $ret .= $repl->$fld.' ';
            }
        } elseif ( strtolower(get_class($repl)) == "trackback" ) {
            $ret = $repl->title.' '.
                   $repl->data.' '.
                   $repl->blog.' '.
                   $repl->url;
        } elseif ( strtolower(get_class($repl)) == "pingback" ) {
            $ret = $repl->title.' '.$repl->excerpt;
        }
        return $ret;
    }

    function mail_confirm_serialize(&$repl, $isspam=true) {
        if ($isspam) {
            $ret = spf_("The following %s has been approved by Linksleeve:\n",
                        get_class($repl));
        } else {
            $ret = spf_("The following %s has been marked as spam by Linksleeve and deleted:\n",
                        get_class($repl));
        }

        if ( strtolower(get_class($repl)) == "blogcomment" ) {
            $ret .= spf_("Name: %s\n", $repl->name).
                    spf_("E-mail: %s\n", $repl->email).
                    spf_("URL: %s\n", $repl->url).
                    spf_("IP address: %s\n", $repl->ip).
                    spf_("Subject: %s\n\n", $repl->subject).
                    $repl->data;
            foreach ($repl->custom_fields as $fld=>$val) {
                $ret .= $repl->$fld.' ';
            }
        } elseif ( strtolower(get_class($repl)) == "trackback" ) {
            $ret .= spf_("Date received: %s\n", $repl->ping_date).
                    spf_("IP address: %s\n", $repl->ip).
                    spf_("Blog: %s\n", $repl->blog).
                    spf_("URL: %s\n", $repl->url).
                    spf_("Title: %s\n\n", $repl->title).
                    $repl->data;
        } elseif ( strtolower(get_class($repl)) == "pingback" ) {
            $ret .= spf_("Date received: %s\n", $repl->ping_date).
                    spf_("IP address: %s\n", $repl->ip).
                    spf_("Source URL: %s\n", $repl->source).
                    spf_("Target URL: %s\n", $repl->target).
                    spf_("Title: %s\n\n", $repl->title).
                    $repl->excerpt;
        }
        return $ret;
    }

    function check_reply(&$repl) {

        $blog = NewBlog();
        $usr = NewUser($blog->owner);

        if (strtolower(get_class($repl)) == 'blogcomment' && $repl->uid) {
            return false;
        }

        # No need to check already invalidated replies.
        if ( (strtolower(get_class($repl)) == "blogcomment" && ! $repl->data) ||
             (strtolower(get_class($repl)) == "trackback" && ! $repl->url) ||
             (strtolower(get_class($repl)) == "pingback" && ! $repl->source) ) {
            return false;
        }

        $input = $this->user_post_serialize($repl);

        // Build the XML-RPC message
        $p = array(new xmlrpcval($input, 'string'));
        $msg = new xmlrpcmsg('slv', $p);

        $linkdata = parse_url($this->service_uri);

        $host = $linkdata['host'];
        $path = isset($linkdata['path']) ? $linkdata['path'] : '';
        $port = isset($linkdata['port']) ? $linkdata['port'] : 80;

        $cli = new xmlrpc_client($path, $host, $port);
        #$cli = new xmlrpc_client("/slv.php", "www.linksleeve.org", 80);
        #$cli->setDebug(1);
        $resp = $cli->send($msg);


        if (!$resp->faultCode()) {

            $val = $resp->value();

            if ($val->scalarval() == '0') {

                if ( $this->mail_conf > 0 && $usr->email() ) {
                    mail($usr->email(),
                          _("Notification - reply blocked by LinkSleeve"),
                          $this->mail_confirm_serialize($repl, true),
                          "From: LnBlog Linksleeve plugin <>");
                }

                if ( strtolower(get_class($repl)) == "blogcomment" ) {
                    $repl->data = '';
                } elseif ( strtolower(get_class($repl)) == "trackback" ) {
                    $repl->url = '';
                } elseif ( strtolower(get_class($repl)) == "pingback" ) {
                    $repl->source = '';
                }
            } elseif ($this->mail_conf == 2) {
                mail($usr->email(),
                          _("Notification - reply allowed by LinkSleeve"),
                          $this->mail_confirm_serialize($repl, false),
                          "From: LnBlog Linksleeve plugin <>");
            }

        } elseif ( $this->mail_conf > 0 && $usr->email() ) {
            mail($usr->email(),
                  _("Notification - LinkSleeve plugin error"),
                  _("The Linksleeve plugin encountered an error.\n").
                  spf_("Fault Code: %s\n", $resp->faultCode()).
                  spf_("Description: %s\n", $resp->faultString()).
                  $this->mail_confirm_serialize($repl, false),
                  "From: LnBlog Linksleeve plugin <>");
        }
    }

}
$plug = new Linksleeve();
