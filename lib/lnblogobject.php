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
 * register, and fire events.
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
}

?>
