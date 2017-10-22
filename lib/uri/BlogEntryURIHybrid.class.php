<?php
class BlogEntryURIHybrid extends BlogEntryURIWrapper {

    function BlogEntryURIHybrid(&$ent) {
        $this->object = $ent;
        $this->base_uri = localpath_to_uri(dirname($ent->file));
        $this->install_uri = INSTALL_ROOT_URL;
        $this->separator = "&amp;";
    }

    function edit() {
        $b = NewBlog();
        return make_uri($b->uri("addentry"),
                        array('entry'=>$this->object->entryID()));
    }

    function editDraft() {
        $b = NewBlog();
        return make_uri($b->uri("addentry"),
                        array('draft'=>$this->object->entryID()));
    }
}
