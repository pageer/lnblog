<?php 
class CommentNotifier extends Plugin {
	
	function CommentNotifier() {
		$this->plugin_desc = _("Sends an e-mail notification when a comment is submitted.");
		$this->plugin_version = "0.1.0";
	}

	function send_notificaiton(&$param) {
		if ($param->isComment()) {
			$parent = $param->getParent();
			if ($parent->mail_notify) {
				$u = NewUser($parent->uid);
				if ($u->email() && ($u->username() != $param->uid) ) {
					# If the comment is from a logged-in user, then retrieve
					# the user's name and e-mail.
					if ($param->uid) {
						$cmt_user = NewUser($param->uid);
						$param->name = $cmt_user->name() ? 
						               $cmt_user->name() : 
						               $cmt_user->username();
						$param->email = $cmt_user->email();
					}
					@mail($u->email(), spf_("Comment on %s", $parent->subject),
					        _("A new reader comment has been posted.\n").
						 spf_("The URL for this comment is: %s\n\n", $param->permalink()).
					    spf_("Name: %s\n", $param->name).
					    spf_("E-mail: %s\n", $param->email).
					    spf_("URL: %s\n", $param->url).
						 spf_("Subject: %s\n\n", $param->subject).
					  	$param->data, "From: LnBlog comment notifier");
				}
			}
		}
	}

}

$notifier = new CommentNotifier();
$notifier->registerEventHandler("blogcomment", "InsertComplete", "send_notificaiton");

?>
