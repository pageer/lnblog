<?php 
class CommentNotifier extends Plugin {
	
	function CommentNotifier() {
		$this->plugin_desc = "Sends an e-mail notification when a comment is submitted.";
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
					mail($u->email(), "Comment on ".$parent->subject,
					     "A new reader comment has been posted.\n".
						  "The URL for this comment is: ".$param->permalink()."\n\n".
					     "Name: ".$param->name."\n".
					     "E-mail: ".$param->email."\n".
					     "URL: ".$param->url."\n".
						  "Subject: ".$param->subject."\n\n".
					  	$param->data, "From: LnBlog comment notifier");
				}
			}
		}
	}

}

$notifier = new CommentNotifier();
$notifier->registerEventHandler("blogcomment", "InsertComplete", "send_notificaiton");

?>
