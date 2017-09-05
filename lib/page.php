<?php
/*
    LnBlog - A simple file-based weblog focused on design elegance.
    Copyright (C) 2005-2013 Peter A. Geer <pageer@skepticats.com>

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

Events:
OnInit         - Fired when a page object is first created.
InitComplete   - Fired after object initialization is complete.
OnOutput       - Fired when processing for HTML output starts.
OutputComplete - Fired after the HTML output has been performed.
*/

class Page extends LnBlogObject {

    /*
    Property: display_object
    Holds a reference to the primary object which is displayed on this page.
    The type of object *will vary* from page to page and may be set to false
    on some pages.  For most pages, it will be the current Blog object, but 
    BlogEntry and Article object are also common. */
    public $display_object = null;
    /*
    Property: doctype
    String holding the DOCTYPE for the page.  Should normally be set using a 
    named constant. */
    public $doctype = '<!DOCTYPE html>';
    /*
    Property: title
    A string used for the page title. */
    public $title = '';
    /*
    Property: mime_type
    Represents the MIME type of the page. */
    public $mime_type = DEFAULT_MIME_TYPE;
    /*
    Property: charset
    Holds the character encoding to use for the page. */
    public $charset = DEFAULT_CHARSET;
    public $rssfeeds = array();
    public $stylesheets = array();
    public $inline_stylesheets = array();
    public $scripts = array();
    public $inline_scripts = array();
    public $metatags = array();
    public $headers = array();
    public $links = array();
    
    public function __construct($ref=null) {
    
        $this->raiseEvent("OnInit");
        $this->display_object = $ref;

        if (LOCAL_JQUERY_NAME) {
            $this->addScript(LOCAL_JQUERY_NAME);
        } else {
            $this->addExternalScript('//code.jquery.com/jquery-1.11.3.min.js');
        }
        $this->addScript("lnblog_lib.js");
        $this->addStylesheet("main.css");
        
        $this->addInlineScript('window.INSTALL_ROOT = "'.INSTALL_ROOT_URL.'"');
        
        $this->raiseEvent("InitComplete");      
    }
    
    # Method: includeJqueryUi
    # Add links to include jQuery UI.
    public function includeJqueryUi() {
        if (LOCAL_JQUERYUI_NAME) {
            $this->addScript(LOCAL_JQUERYUI_NAME);
        } else {
            $this->addExternalScript('//code.jquery.com/ui/1.11.4/jquery-ui.min.js');
        }
        if (LOCAL_JQUERYUI_THEME_NAME) {
            $this->addStylesheet(LOCAL_JQUERYUI_THEME_NAME);
        } else {
            $theme = defined('JQUERYUI_THEME') ? JQUERYUI_THEME : DEFAULT_JQUERYUI_THEME;
            $this->addExternalStylesheet("//code.jquery.com/ui/1.11.4/themes/$theme/jquery-ui.css");
        }
    }
    
    # Method: instance
    # Get the instance for the page singleton.
    public static function instance() {
        static $inst;
        if (! isset($inst)) {
            $inst = new Page();
        }
        return $inst;
    }
    
    # Method: setDisplayObject
    # Sets the object which the page is currently displaying.
    #
    # Parameters:
    # ref - A reference to the object to set.
    public function setDisplayObject($ref) {
        $this->display_object = $ref;
    }

    /*
    Method: addStylesheet
    Adds style sheets to be link into the page.

    Parameters:
    Takes a variable number of string parameters, each representing the
    filename of a CSS file.
    */
    public function addStylesheet() {
        $num_args = func_num_args();
        $arg_list = func_get_args();
        for ($i = 0; $i < $num_args; $i++) {
            $this->stylesheets[] = array('link'=>$arg_list[$i]);
        }
    }
    
    /*
    Method: addInlineStylesheet
    Adds style sheets to be added inline into the page.

    Parameters:
    Takes a variable number of string parameters, each containing the CSS code
    to use for the inline styles.
    */
    public function addInlineStylesheet() {
        $num_args = func_num_args();
        $arg_list = func_get_args();
        for ($i = 0; $i < $num_args; $i++) {
            $this->stylesheets[] = array('text'=>$arg_list[$i]);
        }
    }

