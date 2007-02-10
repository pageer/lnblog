<?php
/*
    LnBlog - A simple file-based weblog focused on design elegance.
    Copyright (C) 2005, 2006 Peter A. Geer <pageer@skepticats.com>

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
File: pingback.php
This file implements Pingback for LnBlog, as described by the Pingback
specification at <http://hixie.ch/specs/pingback/pingback>.

Pingback is similar to Trackback in that it is a mechanism for one blog to
notify another when the first blog links to it.  However, Pingback uses XML-RPC
to send pings rather than HTTP POSTs.  Additionally, the Pingback enables
auto-discovery of pingable resources using HTTP headers and HTML link elements,
as opposed to the embedded RDF code used by Trackback.

The XML-RPC interface for Pingback consists of a single pingback.ping method
which is used to send a ping.  This method takes a source and a target URI
parameter.
*/

require_once("xmlrpc/xmlrpc.inc");
require_once("xmlrpc/xmlrpcs.inc");

require_once("blogconfig.php");
require_once("lib/creators.php");

$signature = array(array($xmlrpcString, $xmlrpcString));
$func_map = array("pingback.ping"=>array("function"=>"get_ping"));

function get_ping($params) {
	# URI of the linking page sent by the client.
	$sourceURI = $params->getParam(0);
	$sourceURI = $sourceURI->scalarval();
	# The URI of the page expected to be on this server.
	$targetURI = $params->getParam(1);
	$targetURI = $targetURI->scalarval();

	$matches = array();
	$local_path = uri_to_localpath($targetURI);

	if (is_dir($local_path)) {
		$ent = NewEntry($local_path);
		
	# If the resulting entry is a file, then see if it's a PHP wrapper script
	# for entry pretty-permalinks.
	} elseif (is_file($local_path)) {
		$dir_path = dirname($local_path);
		$content = file($local_path);
	
		if (preg_match("/chdir\('([^']+)'\)/", $content[0], $matches)) {
			$dir = $matches[1];
		} else $dir = '';
		$dir = mkpath($dir_path, $dir);
		$ent = NewEntry($dir);
		
	# In the future, we will want to add conditions for checking query strings
	# and Apache rewrite rules.
	
	# If no condition is met, then bail out with an unrecognized URI fault.
	} else {
		return new xmlrpcresp(0, 33, "Target URI not recognized.");
	}
	
	if ($ent->isEntry() && $ent->allow_pingback) {

		if ($ent->pingExists($sourceURI)) {
			return new xmlrpcresp(0, 48, "A Pingback for this URI has already been registered.");
		}
		
		$ping = NewPingback();
		
		$content = $ping->fetchPage($sourceURI);
		if (! $content) {
			return new xmlrpcresp(0, 16, "Unable to read source URI.");
		} elseif (! strpos($content, $targetURI)) {
			return new xmlrpcresp(0, 17, "Source URI does not link to target URI.");
		} else {
	
			$ret = preg_match('|.*<title>(.+)</title>.*|i', $content, $matches);
			$title = $ret ? trim($matches[1]) : '';

			$ping->source = $sourceURI;
			$ping->target = $targetURI;
			$ping->title = $title;
			$ping->excerpt = '';
			
			$lines = preg_split("/<p>|\n|<br \>|<br>/i", $content);
			foreach ($lines as $line) {
				$url_pos = strpos($line, $targetURI);
				if ($url_pos) {
					$ping->excerpt = strip_tags($line);
					break;
				}
			}
			
			$ret = $ping->insert($ent);
			
			if ($ret) {
				return new xmlrpcresp(new xmlrpcval($ping->permalink(), 'string'));
			} else {
				return new xmlrpcresp(0, 0, "Unable to record this ping.");
			}
			
		}

	} else {
		return new xmlrpcresp(0, 33, "Target URI not recognized or does not support pingbacks.");
	}
	return new xmlrpcresp(0, 0, "This should never be returned.");
}

$server = new xmlrpc_server($func_map);

?>
