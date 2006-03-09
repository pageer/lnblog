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
	define("INSTALL_ROOT", $instdir);
	if (! defined("PATH_SEPARATOR") ) 
		define("PATH_SEPARATOR", strtoupper(substr(PHP_OS,0,3)=='WIN')?';':':');
	ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.$instdir);
	require_once("blogconfig.php");
	if (isset($_GET["show"]) && $_GET["show"] == "sb_search_results") {
		$do_output = true;
	}
}

# Add this really massive if statements to that we don't end up declaring the 
# same class twice, i.e. if the page is called directly, this class will be defined 
# when it first loads and then again when the plugins are loaded.
if (! class_exists("SidebarSearch")) {  # Start massive if statement

class SidebarSearch extends Plugin {

	function SidebarSearch() {
		$this->plugin_desc = _("Search for terms in blog entries.");
		$this->plugin_version = "0.1.0";
		$this->addOption("caption", _("Caption for search panel"), _("Search"));
		$this->getConfig();
	}

	function sidebar_panel($param=false) {
		$blg = NewBlog();
		if (! $blg->isBlog()) return true;
		$tooltip = _("Search for posts containing a space-separated list of words. If the search sting is enclosed in forward slashes, it will be treated as a regular expression.");
		if ($this->caption) { # Suppress empty header ?>
<h3><?php echo $this->caption; ?></h3><?php
		} ?>
<div class="panel">
<label for="sb_search_terms" title="<?php echo $tooltip;?>"><?php p_('Search this weblog');?></label>
<form method="post" action="<?php echo INSTALL_ROOT_URL;?>/plugins/sidebar_search.php?blog=<?php echo $blg->blogid;?>&amp;show=sb_search_results">
<fieldset style="border: 0">
<input type="text" id="sb_search_terms" name="sb_search_terms" title="<?php echo $tooltip; ?>" />
<input type="submit" id="sb_search_submit" name="sb_search_submit" value="<?php p_("Search"); ?>" />
</fieldset>
</form>
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
		$search_data = trim($_POST["sb_search_terms"]);
		if (get_magic_quotes_gpc()) $search_data = stripslashes($search_data);
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
		$blog = NewBlog();
		$page = NewPage(&$blog);
		$LINK_LIST = $this->find_entries();
		if (! count($LINK_LIST)) $LINK_LIST = false;

		$tpl = NewTemplate(LIST_TEMPLATE);
		if (! $LINK_LIST) {
			$tpl->set("LIST_TITLE", _("No search results found."));
			$tpl->set("ITEM_LIST", array(_("No posts matched")));
		} elseif (isset($_POST["sb_search_terms"]) && trim($_POST["sb_search_terms"])) {
			$tpl->set("LIST_TITLE", 
			          spf_('Search results for "%s"', $_POST["sb_search_terms"]));
			$tpl->set("LINK_LIST", $LINK_LIST);
		} else {
			$tpl->set("LIST_TITLE", 
			          spf_('No search terms'));
			$tpl->set("LINK_LIST", $LINK_LIST);
		}

		$body = $tpl->process();

		$page->title = spf_("Search results - ", $blog->name);
		$page->display($body, &$blog);
	}

}

} # End massive if statement

$sbsearch = new SidebarSearch();
if ($do_output) {
	$sbsearch->show_page();
} else {
	$sbsearch->registerEventHandler("sidebar", "OnOutput", "sidebar_panel");
}

?>
