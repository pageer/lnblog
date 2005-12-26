<?php 

class GoogleSearch extends Plugin {

	function GoogleSearch() {
		$this->plugin_desc = _("Use Google to search the blog.");
		$this->plugin_version = "0.1.1";
		$this->addOption("caption", _("Caption for search panel"), _("Search"));
		$this->getConfig();
	}

	function output() {
		$blg = NewBlog();
		if (! $blg->isBlog() ) return false;
		$blog_url = $blg->getURL();
		if ($this->caption) { # Suppress empty header ?>
<h3><?php echo $this->caption; ?></h3><?php
		} ?>
<div class="panel">
<?php p_('Powered by <a href="http://www.google.com/">Google&reg;</a>', ''); ?>
<form method="get" action="http://www.google.com/search">
<fieldset style="border: 0">
<input type="text" name="q" />
<input type="hidden" name="as_sitesearch" value="<?php echo $blog_url; ?>" />
<input type="submit" name="btnG" value="<?php p_("Search"); ?>" />
</fieldset>
</form>
</div>
<?php
	}

}

$searchbar = new GoogleSearch();
$searchbar->registerEventHandler("sidebar", "OnOutput", "output");
?>
