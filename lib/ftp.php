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

# This is a class for doing file operations using the FTP interface.  The 
# purpose for this is to keep sane file permissions on the software-generated
# content.  The FTP interface can create files and directories owned by the 
# user, while the normal local file creation functions will create files 
# owned by the web server process.

require_once("blogconfig.php");

class FTPConnection {
	
	function FTPConnection() {

	}
	
}

?>
