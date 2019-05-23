<?php

use Prophecy\Argument;

class WebPagesTest extends \PHPUnit\Framework\TestCase {

    public function testEditEntry_WhenEmptyPostAndNotLoggedIn_Shows403Error() {
        $this->entry->isEntry()->willReturn(false);
        $this->entry->isArticle()->willReturn(false);
        $this->entry->getAutoPublishDate()->willReturn('');
        $this->entry->raiseEvent(Argument::any())->willReturn(null);
        $this->page->setDisplayObject(Argument::any())->willReturn(null);
        $this->user->checkLogin()->willReturn(false);

        $this->page->error(403, Argument::any())->shouldBeCalled();

        $this->webpage->entryedit();
    }

    public function testEditEntry_WhenNewPostAndNoPermissions_DoesNotPublish() {
        $_POST['body'] = "This is a test entry";
        $_POST['post'] = 'post';
        $this->entry->entryID()->willReturn('');
        $this->entry->isEntry()->willReturn(false);
        $this->entry->isPublished()->willReturn(false);
        $this->entry->isDraft()->willReturn(false);
        $this->entry->isArticle()->willReturn(false);
        $this->entry->getAutoPublishDate()->willReturn('');
        $this->entry->getPostData()->willReturn(null);
        $this->entry->raiseEvent(Argument::any())->willReturn(null);
        $this->entry->permalink()->willReturn('');
        $this->user->checkLogin()->willReturn(true);
        $this->system->canAddTo($this->blog, $this->user)->willReturn(false);
        $this->system->canModify($this->blog, $this->user)->willReturn(false);

        $this->publisher->publishEntry($this->entry, Argument::any())->shouldNotBeCalled();

        $this->webpage->entryedit();
    }

    public function testEditEntry_WhenNotLoggedIn_ShowsError() {
        $_POST['body'] = "This is a test entry";
        $_POST['post'] = 'post';
        $this->setUpEntryEditStubs(false);
        $this->entry->data = 'some data';
        $this->entry->isEntry()->willReturn(false);
        $this->entry->isPublished()->willReturn(false);
        $this->entry->isDraft()->willReturn(false);
        $this->system->canAddTo(Argument::any(), Argument::any())->willReturn(false);
        $this->system->canModify(Argument::any(), Argument::any())->willReturn(false);
        $this->page->addInlineScript(Argument::any());
        $this->user->username()->willReturn('test');

        $this->page->display(Argument::containingString("permission denied"), Argument::any())->shouldBeCalled();

        $this->webpage->entryedit();
    }

    public function testEditEntry_WhenLoggedInButNoPost_ShowsPage() {
        $this->setUpEntryEditStubs();
        $this->entry->data = 'some data';
        $this->entry->isEntry()->willReturn(false);
        $this->entry->isPublished()->willReturn(false);
        $this->entry->isDraft()->willReturn(false);
        $this->system->canAddTo(Argument::any(), Argument::any())->willReturn(true);
        $this->system->canModify(Argument::any(), Argument::any())->willReturn(true);

        $this->page->display(Argument::containingString("textarea"), Argument::any())->shouldBeCalled();

        $this->webpage->entryedit();
    }

    public function testEntryEdit_WhenEntryDoesNotExistAndPostParamPassed_Publishes() {
        $_POST['body'] = "This is a test entry";
        $_POST['post'] = 'post';
        $this->setUpEntryEditStubs();
        $this->entry->data = 'some data';
        $this->entry->send_pingback = false;
        $this->entry->isEntry()->willReturn(false);
        $this->entry->isPublished()->willReturn(false);
        $this->system->canAddTo($this->blog, $this->user)->willReturn(true);
        $this->system->canModify($this->blog, $this->user)->willReturn(false);

        $this->publisher->publishEntry($this->entry, Argument::any())->shouldBeCalled();

        $this->webpage->entryedit();
    }

