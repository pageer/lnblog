<?php

# Plugin: SiteMap
# Shows a user-defined site map in the menu bar.
#
# This plugin allows you to put a simple site map in the menubar of your 
# blogs.  The site map itself is stored in a file that you must edit by hand.
#
# Note that there are two formats for the site map.  The default format is
# simply a list of hyperlinks with one link per line.  When the page is 
# displayed, these will be converted to an HTML unordered list, with 
# a customizable label in front of it. 
#
# The second format, which is enabled by an option, is to treat the sitemap
# file as raw HTML to be included directly in the page.  This does not 
# create an automatic list for you or, in fact, do any processing on the
# file at all.  This option is used if you want to do anything fancy with 
# the menubar, such as adding drop-down menus with JavaScript or CSS.
#
# As of version 0.3.0 of this plugin (which ships with LnBlog 0.7.0), there is
# an auto-sitemap feature.  This is turned on by default and basically just
# puts links to all the registered blogs in the site map.  You can still use the
# old file-based sitemap as well, but it is no longer required.  If you use
# both, then the auto-generated links will be shown first and the custom ones
# will be second.  

class SiteMap extends Plugin {

	var $link_file;

	function SiteMap($do_output=0) {
		global $SYSTEM;

		$this->plugin_desc = _("Show a sitemap in the menubar");
		$this->plugin_version = "0.3.1";
		$this->list_header = _("Site Map");
		$this->no_markup = false;
		$this->addOption("auto_map", _("Automatically list all blogs in sitemap"),
		                 true, "checkbox");
		$this->addOption("link_file", _("Name of link-list file"),
		                 "sitemap.htm", "text");
		$this->addOption("list_header", _("Heading at start of menubar"),
		                 _("Site Map"), "text");
		$this->addOption("no_markup", 
			_("Use unmodified file contents as menubar (no auto-generated HTML)"),
			false, "checkbox");

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

	function output($parm=false) {
		global $SYSTEM;
		
		$map_file = '';
		$blog = NewBlog();
		
		if ( $blog->isBlog() && 
		     file_exists(BLOG_ROOT.PATH_DELIM.$this->link_file) ) {
			$map_file = BLOG_ROOT.PATH_DELIM.$this->link_file;
		} elseif (is_file(mkpath(USER_DATA_PATH,$this->link_file))) {
			$map_file = mkpath(USER_DATA_PATH,$this->link_file);
		}
		
		$markup = '';
		if ($this->auto_map) {
			$blogs = $SYSTEM->getBlogList();
			foreach ($blogs as $b) {
				$markup .= '<li><a href="'.$b->getURL().'" title="'.
				           $b->description.'">'.$b->name."</a></li>\n";
			}
		}
		
		if ($this->no_markup && is_file($map_file)) {
			$data = file($map_file);
			if ($markup) echo "<ul>\n$markup</ul>\n";  #Auto-generated links.
			foreach ($data as $line) echo $line;
		} else {
?>
<h2><?php echo $this->list_header; ?></h2>
<ul>
<?php 
			echo $markup;  # Output the auto-generated links, if any.
			if (is_file($map_file)) {
				$data = file($map_file);
				foreach ($data as $line) { 
					if (trim($line) != '') {
?>
<li><?php echo $line; ?></li>
<?php
					}
				}
			} elseif (! $this->auto_map) { ?>
<li><a href="/" title="<?php p_("Site home page"); ?>"><?php p_("Home"); ?></a></li>
<?php 
			} ?>
</ul>
<?php
		}
	}
	
	function showLink($param) {
		$blog = NewBlog();
		echo '<li><a href="'.$blog->uri('blog').'map.php">'.
			_("Edit custom sitemap").'</a></li>';
	}

}
$map =& new SiteMap(0);
$map->registerEventHandler("loginops", "PluginOutput", "showLink");
?>
