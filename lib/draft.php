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

# Class: Draft
# Create and edit draft entries to be published later.

class Draft extends BlogEntry {

	function insert() {

		$usr = NewUser();
		if (! $usr->checkLogin() ) return false;
		$this->uid = $usr->username();
	
		$curr_ts = time();
		$dir_path = mkpath(BLOG_ROOT,ENTRY_DRAFT_PATH,date("Y-m-d_His", $curr_ts));
		# If the entry driectory already exists, something is wrong. 
		if ( is_dir($dir_path) ) return false;
		
		$this->raiseEvent("OnInsert");

		$ret = create_directory_wrappers($dir_path, ENTRY_DRAFTS);

		$this->file = $dir_path.PATH_DELIM.ENTRY_DEFAULT_FILE;
		# Set the timestamp and date, plus the ones for the original post, if
		# this is a new entry.
		$this->ip = get_ip();

		$ret = $this->writeFileData();
		$this->raiseEvent("InsertComplete");
		return $ret;
	}
	
	function delete() {
		if (file_exists($this->file)) {
			$fs = NewFS();
			$ret = $fs->delete($this->file);
		} else $ret = false;
		return $ret;
	}
	
	function update() {
		$fs = NewFS();
		$ret = $fs->rename($this->file, $this->file.".bak");
		$ret = $this->writeFileData();
		if (! $ret) {
			$ret = $fs->rename($this->file.".bak", $this->file);
			return false;
		}
		return true;
	}

}