    public function testEntryEdit_WhenEntryDoesNotExistAndPostedWithArticleParam_PublishesAsArticle() {
        $_POST['body'] = "This is a test entry";
        $_POST['post'] = 'post';
        $_POST['publisharticle'] = '1';
        $this->setUpEntryEditStubs();
        $this->entry->data = 'some data';
        $this->entry->send_pingback = false;
        $this->entry->isEntry()->willReturn(false);
        $this->entry->isPublished()->willReturn(false);
        $this->system->canAddTo($this->blog, $this->user)->willReturn(true);
        $this->system->canModify($this->blog, $this->user)->willReturn(false);

        $this->publisher->publishArticle($this->entry, Argument::any())->shouldBeCalled();

        $this->webpage->entryedit();
    }

    public function testEntryEdit_WhenEntryDoesNotExistAndDraftSavedWithArticleParam_SavesDraftWithArticleFlag() {
        $_POST['body'] = "This is a test entry";
        $_POST['draft'] = 'draft';
        $_POST['publisharticle'] = '1';
        $this->setUpEntryEditStubs();
        $this->entry->data = 'some data';
        $this->entry->send_pingback = false;
        $this->entry->isEntry()->willReturn(false);
        $this->entry->isPublished()->willReturn(false);
        $this->system->canAddTo($this->blog, $this->user)->willReturn(true);
        $this->system->canModify($this->blog, $this->user)->willReturn(false);

        $this->publisher->publishArticle($this->entry, Argument::any())->shouldNotBeCalled();
        $this->publisher->createDraft($this->entry, Argument::any())->shouldBeCalled();

        $this->webpage->entryedit();
    }
    public function testEntryEdit_WhenEntryDoesNotExistAndDraftParamPassed_CreatesDraft() {
        $_POST['body'] = "This is a test entry";
        $_POST['draft'] = 'draft';
        $this->setUpEntryEditStubs();
        $this->entry->data = 'some data';
        $this->entry->isEntry()->willReturn(false);
        $this->entry->isPublished()->willReturn(false);
        $this->system->canAddTo($this->blog, $this->user)->willReturn(true);
        $this->system->canModify($this->blog, $this->user)->willReturn(false);

        $this->publisher->createDraft($this->entry, Argument::any())->shouldBeCalled();

        $this->webpage->entryedit();
    }

    public function testEditEntry_WhenEntryDoesNotExistAndPublishFails_ShowsError() {
        $_POST['body'] = "This is a test entry";
        $_POST['post'] = 'post';
        $this->setUpEntryEditStubs();
        $this->entry->data = 'some data';
        $this->entry->isEntry()->willReturn(false);
        $this->entry->isPublished()->willReturn(false);
        $this->entry->isDraft()->willReturn(false);
        $this->system->canAddTo($this->blog, $this->user)->willReturn(true);
        $this->system->canModify($this->blog, $this->user)->willReturn(false);
        $this->publisher->publishEntry($this->entry, Argument::any())->willThrow(new Exception("Publish Failure!"));

        $this->page->display(Argument::containingString("Publish Failure!"), $this->blog)->shouldBeCalled();

        $this->webpage->entryedit();
    }

    public function testEditEntry_WhenEntryExistsButNotPublishedAndDraftParam_UpdatesEntry() {
        $_POST['body'] = "This is a test entry";
        $_POST['draft'] = 'draft';
        $this->setUpEntryEditStubs();
        $this->entry->data = 'some data';
        $this->entry->send_pingback = false;
        $this->entry->isEntry()->willReturn(true);
        $this->entry->isPublished()->willReturn(false);
        $this->entry->getAttachments()->willReturn([]);
        $this->system->canAddTo($this->blog, $this->user)->willReturn(false);
        $this->system->canModify($this->entry, $this->user)->willReturn(true);

        $this->publisher->update($this->entry)->shouldBeCalled();
        $this->page->redirect(Argument::any())->shouldBeCalled();

        $this->webpage->entryedit();
    }

