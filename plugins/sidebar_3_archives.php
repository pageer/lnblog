<?php

class Archives extends Plugin {

	function Archives() {
		$this->plugin_name = "List the months of archives for a blog.";
		$this->plugin_version = "0.1.0";
	}

	function output($parm=false) {
		if (! defined("BLOG_ROOT")) return false;
		$blg = NewBlog();
		
		$MAX_MONTHS = 6;
		
		$root = $blg->getURL();
		$month_list = $blg->getRecentMonthList($MAX_MONTHS);
?>
<h3><a href="<?php echo $root.BLOG_ENTRY_PATH; ?>/">Archives</a></h3>
<ul>
<?php
		foreach ($month_list as $month) {
			$ts = mktime(0, 0, 0, $month["month"], 1, $month["year"]);
			$link_desc = strftime("%B %Y", $ts);
?>
<li><a href="<?php echo $month["link"]; ?>"><?php echo $link_desc; ?></a></li>
<?php 
		}  # End of foreach loop.
?>
<li><a href="<?php echo $root.BLOG_ENTRY_PATH."/all.php"; ?>">Show all entries</a></li>
</ul><?php
	}  # End of function
}

$arch = new Archives();
$arch->registerEventHandler("sidebar", "OnOutput", "output");
?>
