<?php
class MarkdownTextProcessor extends HTMLTextProcessor
{
    public $filter_id = MARKUP_MARKDOWN;
    public $filter_name = 'Markdown';

    public function toHTML() {
        $parsedown = new Parsedown();
        $this->formatted = $parsedown->text($this->formatted);
        if ($this->entry) {
            $this->formatted = $this->fixAllUrls($this->formatted);
        }
    }
}
