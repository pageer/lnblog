<?php

namespace LnBlog\Import;

use BlogComment;
use BlogEntry;
use Exception;
use Pingback;
use User;

# Class: ImportReport
# Contains the data for an import report, including details of the entities
# that were imported and any errors.
class ImportReport
{

    # Property: users
    # List of user names that were imported.
    public $users = [];

    # Property: entries
    # List of global entry IDs that were imported
    public $entries = [];

    # Property: drafts
    # List of global IDs for drafts that were imported
    public $drafts = [];

    # Property: articles
    # List of global IDs for articles that were imported
    public $articles = [];

    # Property: comments
    # List of global IDs for comments that were imported
    public $comments = [];

    # Property: pings
    # List of global IDs for pings that were imported
    public $pings = [];

    # Property: media_items
    # List of media items imported.  (Not currently supported.)
    public $media_items = [];

    # Property: user_errors
    # List of errors encountered in importing users
    public $user_errors = [];

    # Property: entry_errors
    # List of errors encountered in importing entries
    public $entry_errors = [];

    # Property: draft_errors
    # List of errors encountered in importing drafts
    public $draft_errors = [];

    # Property: article_errors
    # List of errors encountered in importing articles
    public $article_errors = [];

    # Property: comment_errors
    # List of errors encountered in importing comments
    public $comment_errors = [];

    # Property: ping_errors
    # List of errors encountered in importing pings
    public $ping_errors = [];

    public function addUser(User $user) {
        $this->users[] = $user->username();
    }

    public function addEntry(BlogEntry $entry) {
        $this->entries[] = $entry->globalID();
    }

    public function addEntryError(BlogEntry $entry, Exception $e) {
        $this->entry_errors[] = $e->getMessage() . ' - ' . $entry->subject;
    }

    public function addDraft(BlogEntry $entry) {
        $this->drafts[] = $entry->globalID();
    }

    public function addDraftError(BlogEntry $entry, Exception $e) {
        $this->draft_errors[] = $e->getMessage() . ' - ' . $entry->subject;
    }

    public function addArticle(BlogEntry $entry) {
        $this->articles[] = $entry->globalID();
    }

    public function addArticleError(BlogEntry $entry, Exception $e) {
        $this->article_errors[] = $e->getMessage() . ' - ' . $entry->subject;
    }

    public function addComment(BlogComment $comment) {
        $this->comments[] = $comment->globalID();
    }

    public function addCommentError(BlogComment $comment, Exception $e) {
        $this->comment_errors[] = $e->getMessage() . ' - ' . $comment->subject;
    }

    public function addPing(Pingback $comment) {
        $this->pings[] = $comment->globalID();
    }

    public function addPingError(Pingback $comment, Exception $e) {
        $this->ping_errors[] = $e->getMessage();
    }
}
