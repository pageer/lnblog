<?php
class BlogURIHybrid extends BlogURIWrapper {

    function BlogURIHybrid(&$blog) {
        $this->object = $blog;
        $this->base_uri = localpath_to_uri($blog->home_path);
        $this->install_uri = INSTALL_ROOT_URL;
        $this->separator = "&amp;";
    }

    function addentry() {
        return make_uri($this->base_uri."pages/entryedit.php", array("action"=>"newentry"));
    }
    function addarticle() {
        return make_uri($this->base_uri, array("action"=>"newentry", "type"=>"article"));
    }

}
