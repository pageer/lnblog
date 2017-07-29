<?php
class BlogEntryURIWrapper extends LnBlogObject {
	function BlogEntryURIWrapper(&$ent) {
		$this->object = $ent;
		$this->base_uri = localpath_to_uri(dirname($ent->file));
		$this->separator = "&amp;";
	}
	
	function setEscape($val) { $this->separator = $val ? "&amp;" : "&"; }
	
	function permalink() {
		$ent =& $this->object;
		$pretty_file = $ent->calcPrettyPermalink();
		if ($pretty_file)
			$pretty_file = mkpath(dirname($ent->localpath()),$pretty_file);
		if ( file_exists($pretty_file) ) {
			# Check for duplicated entry subjects.
			$base_path = substr($pretty_file, 0, strlen($pretty_file)-5);
			$i = 2;
			if ( file_exists( $base_path.$i.".php") ) {
				while ( file_exists( $base_path.$i.".php" ) ) {
					$contents = file_get_contents($base_path.$i.".php");
					if (strpos($contents, basename(dirname($ent->file)))) {
						return localpath_to_uri($base_path.$i.".php");
					}
					$i++;
				}
				return $this->base_uri;
			} else {
				return localpath_to_uri($pretty_file);
			}
		} else {
			$pretty_file = $ent->calcPrettyPermalink(true);
			if ($pretty_file)
				$pretty_file = mkpath(dirname($ent->localpath()),$pretty_file);
			if ( file_exists($pretty_file) ) {
				return localpath_to_uri($pretty_file);
			} else {
				return $this->base_uri;;
			}
		}
	}
	
	function entry() { return $this->permalink(); }
	function page() { return $this->permalink(); } 
	
	function base() { return $this->base_uri; }
	function basepage() { return $this->base_uri."index.php"; } 
	
	function comment() { return $this->base_uri.ENTRY_COMMENT_DIR."/"; }
	function commentpage() { return $this->base_uri.ENTRY_COMMENT_DIR."/index.php"; }
	
	function send_tb() { return $this->base_uri.ENTRY_TRACKBACK_DIR."/?action=ping"; }
	function get_tb() { return $this->base_uri.ENTRY_TRACKBACK_DIR."/index.php"; }
	function trackback() { return $this->base_uri.ENTRY_TRACKBACK_DIR."/"; }
	function pingback() { return $this->base_uri.ENTRY_PINGBACK_DIR."/"; }
	function upload() {return $this->base_uri."/?action=upload"; }
	
	function edit() { return $this->base_uri."?action=editentry"; }
	function editDraft() {
		$b = NewBlog();
		return make_uri($b->uri('drafts'), 
		                array('action'=>'edit', 'draft'=>$this->object->entryID()));
	}
	
	function delete() {
		$ent =& $this->object;
		if ($ent->isDraft()) {
			$entry_type = 'draft';
		} else {
			$entry_type = $ent->isArticle() ? 'article' : 'entry';
		}
		$qs_arr['action'] = 'delentry';
		$qs_arr[$entry_type] = $ent->entryID();
		return make_uri($this->object->getParent()->uri('base'), $qs_arr);
	}
	
	function manage_reply() {
		return $this->base_uri."?action=managereplies";
	}
	function managereply() { return $this->manage_reply(); }
}
