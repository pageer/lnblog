<?php

class Articles extends Plugin {

	function Articles() {
		$this->plugin_desc = _("List the articles for a blog.");
		$this->plugin_version = "0.2.2";
		$this->header = _("Articles");
		$this->static_link = true;
		$this->custom_links = "links.htm";
		$this->addOption("header", _("Sidebar section heading"),
			_("Articles"), "text");
		$this->addOption("static_link", 
			_("Show link to list of static articles"),
			true, "checkbox");
		$this->addOption("custom_links", 
			_("File where additional links to display are stored"),
			"links.htm", "true");
		$this->getConfig();
	}
	
	function output($parm=false) {
		
		$blg = NewBlog();
		if (! $blg->isBlog()) return false;
		
		$art_list = $blg->getArticleList();
		if (count($art_list) > 0) { 
			if ($this->header) { # Suppress empty header ?>
<h3><a href="<?php echo $blg->getURL(false).BLOG_ARTICLE_PATH; ?>/"><?php echo $this->header; ?></a></h3><?php
			} ?>
<ul>
<?php	
			foreach($art_list as $dir) { ?>
<li><a href="<?php echo $dir["link"]; ?>"><?php echo $dir["title"]; ?></a></li>
<?php 
			} # End foreach loop 
			if (is_file($blg->home_path.PATH_DELIM.$this->custom_links)) {
				$data = file($blg->home_path.PATH_DELIM.$this->custom_links);
				foreach ($data as $line) {
?><li><?php echo $line;?></li><?php
				}
			}
			if ($this->static_link) { # Optionally show link to article index ?>
<li style="margin-top: 0.5em"><a href="<?php echo $blg->getURL(false).BLOG_ARTICLE_PATH; ?>/"><?php p_("All static pages");?></a></li><?php
			} 
			if ($blg->canModifyBlog()) {
?><li style="margin-top: 0.5em"><a href="<?php echo INSTALL_ROOT_URL;?>editfile.php?blog=<?php echo $blg->blogid;?>&amp;list=yes&amp;file=<?php echo $this->custom_links;?>"><?php p_("Add custom links");?></a></li>
<?php
			}?>
</ul>
<?php 	
		} # End if block 
	}  # End function
	
}

$art = new Articles();
$art->registerEventHandler("sidebar", "OnOutput", "output");
?>
