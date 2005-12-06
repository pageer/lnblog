<?php
class MetaData extends Plugin {

	function MetaData() {
		$this->plugin_desc = _("Add some META tags to the page.");
		$this->plugin_version = "0.2.0";
		$this->meta_keywords = "";
		$this->member_list = array();
		$this->member_list["meta_keywords"] = 
			array("description"=>_("Keywords for search engines"));
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

$meta = new MetaData();
$meta->registerEventHandler("page", "OnOutput", "addMetaTags");

?>
