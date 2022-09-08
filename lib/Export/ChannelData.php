<?php

namespace LnBlog\Export;

class ChannelData
{
    public string $class = '';
    public string $title = '';
    public string $description = '';
    public string $permalink = '';
    public string $owner_name = '';
    public string $owner_email = '';
    public string $owner_url = '';
    /**
     * @var string[] $tags
     */
    public array $tags = [];
    /**
     * @var ItemData[] $children
     */
    public array $children = [];
}
