<?php

namespace LnBlog\Tests\Forms;

use BasePages;
use BlogComment;
use BlogEntry;
use FormInvalid;
use GlobalFunctions;
use LnBlog\Forms\CommentForm;
use LnBlog\Tests\LnBlogBaseTestCase;
use Prophecy\Argument;
use Publisher;
use User;

class FormFieldTest extends LnBlogBaseTestCase
{
    private $pages;
    private $entry;
    private $user;
    private $globals;

    public function testValidate_WhenAllFieldsValid_ReturnsTrue() {
        $data = $this->getValidData();

        $form = $this->getForm();
        $result = $form->validate($data);

        $this->assertTrue($result);
    }

    /**
     * @dataProvider fieldValidationFailureProvider
     */
    public function testValidate_WhenFieldHasInvalidValue_ReturnsFalse(array $changedData) {
        $data = $changedData + $this->getValidData();

        $form = $this->getForm();
        $result = $form->validate($data);

        $this->assertFalse($result);
    }

    public function fieldValidationFailureProvider(): array {
        return [
            'subject contains newlines' => [
                ['subject' => "This is a test\nand you fail"],
            ],
            'data is empty' => [
                ['data' => '   '],
            ],
            'username has newlines' => [
                ['username' => "Bob\nSmith"],
            ],
            'homepage not a url' => [
                ['homepage' => 'some random crap'],
            ],
            'email is invalid' => [
                ['email' => 'more random crap'],
            ],
        ];
    }

    public function testProcess_WhenSuccessful_InsertsComment() {
        $data = [
            'showemail' => '',
            'remember' => ''
        ];
        $data = $data + $this->getValidData();
        $this->entry->addReply(Argument::any(), null)->willReturnArgument(0);
        $this->entry->uri('basepage')->willReturn('someurl');

        $form = $this->getForm();
        $result = $form->process($data);

        $this->assertInstanceOf(BlogComment::class, $result);
        $this->assertEquals($data['username'], $result->name);
        $this->assertEquals($data['email'], $result->email);
        $this->assertEquals($data['homepage'], $result->url);
        $this->assertEquals($data['subject'], $result->subject);
        $this->assertEquals($data['data'], $result->data);
    }

    public function testProcess_WhenInsertFails_Throws() {
        $data = [
            'remember' => ''
        ];
        $data = $data + $this->getValidData();
        $this->entry->addReply(Argument::any(), null)->willReturn(false);
        $this->entry->uri('basepage')->willReturn('someurl');

        $this->expectException(FormInvalid::class);

        $form = $this->getForm();
        $result = $form->process($data);
    }

    public function testProcess_WhenRememberIsSet_SetsCookies() {
        $data = [
            'remember' => '1'
        ];
        $data = $data + $this->getValidData();
        $this->entry->addReply(Argument::any(), null)->willReturnArgument(0);
        $this->entry->uri('basepage')->willReturn('someurl');
        $this->globals->time()->willReturn(123);

        $this->globals->setcookie('comment_name', $data['username'], Argument::any(), '/')->shouldBeCalled();
        $this->globals->setcookie('comment_email', $data['email'], Argument::any(), '/')->shouldBeCalled();
        $this->globals->setcookie('comment_url', $data['homepage'], Argument::any(), '/')->shouldBeCalled();
        $this->globals->setcookie('comment_showemail', '1', Argument::any(), '/')->shouldBeCalled();

        $form = $this->getForm();
        $result = $form->process($data);
    }

    public function testRender_WhenSuccessfulPost_ClearsForm() {
        $data = [
            'remember' => ''
        ];
        $data = $data + $this->getValidData();
        $this->entry->addReply(Argument::any(), null)->willReturnArgument(0);
        $this->entry->uri('basepage')->willReturn('someurl');
        $this->entry->getCommentCount()->willReturn(1);

        $form = $this->getForm();
        $result = $form->process($data);
        $html = $form->render($this->pages->reveal());

        $this->assertStringNotContainsString($data['homepage'], $html);
        $this->assertStringNotContainsString($data['username'], $html);
        $this->assertStringNotContainsString($data['email'], $html);
        $this->assertStringNotContainsString('checked', $html);
    }

    public function testRender_WhenPostFails_LeavesForm() {
        $data = [
            'remember' => ''
        ];
        $data = $data + $this->getValidData();
        $this->entry->addReply(Argument::any(), null)->willReturn(false);
        $this->entry->uri('basepage')->willReturn('someurl');
        $this->entry->getCommentCount()->willReturn(1);

        $form = $this->getForm();
        try {
            $result = $form->process($data);
        } catch (FormInvalid $e) {
            // Swallow and continue
        }
        $html = $form->render($this->pages->reveal());

        $this->assertStringContainsString($data['homepage'], $html);
        $this->assertStringContainsString($data['username'], $html);
        $this->assertStringContainsString($data['email'], $html);
        $this->assertStringContainsString('checked', $html);
    }

    public function testRender_WhenCookiesAreSet_PopulatesFormValues() {
        $data = $this->getValidData();
        $_COOKIE['comment_url'] = $data['homepage'];
        $_COOKIE['comment_name'] = $data['username'];
        $_COOKIE['comment_email'] = $data['email'];
        $_COOKIE['comment_showemail'] = $data['showemail'];

        $form = $this->getForm();
        $html = $form->render($this->pages->reveal());

        $this->assertStringContainsString($data['homepage'], $html);
        $this->assertStringContainsString($data['username'], $html);
        $this->assertStringContainsString($data['email'], $html);
        $this->assertStringContainsString('checked', $html);
    }

    protected function setUp(): void {
        parent::setUp();

        $this->pages = $this->prophet->prophesize(BasePages::class);
        $this->entry = $this->prophet->prophesize(BlogEntry::class);
        $this->user = $this->prophet->prophesize(User::class);
        $this->globals = $this->prophet->prophesize(GlobalFunctions::class);
    }

    private function getValidData(): array {
        return [
            'subject' => 'Some subject',
            'data' => 'See my lame comment',
            'username' => 'Bubba Smith',
            'homepage' => 'http://example.com',
            'email' => 'bubba@example.com',
            'showemail' => '1',
            'remember' => '1'
        ];
    }

    private function getForm(): CommentForm {
        return new CommentForm(
            $this->entry->reveal(),
            $this->user->reveal(),
            $this->globals->reveal()
        );
    }
}
