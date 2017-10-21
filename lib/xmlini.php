<?php
# Class: LnBlogXMLConfig
# A dummy class for use with the <SimpleXMLWriter> class.  This is used simply
# to create blank objects that XML elements can be inserted into.
class LnBlogXMLConfig extends LnBlogObject { }

# Class: XMLINI
# An simple XML configuration file scheme that mimics the behavior of the 
# INIParser class.  The main difference is, obviously, the storage format.
# This class uses XML instead of an INI file, thus removing the single-line
# data value limit, among other things.
class XMLINI {

    private $fs;
    private $filename;

	public function __construct($file=false, $fs = null) {
        $this->fs = $fs ?: NewFS();
        $this->filename = $file;

		if ($file && $this->fs->file_exists($this->filename)) {
			$this->readFile();
		} else {
			$this->data = new LnBlogXMLConfig();
		}
	}
	
	# Method: readFile
	# Read the contents of an XML file into a class.  There is an array data
	# member for each tag, with the children as the array elements.
	# Basically, it mirrors the section/key/value structure of an INI file.
	function readFile() {
		$file_contents = $this->fs->read_file($this->filename);
		$file = new SimpleXMLReader($file_contents);
		$file->setOption('assoc_arrays');
		$file->parse();
		$this->data = $file->makeObject('LnBlogXMLConfig');
	}

	# Method: getFile
	# Serializes the XML file into a string.
	#
	# Returns:
	# The configuration data as an XML string.

	function getFile() {
	    return $this->data->serializeXML();
	}
	
	# Method: writeFile
	# Write the XML file back to the disk.
	#
	# Parameters:
	# file - The filename to write to.  *Defaults* to the filename property.
	#
	# Returns:
	# True if the write succeeds, false otherwise.

	function writeFile($file=false) {
        if ($file) {
            $this->filename = $file;
        }
        if (! $this->filename) {
            return false;
        }
		return $this->fs->write_file($this->filename, $this->getFile());
	}
	
	# Method: value
	# Get a value from the XML file.  The values are organized by section and key,
	# just as in an INI file.
	#
	# Parameters:
	# sec     - The section of of the target key.
	# var     - The target key within that section.
	# default - The *optional* default value if the target does not exist.
	#
	# Returns:
	# A string containing the value of the given key, or the default value.

	function value($sec, $var=false, $default=false) {
		$sec = strtolower($sec);
		$var = strtolower($var);
		if ( isset($this->data->$sec) &&
		     isset($this->data->{$sec}[$var]) ) {
			return $ret = $this->data->{$sec}[$var];
		} else {
			return $default;
		}
	}

	# Method: valueIsSet
	# Determine if a particular key has been set.
	#
	# Parameters:
	# sec     - The section of of the target key.
	# var     - The target key within that section.
	#
	# Returns:
	# True if the given key has been set, false otherwise.

	function valueIsSet($sec, $var) {
		$sec = strtolower($sec);
		$var = strtolower($var);
		return isset($this->data->{$sec}[$var]);
	}
	
	# Method: setValue
	# Sets a value for a key.  
	#
	# Parameters:
	# sec - The section of of the target key.
	# var - The target key within that section.
	# val - The *optional* value.  If omitted, the key is set to boolean true;
	#
	# Returns:
	# The value to which the key was set.
	
	function setValue ($sec, $var, $val=true) {	
		$sec = strtolower($sec);
		$var = strtolower($var);
		if (! isset($this->data->$sec)) {
			$this->data->$sec = array();
		}
		$this->data->{$sec}[$var] = $val;
		return $val;
	}
	
	# Method: getSection
	# Returns the given section as an array.
	#
	# Parameters:
	# sec - The section of the file to get.
	#
	# Returns:
	# An array of the section with variables as keys and 
	# values as elements.

	function getSection($sec) {
		if (! isset($this->data->$sec)) {
			return array();
		} else {
			return $this->data->$sec;
		}
	}
	
	# Method: getSectionNames
	# Gets a list of the section names available in the file.
	#
	# Returns:
	# An array of strings, each of which is the name of a section.

	function getSectionNames() {
		$ret = array();
		foreach ($this->data as $sec=>$vals) {
			$ret[] = $sec;
		}
		return $ret;
	}
	
	# Method: merge
	# Merge another XML file into the current one.
	# If a key from the other file is not set, it will be set with the value 
	# from the other file.  Keys that are already set will not be modified.
	#
	# Parameters:
	# file - The XMLINI object to merge with the current object.

	function merge($file) {
        if ($file->data && ! $this->data) {
            $this->data = $file->data;
        } elseif (! $file->data) {
           return;
        }
	
		foreach ($file->data as $sec=>$keys) {
			if (isset($this->data->$sec) ) {
				foreach ($keys as $key=>$val) {
					if (! isset($this->data->{$sec}[$key])) {
						$this->data->{$sec}[$key] = $val;
					}
				}
			} else {
				$this->data->$sec = $keys;
			}
		}
	}
}
