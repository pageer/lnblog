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

use LnBlog\Plugins\PluginTemplate;

class BotBlock extends Plugin
{
    const PLUGIN_DIR = 'botblock';
    const SCRIPT_TEMPLATE = 'script_tpl.php';
    const FORM_TEMPLATE = 'form_tpl.php';

    public $block_links;
    public $accessible;

    protected $rand1;
    protected $rand2;
    protected $operator;
    protected $salt;
    protected $tok;

    /**
     * @codeCoverageIgnore
     */
    public static function getLoader(): callable {
        return function (): Plugin {
            return new self();
        };
    }

    public function __construct() {
        $this->plugin_version = "0.3.0";
        $this->plugin_desc = _("Attempts to block spambots from posting comments.");

        # Option: Block comments with HTML links
        # When enabled, this will cause the plugin to reject any comments that contian
        # HTML link code.  URLs will still be linkified via auto-code, but since HTML is
        # not allowed in comments, any that contain HTML links will be rejected.
        $this->addOption(
            'block_links',
            _('Block comments with HTML links in them.'),
            true, "checkbox"
        );

        # Option: Include text CAPTCHA without JavaScript
        # When this is enabled, an automatic CAPTCHA as described above will be made
        # accessible.  That is, the math problem will be displayed to the user if
        # JavaScript is disabled.  Otherwise, the user will just see a message that
        # JavaScript is required to post comments.
        $this->addOption(
            'accessible',
            _('Include text CAPTCHA for user agents without JavaScript'),
            true, 'checkbox'
        );

        parent::__construct();

        if ($this->block_links) {
            $this->registerEventHandler("blogcomment", "OnInsert", "checkMarkup");
        }

        $this->rand1 = rand(1, 100);
        $this->rand2 = rand(1, 100);
        $this->operator = rand(1, 3);
        $this->salt = phpversion() . ($_SERVER['REMOTE_ADDR'] ?? 'localhost');
        $tmp = $this->computeValue();
        $this->tok = $this->makeToken($tmp);

        $this->registerEventHandler("blogcomment", "OnInsert", "checkICToken");
        $this->registerEventHandler("commentform", "FormBegin", "addICForm");
        $this->registerEventHandler('page', 'OnOutput', 'outputScript');
    }

    public function checkMarkup($cmt): void {
        // Bail out if we're not blocking links.
        if (!$this->block_links) {
           return;
        }

        if (preg_match('/<a[^>]+href=/i', $cmt->data)) {
            $cmt->data = '';
        }
    }

    public function outputScript($page): void {
        $page = $page ?: Page::instance();
        $template = new PluginTemplate(self::PLUGIN_DIR, self::SCRIPT_TEMPLATE);
        $template->set('RAND1', $this->rand1);
        $template->set('RAND2', $this->rand2);
        $template->set('OPERATION', $this->getOper());
        $script_data = $template->process();
        $page->addInlineScript($script_data);
    }

    public function checkICToken($cmt): void {
        if (self::globalContext() !== self::CONTEXT_WEB) {
            return;
        }
        $tok = $this->makeToken($_POST['link'] ?? '');
        if ($tok != ($_POST['ictok'] ?? '')) {
            $cmt->data = '';
        }
    }

    public function addICForm(): void {
        $template = new PluginTemplate(self::PLUGIN_DIR, self::FORM_TEMPLATE);
        $template->set('ACCESSIBLE', $this->accessible);
        $template->set('RAND1', $this->rand1);
        $template->set('RAND2', $this->rand2);
        $template->set('TOKEN', $this->tok);
        $template->set('OPERATION', $this->getOper(true));
        $template->output();
    }

    protected function computeValue(): int {
        $tmp = $this->rand1;
        switch ($this->operator) {
            case 1: $tmp += $this->rand2; 
                break;
            case 2: $tmp -= $this->rand2; 
                break;
            case 3: $tmp *= $this->rand2; 
                break;
        }

        return $tmp;
    }

    protected function getOper($text=false): string {
        switch ($this->operator) {
            case 1:
                return $text ? 'sum' : '+';
            case 2:
                return $text ? 'difference' : '-';
            case 3:
                return $text ? 'product' : '*';
        }

        return '';
    }

    private function makeToken(string $value): string {
        return md5($value . $this->salt);
    }
}

return BotBlock::getLoader();
