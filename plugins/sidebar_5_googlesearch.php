<?php 

class GoogleSearch extends Plugin {

	function GoogleSearch() {
		$this->plugin_desc = "Use Google to search the blog.";
		$this->plugin_version = "0.1.0";
	}

	function output() {
		if (! defined("BLOG_ROOT") ) return false;
		$blg = NewBlog();
		$blog_url = $blg->getURL();
?>
<h3>Search</h3>
<div class="panel">
Powered by <a href="http://www.google.com/">Google</a>&reg;
<form method="get" action="http://www.google.com/search">
<fieldset style="border: 0">
<input type="text" name="q" />
<input type="hidden" name="as_sitesearch" value="<?php echo $blog_url; ?>" />
<input type="submit" name="btnG" value="Search" />
</fieldset>
</form>
</div>
<?php
	}

}

$searchbar = new GoogleSearch();
$searchbar->registerEventHandler("sidebar", "OnOutput", "output");
?>
