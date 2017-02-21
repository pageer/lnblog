<?php
class BlogCommentURIWrapper extends LnBlogObject {
	function BlogCommentURIWrapper(&$ent) {
		$this->object = $ent;
		$this->base_uri = localpath_to_uri(dirname($ent->file));
		$this->separator = "&amp;";
	}
	
	function setEscape($val) { $this->separator = $val ? "&amp;" : "&"; }
	
	function permalink() { return $this->base_uri."#".$this->object->getAnchor(); }
	function comment() { return $this->permalink(); }
	function delete() {
		$qs_arr = array();
		$parent = $this->object->getParent();
		$entry_type = is_a($this->object, 'Article') ? 'article' : 'entry';
		$qs_arr['action'] = 'delcomment';
		$qs_arr[$entry_type] = $parent->entryID();
		$qs_arr['delete'] = $this->object->getAnchor();
		return make_uri($parent->getParent()->getURL(), $qs_arr);
	}
}