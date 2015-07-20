LnBlog: A flexible file-base weblog
Copyright (C) 2005-2011 Peter A. Geer <pageer@skepticats.com>

About LnBlog
------------
LnBlog is a weblog system that is written in PHP and stores data in text files.
It aims to provide a rich feature set with maximum flexibility while remaining
low on requirements.

Documentation
-------------
Full documentation is available at the LnBlog website.  It can be viewed online 
at the following URL:
http://www.skepticats.com/lnblog/documentation/
You may also download a copy of the documentation for local viewing at the 
following location:
http://www.skepticats.com/lnblog/content/download/lnblog_docs.zip

Requirements
------------
LnBlog needs a web server with PHP 5.3 or greater installed and the ability to
write to the file system.  Both Apache and IIS are supported and no database
is required.  File writing through FTP is also supported, but is no longer a
recommended configuration.  Support for the CURL, gettext, and either
mime-magic or fileinfo extensions is helpful, but not required.  

Installation
------------
There are two ways to install: from the distribution ZIP file downloaded from
the LnBlog webpage (recommended) or from source, i.e. the cloned Mercurial repository.

To install the distribution ZIP file, simply extract the ZIP archive and upload 
the resulting folder to the publicly accessible portion of your web server.
After that, open a web browser go to the URL corresponding to that location.
This will start the graphical configuration process.  You will be prompted to 
configure file writing and create an initial user account, after which you will 
be taken to the administration page where you can create more users, create blog,
and set other options.

To install directly from a copy of the source repository, you will need to have
Composer installed (http://getcomposer.org/).  Just move the the source directory
to the web-accessible portion of your web server and run "composer install" from
that directory.  You can then run webup process as described above.

Upgrade
-------
To upgrade an existing LnBlog installation, extract the new ZIP archive and 
upload the folder to your server.  You should then rename your old LnBLog 
directory to, e.g., LnBlog-old, and rename the new one in its place (i.e., give
it the same name the old version had).  Lastly, copy or move the userdata 
subdirectory from your old  LnBlog directory to the new one, overwriting existing
files.

If you installed from source, you can simply pull updates from the Mercurial
repository by running "hg pull -u".  You may also need to run "composer update"
if new third-party dependencies have been added.

Plugins
-------
LnBlog supports an event-driven plugin system and ships with a large number of 
standard plugins.  There are also a number of non-standard plugins available 
from the official web site.  These include extra sidebar panels, JavaScript 
rich-text editors, and others.  These can be downloaded from:
http://www.skepticats.com/lnblog/content/plugins/

License
-------
LnBlog: A flexible file-base weblog
Copyright (C) 2005-2011 Peter A. Geer <pageer@skepticats.com>

This program is free software; you can redistribute it and/or modify it under the
terms of the GNU General Public License as published by the Free Software 
Foundation; either version 2 of the License, or (at your option) any later 
version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY 
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A 
PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this
program; if not, write to the Free Software Foundation, Inc., 675 Mass Ave, 
Cambridge, MA 02139, USA.

Credits
-------
Icons taken from Fam Fam Silk <http://www.famfamfam.com/lab/icons/silk/>
