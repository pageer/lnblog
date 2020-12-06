<?php

# Class: TextProcessor
# Base class for text processors 
abstract class TextProcessor {
    private static $available_filters = array(
        'AutoMarkupTextProcessor',
        'HTMLTextProcessor',
        'LBCodeTextProcessor',
        'MarkdownTextProcessor',
    );

    private $url_resolver;

    # Property: text
    # (string) The original, unformatted text.
    protected $text = '';

    # Property: formatted
    # (string) The final, formatted text.
    protected $formatted = '';

    # Property: entry
    # (Entry) The entry used to resolve URIs.
    protected $entry = null;
    
    # Property: filter_id
    # (int) The ID fo the text filter.
    public $filter_id = 0;

    # Property: filter_name
    # (string) The human-readable name of the filter.
    public $filter_name = '';

    # Property: no_surround
    # (boolean) Skip the surrounding markup, i.e. don't wrap in <P> tags.
    public $no_surround = false;
    
    abstract protected function toHTML();
    
    /* Method: get
       Factory method to get a processor based on a markup type.

       Parameter:
       markup_type - A constant denoting the markup type, such as MARKUP_BBCODE.
       entry       - The entry object against which relative URIs should be resolved.
       text        - The data to format.

       Returns:
       The instantiated TextProcessor
     */
    static public function get($markup_type, Entry $entry = null, $text = '') {
        $filters = self::getAvailableFilters();
        foreach ($filters as $f) {
            if ($f->filter_id == $markup_type) {
                $f->setEntry($entry);
                $f->setText($text);
                return $f;
            }
        }
        # Throw an exception if we don't find the filter.
        throw new Exception('Text filter ' . $markup_type . ' not found!');
    }
    
    /* Method: getFilterList
       Convenience function that returns a map of filter IDs to names.

       Returns
       An associative array mapping filter IDs to filter names.
     */
    static public function getFilterList() {
        $filters = self::getAvailableFilters();
        $ret = array();
        foreach ($filters as $filt) {
            $ret[$filt->filter_id] = $filt->filter_name;
        }
        return $ret;
    }
    
    /* Method: getAvailableFilters
       Gets an array of instances of the available textprocessors.

       Returns:
       An array of TextProcessor objects.
     */
    static public function getAvailableFilters() {
        static $filters = array();
        
        # This isn't going to change in a single request, so don't re-run it.
        if (! empty($filters)) {
            return $filters;
        }
        
        $classes = get_declared_classes();
        $ret = array();
        foreach (self::$available_filters as $class) {
            $filters[] = new $class();
        }
        return $filters;
    }
    
    public function __construct(Entry $entry = null, $text = '', UrlResolver $resolver = null) {
        $this->entry = $entry;
        $this->text = $text;
        $this->url_resolver = $resolver ?: new UrlResolver(SystemConfig::instance(), NewFS());
    }
    
    /* Method: setText
       Set the initia text to process

       Parameters:
       text - (string) The text to format
     */
    public function setText($text) {
        $this->text = $text;
        $this->formatted = $text;
    }
    
    /* Method: getRawText
       Get the initial text passed for processing.

       Returns:
       (string) The raw text
     */
    public function getRawText() {
        return $this->text;
    }
    
    /* Method: setEntry
       Set the entry object against which URIs will be resolved.

       Parameters:
       entry - (Entry) The entry object for resolution
     */
    public function setEntry(Entry $entry = null) {
        $this->entry = $entry;
    }
    
    /* Method: getHTML
       Get the formatted HTML text.

       Parameters:
       text - (string) Optional text to translate.  Convenience parameter used
              for multiple calls on the same processor instance.
       
       Returns:
       (string) The formatted HTML
     */
    public function getHTML($text = null) {
        if ($text !== null) {
            $this->setText($text);
        }
        $this->toHTML();
        return $this->formatted;
    }
    
    /* Method: fixIndividualURI
       Fixes a relative URI to be absolute.
      
       If the formatter is passed a null entry, then relative URIs will not be resolved.
       
       Parameters:
       uri - (string) The relative URI to fix
       
       Returns:
       (string) The absolute URI.
     */
    protected function fixIndividualURI($uri) {
        
        if ($this->entry === null) {
            return $uri;
        }
        
        $parent = $this->entry->getParent();
        $searchpath = array($this->entry->localpath());
        $upload_dirs = explode(",", FILE_UPLOAD_TARGET_DIRECTORIES);
        foreach ($upload_dirs as $dir) {
            $searchpath[] = Path::mk($parent->home_path, $dir);
        }
        $searchpath[] = $parent->home_path;
        
        $temp_uri = str_replace("/", PATH_DELIM, $uri);
        
        return $this->url_resolver->localpathToUri($path, $parent, $this->entry);
    }
    
    /* Method: sanitizeText
       Strip HTML code out of a string.  Note that the UNICODE_ESCAPE_HACK
       configuration constant can be used to switch between using the PHP
       htmlentities() and htmlspecialchars() functions to sanitize input.
       This is because htmlentities() has a nasty habit of mangling Unicode.
       
       Parameters:
       data - (string) The data to clear
      
       Returns:
       A copy of data with HTML special characters such as angle brackets and
       ampersands converted into their corresponding HTML entities.  This will
       cause them to display in a web page as characters, not HTML markup.
     */
    protected function sanitizeText($data) {
        
        if (UNICODE_ESCAPE_HACK) {
            $ret = htmlentities($data);
            $ret = preg_replace("/&amp;(#\d+);/Usi", "&$1;", $ret);
            $ret = preg_replace("/&amp;(\w{2,7});/Usi", "&$1;", $ret);
        } else {
            $ret = htmlspecialchars($data);
        }
        return $ret;
    }
}
