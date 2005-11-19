<?php  
class Recent extends Plugin {

	function Recent() {
		$this->plugin_desc = _("Show some of the more recent posts in the sidebar.");
		$this->plugin_version = "0.1.0";
	}

	function output($parm=false) {
		
		$blg = NewBlog();
		if (! $blg->isBlog()) return false;
		
		# Show some of the more recent entries.  If we're on the "front page"
		# of the blog, then show the next set of entries.  Otherwise, show the 
		# most recent entries.
		$is_index = ( current_url() == BLOG_ROOT_URL || 
		              current_url() == BLOG_ROOT_URL."index.php");

		if ($is_index) {
			$next_list = $blg->getNextMax();
		} else {
			$next_list = $blg->getRecent();
		}

		if ( count($next_list) > 0 ) { 
			if ($is_index) {
?>
<h3><a href="<?echo $blg->getURL(); ?>"><?php p_("Older Entries"); ?></a></h3>
<?php 
			} else { # !$is_index 
?>
<h3><a href="<?echo $blg->getURL(); ?>"><?php p_("Recent Entries"); ?></a></h3>
<?php 
			} # End inner if
?>
<ul>
<?php 
			foreach ($next_list as $ent) { 
?>
<li><a href="<?php echo $ent->permalink(); ?>"><?php echo $ent->subject; ?></a></li>
<?php 
			}	 # End foreach
?>
</ul>
<?php 
		}  # End outer if
	} #End function init_output

}

$rec = new Recent();
$rec->registerEventHandler("sidebar", "OnOutput", "output");
?>
