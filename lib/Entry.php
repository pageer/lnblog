<?php
/*
    LnBlog - A simple file-based weblog focused on design elegance.
    Copyright (C) 2005-2011 Peter A. Geer <pageer@skepticats.com>

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

# Class: Entry
# An abstract class representing entries of all types in the blog database.
abstract class Entry extends LnBlogObject{

    /* An ID for the object that is unique across the class (not used). */
    public $id = '';

    /* The user ID of this object's owner. */
    public $uid = '';

    /* The IP address logged for this object at last modification. */
    public $ip = '';

    /* The UNIX timestamp when the object was last modified. */
    public $timestamp = 0;

    /* UNIX timestamp when the object was created. */
    public $post_ts = 0;

    /* The subject text associated with this object. */
    public $subject = '';

    /* An abstract of the text of this object (not used). */
    public $abstract = '';

    /* A comma-delimited list of tags applied to this entry. */
    public $tags = "";

    /* The main text data of this object.  May be one of several different kinds of markup. */
    public $data = '';

    # Property: file
    # The path to the file that holds data for this entry.  Note that this is
    # specific to filesystem storage and is for internal use only.
    public $file = '';

    # Property: has_html
    # Holds the type of markup used in the data property.  This can be one
    # of several defined constants, includine <MARKUP_NONE>, <MARKUP_BBCODE>,
    # and <MARKUP_HTML>.
    public $has_html = MARKUP_NONE;

    # Property: custom_fields
    # An array of custom fields for the entry, with keys being the field name
    # for use in the data structure and configuration files and the value
    # being a short description to display to the user.
    public $custom_fields = array();

    # Property: metadata_fields
    # An array of property->var pairs.  These are the object member
    # variable names and the data file variable names respectively.
    # They are used to retreive data from persistent storage.
    public $metadata_fields = array(
        "id"=>"PostID",
        "uid"=>"UserID",
        "timestamp"=>"Timestamp",
        "post_ts"=>"PostTimeStemp",
        "ip"=>"IP",
        "subject"=>"Subject",
        "has_html"=>"HasHTML",
        "tags"=>"Tags",
        "custom"=>"Custom"
    );

    protected $fs;

    protected function __construct(FS $filesystem) {
        $this->fs = $filesystem;
    }

    /*
    Method: localpath
    Get the path to this entry's directory on the local filesystem.  Note
    that this is specific to file-based storage and so should only be
    called internally.

    Returns:
    A string representing a path to the object or false on failure.
    */
    protected function localpath() {}

    /*
    Method: title
    An RSS compatibility method for getting the title of an entry.

    Parameters:
    no_escape - *Optional* boolean that tells the function to not escape
                ampersands and angle braces in the return value.

    Returns:
    A string containing the title of this object.
    */
    public function title($no_escape=false) {
        $ret = $this->subject ? $this->subject : NO_SUBJECT;
        return $no_escape ? $ret : htmlspecialchars($ret);
    }

    /*
    Method: description
    An RSS compatibility method.  Like <title>, but for the main
    text of the item.

    Returns:
    A string containing HTML code for the item's text.
    */
    public function description() {
        return $this->markup();
    }

    /*
    Method: data
    Set or return the data property.  If the optional value parameter is set
    to a true value (i.e. a non-empty string), then this value is set to the
    data property.  Otherwise, the data property is returned.
    */
    public function data($value=false) {
        if ($value) $this->data = $value;
        else return $this->data;
    }

    /*
    Method: tags
    Set or return an array of tags for this entry.  Each tag is an arbitrary
    string entered by the user with no inherent meaning.
    */
    public function tags($list=false) {
        if ($list) {
            $this->tags = implode(TAG_SEPARATOR, $list);
        } else {
            if (! $this->tags) return array();
            $ret = explode(TAG_SEPARATOR, $this->tags);
            foreach ($ret as $key=>$val) {
                $ret[$key] = trim($val);
            }
            return $ret;
        }
    }

    /*
    Method: permalink
    Abstract function that returns the object's permalink.
    Child classes *must* over-ride this.
    */
    public function permalink() {
        return "";
    }

    /*
    Method: getParent
    Abstract function to return the parent object of the current object.
    This will be a Blog object for BlogEntry or Article objects, and a
    BlogEntry or Article for BlogComment or TrackBack objects.
    Child classes *must* over-ride this method.
    */
    public function getParent() {
        return false;
    }

    # Method: markup
    # Apply appropriate markup to the entry data.
    #
    # Parameters:
    # data         - *Optional* data to markup.  If not specified, use the
    #                data property of the current object.
    # use_nofollow - Apply rel="nofollow" to links.  *Default* is false.
    #
    # Returns:
    # A string with markup applied.

    public function markup($data="", $use_nofollow=false) {
        if (! $data) {
            $data = $this->data;
        }
        $processor = TextProcessor::get($this->has_html, $this, $data);
        $processor->use_nofollow = $use_nofollow;
        return $processor->getHTML();
    }

    # Method: getAbstract
    # A quick function to get a plain text abstract of the entry data
    # by grabbing the first paragraph of text or the first N characters,
    # whichever comes first.  Markup is removed in the process.
    # Note that it attempts to do word wrapping to avoid cutting words off in
    # the middle.
    #
    # Parameters:
    # nuchars - *Optional* number of characters.  *Defaults* to 500.
    #
    # A string containing the abstract text, with all markup stripped out.

    public function getAbstract($numchars=500) {
        if (!$this->data || $numchars < 1) return '';
        if ($this->has_html == MARKUP_BBCODE) {
            $data = $this->bbcodeToHTML($this->data, true);
        } elseif ($this->has_html == MARKUP_HTML) {
            $data = strip_tags($this->data);
        } else {
            $data = TextProcessor::get(MARKUP_NONE, $this, $this->data)->getHTML();
        }

        $data = explode("\n", $data);
        if (strlen($data[0]) > $numchars) {
            return wordwrap($data[0],$numchars);
        } else {
            return $data[0];
        }
    }

    # Method: getSummary
    # Gets a summary of the entry.  Returns the *abstract* property if it is
    # set or the first HTML paragraph otherwise.
    public function getSummary() {
        if ($this->abstract) return $this->markup($this->abstract);

        $data = $this->markup();
        $endpos = strpos($data, "</p>");
        return substr($data, 0, $endpos)."</p>";
    }

    # Method: prettyDate
    # Get a human-readable date from a timestamp.
    #
    # Parameters:
    # ts - *Optional* timestamp for the date.  If unset, use time().
    #
    # Returns:
    # A string with a formatted, localized date.

    public function prettyDate($ts=false) {
        $date_ts = $ts ? $ts : $this->timestamp;
        # If we don't aren't passed a timestamp and don't already have one,
        # then just use the current time.
        if (! $date_ts) $date_ts = time();
        $print_date = fmtdate(ENTRY_DATE_FORMAT, $date_ts);
        return $print_date;
    }

    # Method: readFileData
    # Reads entry data from a file.  As of verions 0.8.2, data is stored in XML
    # format, with the elements corresponding directly to class properties.
    #
    # In previous versions, file metadata is enclosed in META tags
    # which are HTML comments, in one-per-line format.  Here is an example.
    # |<!--META Subject: This is a subject META-->
    # Variables are created for every such line which is referenced in the
    # metadata_fields property of the object.  The metadata_fields property is
    # an associative array where the element key is the propery of the object to
    # which the value is assigned and the element valueis the case-insensitive
    # variable used in the file, as with "Subject" in the example above.
    #
    # There is also a custom_fields property.  This is an associative array,
    # just as metadata_fields.  If custom_fields is populated, its members are
    # merged into metadata_fields, in effect adding elements to the standard
    # metadata fields.
    #
    # Returns:
    # The body of the entry as a string.

    public function readFileData() {

        if (substr($this->file, strlen($this->file)-4) != ".xml") {
            $this->readOldFile();
        } else {
            $this->deserializeXML($this->fs->read_file($this->file));
        }

        if (is_subclass_of($this, 'BlogEntry')) {
            $this->id = str_replace(PATH_DELIM, '/',
                                    substr(dirname($this->file),
                                    strlen(calculate_document_root()) ) );
        } else {
            $this->id = str_replace(PATH_DELIM, '/',
                                    substr($this->file, strlen(calculate_document_root())) );
        }

        if (! $this->post_ts) {
            $this->post_ts = $this->fs->filemtime($this->file);
        }
        if (! $this->timestamp) {
            $this->timestamp = $this->fs->filemtime($this->file);
        }

        return $this->data;
    }

    public function readOldFile() {
        $data = $this->fs->file($this->file);
        $file_data = "";
        if (! $data) $file_data = false;
        else
            foreach ($this->custom_fields as $fld=>$desc) {
                $this->metadata_fields[$fld] = $fld;
            }
            $lookup = array_flip($this->metadata_fields);
            foreach ($data as $line) {
                preg_match('/<!--META ([\w|\s|-]*): (.*) META-->/', $line, $matches);
                if ($matches && isset($lookup[strtolower($matches[1])])) {
                    $field = $lookup[strtolower($matches[1])];
                    $this->$field = $matches[2];
                }
                $cleanline = preg_replace("/<!--META.*META-->\s\r?\n?\r?/", "", $line);

                $file_data .= $cleanline;
            }
        $this->data = $file_data;
    }

    # Method: writeFileData
    # Write entry data to a file.  The contents of the file are determined by
    # the properties of the object and the contents of the metadata_fields and
    # custom_fields properties, just as with <readFileData>.  This function
    # writes the metadata to HTML comments, as mentioned above, while the body
    # data is written at the end of the file.  Note that, in order for the file
    # to be written, the file property of the object must be set.
    #
    # Returns:
    # True if the file write is successful, false otherwise.

    public function writeFileData() {
        $file_data = $this->serializeXML();
        if (! $this->fs->is_dir(dirname($this->file)) )
            $this->fs->mkdir_rec(dirname($this->file));
        $ret = $this->fs->write_file($this->file, $file_data);
        return $ret;
    }
}