    public function testEditEntry_WhenEntryExistsAndPublishedAndPostParamPassed_UpdatesEntry() {
        $_POST['body'] = "This is a test entry";
        $_POST['post'] = 'post';
        $this->setUpEntryEditStubs();
        $this->entry->data = 'some data';
        $this->entry->send_pingback = false;
        $this->entry->isEntry()->willReturn(true);
        $this->entry->isPublished()->willReturn(true);
        $this->entry->getAttachments()->willReturn([]);
        $this->system->canAddTo($this->blog, $this->user)->willReturn(false);
        $this->system->canModify($this->entry, $this->user)->willReturn(true);

        $this->publisher->update($this->entry, Argument::any())->shouldBeCalled();

        $this->webpage->entryedit();
    }

    public function testEditEntry_WhenEntryExistsAndNoUpdatePermissions_DoesNotUpdate() {
        $_POST['body'] = "This is a test entry";
        $this->entry->isEntry()->willReturn(true);
        $this->entry->isArticle()->willReturn(false);
        $this->entry->isPublished()->willReturn(false);
        $this->entry->isDraft()->willReturn(true);
        $this->entry->entryID()->willReturn('entries/2019/01/02_1234');
        $this->entry->getAutoPublishDate()->willReturn('');
        $this->entry->getPostData()->willReturn(null);
        $this->entry->raiseEvent(Argument::any())->willReturn(null);
        $this->entry->permalink()->willReturn('');
        $this->entry->getAttachments()->willReturn([]);
        $this->user->checkLogin()->willReturn(true);
        $this->system->canAddTo($this->blog, $this->user)->willReturn(false);
        $this->system->canModify($this->entry, $this->user)->willReturn(false);

        $this->publisher->update($this->entry, Argument::any())->shouldNotBeCalled();

        $this->webpage->entryedit();
    }

    public function testEditEntry_WhenEntryExistsAndUpdateFails_ShowsError() {
        $_POST['body'] = "This is a test entry";
        $_POST['draft'] = 'draft';
        $this->setUpEntryEditStubs();
        $this->entry->data = 'some data';
        $this->entry->isEntry()->willReturn(true);
        $this->entry->isPublished()->willReturn(false);
        $this->entry->isDraft()->willReturn(true);
        $this->entry->getAttachments()->willReturn([]);
        $this->system->canAddTo($this->blog, $this->user)->willReturn(false);
        $this->system->canModify($this->entry, $this->user)->willReturn(true);
        $this->publisher->update($this->entry)->willThrow(new Exception("Update Failure!"));

        $this->page->display(Argument::containingString("Update Failure!"), $this->blog)->shouldBeCalled();

        $this->webpage->entryedit();
    }

    public function testEditEntry_WhenEntryExistsAndPreviewAndSaveParamsPassed_UpdatesDraft() {
        $_POST['body'] = "This is a test entry";
        $_POST['preview'] = 'preview';
        $_GET['save'] = 'draft';
        $this->setUpEntryEditStubs();
        $this->entry->data = 'some data';
        $this->entry->send_pingback = false;
        $this->entry->get()->willReturn("This is a test entry");
        $this->entry->isEntry()->willReturn(true);
        $this->entry->isPublished()->willReturn(false);
        $this->entry->getAttachments()->willReturn([]);
        $this->user->exportVars(Argument::any())->willReturn(null);
        $this->system->canAddTo($this->blog, $this->user)->willReturn(false);
        $this->system->canModify($this->entry, $this->user)->willReturn(true);

        $this->publisher->update($this->entry, Argument::any())->shouldBeCalled();

        $this->webpage->entryedit();
    }

