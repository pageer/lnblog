This directory holds the plugins used by LnBlog.  The rules for how plugins 
are loaded is as follows:

1) When a page is loaded, LnBlog loads all files in this directory that end
   with a ".php" extension.  Files that do not end with this are ignored by 
	the plugin loader.

2) Individual files are loaded in alphabetical order by file name.

3) For plugins that write user-visible data, e.g. sidebar panels, the order 
   in which they are loaded IS IMPORTANT.  For example, the sidebar panels
	are displayed in the order in which they are loaded.

4) Subdirectories of this directory are ignored by the plugin loader.  This 
   means that you can temporarily disable a plugin without deleting it by 
	moving it to a subdirectory.
