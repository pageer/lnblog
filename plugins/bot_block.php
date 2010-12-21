<?php 
require_once("lib/plugin.php");
class BotBlock extends Plugin {
	function BotBlock () {
		global $PAGE;
		
		$this->plugin_version = "0.2.0";
		$this->plugin_desc = _("Attempts to block spambots from posting comments.");
		
		$this->addOption('block_links', 
		                 _('Block comments with HTML links in them.'), 
		                 true, "checkbox");
		$this->addOption('accessible', 
		                 _('Include text CAPTCHA for user agents without JavaScript'),
		                 true, 'checkbox');
	
		$this->getConfig();
		
		if ($this->block_links) {
			$this->registerEventHandler("blogcomment", "OnInsert", "checkMarkup");
		}
		
		$this->rand1 = rand(1, 100);
		$this->rand2 = rand(1, 100);
		$this->operator = rand(1, 3);
		$this->salt = phpversion().$_SERVER['REMOTE_ADDR'];
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
		global $PAGE;
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
		$PAGE->addInlineScript($script_data);
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
