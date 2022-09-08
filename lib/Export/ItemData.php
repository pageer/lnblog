<?php

namespace LnBlog\Export;

use DateTime;

class ItemData
{
    public string $class = '';
    public string $title = '';
    public string $description = '';
    public string $owner_name = '';
    public string $owner_email = '';
    public string $owner_url = '';
    public ?DateTime $publish_date = null;
    public ?DateTime $update_date = null;
    public string $enclosure = '';
    public string $enclosure_type = '';
    public string $enclosure_size = '';
    public string $permalink = '';
    public string $comments_url = '';
    /**
     * @var string[] $tags
     */
    public array $tags = [];
}
