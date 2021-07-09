<?php

namespace LnBlog\Forms;

use BlogComment;
use BlogEntry;
use EventRegister;
use FormInvalid;
use GlobalFunctions;
use LnBlog\Forms\Renderers\InputRenderer;
use LnBlog\Forms\Renderers\TextAreaRenderer;
use PHPTemplate;
use Publisher;
use User;

class CommentForm extends BaseForm
{
    const TEMPLATE = 'comment_form_tpl.php';

    private $use_comment_link = false;
    private $anchor = 'commentsubmit';

    private $globals;
    private $parent;
    private $user;

    public function __construct(
        BlogEntry $parent,
        User $user,
        GlobalFunctions $globals
    ) {
        $this->parent = $parent;
        $this->user = $user;
        $this->globals = $globals;
        $this->action = $parent->uri('basepage');

        $this->fields = [
            'subject' => new FormField(
                'subject',
                new InputRenderer('text'),
                $this->noNewlines()
            ),
            'data' => new FormField(
                'data',
                new TextAreaRenderer(),
                $this->dataValid()
            ),
            'username' => new FormField(
                'username',
                new InputRenderer('text'),
                $this->noNewlines()
            ),
            'homepage' => new FormField(
                'homepage',
                new InputRenderer('text'),
                $this->filterValidateOptional(FILTER_VALIDATE_URL)
            ),
            'email' => new FormField(
                'email',
                new InputRenderer('text'),
                $this->filterValidateOptional(FILTER_VALIDATE_EMAIL)
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

        $this->initializeFieldsFromCookie($_COOKIE);
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
        $template->set('ANCHOR', $this->anchor);
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

        $result = $this->parent->addReply($comment);
        if (!$result) {
            $this->is_validated = false;
            $this->errors[] = _("Error: unable to add comment please try again.");
            throw new FormInvalid();
        }

        # If the "remember me" box is checked, save the info in cookies.
        if ($this->fields["remember"]->getValue()) {
            $path = "/";  # Do we want to do domain cookies?
            $exp = $this->globals->time() + 2592000;  # Expire cookies after one month.
            $this->globals->setcookie("comment_name", $this->fields["username"]->getValue(), $exp, $path);
            $this->globals->setcookie("comment_email", $this->fields["email"]->getValue(), $exp, $path);
            $this->globals->setcookie("comment_url", $this->fields["homepage"]->getValue(), $exp, $path);
            $this->globals->setcookie("comment_showemail", $this->fields["showemail"]->getRawValue(), $exp, $path);

            # Clear the non-remembered fields
            $this->fields['subject']->setRawValue('');
            $this->fields['data']->setRawValue('');
        } else {
            $this->clear();
        }

        return $comment;
    }

    private function initializeFieldsFromCookie(array $cookie) {
        $this->fields['homepage']->setRawValue($cookie['comment_url'] ?? '');
        $this->fields['username']->setRawValue($cookie['comment_name'] ?? '');
        $this->fields['email']->setRawValue($cookie['comment_email'] ?? '');
        $this->fields['showemail']->setRawValue($cookie['comment_showemail'] ?? '');
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
        return function (string $value): array {
            if (empty(trim($value))) {
                return [_("Error: you must include something in the comment body.")];
            }
            return [];
        };
    }

    private function toBool(): callable {
        return function (string $value): bool {
            return (bool) $value;
        };
    }

    private function filterValidateOptional($filter): callable {
        return function (string $value) use ($filter) {
            if ($value) {
                $result = \filter_var($value, $filter);
                if (!$result) {
                    return [_('Invalid value')];
                }
            }
            return [];
        };
    }
}
