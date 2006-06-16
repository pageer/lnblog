<?php 
class TrackbackNotifier extends Plugin {
	
	function TrackbackNotifier() {
		$this->plugin_desc = _("Sends an e-mail notification when a trackback ping is received.");
		$this->plugin_version = "0.1.1";
	}

	function send_notificaiton(&$param) {
		$parent = $param->getParent();
		if ($parent->mail_notify && $parent->allow_tb) {
			$u = NewUser($parent->uid);
			if ($u->email()) {
				if (! $param->url) return false;
				@mail($u->email(), spf_("Trackback on %s", $parent->subject),
						_("A new trackback ping has been received.\n").
					 spf_("The URL for this ping is: %s\n\n", $param->uri("trackback")).
					 spf_("Date received: %s\n", $param->ping_date).
					 spf_("IP address: %s\n", $param->ip).
					 spf_("Blog: %s\n", $param->blog).
					 spf_("URL: %s\n", $param->url).
					 spf_("Title: %s\n\n", $param->title).
					$param->data, "From: LnBlog comment notifier <>");
			}
		}
	}

}

$notifier = new TrackbackNotifier();
$notifier->registerEventHandler("trackback", "ReceiveComplete", "send_notificaiton");

?>
