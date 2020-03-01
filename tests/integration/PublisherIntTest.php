<?php

use LnBlog\Tasks\TaskManager;

class PublisherIntTest extends \PHPUnit\Framework\TestCase {

    const TESTPATH = "temp/publishertest";

    public function testCreatePublisher() {
        $blog = new Blog();
        $blog->name = "Test Blog";
        $blog->insert(self::TESTPATH);

        $user = new User("testuser", "testpass");

        $publisher = new Publisher($blog, $user, NewFS(), new WrapperGenerator(NewFS()), new TaskManager());
        $this->assertTrue(is_dir(self::TESTPATH));

        return $publisher;
    }

    /**
     * @depends testCreatePublisher
     */
    public function testCreateDraftEntry($publisher) {
        $entry = new BlogEntry();
        $entry->subject = "Some Stuff";
        $entry->body = "This is some stuff, I guess.";

        $entry_path = self::TESTPATH . "/drafts/02_0345";
        $publisher->createDraft($entry, new DateTime("2017-01-02 03:45:00"));
        $draft = new BlogEntry($entry_path, NewFS());

        $this->assertTrue(file_exists($entry_path."/entry.xml"));
        $this->assertEquals("Some Stuff", $draft->subject);
        $this->assertEquals("This is some stuff, I guess.", $draft->body);
        $this->assertEquals("testuser", $draft->uid);

        return $publisher;
    }

    /**
     * @depends testCreateDraftEntry
     */
    public function testPublishEntryFromDraft($publisher) {
        $draft_path = self::TESTPATH . "/drafts/02_0345";
        $entry = new BlogEntry($draft_path);

        $entry_path = self::TESTPATH . "/entries/2017/03/04_1234";
        $publisher->publishEntry($entry, new DateTime('2017-03-04 12:34:00'));
        $published_entry = new BlogEntry($entry_path, NewFS());

        $this->assertTrue(file_exists($entry_path."/entry.xml"));
        $this->assertTrue(file_exists(self::TESTPATH."/entries/2017/03/Some_Stuff.php"));
        $this->assertFalse(file_exists($draft_path."/entry.xml"));
        $this->assertEquals("Some Stuff", $published_entry->subject);
        $this->assertEquals("This is some stuff, I guess.", $published_entry->body);
        $this->assertEquals("testuser", $published_entry->uid);

        return array($publisher, $entry);
    }

    /**
     * @depends testPublishEntryFromDraft
     */
    public function testUnpublishEntry($data) {
        list($publisher, $entry) = $data;
        $entry_path = self::TESTPATH . "/entries/2017/03/04_1234";
        $draft_path = self::TESTPATH . "/drafts/04_123400";

        $publisher->unpublish($entry);
        $draft = new BlogEntry($draft_path, NewFS());

        $this->assertFalse(file_exists($entry_path."/entry.xml"));
        $this->assertFalse(file_exists(self::TESTPATH."/entries/2017/03/Some_Stuff.php"));
        $this->assertTrue(file_exists($draft_path."/entry.xml"));
        $this->assertEquals("Some Stuff", $draft->subject);
        $this->assertEquals("This is some stuff, I guess.", $draft->body);
        $this->assertEquals("testuser", $draft->uid);

        return array($publisher, $draft);
    }

    /**
     * @depends testUnpublishEntry
     */
    public function testDeleteDraftEntry($data) {
        list($publisher, $entry) = $data;
        $draft_path = self::TESTPATH . "/drafts/04_123400";

        $publisher->delete($entry);
        $draft = new BlogEntry($draft_path, NewFS());

        $this->assertFalse(is_dir($draft_path));
    }

    /**
     * @depends testCreatePublisher
     */
    public function testPublishEntryFromNothing_CreatesFileAndPermalink($publisher) {
        $entry = new BlogEntry();
        $entry->subject = "Some Published Stuff";
        $entry->body = "This is some published stuff.";

        $entry_path = self::TESTPATH . "/entries/2017/02/03_0345";
        $publisher->publishEntry($entry, new DateTime("2017-02-03 03:45:00"));
        $published_entry = new BlogEntry($entry_path, NewFS());

        $this->assertTrue(file_exists($entry_path."/entry.xml"));
        $this->assertTrue(file_exists(self::TESTPATH."/entries/2017/02/Some_Published_Stuff.php"));
        $this->assertEquals("Some Published Stuff", $published_entry->subject);
        $this->assertEquals("This is some published stuff.", $published_entry->body);
        $this->assertEquals("testuser", $published_entry->uid);

        return array($publisher, $published_entry);
    }

