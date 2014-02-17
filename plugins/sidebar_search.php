<?php
# Determine if blogconfig.php has already been loaded.  
# If not, we will need to compensate for this.
$files = get_included_files();
foreach ($files as $f) {
	if (strstr($f, "blogconfig.php")) {
		$files = true;
		break;
	}
}

# If we haven't loaded blogconfig.php, i.e. if this page has been called directly,
# then we have to take care of that.  We set the INSTALL_ROOT (since we can be sure
# that it is the parent of the current directory), adjust the include_path, and 
# then include blogconfig.php to do the rest.
$do_output = false;
if ($files !== true) {
	session_start();
	$instdir = dirname(getcwd());
	#define("INSTALL_ROOT", $instdir);
	if (! defined("PATH_SEPARATOR") ) 
		define("PATH_SEPARATOR", strtoupper(substr(PHP_OS,0,3)=='WIN')?';':':');
	ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.$instdir);
	require_once("blogconfig.php");
	if (isset($_GET["show"]) && $_GET["show"] == "sb_search_results") {
		$do_output = true;
	}
}
	
require_once("lib/utils.php");

# Add this really massive if statements to that we don't end up declaring the 
# same class twice, i.e. if the page is called directly, this class will be defined 
# when it first loads and then again when the plugins are loaded.
if (! class_exists("SidebarSearch")) {  # Start massive if statement

class SidebarSearch extends Plugin {

	public function __construct($do_output=0) {
		global $SYSTEM;
		$this->plugin_desc = _("Search for terms in blog entries.");
		$this->plugin_version = "0.2.0";
		$this->addOption("caption", _("Caption for search panel"), _("Search"));
		$this->addOption("label", _("Search field label"), _('Search this weblog'));
		$this->addOption("label_in_box", _("Put label inside box (HTML5 only)"), false, "checkbox");
		$this->addOption("use_google", _("Search through Google"), false, "checkbox");
		$this->addOption("show_in", 
		                 _("Show search box in what part of page"), 
		                 "sidebar", "select", 
		                 array("sidebar"=>_("Sidebar"), 
		                       "banner"=>_("Banner"), 
		                       "menubar"=>_("Menubar"))
		                );

		$this->addOption('no_event',
			_('No event handlers - do output when plugin is created'),
			$SYSTEM->sys_ini->value("plugins","EventDefaultOff", 0), 
			'checkbox');

		$this->getConfig();

		if ( ! ($this->no_event || 
		        $SYSTEM->sys_ini->value("plugins","EventForceOff", 0)) ) {
			switch ($this->show_in) {
				case "banner":
					$this->registerEventHandler("banner", "OnOutput", "sidebar_panel");
					break;
				case "menubar":
					$this->registerEventHandler("menubar", "OnOutput", "sidebar_panel");
					break;
				default:
					$this->registerEventHandler("sidebar", "OnOutput", "sidebar_panel");
			}
		}
		
		if ($do_output) $this->sidebar_panel();

	}

	function sidebar_panel($param=false) {
		$blg = NewBlog();
		if (! $blg->isBlog()) return true;
		
		$tooltip = _("Search for posts containing a space-separated list of words. If the search sting is enclosed in forward slashes, it will be treated as a regular expression.");
		
		switch ($this->show_in) {
			case "banner": $class = "bannerpanel"; break;
			case "menubar": $class = "menupanel"; break; 
			default: $class = "panel";
		}
		
		$placeholder = $this->label_in_box ? ('placeholder="' . htmlspecialchars($this->label).  '"') : '';
		
		if ($this->caption && $this->show_in == 'sidebar'): /* Suppress empty header */ ?>
		<h3><?php echo $this->caption; ?></h3>
		<?php endif; ?>
		<div class="<?php echo $class?>">
		<?php if ($this->use_google): /* Use the Google search form */ ?>
		<form method="get" action="http://www.google.com/search">
		<fieldset style="border: 0">
			<?php if ($this->label && ! $this->label_in_box): ?>
			<label for="sb_search_terms" title="<?php echo $tooltip?>"><?php echo $this->label?></label>
			<?php endif; ?>
			<input type="text" name="q" <?php echo $placeholder?> />
			<input type="hidden" name="as_sitesearch" value="<?php echo $blg->getURL()?>" />
			<input type="submit" name="btnG" value="<?php p_("Search"); ?>" />
		</fieldset>
		</form>
		<?php else: /* Use the built-in search. */ ?>
		<form method="post" action="<?php echo INSTALL_ROOT_URL;?>plugins/sidebar_search.php?blog=<?php echo $blg->blogid;?>&amp;show=sb_search_results">
		<fieldset style="border: 0">
			<?php if ($this->label && ! $this->label_in_box): ?>
			<label for="sb_search_terms" title="<?php echo $tooltip?>"><?php echo $this->label?></label>
			<?php endif; ?>
			<input type="text" id="sb_search_terms" name="sb_search_terms" title="<?php echo $tooltip?>" <?php echo $placeholder?> />
			<input type="submit" id="sb_search_submit" name="sb_search_submit" value="<?php p_("Search"); ?>" />
		</fieldset>
		</form>
		<?php endif; ?>
		</div><?php
	}

	function find_entries() {
		# Start by parsing out the search terms.  We use a painfully simple
		# syntax where search terms are separated by spaces.  There are no 
		# logical connectives and only pages that match all terms are found.
		# Alternatively, if the search term is enclosed in slashes, then the 
		# search term is treated as a raw regular expression.
		$blg = NewBlog();
		if (! isset($_POST["sb_search_terms"])) return false;
		$search_data = trim(POST("sb_search_terms"));
		if (preg_match("/\/.+\//", $search_data)) {
			$terms = array(trim($_POST["sb_search_terms"])."i");
		} else {
			$temp_terms = explode(" ", $search_data);
			$terms = array();
			foreach ($temp_terms as $term) {
				if (trim($term) != "") $terms[] = "/".trim($term)."/i";
			}
		}
		if (count($terms) > 0) {
			$entry_list = $blg->getEntries(-1);
			$ret = array();
			foreach ($entry_list as $ent) {
				$res = 0;
				foreach ($terms as $trm) {
					if (preg_match($trm, $ent->data) || preg_match($trm, $ent->subject)) {
						$res++;
					} else {
						break;
					}
				}
				if ($res == count($terms)) {
					$ret[] = array("link"=>$ent->permalink(), "title"=>$ent->subject);
				}
			}
		} else {
			$ret = false;
		}
		return $ret;
	}

	function show_page($param=false) {
		global $PAGE;
		$blog = NewBlog();
		$PAGE->setDisplayObject($blog);
		$LINK_LIST = $this->find_entries();
		if (! count($LINK_LIST)) $LINK_LIST = false;

		$tpl = NewTemplate(LIST_TEMPLATE);
		if (! $LINK_LIST) {
			$tpl->set("LIST_TITLE", _("No search results found."));
			$tpl->set("ITEM_LIST", array(_("No posts matched")));
		} elseif (trim(POST("sb_search_terms"))) {
			$tpl->set("LIST_TITLE", 
			          spf_('Search results for "%s"', POST("sb_search_terms")));
			$tpl->set("LINK_LIST", $LINK_LIST);
		} else {
			$tpl->set("LIST_TITLE", 
			          spf_('No search terms'));
			$tpl->set("LINK_LIST", $LINK_LIST);
		}

		$body = $tpl->process();

		$PAGE->title = spf_("Search results - ", $blog->name);
		$PAGE->display($body, $blog);
	}

}

} # End massive if statement

$sbsearch = new SidebarSearch();
if ($do_output) {
	$sbsearch->show_page();
} else {
global $PLUGIN_MANAGER;
	if (! $PLUGIN_MANAGER->plugin_config->value('sidebarsearch', 'creator_output', 0)) {
		$sb = new SidebarSearch();
	}
}
