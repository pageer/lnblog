<body>
<!-- Container div for fixed-width layout. -->
<div id="container">
<!-- Rounded top border -->
<div class="roundtop"><img src="<?php echo getlink("tl.png");?>" alt="" width="15" height="15" class="corner" style="display: none" /></div>
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
<!-- Rounded bottom border -->
<div class="roundbottom"><img src="<?php echo getlink("bl.png");?>" alt="" width="15" height="15" class="corner" style="display: none" /></div>
</div>
</body>
