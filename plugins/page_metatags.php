<?php
# Plugin: MetaData
# Adds HTML META tags to the page heading.  This includes description, author,
# and generator values.  There is also an option to include a list of keywords
# for use by search engines.

class MetaData extends Plugin {

	function MetaData() {
		$this->plugin_desc = _("Add some META tags to the page.");
		$this->plugin_version = "0.2.0";
		$this->addOption('meta_keywords', _("Keywords for search engines"),
		                 "", "text");
		$this->getConfig();
	}

	function addMetaTags(&$page) {
		if (strtolower(get_class($page->display_object)) != "blog") {
			return false;
		}
		$page->addMeta($page->display_object->description, "description");
		$page->addMeta($this->meta_keywords, "keywords");
		$usr = NewUser($page->display_object->owner);
		$page->addMeta($usr->displayName(), "author");
		$page->addMeta(PACKAGE_NAME." ".PACKAGE_VERSION, "generator");
	}
}

$meta =& new MetaData();
$meta->registerEventHandler("page", "OnOutput", "addMetaTags");

?>
