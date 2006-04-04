<?php 

# Plugin: PageHeader
# Adds header text to the page banner.
#
# This plugin shows the blog name as a heading link in the banner.
# There is also an option to show the blog description as a lesser heading.

class PageHeader extends Plugin {

	function PageHeader() {
		$this->plugin_desc = _("Output a banner for the page.");
		$this->plugin_version = "0.2.1";
		$this->show_desc = false;
		$this->addOption("show_desc", _("Show description"), false,"checkbox");
		$this->getConfig();
	}

	function output($parm=false) {
		$blg = NewBlog();
		if ($blg->isBlog()) { 
			?>
<h1><a href="<?php echo $blg->getURL(); ?>" title="<?php echo $blg->description; ?>"><?php echo $blg->name; ?></a></h1>
<?php 
			if ($this->show_desc) { ?>
<h3><?php echo $blg->description;?></h3>
<?php
			}
		} else { 
?>
<h1><a href="<?php echo PACKAGE_URL; ?>"><?php echo PACKAGE_NAME; ?></a></h1>
<?php 
			if ($this->show_desc) { ?>
<h3><?php echo PACKAGE_DESCRIPTION; ?></h3>
<?php 
			}
		} 
	}
}

$bann = new PageHeader();
$bann->registerEventHandler("banner", "OnOutput", "output");
?>
