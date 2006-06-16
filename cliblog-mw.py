#!/usr/bin/python

# CLIblog-MW - A command-line client for the MetaWeblog blogging API.
# Copyright (C) 2006 Peter A. Geer <pageer@skepticats.com>
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.

# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.

import getopt, sys, time, types, string
import os, os.path
import xmlrpclib
import mimetypes
import base64

version = ('0', '1', '0')

def main():
	
	short_args = 'a:t:c:d:f:hn:e:p:v'
	long_args = ['action=','title=','categories=','description=','file=','help','numposts=','entryid=','profile=','version']
	
	try:
		opts,args = getopt.getopt(sys.argv[1:], short_args, long_args)
	except:
		usage()
		sys.exit(2)

	if len(args) == 0 and len(opts) == 0:
		usage()
		sys.exit(0)
		
	method = 'newPost'
	title = ''
	cats = []
	text = ''
	infile = ''
	numposts = 5
	entid = ''
	profile = ''
	
	for o, a in opts:
		if o in ('-a', '--action'):
			if a.lower() in ('newpost','editpost','getpost','getrecentposts','getcategories','newmediaobject'):
				method = a
			else:
				print 'Action '+a+' not recognized.'
				usage()
				sys.exit(2)

		elif o in ('-t', '--title'):
			title = a

		elif o in ('-c', '--catgeories'):
			cats = a.split(',')

		elif o in ('-d', '--description'):
			text = a

		elif o in ('-f', '--file'):
			infile = a

		elif o in ('-n', '--numposts'):
			numposts = a

		elif o in ('-e', '--entryid'):
			entid = a
			
		elif o in ('-p', '--profile'):
			profile = a

		elif o in ('-h', '--help'):
			usage()
			sys.exit(0)

		elif o in ('-v', '--version'):
			print string.join(version, '.')
			sys.exit(0)
			
	try:
		if len(args) == 4:
			server_uri = args[0]
			username = args[1]
			password = args[2]
			id = args[3]
		else:
			id = args[0]
			if profile == '':
				path = os.path.join([os.environ('HOME'), '.cliblog', 'default'])
			elif os.access(profile, os.R_OK):
				path = profile
			else:
				path = path = os.path.join([os.environ('HOME'), '.cliblog', profile])
			
			fh = file(path)
			for line in fh:
				key, val = string.split(line, '=', 1)
				if key.lower() == 'uri':
					server_uri = string.strip(val)
				elif key.lower() == 'user':
					username = string.strip(val)
				elif key.lower() == 'password':
					password = string.strip(val)
			
	except:
		print 'Error: Cannot get connection information.  Make sure you have supplied all the '
		print 'parameters or that the profile you gave is correct.'
		usage()
		sys.exit(2)

	# Create the XML-RPC client
	server = xmlrpclib.ServerProxy(server_uri)

	# Select the appropriate method and send the request to the server.
	if method.lower() in ('newpost', 'editpost'):
		if infile != '':
			try:
				data = ''
				lineno = 1
				fh = file(infile, 'r')
				for line in fh:
					# If either of the first two lines has the title or tags, 
					# then extract them.
					# Note that we only use the title and tags in the file if
					# they were NOT specified on the command line.
					# Also note that these lines are optional, so they may not 
					# be given at all.
					if lineno <= 2:
						vals = string.split(line, ':', 1)
						if len(vals) == 2:
							label = vals[0]
							value = vals[1]
						else:
							data = data + line
						if label.lower() == 'title' and title == '':
							title = string.strip(value)
						elif label.lower() == 'tags' and cats == []:
							value = string.strip(value)
							cats = value.split(',')
					else:
						data = data + line
					lineno = lineno+1
				text = data
			except:
				print "Error getting data from file '"+infile+"'."
				sys.exit(3)
		
		entry_struct = {'title':title, 'category':cats, 'description':text}
		try:
			if method.lower() == 'newpost':
				ret = server.metaWeblog.newPost(id, username, password, entry_struct, 1)
			else:
				ret = server.metaWeblog.editPost(id, username, password, entry_struct, 1)
			print ret
		except xmlrpclib.Fault, fault:
			print 'Fault '+str(fault.faultCode)+': '+fault.faultString

	elif method.lower() == 'getpost':
		try:
			ret = server.metaWeblog.getPost(id, username, password)
			print_struct(ret)
		except xmlrpclib.Fault, fault:
			print 'Fault '+str(fault.faultCode)+': '+fault.faultString

	elif method.lower() == 'getcategories':
		try:
			ret = server.metaWeblog.getCategories(id, username, password)
			for catstruct in ret.items():
				print 'Category: '+catstruct[0]
				print_struct(catstruct[1])
				print ''
		except xmlrpclib.Fault, fault:
			print 'Fault '+str(fault.faultCode)+': '+fault.faultString

	elif method.lower() == 'getrecentposts':
		try:
			ret = server.metaWeblog.getRecentPosts(id, username, password, numposts)
			for post in ret:
				print_struct(post)
				print ""
		except xmlrpclib.Fault, fault:
			print 'Fault '+str(fault.faultCode)+': '+fault.faultString

	elif method.lower() == 'newmediaobject':
		try:
			hnd = file(infile)
			data = hnd.read()
			data = string.strip(base64.encodestring(data))
			name = os.path.basename(hnd.name)
			hnd.close()
			type,enc = mimetypes.guess_type(infile)

			try:
				request = {'name':name, 'type':type, 'bits':data}
				if entid != '':
					request['entryid'] = entid
				ret = server.metaWeblog.newMediaObject(id, username, password, request)
				print_struct(ret)
			except xmlrpclib.Fault, fault:
				print 'Fault '+str(fault.faultCode)+': '+fault.faultString

		except:
			print 'Unable to read data from file "'+infile+'".'
			sys.exit(3)


