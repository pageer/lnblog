<?php

class Articles extends Plugin {

	function Articles() {
		$this->plugin_desc = _("List the articles for a blog.");
		$this->plugin_version = "0.2.0";
		$this->header = _("Articles");
		$this->member_list = array();
		$this->member_list["header"] =
			array("description"=>_("Sidebar section heading"),
			      "default"=>_("Articles"));
		$this->getConfig();
	}
	
	function output($parm=false) {
		
		$blg = NewBlog();
		if (! $blg->isBlog()) return false;
		
		$art_list = $blg->getArticleList();
		if (count($art_list) > 0) { ?>
<h3><a href="<?php echo $blg->getURL(false).BLOG_ARTICLE_PATH; ?>/"><?php echo $this->header; ?></a></h3>
<ul>
<?php	
			foreach($art_list as $dir) { ?>
<li><a href="<?php echo $dir["link"]; ?>"><?php echo $dir["title"]; ?></a></li>
<?php 
			} # End foreach loop ?>
</ul>
<?php 
		} # End if block 
	}  # End function
	
}

$art = new Articles();
$art->registerEventHandler("sidebar", "OnOutput", "output");
?>
