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

/* Class: LnBlogObject
 * A base object which is event-aware, i.e. it knows how to create, 
 * register, and fire events.  It also has contains other general-purpose
 * methods, such as simple XML serialization.
 */

class LnBlogObject {
	
	function LnBlogObject() {}

	/* Method: createEvent
	 * Creates an event for the current class.
	 *
	 * Parameters:
	 * name - The name of the event.
	 *
	 * Returns:
	 * True on success, false on failure.
	 */

	function createEvent($name) {
		global $EVENT_REGISTER;
		return $EVENT_REGISTER->addEvent(get_class($this), $name);
	}

	/* Method: hasEvent
	 * Determine whether the given event exists.
	 *
	 * Parameters:
	 * name - The name of the event.
	 *
	 * Returns:
	 * True on success, false on failure.
	 */

	function hasEvent($name) {
		global $EVENT_REGISTER;
		return $EVENT_REGISTER->isEvent(get_class($this), $name);
	}

	/* Method: hasHandlers
	 * Determine if thereare any handlers for the given event.
	 *
	 * Parameters:
	 * name - The name of the event.
	 *
	 * Returns:
	 * True on success, false on failure.
	 */

	function hasHandlers($name) {
		global $EVENT_REGISTER;
		return $EVENT_REGISTER->hasHandlers(get_class($this), $name);
	}

	/* Method: raiseEvent
	 * Raises the given event name for this class.
	 *
	 * Parameters:
	 * name - The name of the event.
	 *
	 * Returns:
	 * True on success, false on failure.
	 */
	 
	function raiseEvent($name) {
		global $EVENT_REGISTER;
		return $EVENT_REGISTER->activateEvent($this, $name); 
	}

	/* Method: registerEventHandler
	 * Registers a handler for an event of this class.
	 *
	 * Parameters:
	 * type - The class of the class that raises the event.
	 * name - The name of the event.
	 * func - The name of the function that will handle this event.
	 *
	 * Returns:
	 * True on success, false on failure.
	 */
	 
	function registerEventHandler($type, $name, $func) {
		global $EVENT_REGISTER;
		return $EVENT_REGISTER->addHandler($type, $name, 
		                                   $this, $func);
	}
	
	/* Method: registerStaticEventHandler
	 * Registers a static handler for an event of this class.  Use this if
	 * your handler belongs to a class but does not require an instance of 
	 * it in order to work.
	 *
	 * Parameters:
	 * type - The class of the class that raises the event.
	 * name - The name of the event.
	 * func - The name of the function that will handle this event.
	 *
	 * Returns:
	 * True on success, false on failure.
	 *
	 * See Also:
	 * <registerEventHandler>
	 */

	function registerStaticEventHandler($type, $name, $func) {
		global $EVENT_REGISTER;
		return $EVENT_REGISTER->addHandler($type, $name, 
		                                   get_class($this), $func, true);
	}

	/* Method: serializeXML
	 * Performs a simple XML serialization of the object.  If the object
	 * has an exclude_fields which is an array, then the method will NOT include
	 * that property in the serialization.  In addition, it will not include any
	 * properties whose name matches an item in the exclude_fields array.
	 *
	 * Returns:
	 * A string containing an XML representation of the object.
	 */
	function serializeXML() {
		$xml = new SimpleXMLWriter($this);
		if (isset($this->exclude_fields) && is_array($this->exclude_fields)) {
			$xml->exclude("exclude_fields");
			foreach ($this->exclude_fields as $fld) {
				$xml->exclude($fld);
			}
		}
		return $xml->serialize();
	}

	/* Method: deserializeXML
	 * Populates the object's properties from a string of XML data.
	 * 
	 * Parameters:
	 * xmldata - The XML containing a serialized representation of the object.
	 *           This is typically the string generated by <serializeXML> and
	 *           can be either a string of data or a path to the data file.
	 */
	function deserializeXML($xmldata) {
		$xml = new SimpleXMLReader($xmldata);
		$xml->parse();
		$xml->populateObject($this);
	}
	
	/* Method: serializeJSON
	 * Works like <serializeXML>, except returns the string in 
	 * JavaScriptObject Notation instead of XML.
	 *
	 * Returns:
	 * The JSON representation of the object.
	 */
	function serializeJSON() {
		if (isset($this->exclude_fields) && is_array($this->exclude_fields)) {
			$this->exclude_fields[] = "exclude_fields";
		} else {
			$this->exclude_fields = array("exclude_fields");
		}

		$items = array();

		foreach ($this as $fld=>$val) {
			if (! in_array($fld, $this->exclude_fields)) {
				$ret = "$fld: ";
				if (is_array($val)) {
					$ret .= "[";
					foreach ($val as $v) {
					
					}
					$ret .= "]";
				} elseif (is_string($val)) {
					$ret .= "'$val'";
				} else {
					$ret .= $val;
				}
				$items[] = $ret;
			}
		}

		$ret = "{".implode(",", $items)."}";
		return $ret;
	 }
}
?>