    public function testEditEntry_WhenEntryExistsAndPreviewParamPassedWithoutSaveParams_DoesNotSaveEntry() {
        $_POST['body'] = "This is a test entry";
        $_POST['preview'] = 'preview';
        $this->entry->get()->willReturn("This is a test entry");
        $this->setUpEntryEditStubs();
        $this->entry->data = 'some data';
        $this->entry->isEntry()->willReturn(true);
        $this->entry->isPublished()->willReturn(false);
        $this->entry->isDraft()->willReturn(true);
        $this->entry->getAttachments()->willReturn([]);
        $this->user->exportVars(Argument::any())->willReturn(null);
        $this->system->canAddTo($this->blog, $this->user)->willReturn(false);
        $this->system->canModify($this->entry, $this->user)->willReturn(true);
        $this->page->display(Argument::any(), Argument::any())->willReturn(null);

        $this->publisher->update($this->entry, Argument::any())->shouldNotBeCalled();

        $this->webpage->entryedit();
    }

    public function testEditEntry_WhenEntryDoesNotExistAndPreviewAndSaveParamsPassed_CreatesDraft() {
        $_POST['body'] = "This is a test entry";
        $_POST['preview'] = 'preview';
        $_GET['save'] = 'draft';
        $this->setUpEntryEditStubs();
        $this->setUpForNewEntryWithPermissions();
        $this->entry->data = 'some data';
        $this->entry->get()->willReturn("This is a test entry");
        $this->user->exportVars(Argument::any())->willReturn(null);

        $this->publisher->createDraft($this->entry, Argument::any())->shouldBeCalled();

        $this->webpage->entryedit();
    }

    public function testEditEntry_WhenEntryDoesNotExistAndPreviewPassedWithoutSave_DoesNotCreateDraft() {
        $_POST['body'] = "This is a test entry";
        $_POST['preview'] = 'preview';
        $this->setUpEntryEditStubs();
        $this->setUpForNewEntryWithPermissions();
        $this->entry->data = 'some data';
        $this->entry->get()->willReturn("This is a test entry");
        $this->user->exportVars(Argument::any())->willReturn(null);
        $this->page->display(Argument::any(), Argument::any())->willReturn(null);

        $this->publisher->createDraft($this->entry, Argument::any())->shouldNotBeCalled();

        $this->webpage->entryedit();
    }

    public function testEditEntry_WhenPreviewAndSaveAndIsAjax_PrintsResponseWithUrlEncodedContentAndDoesNotRedirect() {
        $_POST['body'] = "This is some markup";
        $_POST['preview'] = 'preview';
        $_GET['save'] = 'draft';
        $_GET['ajax'] = 1;
        $this->setUpEntryEditStubs();
        $this->setUpForNewEntryWithPermissions();
        $this->entry->data = 'some data';
        $this->entry->get()->willReturn("This is some markup");
        $this->user->exportVars(Argument::any())->willReturn(null);

        $this->publisher->createDraft($this->entry, Argument::any())->shouldBeCalled();
        $this->page->redirect(Argument::any())->shouldNotBeCalled();

        ob_start();
        $this->webpage->entryedit();
        $output = ob_get_clean();

        $expectedResponse = json_encode([
            'id'=>'asdf', 
            'exists' => false,
            'isDraft' => false,
            'content'=> 'This%20is%20some%20markup'
        ]);
        $this->assertEquals($expectedResponse, $output);
    }

    public function testEditEntry_WhenPreviewAndSaveButNotAjax_Redirects() {
        $_POST['body'] = "This is a test entry";
        $_POST['preview'] = 'preview';
        $_GET['save'] = 'draft';
        $this->setUpEntryEditStubs();
        $this->setUpForNewEntryWithPermissions();
        $this->entry->data = 'some data';

        $this->publisher->createDraft($this->entry, Argument::any())->shouldBeCalled();
        $this->page->redirect(Argument::any())->shouldBeCalled();

        $this->webpage->entryedit();
    }

