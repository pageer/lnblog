<?php

use LnBlog\Notifications\Notifier;

# Plugin: ReplyNotifier
# Sends e-mail notification when a reply is posted to an entry or article.
#
# This plugin will send an e-mail to the user who posted an entry whenever
# a comment, TrackBack, or Pingback is registered for that entry.  Note that
# this only works if the user has an e-mail address defined in his profile.
#
# No e-mail is sent for comments posted by the entry owner.

class ReplyNotifier extends Plugin
{
    private $notifier;

    function __construct(Notifier $notifier = null) {
        $this->notifier = $notifier ?: new Notifier();

        $this->plugin_desc = _("Sends an e-mail notification when a comment, TrackBack, or Pingback is submitted.");
        $this->addOption(
            "notify_comment", _("Send notification for comments"),
            true, "checkbox"
        );
        $this->addOption(
            "notify_trackback", _("Send notification for TrackBacks"),
            true, "checkbox"
        );
        $this->addOption(
            "notify_pingback", _("Send notification for Pingbacks"),
            true, "checkbox"
        );
        $this->plugin_version = "0.1.1";
        parent::__construct();
    }

    function send_message(&$ent, $subject, $data) {
        $u = NewUser($ent->uid);
        $curr_user = NewUser();
        $owner_reply = ($u->username() == $curr_user->username());

        if ($u->email() && !$owner_reply) {
            $this->notifier->sendEmail(
                $u->email(),
                $subject,
                $data,
                "From: LnBlog comment notifier <".EMAIL_FROM_ADDRESS.">"
            );
        }
    }

    function comment_notify(&$param) {

        if (! $param->isComment()) return false;

        $parent = $param->getParent();

        # If the comment was posted by the entry owner, then bail out.
        if ($param->uid == $parent->uid) return false;

        # If the comment is from a logged-in user, then retrieve
        # the user's name and e-mail.
        if ($param->uid) {
            $cmt_user = NewUser($param->uid);
            $param->name = $cmt_user->displayName();
            $param->email = $cmt_user->email();
            $param->url = $cmt_user->homepage();
        }

        $subject = spf_("Comment on %s", $parent->subject);
        $data = _("A new reader comment has been posted.\n").
                spf_("The URL for this comment is: %s\n\n", $param->permalink()).
                spf_("Name: %s\n", $param->name).
                spf_("E-mail: %s\n", $param->email).
                spf_("URL: %s\n", $param->url).
                spf_("IP address: %s\n", $param->ip).
                spf_("Subject: %s\n\n", $param->subject).
                $param->data;

        $this->send_message($parent, $subject, $data);
    }

    function trackback_notify(&$param) {

        if (! $param->url) return false;

        $parent = $param->getParent();

        $subject = spf_("Trackback on %s", $parent->subject);
        $data = _("A new TrackBack ping has been received.\n").
                spf_("The URL for this ping is: %s\n\n", $param->uri("trackback")).
                spf_("Date received: %s\n", $param->ping_date).
                spf_("IP address: %s\n", $param->ip).
                spf_("Blog: %s\n", $param->blog).
                spf_("URL: %s\n", $param->url).
                spf_("Title: %s\n\n", $param->title).
                $param->data;

        $this->send_message($parent, $subject, $data);
    }

    function pingback_notify(&$param) {

        if (! $param->source || ! $param->target) return false;

        $parent = $param->getParent();

        # Don't notify for pings to the author's other entries.
        if ($param->isLocal()) {
            $ent = get_entry_from_uri($param->source);
            if ($ent->uid == $parent->uid) {
                return false;
            }
        }

        $subject = spf_("Pingback on %s", $parent->subject);
        $data = _("A new Pingback ping has been received.\n").
                spf_("The URL for this ping is: %s\n\n", $param->uri("pingback")).
                spf_("Date received: %s\n", $param->ping_date).
                spf_("IP address: %s\n", $param->ip).
                spf_("Source URL: %s\n", $param->source).
                  spf_("Target URL: %s\n", $param->target).
                spf_("Title: %s\n\n", $param->title).
                $param->excerpt;

        $this->send_message($parent, $subject, $data);
    }

}

$notifier = new ReplyNotifier();
$notifier->registerEventHandler("blogcomment", "InsertComplete", "comment_notify");
$notifier->registerEventHandler("trackback", "ReceiveComplete", "trackback_notify");
$notifier->registerEventHandler("pingback", "InsertComplete", "pingback_notify");