    /*
    Method: addLink
    Adds a generic link element to the page header.
    
    Parameters:
    attribs - An associative array, with each key corresponding to an attribute
              of the link with the corresponding value as the value.
    */
    public function addLink($attribs) {
        if (count($attribs) == 0) return false;
        $this->links[] = $attribs;
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
    public function addRSSFeed($href, $type, $title) {
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
    public function addScript($href, $type="text/javascript") {
        $this->scripts[] = array("href"=>$href, "type"=>$type);
    }

    /*
    Method: addInlineScript
    Adds an inline script to the header of the page.

    Parameters:
    text - The text of the script to add inline.
    type - *Optional* MIME type of the script.  
           The default is text/javascript.
    */
    public function addInlineScript($text, $type="text/javascript") {
        $this->scripts[] = array("text"=>$text, "type"=>$type);
    }
    
    # Method: addExternalScript
    # Add a script file from an external URL to the page.
    public function addExternalScript($href, $type="text/javascript") {
        $this->scripts[] = array('href' => $href, 'type' => $type, 'external' => true);
    }
    
    # Method: addExternalStylesheet
    # Add a stylesheet from an external URL to the page.
    public function addExternalStylesheet($href) {
        $this->stylesheets[] = array('link' => $href, 'external' => true);
    }
    
    /* 
    Method: addScriptFirst
    Like <addScript>, except adds the script at the *beginning* of the list of
    scripts to be inserted.  Use this for initialization of configuration
    scripts that need to run before other things.
    */
    public function addScriptFirst($href, $type="text/javascript") {
        $scr = array("href"=>$href, "type"=>$type);
        array_unshift($this->scripts, $scr);
    }
    
    /*
    Method: addInlineScriptFirst
    Like <addScriptFirst>, except for inline scripts.
    */
    public function addInlineScriptFirst($text, $type="text/javascript") {
        $scr = array("text"=>$text, "type"=>$type);
        array_unshift($this->scripts, $scr);
    }
    
    /*
    Method: addMeta
    Adds a META item to the page.

    Parameters:
    content   - The content of the meta tag.
    name      - *Optional* name attribute.
    httpequiv - *Optional* http-equiv attribute.
    */
    public function addMeta($content, $name=false, $httpequiv=false) {
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
    public function addHeader($name, $content) {
        $this->headers[$name] = $content;
    }
    
    /*
    Method: redirect
    Redirect to another page.

    Parameters:
    url - The target URL to which to redirect.
    */
    public function redirect($url) {
        $url = str_replace(array("\r","\n",'%0d','%0D','%0a','%0A'), '', $url);
        header("Location: ".$url);
        exit;
    }
    
    /*
    Method: refresh
    Refresh the page.
    
    Parameters:
    url   - The URL of the page to refresh.
    delay - *Optional* delay of refresh.  Default is 0.
    */
    public function refresh($url, $delay=0) {
        $url = str_replace(array("\r","\n",'%0d','%0D','%0a','%0A'), '', $url);
        if (! is_int($delay)) $delay = 0;
        header("Refresh: ".$delay."; URL=".$url);
    }

    /*
    Method: display
    Displays the page, i.e. sends it to the browser.
    */
    public function display($page_body, $blog=false) {
        $this->raiseEvent("OnOutput");

        $content_type = $this->mime_type."; charset=".$this->charset;

        if (! isset($this->headers["Content-Type"]))
            $this->headers["Content-Type"] = $content_type;
    
        foreach ($this->headers as $name=>$value) {
            header($name.": ".$value);
        }
    
        $this->addMeta($content_type, false, "Content-type");
        $this->addMeta(PACKAGE_NAME." ".PACKAGE_VERSION, "generator");
        
        $head = NewTemplate(PAGE_HEAD_TEMPLATE);
        $head->set("DOCTYPE", $this->doctype);
        $head->set("PAGE_TITLE", $this->title);
        $head->set("METADATA", $this->metatags);
        $head->set("RSSFEEDS", $this->rssfeeds);
        $head->set("STYLESHEETS",$this->stylesheets);
        $head->set("SCRIPTS",$this->scripts);
        $head->set("LINKS", $this->links);
        
        if ($blog && is_a($blog, 'Blog')) $blog->exportVars($head);
        $head->set("PAGE_CONTENT", $page_body);

        echo $head->process();
        
        $this->raiseEvent("OutputComplete");
        
    }
    
    # Method: error
    # Set an error header and exit.
    #
    # Parameters:
    # error         - The HTTP status code for the response.
    # extra_message - An additional message to output for the response
    public function error($code, $extra_message = '') {
        switch ($code) {
            case 403:
                $message = 'Forbidden';
                break;
            case 404:
                $message = 'Not Found';
                break;
            default:
                $message = '';
        }
        header("HTTP/1.0 $code $message");
        if ($extra_message) {
            echo $extra_message;
        }
        exit;
    }
    
}

$PAGE = Page::instance();
