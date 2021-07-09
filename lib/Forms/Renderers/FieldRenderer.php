<?php

namespace LnBlog\Forms\Renderers;

use BasePages;
use LnBlog\Forms\FormField;

interface FieldRenderer
{
    # Method: setLabel
    # Sets the string to be used as the form label.  This will normally be
    # added inside an HTML LABEL tag attached to the field element
    #
    # Parameters:
    # label - (string) The text of the label.
    public function setLabel(string $label);

    # Method: setAttributes
    # Sets the attributes which will be rendered on the form element.
    # These can be any arbitrary key/value pair.  While the keys should
    # map to the supported attributes of the underlying HTML element, this
    # need not be enforced.  Note that both keys and values may (and should)
    # be escaped by the template.
    #
    # Parameters:
    # attributes - (array) an associative array of attribute names to values.
    public function setAttributes(array $attributes);

    # Method: setData
    # Sets an additional, arbitrary value to be passed directly to the 
    # PHPTemplate instance that renders the field.  This is used to add 
    # any extra data to the template beyond the attributes and the field
    # data itself.
    #
    # Parameters:
    # key   - (string) The variable name to use in the template.
    # value - (mixed) The value to be injected for that name.  This can
    #         be any value or data type.
    public function setData(string $key, $value);

    # Method: render
    # Render the given form field.  This will proces the template for this
    # renderer and pass in the field data and the web page object.  Note that
    # the web page object is needed for certain things like CSRF token
    # processing.
    #
    # Parameters:
    # field - (FormField) The form field to render.
    # pages_obj - (BasePages) The web page object for the current page.
    #
    # Return:
    # A string containing the rendered output for the field.
    public function render(FormField $field, BasePages $pages_obj): string;
}
