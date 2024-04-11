<?php

namespace LnBlog\Tests\Plugins;

//require_once __DIR__ . '/../../../plugins/author_bio.php';

use AuthorBio;
use LnBlog\Tests\LnBlogBaseTestCase;

class AuthorBioTest extends LnBlogBaseTestCase
{
    public function testOutput_NoPicture_OutputsBio(): void {
        $plugin = new AuthorBio();
        $plugin->title = 'My Bio';
        $plugin->bio = 'This is my bio';
        $plugin->markup_type = MARKUP_HTML;

        ob_start();
        $plugin->output();
        $output = ob_get_clean();

        $this->assertStringContainsString("My Bio", $output);
        $this->assertStringContainsString("This is my bio", $output);
    }

    public function testOutput_WithPicture_OutputsImage(): void {
        $plugin = new AuthorBio();
        $plugin->title = 'My Bio';
        $plugin->picture_url = 'https://foo.com/me.jpg';
        $plugin->bio = 'This is my bio';
        $plugin->markup_type = MARKUP_HTML;

        ob_start();
        $plugin->output();
        $output = ob_get_clean();

        $this->assertStringContainsString("My Bio", $output);
        $this->assertStringContainsString("This is my bio", $output);
        $this->assertStringContainsString("https://foo.com/me.jpg", $output);
        $this->assertStringContainsString("<img", $output);
    }

    public function testOutput_MarkdownBio_OutputsHtml(): void {
        $plugin = new AuthorBio();
        $plugin->bio = 'This _is_ my bio';
        $plugin->markup_type = MARKUP_MARKDOWN;

        ob_start();
        $plugin->output();
        $output = ob_get_clean();

        $this->assertStringContainsString("This <em>is</em> my bio", $output);
    }

    public function testOutput_NoBio_NoOutput(): void {
        $plugin = new AuthorBio();
        $plugin->bio = '';
        $plugin->picture_url = '';

        ob_start();
        $plugin->output();
        $output = ob_get_clean();

        $this->assertEmpty($output);
    }
}
