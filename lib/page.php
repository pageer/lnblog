<?php
/*
    LnBlog - A simple file-based weblog focused on design elegance.
    Copyright (C) 2005 Peter A. Geer <pageer@skepticats.com>

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
*/

/*
Class: Page
Represents a web page.  This class is primarily concerned with making sure 
that the things in the HEAD part of the page are set up correctly.

Inherits:
<LnBlogObject>
*/

class Page extends LnBlogObject {

	/*
	Property: display_object
	Holds a reference to the primary object which is displayed on this page.
	The type of object *will vary* from page to page and may be set to false
	on some pages.  For most pages, it will be the current Blog object, but 
	BlogEntry and Article object are also common. */
	var $display_object;
	/*
	Property: doctype
	String holding the DOCTYPE for the page.  Should normally be set using a 
	named constant. */
	var $doctype;
	/*
	Property: title
	A string used for the page title. */
	var $title;
	/*
	Property: mime_type
	Represents the MIME type of the page. */
	var $mime_type;
	/*
	Property: charset
	Holds the character encoding to use for the page. */
	var $charset;
	var $rssfeeds;
	var $stylesheets;
	var $scripts;
	var $metatags;
	var $headers;
	
	function Page($ref=false) {
	
		$this->raiseEvent("OnInit");
		#if ($ref !== false) 
		$this->display_object = &$ref;
		$this->doctype = DEFAULT_DOCTYPE;
		# Set the default style sheets.
		$this->stylesheets = array("main.css", "banner.css", "menubar.css", 
		                           "sidebar.css");
		$this->rssfeeds = array();
		$this->scripts = array();
		$this->metatags = array();
		$this->headers = array();
		
		$this->title = '';

		$this->mime_type = DEFAULT_MIME_TYPE;
		$this->charset = DEFAULT_CHARSET;

		$this->raiseEvent("InitComplete");		
	}

	/*
	Method: addStylesheet
	Adds style sheets to be link into the page.

	Parameters:
	Takes a variable number of string parameters, each representing the
	filename of a CSS file.
	*/

	function addStylesheet() {
		$num_args = func_num_args();
		$arg_list = func_get_args();
		for ($i = 0; $i < $num_args; $i++) {
			$this->stylesheets[] = $arg_list[$i];
		}
	}

	/*
	Method: addRSSFeed
	Adds an RSS feed to the link elements of the page header.

	Parameters:
	href  - The URL of the feed file.
	type  - The MIME type of the feed, e.g. application/xml 
	        or application/rss+xml
	title - The title for the feed.
	*/

	function addRSSFeed($href, $type, $title) {
		$this->rssfeeds[] = array("href"=>$href, "type"=>$type, 
		                          "title"=>$title);
	}

	/*
	Method: addScript
	Adds a script file to the header of the page.

	Parameters:
	href - The URL of the script file.
	type - *Optional* MIME type of the script.  
	       The default is text/javascript.
	*/

	function addScript($href, $type="text/javascript") {
		$this->scripts[] = array("href"=>$href, "type"=>$type);
	}

	/*
	Method: addMeta
	Adds a META item to the page.

	Parameters:
	content   - The content of the meta tag.
	name      - *Optional* name attribute.
	httpequiv - *Optional* http-equiv attribute.
	*/
	function addMeta($content, $name=false, $httpequiv=false) {
		$this->metatags[] = array("content"=>$content, "name"=>$name, 
		                          "http-equiv"=>$httpequiv);
	}
	
	/*
	Method: addHeader
	Add an item to the HTTP header for the page.

	Parameters:
	name    - The header name.
	content - The content of the header.
	*/
	function addHeader($name, $content) {
		$this->headers[$name] = $content;
	}
	
	/*
	Method: redirect
	Redirect to another page.

	Parameters:
	url - The target URL to which to redirect.
	*/
	
	function redirect($url) {
		redirect($url);
	}
	
	/*
	Method: refresh
	Refresh the page.
	
	Parameters:
	url   - The URL of the page to refresh.
	delay - *Optional* delay of refresh.  Default is 0.
	*/
	
	function refresh($url, $delay=0) {
		refresh($url, $delay);
	}

	/*
	Method: display
	Displays the page, i.e. sends it to the browser.
	*/

	function display($page_body, $blog=false) {
		$this->raiseEvent("OnOutput");
	
		foreach ($this->headers as $name=>$value) {
			header($name.": ".$value);
		}
	
		$this->addMeta($this->mime_type."; charset=".$this->charset, false, "Content-type");
		$this->addMeta(PACKAGE_NAME." ".PACKAGE_VERSION, "generator");
		
		$head = NewTemplate(PAGE_HEAD_TEMPLATE);
		$head->set("DOCTYPE", $this->doctype);
		$head->set("PAGE_TITLE", $this->title);
		$head->set("METADATA", $this->metatags);
		$head->set("RSSFEEDS", $this->rssfeeds);
		$head->set("STYLESHEETS",$this->stylesheets);
		$head->set("SCRIPTS",$this->scripts);
		
		if (get_class($blog)) $blog->exportVars(&$head);
		$head->set("PAGE_CONTENT", $page_body);

		$this->raiseEvent("OutputComplete");

		echo $head->process();
		
	}
	
}

?>
