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
File: fs_setup.php
This file configures LnBlog's file writing and allows you to set the 
document root for your web server (it will try to auto-detect this).  
It creates the userdata/fsconfig.php file.

There are two options for file writing.

Native FS - Uses PHP's native filesystem functions.  
FTP FS    - Write files through an FTP connection (usually to localhost).

Note that there is a trade-off here.  Native FS has no special requirements
or configuration in the software, but, if your host is pporly configured, may require manually changing
file permissions due to the fact that it runs as a system account.  FTP FS,
on the other hand, requires the user to enter an FTP username, password, 
host name, and the root of that user's FTP access (assuming the user cannot
access the entire filesystem).  However, with FTP FS, LnBlog can create 
files as the configured user account, rather than a system account like 
"apache" or "IUSR_whatever".  FTP FS is recommended when you are forced to
run under PHP's "safe mode".  However, since this is now deprecated, it is
probably best to use Native FS.
*/

require_once __DIR__."/blogconfig.php";
$admin = new AdminPages();
$admin->fssetup();
