<?php

interface AttachmentContainer {
    public function getAttachments();
    public function addAttachment($path);
    public function removeAttachment($name);
}
