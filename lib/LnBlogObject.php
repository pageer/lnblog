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
class LnBlogObject
{
    protected $exclude_fields = [];

    /* Method: createEvent
     * Creates an event for the current class.
     *
     * Parameters:
     * name - The name of the event.
     *
     * Returns:
     * True on success, false on failure.
     */
    public function createEvent($name) {
        return EventRegister::instance()->addEvent(get_class($this), $name);
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
    public function hasEvent($name) {
        return EventRegister::instance()->isEvent(get_class($this), $name);
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
    public function hasHandlers($name) {
        return EventRegister::instance()->hasHandlers(get_class($this), $name);
    }

    /* Method: raiseEvent
     * Raises the given event name for this class.
     *
     * Parameters:
     * name   - The name of the event.
     * params - Any number of additional parameters may be passed to this method.
     *
     * Returns:
     * True on success, false on failure.
     */
    public function raiseEvent($name) {
        $params = func_get_args();
        array_splice($params, 0, 1);
        return EventRegister::instance()->activateEvent($this, $name, $params);
    }

    /* Method: raiseEventAndPassthruReturn
     * Raises the given event name for this class and pass through the value
     * returned by the underlying callback.
     *
     * Note that if the call triggers more than one callback, you will only
     * get one of the results
     *
     * Parameters:
     * name   - The name of the event.
     * params - Any number of additional parameters may be passed to this method.
     *
     * Returns:
     * The result of the last callback triggered or null on failure.
     */
    public function raiseEventAndPassthruReturn(string $name, array $params) {
        $result = null;
        $success = EventRegister::instance()->activateEventFull(
            $this,
            strtolower(get_class($this)),
            $name,
            $params,
            $result
        );

        if ($success) {
            return $result;
        }

        return null;
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
    public function registerEventHandler($type, $name, $func) {
        return EventRegister::instance()->addHandler(
            $type, $name,
            $this, $func
        );
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
    public function registerStaticEventHandler($type, $name, $func) {
        return EventRegister::instance()->addHandler(
            $type, $name,
            get_class($this), $func, true
        );
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
    public function serializeXML() {
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
    public function deserializeXML($xmldata) {
        $xml = new SimpleXMLReader($xmldata);
        $xml->parse();
        $xml->populateObject($this);
    }

}
