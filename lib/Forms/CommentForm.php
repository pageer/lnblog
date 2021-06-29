<?php

namespace LnBlog\Forms;

use BlogComment;
use BlogEntry;
use EventRegister;
use FormInvalid;
use LnBlog\Forms\Renderers\InputRenderer;
use LnBlog\Forms\Renderers\TextAreaRenderer;
use PHPTemplate;
use User;

class CommentForm extends BaseForm
{
    const TEMPLATE = 'comment_form_tpl.php';

    private $use_comment_link = false;
    private $parent;
    private $user;

    public function __construct(BlogEntry $parent, User $user) {
        $this->parent = $parent;
        $this->user = $user;
        $this->action = $parent->uri('basepage');

        $this->fields = [
            'subject' => new FormField(
                'subject',
                new InputRenderer('text'),
                $this->noNewlines(),
                $this->filterVar(FILTER_SANITIZE_ENCODED)
            ),
            'data' => new FormField(
                'data',
                new TextAreaRenderer(),
                $this->dataValid()
                // No converter - we do that at display time.
            ),
            'username' => new FormField(
                'username',
                new InputRenderer('text'),
                $this->noNewlines(),
                $this->filterVar(FILTER_SANITIZE_ENCODED)
            ),
            'homepage' => new FormField(
                'homepage',
                new InputRenderer('text'),
                $this->noNewlines(),
                $this->filterVar(FILTER_SANITIZE_URL)
            ),
            'email' => new FormField(
                'email',
                new InputRenderer('text'),
                null,
                $this->filterVar(FILTER_SANITIZE_EMAIL)
            ),
            'showemail' => new FormField(
                'showemail',
                new InputRenderer('checkbox'),
                null,
                $this->toBool()
            ),
            'remember' => new FormField(
                'remember',
                new InputRenderer('checkbox'),
                null,
                $this->toBool()
            ),
        ];
    }

    public function setUseCommentLink(bool $use_link) {
        $this->use_comment_link = $use_link;
    }

    protected function addTemplateData(PHPTemplate $template) {
        if ($this->parent->getCommentCount() == 0) {
            if ($this->use_comment_link) {
                $template->set('PARENT_TITLE', trim($this->parent->subject));
            }
            $template->set('PARENT_URL', $this->parent->permalink());
        }
        $field_errors = [];
        foreach ($this->fields as $field) {
            $field_errors = array_merge($field_errors, $field->getErrors());
        }
        $template->set('FIELD_ERRORS', $field_errors);
    }
    
    protected function doAction(): BlogComment {
        $comment = new BlogComment();

        $comment->name = $this->fields['username']->getValue();
        $comment->email = $this->fields['email']->getValue();
        $comment->url = $this->fields['homepage']->getValue();
        $comment->subject = $this->fields['subject']->getValue();
        $comment->data = $this->fields['data']->getValue();
        $comment->show_email = $this->fields['showemail']->getValue();
        $comment->uid = $this->user->username();

        EventRegister::instance()->activateEvent($comment, 'POSTRetreived');

        $result = $comment->insert($this->parent);
        if (!$result) {
            $this->is_validated = false;
            $this->errors[] = _("Error: unable to add commtent please try again.");
            throw new FormInvalid();
        }

        # If the "remember me" box is checked, save the info in cookies.
        if ($this->fields["remember"]->getValue()) {
            $path = "/";  # Do we want to do domain cookies?
            $exp = time() + 2592000;  # Expire cookies after one month.
            setcookie("comment_name", $this->fields["username"]->getValue(), $exp, $path);
            setcookie("comment_email", $this->fields["email"]->getValue(), $exp, $path);
            setcookie("comment_url", $this->fields["homepage"]->getValue(), $exp, $path);
            setcookie("comment_showemail", $this->fields["showemail"]->getValue(), $exp, $path);
        }

        $this->clear();

        return $comment;
    }

    private function intitializeFieldsFromCookie(PHPTemplate $comm_tpl) {
        $this->fields['subject']->setRawValue($_COOKIE['comment_url'] ?? '');
        $this->fields['username']->comm_tpl->set("COMMENT_NAME", $_COOKIE['comment_name'] ?? '');
        $this->fields['email']->comm_tpl->set("COMMENT_EMAIL", $_COOKIE['comment_email'] ?? '');
        $this->fields['showemail']->comm_tpl->set("COMMENT_SHOWEMAIL", $_COOKIE['comment_showemail' ?? '']);
    }

    private function noNewlines(): callable {
        return function (string $value): array {
            if (strpos($value, "\n") !== false) {
                return [_('Error: line breaks are only allowed in the comment body.  What are you, a spam bot?')];
            }
            return [];
        };
    }

    private function dataValid(): callable {
        $no_new_lines = $this->noNewlines();
        return function (string $value) use ($no_new_lines) {
            if (empty($value)) {
                return [_("Error: you must include something in the comment body.")];
            }
            return $no_new_lines($value);
        };
    }

    private function toBool(): callable {
        return function (string $value): bool {
            return (bool) $value;
        };
    }

    private function filterVar($filter): callable {
        return function (string $value) use ($filter) {
            return \filter_var($value, $filter);
        };
    }
}