    /**
     * @depends testPublishEntryFromNothing_CreatesFileAndPermalink
     */
    public function testUpdatePublishedEntry_UpdatesPermalinkAndContentAndMaintainsOldPermalink($data) {
        list($publisher, $entry) = $data;
        $entry_path = self::TESTPATH . "/entries/2017/02/03_0345";

        $entry->subject = "I Have a Thing";
        $entry->body = "Do some stuff.";
        $publisher->update($entry);
        $updated_entry = new BlogEntry($entry_path, NewFS());

        $this->assertTrue(file_exists(self::TESTPATH."/entries/2017/02/I_Have_a_Thing.php"));
        $this->assertTrue(file_exists(self::TESTPATH."/entries/2017/02/Some_Published_Stuff.php"));
        $this->assertEquals("I Have a Thing", $updated_entry->subject);
        $this->assertEquals("Do some stuff.", $updated_entry->body);

        return array($publisher, $updated_entry);
    }

    /**
     * @depends testUpdatePublishedEntry_UpdatesPermalinkAndContentAndMaintainsOldPermalink
     */
    public function testDeletePublishedEntry_DeletesEntryAndAllPermalinks($data) {
        list($publisher, $entry) = $data;

        $publisher->delete($entry);

        $this->assertFalse(file_exists(self::TESTPATH."/entries/2017/02/I_Have_a_Thing.php"));
        $this->assertFalse(file_exists(self::TESTPATH."/entries/2017/02/Some_Published_Stuff.php"));
        $this->assertFalse(file_exists(self::TESTPATH."/entries/2017/02/03_0345/entry.xml"));
    }

    /**
     * @depends testCreatePublisher
     */
    public function testPublishArticle_CreatesFile($publisher) {
        $entry = new BlogEntry();
        $entry->subject = "Some Published Stuff";
        $entry->body = "This is some published stuff.";

        $entry_path = self::TESTPATH . "/content/some_published_stuff";
        $publisher->publishArticle($entry, new DateTime("2017-02-03 03:45:00"));
        $published_entry = new BlogEntry($entry_path, NewFS());

        $this->assertTrue(file_exists($entry_path."/entry.xml"));
        $this->assertEquals("Some Published Stuff", $published_entry->subject);
        $this->assertEquals("This is some published stuff.", $published_entry->body);
        $this->assertEquals("testuser", $published_entry->uid);

        return array($publisher, $published_entry);
    }

    /**
     * @depends testPublishArticle_CreatesFile
     */
    public function testUnpublishArticle_MovesToDraft($data) {
        list($publisher, $entry) = $data;
        $article_path = self::TESTPATH . "/content/some_published_stuff";
        $draft_path = self::TESTPATH . "/drafts/03_034500";

        $publisher->unpublish($entry);
        $draft = new BlogEntry($draft_path);

        $this->assertFalse(file_exists($article_path."/entry.xml"));
        $this->assertTrue(file_exists($draft_path."/entry.xml"));
        $this->assertEquals("Some Published Stuff", $draft->subject);
        $this->assertEquals("This is some published stuff.", $draft->body);
    }

    /**
     * @depends testCreatePublisher
     */
    public function testPublishArticleFromDraft_UsesArticlePath($publisher) {
        $entry = new BlogEntry();
        $entry->subject = "Some Published Stuff";
        $entry->body = "This is some published stuff.";
        $entry->article_path = "some_path";
        $article_path = self::TESTPATH . "/content/some_path";

        $publisher->createDraft($entry, new DateTime("2017-02-03 03:46:00"));
        $draft = new BlogEntry(self::TESTPATH."/drafts/03_0346", NewFS());
        $publisher->publishArticle($draft, new DateTime("2017-02-03 04:45:00"));
        $entry = new BlogEntry($article_path, NewFS());

        $this->assertTrue(file_exists($article_path."/entry.xml"));
        $this->assertEquals("some_path", $entry->article_path);

        return array($publisher, $entry);
    }

    /**
     * @depends testPublishArticleFromDraft_UsesArticlePath
     */
    public function testDeleteArticle_RemovesFiles($data) {
        list($publisher, $entry) = $data;

        $publisher->delete($entry);

        $this->assertFalse(file_exists(self::TESTPATH."/content/some_path/entry.xml"));
    }

    /**** Setup ****/

    public static function setUpBeforeClass() {
        $fs = NewFS();
        if (is_dir(self::TESTPATH)) {
            $fs->rmdir_rec(self::TESTPATH);
        }
    }

    public static function tearDownAfterClass() {
        $fs = NewFS();
        if (is_dir(self::TESTPATH)) {
            $fs->rmdir_rec(self::TESTPATH);
        }
    }
}
