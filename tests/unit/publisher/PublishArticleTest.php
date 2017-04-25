<?php

use Prophecy\Argument;

class PublishArticleTest extends PublisherTestBase {

    /**
     * @expectedException EntryAlreadyExists
     */
    public function testPublishArticle_WhenTargetDirAlreadyExists_Throws() {
        $entry = $this->getTestDraftEntry();
        $this->fs->file_exists('./drafts/02_1234/entry.xml')->willReturn(true);
        $this->fs->is_dir('./content')->willReturn(true);
        $this->fs->is_dir('./content/some_stuff')->willReturn(true);

        $this->publisher->publishArticle($entry, $this->getTestTime());
    }

    public function testPublishArticle_WhenEntryDoesNotExist_SavesAsDraft() {
        $entry = new BlogEntry(null, $this->fs->reveal());
        $entry->article_path = 'some_stuff';
        $this->fs->is_dir('./drafts')->willReturn(true);
        $this->fs->mkdir_rec('./drafts/02_1234')->willReturn(true);
        $this->fs->is_dir('./content')->willReturn(true);
        $this->fs->is_dir('./content/some_stuff')->willReturn(false);
        $this->fs->file_exists('./drafts/02_1234/entry.xml')->willReturn(true);
        $this->fs->rename(Argument::any(), Argument::any())->willReturn(true);

        $this->fs->write_file('./drafts/02_1234/entry.xml', Argument::any())->willReturn(true)->shouldBeCalled();

        $this->publisher->publishArticle($entry, $this->getTestTime());
    }

    public function testPublishArticle_WhenEntryExists_RenamesToArticlePath() {
        $entry = $this->getTestDraftEntry();
        $this->fs->file_exists('./drafts/02_1234/entry.xml')->willReturn(true);
        $this->fs->is_dir('./content')->willReturn(true);
        $this->fs->is_dir('./content/some_stuff')->willReturn(false);

        $this->fs->rename('./drafts/02_1234', './content/some_stuff')->willReturn(true)->shouldBeCalled();

        $this->publisher->publishArticle($entry, $this->getTestTime());
    }

    /**
     * @expectedException   EntryRenameFailed
     */
    public function testPublishArticle_WhenDraftRenameFail_Throws() {
        $entry = $this->getTestDraftEntry();
        $this->fs->file_exists('./drafts/02_1234/entry.xml')->willReturn(true);
        $this->fs->is_dir('./content')->willReturn(true);
        $this->fs->is_dir('./content/some_stuff')->willReturn(false);

        $this->fs->rename('./drafts/02_1234', './content/some_stuff')->willReturn(false);

        $this->publisher->publishArticle($entry, $this->getTestTime());
    }

    public function testPublishAticle_WhenPublishSucceeds_SetsEntryWithUserFileIpAndDates() {
        $_SERVER['REMOTE_ADDR'] = '1.2.3.4';
        $entry = $this->getTestDraftEntry();
        $this->fs->file_exists('./drafts/02_1234/entry.xml')->willReturn(true);
        $this->user->username()->willReturn('billybob');
        $this->fs->is_dir('./content')->willReturn(true);
        $this->fs->is_dir('./content/some_stuff')->willReturn(false);
        $this->fs->rename('./drafts/02_1234', './content/some_stuff')->willReturn(true);

        $this->publisher->publishArticle($entry, $this->getTestTime());

        $expected_time = $this->getTestTime()->getTimestamp();
        $this->assertEquals('./content/some_stuff/entry.xml', $entry->file);
        $this->assertEquals('billybob', $entry->uid);
        $this->assertEquals($expected_time, $entry->post_ts);
        $this->assertEquals($expected_time, $entry->timestamp);
        $this->assertContains('2017-01-02 12:34', $entry->date);
        $this->assertEquals('1.2.3.4', $entry->ip);
    }

    public function testPublishArticle_WhenPathIsValid_RaisesArticleOnInsertEvent() {
        $entry = $this->setUpTestArticleForSuccessfulPublish();
        $event_stub = new PublisherEventTestingStub();
        EventRegister::instance()->addHandler('Article', 'OnInsert', $event_stub, 'eventHandler');

        $this->publisher->publishArticle($entry, $this->getTestTime());

        $this->assertTrue($event_stub->has_been_called);
    }
    
