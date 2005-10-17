<?php 

class LoginOps extends Plugin {

	function Login() {
		$this->plugin_desc = "Adds a control panel to the sidebar.";
		$this->plugin_version = "0.1.0";
	}

	function output($parm=false) {
		# Check if the user is logged in and, if so, present 
		# administrative options.
		$usr = NewUser();
		if (! defined("BLOG_ROOT")) return false;
		$blg = NewBlog();
		$root = $blg->getURL();
		if ($usr->checkLogin()) { 
?>
<h3>Weblog Administration</h3>
<ul>
<li><a href="<?php echo $root; ?>new.php">Add new post</a></li>
<li><a href="<?php echo $root; ?>newart.php">Add new article</a></li>
<li><a href="<?php echo $root; ?>uploadfile.php">Upload file for blog</a></li>
<li><a href="<?php echo $root; ?>edit.php">Edit weblog settings</a></li>
<li><a href="<?php echo $root; ?>map.php">Edit custom sitemap</a></li>
<li><a href="<?php echo $root; ?>useredit.php">Edit User Information</a></li>
<li><a href="<?php echo $root; ?>logout.php">Logout <?php echo $usr->username(); ?></a></li>
</ul>
<?php 
# If the user isn't logged in, give him a login link.
		} else { 
?>
<h3><a href="<?php echo $root; ?>login.php">Login</a></h3>
<?php 
		}  # End if statement
	}   # End function
	
}

$login = new LoginOps();
$login->registerEventHandler("sidebar", "OnOutput", "output");
?>