    public function testEditEntry_WhenPreviewAndNotAjax_OutputsEntryText() {
        $_POST['body'] = "This is a test entry";
        $_POST['preview'] = 'preview';
        $this->setUpEntryEditStubs();
        $this->setUpForNewEntryWithPermissions();
        $this->entry->data = 'This is a test entry';
        $this->entry->get()->willReturn("This is a test entry");
        $this->user->exportVars(Argument::any())->willReturn(null);

        $this->page->display(Argument::containingString("This is a test entry"), Argument::any())->shouldBeCalled();

        $this->webpage->entryedit();
    }

    public function testEditEntry_WhenEntrySendsPingbacksWithErrors_ShowsWarnings() {
        $_POST['body'] = "This is a test entry";
        $_POST['post'] = 'post';
        $this->setUpForSuccessfulPost();
        $ping_data = array(
            array(
                'uri' => 'http://example.com/test',
                'response' => ['code' => 123, 'message' => 'This is an error'],
            ),
        );
        $this->entry->data = 'some data';
        $this->entry->isEntry()->willReturn(true);
        $this->entry->isPublished()->willReturn(false);
        $this->entry->isDraft()->willReturn(true);
        $this->entry->getAttachments()->willReturn([]);
        $this->system->canAddTo($this->blog, $this->user)->willReturn(true);
        $this->system->canModify($this->entry, $this->user)->willReturn(true);
        $this->sys_ini->value("entryconfig", 'AllowLocalPingback', 1)->willReturn(1);
        $this->sys_ini->value("entryconfig", 'EditorOnBottom', 0)->willReturn(0);
        $this->sys_ini->value("entryconfig", 'AllowInitUpload', 1)->willReturn(1);
        $this->page->refresh(Argument::any(), Argument::any())->willReturn(null);
        $this->publisher->publishEntry(Argument::any())->will(function($args) use ($ping_data) {
            EventRegister::instance()->activateEventFull(null, 'BlogEntry', 'PingbackComplete', $ping_data);
        });

        $this->page->display(Argument::containingString("This is an error"), Argument::any())->shouldBeCalled();

        $this->webpage->entryedit();
    }

    public function testEditEntry_WhenEntrySendsPingbacksWithoutErrors_NoWarningDisplayed() {
        $_POST['body'] = "This is a test entry";
        $_POST['post'] = 'post';
        $this->setUpForSuccessfulPost();
        $ping_data = array(
            array(
                'uri' => 'http://example.com/test',
                'response' => ['code' =>  0, 'message' => 'It worked'],
            ),
        );
        $this->entry->data = 'some data';
        $this->entry->isEntry()->willReturn(true);
        $this->entry->isPublished()->willReturn(false);
        $this->entry->getAttachments()->willReturn([]);
        $this->system->canAddTo($this->blog, $this->user)->willReturn(true);
        $this->system->canModify($this->entry, $this->user)->willReturn(true);
        $this->sys_ini->value("entryconfig", 'AllowLocalPingback', 1)->willReturn(1);
        $this->sys_ini->value("entryconfig", 'EditorOnBottom', 0)->willReturn(0);
        $this->sys_ini->value("entryconfig", 'AllowInitUpload', 1)->willReturn(1);
        $this->page->refresh(Argument::any(), Argument::any())->willReturn(null);
        $this->publisher->publishEntry(Argument::any())->will(function($args) use ($ping_data) {
            EventRegister::instance()->activateEventFull(null, 'BlogEntry', 'PingbackComplete', $ping_data);
        });

        $this->page->display(Argument::containingString("It worked"), Argument::any())->shouldNotBeCalled();

        $this->webpage->entryedit();
    }

