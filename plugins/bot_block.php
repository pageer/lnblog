<?php 
require_once("lib/plugin.php");
class BotBlock extends Plugin {
	function BotBlock () {
		$this->plugin_version = "0.1.0";
		$this->plugin_desc = _("Attempts to block bots from posting comments.");
	}
	
	function make_tok() {
		$ip = get_ip();
		# This will depend on the number of plugins you have loaded.
		$cls = get_declared_classes();
		$tok = md5($ip.$cls);
		return $tok;
	}
	
	function check_tok(&$cmt) {
		$tok = $this->make_tok();
		if (! POST("rd") || POST("rd") != $tok) {
			$cmt->subject = '';
			$cmt->data = '';
		}
	}
	
	function add_hidden($parm=false) {
		$tok = $this->make_tok();
		echo '<input type="hidden" name="rd" value="'.$tok.'" />';
	}
}

$cftok =& new BotBlock();
$cftok->registerEventHandler("blogcomment", "OnInsert", "check_tok");
$cftok->registerEventHandler("commentform", "FormBegin", "add_hidden");
?>