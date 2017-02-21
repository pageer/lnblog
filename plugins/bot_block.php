<?php
# Plugin: BotBlock
# This implements some simple counter-measures to prevent comment spam.  There are currently
# two two options: link-blocking and a simple JavaScript CAPTCHA.
#
# The link-blocking feautre simply does what it says: it blocks any comment that
# contains an HTML link.  So if the comment contains an A tag with an HREF attribute,
# it will be rejected.
#
# The CAPTCHA is very simple and just involves some basic math.  The CAPTCHA is completely
# transparent to the user, as it is done in JavaScript.  In most cases,
# this is effectively a test of whether the client knows how to execute JavaScript.
# However, there is also an accessibility option
class BotBlock extends Plugin {
	function __construct() {
		
		$this->plugin_version = "0.2.1";
		$this->plugin_desc = _("Attempts to block spambots from posting comments.");
		
		# Option: Block comments with HTML links
		# When enabled, this will cause the plugin to reject any comments that contian
		# HTML link code.  URLs will still be linkified via auto-code, but since HTML is
		# not allowed in comments, and that contain HTML links will be rejected.
		$this->addOption('block_links', 
		                 _('Block comments with HTML links in them.'), 
		                 true, "checkbox");
		
		# Option: Include text CAPTCHA without JavaScript
		# When this is enabled, an automatic CAPTCHA as described above will be made
		# accessible.  That is, the math problem will be displayed to the user if
		# JavaScript is disabled.  Otherwise, the user will just see a message that
		# JavaScript is required to post comments.
		$this->addOption('accessible', 
		                 _('Include text CAPTCHA for user agents without JavaScript'),
		                 true, 'checkbox');
	
		parent::__construct();
		
		if ($this->block_links) {
			$this->registerEventHandler("blogcomment", "OnInsert", "checkMarkup");
		}
		
		$this->rand1 = rand(1, 100);
		$this->rand2 = rand(1, 100);
		$this->operator = rand(1, 3);
		$this->salt = phpversion().(isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'localhost');
		$tmp = $this->rand1;
		switch ($this->operator) {
			case 1: $tmp += $this->rand2; break;
			case 2: $tmp -= $this->rand2; break;
			case 3: $tmp *= $this->rand2; break;
		}
		
		$this->tok = md5( $tmp.$this->salt );
		
		$this->registerEventHandler("blogcomment", "OnInsert", "checkICToken");
		$this->registerEventHandler("commentform", "FormBegin", "addICForm");
		$this->registerEventHandler('page', 'OnOutput', 'outputScript');
	}
	
	function checkMarkup(&$cmt) {
		if (preg_match('/<a\s+href=/i', $cmt->data)) {
			$cmt->data = '';
		}
	}
	
	function getOper($text=false) {
		switch ($this->operator) {
			case 1:
				return $text ? 'sum' : '+';
			case 2:
				return $text ? 'difference' : '-';
			case 3:
				return $text ? 'product' : '*';
		}
	}
	
	function outputScript() {
		ob_start();
?>
function addIt() {
	var res = <?php echo $this->rand1.' '.$this->getOper().' '.$this->rand2; ?>;
	if (document.getElementById('link')) {
		document.getElementById('link').value = res;
		document.getElementById('ic_block').style.display = 'none';
	}
}
window.addEventListener('load', addIt, false);
<?php
		$script_data = ob_get_contents();
		ob_end_clean();
		Page::instance()->addInlineScript($script_data);
	}
	
	function checkICToken(&$cmt) {
		$tok = md5($_POST['link'].$this->salt);
		if ($tok != $_POST['ictok']) {
			$cmt->data = '';
		}
	}
	
	function addICForm() {
?>
<div id="ic_block">
<?php if ($this->accessible): ?>
	<label for="link"><?php pf_("Enter the %s of %s and %s.", $this->getOper(true), $this->rand1, $this->rand2);?></label>
	<input type="text" name="link" id="link" />
<?php else: ?>
	<em><?php p_("Sorry, but you must have JavaScript enabled to post comments.");?></em>
	<input type="hidden" name="link" id="link" />
<?php endif; ?>
	<input type="hidden" name="ictok" value="<?php echo $this->tok;?>" />
</div>
<?php
	}

}

$cftok = new BotBlock();