def print_struct(post):
	for key,val in post.items():
		if type(val) == types.ListType:
			print key+': '+string.join(val, ',')
		else:
			print key+': '+str(val)


def usage():
	print sys.argv[0]+": send messages to a blog using the MetaWeblog API.\n\
\n\
Usage: "+sys.argv[0]+" [options] [-p profile | uri user password] identifier\n\
\n\
The options listed below are used to set the action and data send in the API \n\
call.  The connection information is passed to the API call using the other\n\
parameters.  They are the URI of the web service, the user and password to log\n\
in to the blog.  The only required parameter is the blog/entry identifier.\n\
The server's response is printed to standard output.\n\n\
Alternatively, you can create a profile that holds this information.  Profiles\n\
are simply text files with one line per entry with a key=value format.  If the\n\
-p option is given a name rather than a path, it will search for the profile in\n\
the $HOME/.cliblog directory.  If no profile and no connection parameters are\n\
given, then it will try to use the $HOME/.cliblog/default file.\n\n\
Here is an example profile file:\n\
uri=http://somehost.com/someservice\n\
user=joeblow\n\
password=12345\n\
\n\
Examples:\n\n\
Get a list of the blog categories, listing connection info on the command line:\n\
   "+sys.argv[0]+" -a getcategories http://myserver.com/service bob passwd myblog\n\n\
Upload an MP3 to the podcast blog using the ~/.cliblog/bobs profile file:\n\
   "+sys.argv[0]+" -p bobs -a newMediaObject -f ~/podcast/ep06.mp3 podcast\n\n\
Create a new post in myblog connectin with the ~/.cliblog/default profile file.\n\
   "+sys.argv[0]+" -t 'Test post' -c Test,Foo -d 'This is a test post' myblog\n\
\n\
Options:\n\n\
-a  --action       The action to take.  Can be newPost, editPost, getPost, \n\
                   getCategories, getRecentPosts, or newMediaObject.  \n\
                   The default is newPost.\n\
\n\
-t  --title        String for the title.  Only used with the newPost and\n\
                   editPost methods.\n\
\n\
-c  --categories   The categories for the post when using the newPost or \n\
                   editPost method.  To give the post multiple categories,\n\
                   separate the categories by commas.\n\
\n\
-d  --description  The full text of the post for newPost or editPost methods.\n\
\n\
-n  --numposts     The number of posts to get when calling the getRecentPosts\n\
                   method.  The default is 5.\n\
\n\
-e  --entryid      For newMediaObject calls, an optional entry ID to which the \n\
                   file should be added.  Note that this is not a standard part\n\
                   of the MetaWeblog API and WILL NOT be implemented by all \n\
                   blogs.  This is currently supported by LnBlog version 0.7.1\n\
                   and greater.\n\
\n\
-f  --file         The file name used for this request.  If the request uses\n\
                   the newMediaObject method, then this is the file that will\n\
                   be sent to the blog.\n\
                   If it uses the newPost or editPost method, then this file \n\
                   will be used as the text for the entry.  If the file has \n\
                   lines beginning with 'Title:' or 'Tags:', then these will be\n\
                   used for the post title and catagories.  Note that the\n\
                   --title and --catagories flags, if specified, will override \n\
                   the file contents when determining the post contnet.  \n\
                   However, the --description tag will be ignored if a file is\n\
                   specified.\n\
                   Here is some example file content:\n\
                        Title: My test post title\n\
                        Tags: Test,Example\n\
                        Here is the actual content of the post body.\n\
\n\
-p  --profile      Use given profile file for the connection information.  If\n\
                   the four\n"


if __name__ == "__main__":
    main()
