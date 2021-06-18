<?php

namespace LnBlog\Attachments;

interface AttachmentContainer
{
    public function getAttachments();
    public function addAttachment($path);
    public function removeAttachment($name);
    public function getManagedFiles();
}
