<?php
class MetaData extends Plugin {

	function MetaData() {
		$this->plugin_desc = _("Add some META tags to the page.");
		$this->plugin_version = "0.1.0";
	}

	function addMetaTags(&$page) {
		if (strtolower(get_class($page->display_object)) != "blog") {
			return false;
		}
		$page->addMeta($page->display_object->description, "description");
		$usr = NewUser($page->display_object->owner);
		$page->addMeta($usr->displayName(), "author");
		$page->addMeta(PACKAGE_NAME." ".PACKAGE_VERSION, "generator");
	}
}

$meta = new MetaData();
$meta->registerEventHandler("page", "OnOutput", "addMetaTags");

?>
