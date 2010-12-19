<?php

class Breadcrumbs extends Plugin {

	var $link_file;

	function Breadcrumbs($do_output=0) {
		global $SYSTEM;

		$this->plugin_desc = _("Show a \"bread-crumb\" trail indicating the user's current location in the blog.");
		$this->plugin_version = "0.1.0";
		$this->addOption("list_header", _("Heading at start of trail"),
		                 _("Location"), "text");
		$this->addOption("item_sep", _("Separator for location components (HTML)"),
		                 _("&lt;&lt;"), "text");

		$this->addOption('no_event',
			_('No event handlers - do output when plugin is created'),
			$SYSTEM->sys_ini->value("plugins","EventDefaultOff", 0), 
			'checkbox');

		$this->getConfig();

		if ( $this->no_event || 
		     $SYSTEM->sys_ini->value("plugins","EventForceOff", 0) ) {
			# If either of these is true, then don't set the event handler
			# and rely on explicit invocation for output.
		} else {
			$this->registerEventHandler("menubar", "OnOutput", "output");
		}
		
		if ($do_output) $this->output();
	}

	function list_wrap($uri, $text) {
		return '<li><a href="'.$uri.'">'.$text."</a></li>\n";
	}

	function output($parm=false) {
		global $SYSTEM;
		
		$blog = NewBlog();
		$ent = NewEntry();
		if (! $blog->isBlog() ) return false;

		$ret = '';

		$path = $_SERVER['PHP_SELF'];
		$pos = strpos($path, $blog->blogid);
		if ($pos !== false) $path = substr($path, $pos+strlen($blog->blogid));
		
		$tok = strtok($path, '/');
	
		while ($tok !== false) {
			if ($tok == BLOG_ENTRY_PATH) {
				$ret .= $this->list_wrap($blog->uri('archives'), _("Archives"));
			} elseif ($tok == BLOG_ARTICLE_PATH) {
				$ret .= $this->list_wrap($blog->uri('articles'), _("Articles"));
			} elseif (is_numeric($tok) && strlen($tok) == 4) {
				$year = $tok;
				$ret .= $this->list_wrap($blog->uri('listyear', $tok), $tok);
			} elseif (is_numeric($tok) && strlen($tok) == 2 && isset($year)) {
				$month = fmtdate("%B", mktime(0,0,0,$tok,1,2000));
				$ret .= $this->list_wrap($blog->uri('listmonth', $year, $tok), $month);
			} elseif ($tok == ENTRY_PINGBACK_DIR) {
				$ret .= $this->list_wrap($ent->uri('pingback'), _("Pingbacks"));
			} elseif ($tok == ENTRY_TRACKBACK_DIR) {
				$ret .= $this->list_wrap($ent->uri('trackback'), _("TrackBacks"));
			} elseif ($tok == ENTRY_COMMENT_DIR) {
				$ret .= $this->list_wrap($ent->uri('comment'), _("Comments"));
			#} elseif ($tok == 'sidebar_search.php') {
			#	$ret .= $this->list_wrap($ent->uri('comment'), _("Comments"));
			} elseif ($tok == 'index.php') {
				# Do nothing - we don't show the wrapper scripts.
			} elseif ($ent && $ent->isEntry()) {
				$ret .= $this->list_wrap($ent->uri('permalink'), $ent->subject);
			} elseif ($tok == 'sidebar_search.php') {
				$ret .= '<li>'._('Search results').'</li>';
			}

			$tok = strtok('/');
		}
	
		if (GET('action') == 'tags') {
			$ret .= $this->list_wrap($blog->uri('tags'), _('Tags'));
			if (GET('tag')) {
				$ret .= $this->list_wrap($blog->uri('tags', htmlspecialchars(GET('tag'))), htmlspecialchars(GET('tag')));
			}
		}
	
		if ($ret) {
		
			$ret = "<ul class=\"location\">\n".$this->list_wrap($blog->uri('blog'), $blog->name).$ret."</ul>\n";
			if ($this->list_header) $ret = '<h2>'.$this->list_header."</h2>\n".$ret;
	
			echo $ret;
		}
	}
	
}
$map =& new Breadcrumbs();
?>