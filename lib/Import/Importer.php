<?php

namespace LnBlog\Import;

use Blog;
use UrlPath;

# Interface: Importer
# A common interface for classes that handle importing data.
interface Importer
{
    # Method: setImportOptions
    # Sets importer-specific options for how to handle the data.
    #
    # Parameters:
    # options - an associative array of options (types are importer-specific).
    public function setImportOptions(array $options): void;

    # Method: getImportOptions
    # Gets the current options for the importer.
    #
    # Returns:
    # Associative array of options.
    public function getImportOptions(): array;

    # Method: import
    # Imports a given export file into a given blog.
    #
    # This imports only the *contents* of the blog.  Any blog metadata (such as name,
    # description, etc.) present in the import is ignored.
    #
    # Parameters:
    # blog - the blog into which data will be imported
    # source - a FileImportSource representing the data file to import
    public function import(Blog $blog, FileImportSource $source): void;

    # Method: importAsNewBlog
    # Imports a given export file into a new blog.
    #
    # This will attempt to import all supported data from the export file,
    # including any blog metadata
    #
    # Parameters:
    # blogid - string with the blogid to use in creating the new blog
    # paths - a UrlPath object containing the path and URL for th enew blog
    # source - a FileImportSource representing the data file to import
    #
    # Returns:
    # The newly created Blog object.
    public function importAsNewBlog(string $blogid, UrlPath $paths, FileImportSource $source): Blog;

    # Method: getImportReport
    # Gets the results of an import.
    #
    # Returns:
    # An ImportReport object containing the results.
    public function getImportReport(): ImportReport;
}
