<?php
class TrackbackURIWrapper extends LnBlogObject {
	function TrackbackURIWrapper(&$ent) {
		$this->object = $ent;
		$this->base_uri = localpath_to_uri(dirname($ent->file));
		$this->separator = "&amp;";
	}
	
	function setEscape($val) { $this->separator = $val ? "&amp;" : "&"; }

	function trackback() { return $this->base_uri."#".$this->object->getAnchor(); }
	function pingback() { return $this->base_uri."#".$this->object->getAnchor(); }
	
	function permalink() { return $this->trackback(); }
	function delete() {
		$qs_arr = array();
		$parent = $this->object->getParent();
		$entry_type = is_a($this->object, 'Article') ? 'article' : 'entry';
		$qs_arr['blog'] = $parent->parentID();
		$qs_arr[$entry_type] = $parent->entryID();
		$qs_arr['delete'] = $this->object->getAnchor();
		return make_uri(INSTALL_ROOT_URL."pages/delcomment.php", $qs_arr);
	}
	
}