    public function testPublishArticle_WhenPathIsValid_RaisesArticleInsertCompleteEvent() {
        $entry = $this->setUpTestArticleForSuccessfulPublish();
        $event_stub = new PublisherEventTestingStub();
        EventRegister::instance()->addHandler('Article', 'InsertComplete', $event_stub, 'eventHandler');

        $this->publisher->publishArticle($entry, $this->getTestTime());

        $this->assertTrue($event_stub->has_been_called);
    }

    public function testPublishArticle_WhenPathIsNotValid_DoesNotRaiseArticleEvents() {
        $entry = $this->getTestDraftEntry();
        $this->fs->file_exists('./drafts/02_1234/entry.xml')->willReturn(true);
        $this->fs->is_dir(Argument::any())->willReturn(true);
        $event_stub = new PublisherEventTestingStub();
        EventRegister::instance()->addHandler('Article', 'OnInsert', $event_stub, 'eventHandler');
        EventRegister::instance()->addHandler('Article', 'InsertComplete', $event_stub, 'eventHandler');

        try {
            $this->publisher->publishArticle($entry, $this->getTestTime());
        } catch (Exception $e) {}

        $this->assertFalse($event_stub->has_been_called);
    }

    public function testPublishArticle_WhenPublishSucceeds_CreatesDirectoryWrappers() {
        $entry = $this->setUpTestArticleForSuccessfulPublish();
        
        $this->wrappers->createDirectoryWrappers('./content/some_stuff', ARTICLE_BASE)->shouldBeCalled();
        $this->wrappers->createDirectoryWrappers('./content/some_stuff/comments', ENTRY_COMMENTS)->shouldBeCalled();
        $this->wrappers->createDirectoryWrappers('./content/some_stuff/trackback', ENTRY_TRACKBACKS)->shouldBeCalled();
        $this->wrappers->createDirectoryWrappers('./content/some_stuff/pingback', ENTRY_PINGBACKS)->shouldBeCalled();

        $this->publisher->publishArticle($entry, $this->getTestTime());
    }

    public function testPublishArticle_WhenArticlesDirDoesNotExist_CreatesArticlesDirWrappers() {
        $entry = $this->setUpTestArticleForSuccessfulPublish();
        $this->fs->is_dir('./content/some_stuff')->willReturn(false);
        $this->fs->is_dir('./content')->willReturn(false);
        $this->wrappers->createDirectoryWrappers('./content/some_stuff', ARTICLE_BASE)->willReturn(true);
        $this->wrappers->createDirectoryWrappers('./content/some_stuff/comments', ENTRY_COMMENTS)->willReturn(true);
        $this->wrappers->createDirectoryWrappers('./content/some_stuff/trackback', ENTRY_TRACKBACKS)->willReturn(true);
        $this->wrappers->createDirectoryWrappers('./content/some_stuff/pingback', ENTRY_PINGBACKS)->willReturn(true);

        $this->wrappers->createDirectoryWrappers('./content', BLOG_ARTICLES)->willReturn(true)->shouldBeCalled();

        $this->publisher->publishArticle($entry, $this->getTestTime());
    }

    public function testPublishArticle_WhenArticlePathIsNotSet_PublishesWithPathGeneratedFromSubject() {
        $entry = $this->setUpTestArticleForSuccessfulPublish();
        $entry->subject = "Whatever THing";
        $entry->article_path = '';
        $this->fs->is_dir('./content/whatever_thing')->willReturn(false);

        $this->fs->rename('./drafts/02_1234', './content/whatever_thing')->willReturn(true)->shouldBeCalled();

        $this->publisher->publishArticle($entry, $this->getTestTime());
    }

    private function getTestDraftEntry() {
        $entry = new BlogEntry(null, $this->fs->reveal());
        $entry->file = './drafts/02_1234/entry.xml';
        $entry->article_path = 'some_stuff';
        return $entry;
    }

    private function setUpTestArticleForSuccessfulPublish() {
        $entry = $this->getTestDraftEntry();
        $this->fs->file_exists('./drafts/02_1234/entry.xml')->willReturn(true);
        $this->fs->is_dir('./content')->willReturn(true);
        $this->fs->is_dir('./content/some_stuff')->willReturn(false);
        $this->fs->rename('./drafts/02_1234', './content/some_stuff')->willReturn(true);
        return $entry;
    } 
}