    public function testEditEntry_WhenEntryHasUploadsWithErrors_ShowsWarnings() {
        $_POST['body'] = "This is a test entry";
        $_POST['post'] = 'post';
        $this->setUpForSuccessfulPost();
        $this->entry->data = 'some data';
        $this->entry->isEntry()->willReturn(true);
        $this->entry->isPublished()->willReturn(false);
        $this->entry->isDraft()->willReturn(true);
        $this->entry->getAttachments()->willReturn([]);
        $this->system->canAddTo($this->blog, $this->user)->willReturn(true);
        $this->system->canModify($this->entry, $this->user)->willReturn(true);
        $this->page->refresh(Argument::any(), Argument::any())->willReturn(null);
        $this->publisher->publishEntry(Argument::any())->will(function($args) {
            EventRegister::instance()->activateEventFull(null, 'BlogEntry', 'UploadError', array("Error moving uploaded file"));
        });

        $this->page->display(Argument::containingString("upload error"), Argument::any())->shouldBeCalled();
        $this->page->display(Argument::containingString("Error moving uploaded file"), Argument::any())->shouldBeCalled();

        $this->webpage->entryedit();
    }

    public function testEditEntry_WhenEntryHasUploadsWithoutErrors_NoWarningDisplayed() {
        $_POST['body'] = "This is a test entry";
        $_POST['post'] = 'post';
        $this->setUpForSuccessfulPost();
        $this->entry->data = 'some data';
        $this->entry->isEntry()->willReturn(true);
        $this->entry->isPublished()->willReturn(false);
        $this->entry->getAttachments()->willReturn([]);
        $this->system->canAddTo($this->blog, $this->user)->willReturn(true);
        $this->system->canModify($this->entry, $this->user)->willReturn(true);
        $this->page->refresh(Argument::any(), Argument::any())->willReturn(null);
        $this->publisher->publishEntry(Argument::any())->will(function($args) {
            EventRegister::instance()->activateEventFull(null, 'BlogEntry', 'UploadSuccess');
        });

        $this->page->display(Argument::containingString("upload errors"), Argument::any())->shouldNotBeCalled();

        $this->webpage->entryedit();
    }

    public function testWebmention_WhenValid_AddsWebmention() {
        $_POST['source'] = 'http://yoursite.com/test1';
        $_POST['target'] = 'https://mysite.com/test2';

        $this->webpage->webmention();

        $this->social_server->addWebmention(
            'http://yoursite.com/test1',
            'https://mysite.com/test2'
        )->shouldHaveBeenCalled();
    }

    public function testWebmention_WhenAddingThrowsInvalidReceive_Returns400() {
        $_POST['source'] = 'http://yoursite.com/test1';
        $_POST['target'] = 'https://mysite.com/test2';
        $this->social_server->addWebmention(
            'http://yoursite.com/test1',
            'https://mysite.com/test2'
        )->willThrow(new WebmentionInvalidReceive('test'));

        $this->webpage->webmention();

        $this->page->error(400, "\r\n\r\ntest")->shouldHaveBeenCalled();
    }

    public function testWebmention_WhenAddingThrowsUnexpectedError_Returns500() {
        $_POST['source'] = 'http://yoursite.com/test1';
        $_POST['target'] = 'https://mysite.com/test2';
        $this->social_server->addWebmention(
            'http://yoursite.com/test1',
            'https://mysite.com/test2'
        )->willThrow(new Exception());

        $this->webpage->webmention();

        $this->page->error(500)->shouldHaveBeenCalled();
    }

    protected function setUp() {
        EventRegister::instance()->clearAll();
        $_POST = [];
        $_GET = [];

        $this->prophet = new \Prophecy\Prophet();

        $this->system = $this->prophet->prophesize(System::class);
        $this->sys_ini = $this->prophet->prophesize(INIParser::class);
        $this->system->reveal()->sys_ini = $this->sys_ini->reveal();
        System::$static_instance = $this->system->reveal();

        $this->blog = $this->prophet->prophesize(Blog::class);
        $this->user = $this->prophet->prophesize(User::class);
        $this->entry = $this->prophet->prophesize(BlogEntry::class);
        $this->publisher = $this->prophet->prophesize(Publisher::class);
        $this->mapper = $this->prophet->prophesize(EntryMapper::class);
        $this->social_server = $this->prophet->prophesize(SocialWebServer::class);
        $this->page = $this->prophet->prophesize(Page::class);

        $this->webpage = new TestableWebPages($this->blog->reveal(), $this->user->reveal());

        $this->webpage->test_page = $this->page->reveal();
        $this->webpage->test_entry = $this->entry->reveal();
        $this->webpage->test_publisher = $this->publisher->reveal();
        $this->webpage->test_social_server = $this->social_server->reveal();
    }

