<?php

namespace LnBlog\Tests\Plugins;

require_once __DIR__ . '/../../../plugins/bot_block.php';

use BlogComment;
use BotBlock;
use Generator;
use LnBlog\Tests\LnBlogBaseTestCase;
use Page;

class BotBlockTest extends LnBlogBaseTestCase
{
    /**
     * @dataProvider getLinkMarkup
     */
    public function testCheckMarkup(string $text, string $expected_text): void {
        $comment = new BlogComment();
        $comment->data = $text;

        $plugin = new BotBlock();
        $plugin->checkMarkup($comment);

        $this->assertEquals($expected_text, $comment->data);
    }

    public function getLinkMarkup(): Generator {
        yield ['This has no link', 'This has no link'];
        yield ['This has a <a href="http://foo.com">link</a>', ''];
        yield ['This has a <A HREF="HTTP://FOO.COM">LINK</A>', ''];
        yield ['This has a <a id="test" href="http://foo.com">link</a>', ''];
        yield ['This has a <a
            href="http://foo.com">
                link
            </a>', ''];
        yield ['This has a <a
            class="fizz"
            href="http://foo.com">link</a>', ''];
        yield['An <a name="foo">anchor</A>', 'An <a name="foo">anchor</A>'];
    }

    public function testCheckMarkup_NoLinkBlock_CommentUnchanged(): void {
        $text = 'This has a <a href="http://foo.com">link</a>';
        $comment = new BlogComment();
        $comment->data = $text;

        $plugin = new BotBlock();
        $plugin->block_links = false;
        $plugin->checkMarkup($comment);

        $this->assertEquals($text, $comment->data);
    }

    public function testCheckICToken_Success_TextUnchanged(): void {
        $text = 'This is a test';
        $comment = new BlogComment();
        $comment->data = $text;

        $plugin = $this->getCheckablePlugin();
        $properties = $plugin->getProperties();
        $_POST['link'] = $plugin->getLinkValue();
        $_POST['ictok'] = $properties['tok'];

        $plugin->checkICToken($comment);

        $this->assertEquals($text, $comment->data);
    }

    public function testCheckICToken_BadToken_CommentBlanked(): void {
        $text = 'This is a test';
        $comment = new BlogComment();
        $comment->data = $text;

        $plugin = $this->getCheckablePlugin();
        $properties = $plugin->getProperties();
        $_POST['link'] = 'fakeval';
        $_POST['ictok'] = $properties['tok'];

        $plugin->checkICToken($comment);

        $this->assertEmpty($comment->data);
    }

    public function testCheckICToken_ValueMissing_CommentBlanked(): void {
        $text = 'This is a test';
        $comment = new BlogComment();
        $comment->data = $text;

        $plugin = $this->getCheckablePlugin();
        $properties = $plugin->getProperties();
        $_POST['ictok'] = $properties['tok'];

        $plugin->checkICToken($comment);

        $this->assertEmpty($comment->data);
    }

    public function testCheckICToken_CheckTokenCorrupt_BlanksComment(): void {
        $text = 'This is a test';
        $comment = new BlogComment();
        $comment->data = $text;

        $plugin = $this->getCheckablePlugin();
        $_POST['link'] = $plugin->getLinkValue();
        $_POST['ictok'] = 'baddata';

        $plugin->checkICToken($comment);

        $this->assertEmpty($comment->data);
    }

   public function testCheckICToken_CheckTokenMissing_BlanksComment(): void {
        $text = 'This is a test';
        $comment = new BlogComment();
        $comment->data = $text;

        $plugin = $this->getCheckablePlugin();
        $_POST['link'] = $plugin->getLinkValue();

        $plugin->checkICToken($comment);

        $this->assertEmpty($comment->data);
    }

   public function testCheckICToken_NonWebContext_CommentUnchanged(): void {
        $text = 'This is a test';
        $comment = new BlogComment();
        $comment->data = $text;

        BotBlock::globalContext(BotBlock::CONTEXT_CLI);
        $plugin = $this->getCheckablePlugin();
        $plugin->checkICToken($comment);

        $this->assertEquals($text, $comment->data);
    }

    public function testOutputScript_AddsScript(): void {
        $page = $this->getMockPage();

        $plugin = $this->getCheckablePlugin();
        $properties = $plugin->getProperties();
        $plugin->outputScript($page);

        $this->assertCount(1, $page->scripts);
        $this->assertStringContainsString($properties['rand1'], $page->scripts[0]);
        $this->assertStringContainsString($properties['rand2'], $page->scripts[0]);
        $this->assertStringContainsString($properties['operator_symbol'], $page->scripts[0]);
        $this->assertStringContainsString('addEventListener', $page->scripts[0]);
    }

    public function testAddICForm_Accessible_AddsText(): void {
        $plugin = $this->getCheckablePlugin();
        $properties = $plugin->getProperties();

        ob_start();
        $plugin->addICForm();
        $output = ob_get_clean();

        $this->assertStringContainsString($properties['tok'], $output);
        $this->assertStringContainsString($properties['rand1'], $output);
        $this->assertStringContainsString($properties['rand2'], $output);
        $this->assertStringContainsString($properties['operator_text'], $output);
    }

    public function testAddICForm_NotAccessible_AddsJustTok(): void {
        $plugin = $this->getCheckablePlugin();
        $properties = $plugin->getProperties();
        $plugin->accessible = false;

        ob_start();
        $plugin->addICForm();
        $output = ob_get_clean();

        $this->assertStringContainsString($properties['tok'], $output);
        $this->assertStringNotContainsString($properties['rand1'], $output);
        $this->assertStringNotContainsString($properties['rand2'], $output);
        $this->assertStringNotContainsString($properties['operator_text'], $output);
    }

    protected function setUp(): void {
        BotBlock::globalContext(BotBlock::CONTEXT_WEB);
        parent::setUp();
    }

    private function getCheckablePlugin(): BotBlock {
        return new class () extends BotBlock {
            public function getProperties(): array {
                return [
                    'rand1' => $this->rand1,
                    'rand2' => $this->rand2,
                    'operator' => $this->operator,
                    'operator_text' => $this->getOper(true),
                    'operator_symbol' => $this->getOper(false),
                    'salt' => $this->salt,
                    'tok' => $this->tok,
                ];
            }
            public function getLinkValue(): int {
                return $this->computeValue();
            }
        };
    }

    private function getMockPage(): Page {
        return new class () extends Page {
            public $scripts = [];
            public function __construct() {}
            public function addInlineScript($text, $type = "text/javascript"): void {
                $this->scripts[] = $text;
            }
        };
    }
}
