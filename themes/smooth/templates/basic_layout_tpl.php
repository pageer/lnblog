<body>
<!-- Container div for fixed-width layout. -->
<div id="container">
<!-- Site banner -->
<div id="banner"><?php include("include_banner.php"); ?></div>
<!-- A menu/navigation bar -->
<div id="menubar"><?php include("include_menubar.php"); ?></div>
<div id="maincontainer">
<!-- A sidebar -->
<div id="sidebar"><?php include("include_sidebar.php"); ?></div>
<!-- Main page content -->
<div id="pagecontent">
<?php echo $PAGE_CONTENT; ?>
</div>
</div>
</div>
</body>