    protected function tearDown() {
        $this->prophet->checkPredictions();
    }

    private function setUpEntryEditStubs($logged_in = true) {
        $this->entry->entryID()->willReturn('asdf');
        $this->entry->isArticle()->willReturn(false);
        $this->entry->getAutoPublishDate()->willReturn('');
        $this->entry->getPostData()->willReturn(null);
        $this->entry->raiseEvent(Argument::any())->willReturn(null);
        $this->entry->permalink()->willReturn('');
        $this->user->checkLogin()->willReturn($logged_in);
        $this->page->redirect(Argument::any())->willReturn(null);
        $this->page->setDisplayObject(Argument::any())->willReturn(null);
        $this->page->addStylesheet(Argument::any())->willReturn(null);
        $this->page->addScript(Argument::any())->willReturn(null);
        $this->page->addInlineScript(Argument::any())->willReturn(null);
    }

    private function setUpForNewEntryWithPermissions() {
        $this->entry->isEntry()->willReturn(false);
        $this->entry->isPublished()->willReturn(false);
        $this->entry->isDraft()->willReturn(false);
        $this->system->canAddTo($this->blog, $this->user)->willReturn(true);
        $this->system->canModify($this->entry, $this->user)->willReturn(true);
    }

    private function setUpForSuccessfulPost() {
        $_POST['body'] = "This is a test entry";
        $_POST['post'] = 'post';
        $this->setUpEntryEditStubs();
        $this->entry->data = 'This is a test entry';
        $this->entry->isEntry()->willReturn(false);
        $this->entry->isPublished()->willReturn(false);
        $this->system->canAddTo($this->blog, $this->user)->willReturn(true);
        $this->system->canModify($this->entry, $this->user)->willReturn(true);
    }

    private function setUpForSuccessfulSave($is_published) {
        $_POST['body'] = "This is a test entry";
        if ($is_published) {
            $_POST['post'] = 'post';
        } else {
            $_POST['draft'] = 'draft';
        }
        $this->setUpEntryEditStubs();
        $this->entry->data = 'This is a test entry';
        $this->entry->isEntry()->willReturn(true);
        $this->entry->isPublished()->willReturn($is_published);
        $this->system->canAddTo($this->blog, $this->user)->willReturn(true);
        $this->system->canModify($this->entry, $this->user)->willReturn(true);
    }

    private function setUpForSuccessfulPreviewWithSave() {
        $_POST['body'] = "This is a test entry";
        $_POST['preview'] = 'preview';
        $_GET['save'] = 'draft';
        $this->setUpEntryEditStubs();
        $this->entry->data = 'This is a test entry';
        $this->entry->isEntry()->willReturn(true);
        $this->entry->isPublished()->willReturn(true);
        $this->system->canAddTo($this->blog, $this->user)->willReturn(true);
        $this->system->canModify($this->entry, $this->user)->willReturn(true);
    }
}

class TestableWebPages extends WebPages {
    public $test_entry = null;
    public $test_page = null;
    public $test_publisher = null;

    protected function getPage() {
        return $this->test_page ?: parent::getPage();
    }

    protected function getEntry($path = false) {
        return $this->test_entry ?: parent::getEntry($path);
    }

    protected function getPublisher() {
        return $this->test_publisher ?: parent::getPublisher();
    }

    protected function getSocialWebServer() {
        return $this->test_social_server ?: parent::getSocialWebServer();
    }
}
