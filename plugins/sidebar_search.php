<?php
# Plugin: SidebarSearch
# This plugin adds a configurable search feature to blogs.  
#
# There are several options available for this plugin.  First, there are the
# display options.  This includes options to control the text and labels used
# to display the search box.  There is also a setting to control where the
# search box is displayed: the sidebar, the banner (where the blog title is),
# or the menubar (where the sitemap is).
#
# In addition, there is an option to set the search engine.  There are currently three choices.
#
# - Built-in search (default)
# - Google
# - Bing
# 
# The Google and Bing searches, as you might expect, will simply do a site-specific
# search at those search engines using your blog's URL.  Whether or not that search
# actually returns anything will depend on when Google/Bing last indexed your site.
# Obviously, if they haven't crawled your site since a post went live, that post won't
# show in the search results.
#
# The built-in search, on the other hand, is guaranteed to always have access to all
# your posts, because it is built right into the plugin and can access the data files
# on your server directly.  The down side is that the search is pretty dumb (it does
# naive sting matching) and not particularly fast.  For the typical blog,
# if you don't get many search requests then the search performance will probably
# be "good enough".  However, if you start getting lots of traffic, you'll probably
# want to switch to Google or Bing.

$do_output = (isset($_GET["show"]) && $_GET["show"] == "sb_search_results");
	
require_once INSTALL_ROOT.PATH_DELIM."lib/utils.php";

# Add this really massive if statements to that we don't end up declaring the 
# same class twice, i.e. if the page is called directly, this class will be defined 
# when it first loads and then again when the plugins are loaded.
if (! class_exists("SidebarSearch")):  # Start massive if statement

class SidebarSearch extends Plugin {

	public function __construct($do_output=0) {
		$this->plugin_desc = _("Search for terms in blog entries.");
		$this->plugin_version = "0.3.0";
		$this->addOption("caption", _("Caption for search panel"), _("Search"));
		$this->addOption("label", _("Search field label"), _('Search this weblog'));
		$this->addOption("label_in_box", _("Put label inside box (HTML5 only)"), false, "checkbox");
		$this->addOption("search_provider", _("Search engine"), "native", "select",
						 array(
							'native' => 'Built-in search',
							'google' => 'Google',
							'bing' => 'Bing',
						));
		$this->addOption("show_in", 
		                 _("Show search box in what part of page"), 
		                 "sidebar", "select", 
		                 array("sidebar"=>_("Sidebar"), 
		                       "banner"=>_("Banner"), 
		                       "menubar"=>_("Menubar"))
		                );

		$this->addOption('no_event',
			_('No event handlers - do output when plugin is created'),
			System::instance()->sys_ini->value("plugins","EventDefaultOff", 0), 
			'checkbox');

		parent::__construct();

		if ( ! ($this->no_event || 
		        System::instance()->sys_ini->value("plugins","EventForceOff", 0)) ) {
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
		
		switch ($this->search_provider) {
			case 'google':
				$form_url = "http://www.google.com/search";
				$method = "get";
				$search_boxes = array(
					'visible' => 'q',
					'hidden' => array('as_sitesearch' => $blg->getURL()),
				);
				break;
			case 'bing':
				$form_url = "http://www.bing.com/search";
				$method = "get";
				$search_boxes = array(
					'visible' => 'q',
					'hidden' => array('q1' => 'site:'.$blg->getURL()),
				);
				break;
			case 'native':
			default:
				#$form_url = INSTALL_ROOT_URL."plugins/sidebar_search.php?blog=".$blg->blogid."&amp;show=sb_search_results";
				$form_url = $blg->getURL().'?plugin=sidebar_search&amp;show=sb_search_results';
				$method = "get";
				$search_boxes = array(
					'visible' => 'sb_search_terms',
					'hidden' => array(
						#'blog' => $blg->blogid,
						'plugin' => 'sidebar_search',
						'show' => 'sb_search_results',
					),
				);
				break;
		}
		
		if ($this->caption && $this->show_in == 'sidebar'): /* Suppress empty header */ ?>
		<h3><?php echo $this->caption; ?></h3>
		<?php endif; ?>
		<div class="<?php echo $class?>">
		<form method="<?php echo $method?>" action="<?php echo $form_url?>">
		<fieldset style="border: 0">
			<?php if ($this->label && ! $this->label_in_box): ?>
			<label for="sb_search_terms" title="<?php echo $tooltip?>"><?php echo $this->label?></label>
			<?php endif; ?>
			<input type="text" id="sb_search_terms" name="<?php echo $search_boxes['visible']?>" <?php echo $placeholder?> />
			<?php foreach ($search_boxes['hidden'] as $name => $val): ?>
			<input type="hidden" name="<?php echo $name?>" value="<?php echo $val?>" />
			<?php endforeach;?>
			<input type="submit" value="<?php p_("Search"); ?>" />
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
		if (! isset($_GET["sb_search_terms"])) {
			return false;
		}
		$search_data = trim(GET("sb_search_terms"));
		if (preg_match("/\/.+\//", $search_data)) {
			$terms = array(trim($_GET["sb_search_terms"])."i");
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
		Page::instance()->setDisplayObject($blog);
		$LINK_LIST = $this->find_entries();
		if (! count($LINK_LIST)) $LINK_LIST = false;

		$tpl = NewTemplate(LIST_TEMPLATE);
		if (! $LINK_LIST) {
			$tpl->set("LIST_TITLE", _("No search results found."));
			$tpl->set("ITEM_LIST", array(_("No posts matched")));
		} elseif (trim(GET("sb_search_terms"))) {
			$tpl->set("LIST_TITLE", 
			          spf_('Search results for "%s"', GET("sb_search_terms")));
			$tpl->set("LINK_LIST", $LINK_LIST);
		} else {
			$tpl->set("LIST_TITLE", 
			          spf_('No search terms'));
			$tpl->set("LINK_LIST", $LINK_LIST);
		}

		$body = $tpl->process();

		Page::instance()->title = spf_("Search results - ", $blog->name);
		Page::instance()->display($body, $blog);
	}

}

endif; # End massive if statement

$sbsearch = new SidebarSearch();
if ($do_output) {
	$sbsearch->show_page();
} else {
	if (! PluginManager::instance()->plugin_config->value('sidebarsearch', 'creator_output', 0)) {
		$sb = new SidebarSearch();
	}
}
