<?php

abstract class TextProcessor {
	/**
	 * @var string The original, unformatted text.
	 */
	protected $text = '';
	/**
	 * @var string The final, formatted text.
	 */
	protected $formatted = '';
	/**
	 * @var Entry The entry used to resolve URIs.
	 */
	protected $entry = null;
	/**
	 * @var int The ID fo the text filter.
	 */
	public $filter_id = 0;
	/**
	 * @var string The human-readable name of the filter.
	 */
	public $filter_name = '';
	/**
	 * @var boolean Skip the surrounding markup, i.e. don't wrap in <P> tags.
	 */
	public $no_surround = false;
	
	abstract protected function toHTML();
	
	/**
	 * Factory method to get a processor based on a markup type.
	 * 
	 * @param int     $markup_type  A constant denoting the markup type, such as MARKUP_BBCODE.
	 * @param Entry	  $entry        The entry object against which relative URIs should be resolved.
	 * @param string  $text         The data to format.
	 * 
	 * @return TextProcessor    The instantiated processor
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
	
	/**
	 * Convenience function that returns a map of filter IDs to names.
	 * 
	 * @return array    An associative array mapping filter IDs to filter names.
	 */
	static public function getFilterList() {
		$filters = self::getAvailableFilters();
		$ret = array();
		foreach ($filters as $filt) {
			$ret[$filt->filter_id] = $filt->filter_name;
		}
		return $ret;
	}
	
	/**
	 * Gets an array of instances of the available textprocessors.
	 * 
	 * @return array    An array of TextProcessor objects.
	 */
	static public function getAvailableFilters() {
		static $filters = array();
		
		# This isn't going to change in a single request, so don't re-run it.
		if (! empty($filters)) {
			return $filters;
		}
		
		self::loadAllFilters();
		$classes = get_declared_classes();
		$ret = array();
		foreach ($classes as $class) {
			if (is_subclass_of($class, 'TextProcessor')) {
				$filters[] = new $class();
			}
		}
		return $filters;
	}
	
	/**
	 * Load all text processor classes found.
	 */
	static public function loadAllFilters() {
		$filter_dir = mkpath(INSTALL_ROOT, 'lib', 'textprocessors');
		$files = scandir($filter_dir);
		foreach ($files as $file) {
			if (strpos($file, '.class.php')) {
				require_once $file;
			}
		}
	}
	
	public function __construct(Entry $entry = null, $text = '') {
		$this->entry = $entry;
		$this->text = $text;
	}
	
	/**
	 * Set the initia text to process
	 * 
	 * @param string $text The text to format
	 */
	public function setText($text) {
		$this->text = $text;
		$this->formatted = $text;
	}
	
	/**
	 * Get the initial text passed for processing.
	 * 
	 * @return string    The raw text
	 */
	public function getRawText() {
		return $this->text;
	}
	
	/**
	 * Set the entry object against which URIs will be resolved.
	 * 
	 * @param Entry   $entry The entry object for resolution
	 */
	public function setEntry(Entry $entry = null) {
		$this->entry = $entry;
	}
	
	/**
	 * Get the formatted HTML text.
	 * 
	 * @param string $text Optional text to translate.  Convenience parameter used
	 *                     for multiple calls on the same processor instance.
	 * 
	 * @return string    The formatted HTML
	 */
	public function getHTML($text = null) {
		if ($text !== null) {
			$this->setText($text);
		}
		$this->toHTML();
		return $this->formatted;
	}
	
	/**
	 * Fixes a relative URI to be absolute.
	 *
	 * If the formatter is passed a null entry, then relative URIs will not be resolved.
	 * 
	 * @param string $uri The relative URI to fix
	 * 
	 * @return string The absolute URI.
	 */
	protected function fixIndividualURI($uri) {
		
		if ($this->entry === null) {
			return $uri;
		}
		
		$parent = $this->entry->getParent();
		$searchpath = array($this->entry->localpath());
		$upload_dirs = explode(",", FILE_UPLOAD_TARGET_DIRECTORIES);
		foreach ($upload_dirs as $dir) {
			$searchpath[] = mkpath($parent->home_path, $dir);
		}
		$searchpath[] = $parent->home_path;
		
		$temp_uri = str_replace("/", PATH_DELIM, $uri);
		
		foreach ($searchpath as $path) {
			if (file_exists(mkpath($path, $temp_uri))) {
				$uri = localpath_to_uri(mkpath($path, $temp_uri));
				break;
			}
		}
	
		return $uri;
	}
	
	/**
	 * Strip HTML code out of a string.  Note that the UNICODE_ESCAPE_HACK
	 * configuration constant can be used to switch between using the PHP
	 * htmlentities() and htmlspecialchars() functions to sanitize input.
	 * This is because htmlentities() has a nasty habit of mangling Unicode.
	 * 
	 * @param string $data The data to clear
	 *
	 * @return string A copy of data with HTML special characters such as angle brackets and
	 *                ampersands converted into their corresponding HTML entities.  This will
	 *                cause them to display in a web page as characters, not HTML markup.
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