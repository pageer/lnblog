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
 * Class: EventRegister
 * Handles registering events and calling the event handlers.
 * The event register is a hierarchical list with the following format:
 * Raising class -> Event -> Catching class -> Callback type -> Function
 * The first three elements should be self explanatory, as should the 
 * function name.  The callback type, however, represents how the function
 * will be called.  The valid types are "instance" and "static", the 
 * difference being that an instance of the class is not created for "static"
 * callbacks.
 *
 * Note that this presupposes that all events are raised and caught by a 
 * class, but this is not an absolute requirement.  For either type of 
 * callback function, if the catching class does not exist (i.e. the callback
 * was directly registered using a dumby name), the event will be raised as
 * a regular function.
 */

class EventRegister {

	var $event_list;

	function EventRegister() {
		$this->event_list = array();
	}

	/* 
	 * Method: isEvent
	 * Check if an event exists.
	 *
	 * Parameters:
	 * raising_class - The name of the class that this event belongs to. 
	 * name          - The name of the event.
	 * 
	 * Returns:
	 * True if the event exists, false if it does not.
	 */

	function isEvent($raising_class, $name) {
		$rclass = strtolower($raising_class);
		$ename = strtolower($name);
		if (isset($this->event_list[$rclass][$ename])) return true;
		else return false;
	}

	/* 
	 * Method: hasHandlers
	 * Check if a given event has any handlers registered.
	 *
	 * Parameters:
	 * raising_class - The name of the class that this event belongs to. 
	 * name          - The name of the event.
	 *
	 * Returns:
	 * True if the event has one or more handlers registered, 
	 * false if it does not.
	 */

	function hasHandlers($raising_class, $name) {
		if (! $this->isEvent($raising_class, $name)) return false;
		$rcls = strtolower($raising_class);
		$ename = strtolower($name);
		return (is_array($this->event_list[$rcls][$ename]) &&
		           count($this->event_list[$rcls][$ename]) > 0);
	}
	
	/*
	 * Method: addEvent
	 * Creates a new event.
	 *
	 * Parameters:
	 * raising_class - The name of the class that this event belongs to, 
	 *                 i.e. the class that will raise it.
	 * name          - The name of the event.
	 *
	 * Returns:
	 * False if the event already exists, true otherwise.
	 */

	function addEvent($raising_class, $name) {
		$rclass = strtolower($raising_class);
		$ename = strtolower($name);
		if ($this->isEvent($rclass,$ename)) return false;
		if (! isset($this->event_list[$rclass])) {
			$this->event_list[$rclass] = array();
		}
		$this->event_list[$rclass][$ename] = array();
		return true;
	}

	/* Method: addHandler
	 * Adds a handler function to an event.  If the event does not already 
	 * exist, then it is created.
	 *
	 * Parameters:
	 * raiseing_class - The name of the class that will raise the event.
	 * name           - The name of the event.
	 * catching_class - The name of the class to which the event
	 *                  handler belongs.
	 * handler        - The name of the function (presumably a member function
	 *                  of the catching class, but not necessarily) that will
	 *                  handle this event.
	 * static         - *Optional* boolean parameter determing whether the 
	 *                method is static, i.e. does not need an instance of the
	 *                class to work.  Defaults to false (instance method).
	 */

		function addHandler($raising_class, $name, 
	                    $catching_class, $handler, $static=false) {
		$rclass = strtolower($raising_class);
		$ename = strtolower($name);
		$cclass = strtolower($catching_class);
		if (! $this->isEvent($rclass,$ename)) $this->addEvent($rclass, $ename);
		if (! isset($this->event_list[$rclass][$ename][$cclass])) {
			$this->event_list[$rclass][$ename][$cclass] = array();
			$this->event_list[$rclass][$ename][$cclass]['static'] = array();
			$this->event_list[$rclass][$ename][$cclass]['instance'] = array();
		}							
		$mtype = $static ? 'static' : 'instance';
		$this->event_list[$rclass][$ename][$cclass][$mtype][] = $handler;
		return true;
	}

	/* 
	 * Method: activateEventFull
	 * Raises an arbitrary event for the given class. 
	 * 
	 * Parameters:
	 * param    - An arbitrary object that is passe by reference to the event
	 *            handlers.
	 * raisecls - The name of the class the event belongs to.
	 * event    - The name of the event.
	 *
	 * Returns:
	 * False if the event does not exist, true otherwise.
	 *
	 * See Also:
	 * <activateEvent>
	 */

	function activateEventFull(&$param, $raisecls, $event) {
		$rcls = strtolower($raisecls);
		$ename = strtolower($event);
		if (! $this->isEvent($rcls,$ename)) return false;
		foreach ($this->event_list[$rcls][$ename] as $classname=>$handlers) {
			if (class_exists($classname) && $handlers['instance']) {
				$tmp_class = new $classname;
			}
			
			foreach ($handlers['instance'] as $hnd) {
				if (class_exists($classname) && 
				    method_exists($tmp_class, $hnd)) {
					$tmp_class->$hnd($param);
				} else {
					call_user_func($hnd, $param);
				}
			}
			foreach ($handlers['static'] as $hnd) {
				$methods = get_class_methods($classname);
				$ret = array_search($hnd,$methods);
				if ($ret !== false && $ret !== null) {
					call_user_func(array($classname, $hnd), $param);
				} else {
					call_user_func($hnd, $param);
				}
			}
		}
		return true;
	}

	/* Method: activateEvent
	 * Activates an event for the raising object.  
	 *
	 * Parameters:
	 * raiser - The object which is raising the event.  This is passed by 
	 *          reference to the event handler.
	 * event  - The name of the event.
	 *
	 * Returns:
	 * False if the event does not exist, true otherwise.
	 *
	 * See Also:
	 * <activateEventFull>
	 */

	function activateEvent(&$raiser, $event) {
		return $this->activateEventFull($raiser, 
		             strtolower(get_class($raiser)), $event);
	}
}

$EVENT_REGISTER = new EventRegister();
?>
