About LnBlog
============
LnBlog is a weblog system that is written in PHP and stores data in text files.
It aims to provide a rich feature set with maximum flexibility while remaining
low on requirements.

Documentation
=============
Full documentation is available at the LnBlog website.  It can be viewed online 
at the following URL:  
https://www.skepticats.com/lnblog/documentation/

You may also download a copy of the documentation for local viewing at the 
following location:  
https://www.skepticats.com/lnblog/content/download/lnblog_docs.zip

Requirements
============
LnBlog needs a web server with PHP 7.0 or greater installed and the ability to
read and write to the local filesystem.  Support for the CURL, gettext, and 
either mime-magic or fileinfo extensions is helpful, but no non-standard 
extensions are required.

No database is needed and no particular web server is required.  LnBlog
is tested with Apache running on Windows and Linux.  Other web servers and
operating systems should work, but are not actively tested.

Installation
============
There are two ways to install: from the distribution ZIP file downloaded from
the LnBlog webpage (recommended) or from source, i.e. the cloned Git repository.

Installing from zip archive
---------------------------
To install the [ZIP file distribution](https://www.skepticats.com/lnblog/content/download/),
 simply extract the ZIP archive and upload 
the resulting folder to the publicly accessible portion of your web server.
After that, open a web browser go to the URL corresponding to that location.
This will start the graphical configuration process.  You will be prompted to 
confirm path information and create an initial user account, after which you will 
be taken to the administration page where you can create more users, create blogs,
and set other options.

Building from source
--------------------
To build LnBlog from source you will need the following installed on your system:
* Git
* PHP
* Composer
* Node.JS/NPM

To get the latest code and create an installable build, run the following
commands from the console:

```
git clone https://github.com/pageer/lnblog.git
cd lnblog
composer install --dev
vendor/bin/phing -Dversion='latest' build
```

This will create the directory `build/LnBlog-latest/`, which contains the same files
as the zip archive distribution described above.  You can copy that directory to 
the web-accessible portion of your web server and follow the installation instructions above.

Note that it is also possible to put the directory pulled from Git directly on your 
web server.  This makes it easy to pull updates to the code at will.  However, you will
have to manually handle changes to third-party dependencies.  For PHP dependencies, this means
you will have to run `composer install` to pull in updates.  For JavaScript dependencies,
you should create a build as described above and recursively copy the contents of the 
`build/third-party/` directory into the root LnBLog directory using something like this command:

```
cp -R build/third-party/ . 
```

When pulling new changes, you may have to repeat these steps if any of the external 
dependencies change.  (Look for changes in `composer.lock`, `packages.json`, or `build.xml`.)

Upgrading
=========
To upgrade an existing LnBlog installation, extract the new ZIP archive 
(or create a new build from source) and upload the folder to your server.  You should 
then rename your old LnBLog directory to, e.g., LnBlog-old, and rename the new one 
in its place (i.e., give it the same name the old version had).  If your `userdata` directory
is contained inside the LnBlog directory, you should move it from the old version to the new one.
(Note: it is now recommended to keep your `userdata` directory alongside the code direcory,
not inside it.)

License
=======
LnBlog: A flexible file-base weblog
Copyright (C) 2005-2020 Peter A. Geer <pageer@skepticats.com>

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
=======
Icons taken from Fam Fam Silk <http://www.famfamfam.com/lab/icons/silk/>
