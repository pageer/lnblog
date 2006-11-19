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

import xml.dom.minidom

version = ('0', '2', '0')
package_name = 'cliblog'

class Connection:
	def __init__(self):
		self.username = ''
		self.password = ''
		self.blog = ''
		self.uri = ''
		
	def __init__(self, profile):
		self.username = ''
		self.password = ''
		self.blog = ''
		self.uri = ''
		self.readProfile(profile)
	
	def createServer(self, uri = ''):
		if uri != '':
			self.uri = uri
		self.server = xmlrpclib.ServerProxy(self.uri)
		
	def readProfile(self, filename):
		if os.access(filename, os.R_OK):
			path = filename
		else:
			path = os.path.join([os.environ('HOME'), '.' + package_name, filename + '.xml'])
		domtree = xml.dom.minidom.parse(path)
		self.uri = document.getElementsByTagName('uri')[0].childNodes[0].data
		self.username = document.getElementsByTagName('username')[0].childNodes[0].data
		self.password = document.getElementsByTagName('password')[0].childNodes[0].data
		blog = document.getElementsByTagName('blog')
		if blog != []:
			self.blog = blog
			
	def writeProfile(self, name):
		path = os.path.join([os.environ('HOME'), '.' + package_name)
		if os.access(path, os.W_OK):
			
		else:
			path = os.environ('HOME')
		
		

class BlogEntry:
	def __init__(self):
		self.id = ''
		self.description = ''
		self.title = ''
		self.categories = []
		
	def __init__(self, struct):
		self.id = ''
		self.description = ''
		self.title = ''
		self.categories = []
		self.unserialize(struct)

	def __init__(self, data, title, categories):
		self.id = ''
		self.description = data
		self.title = title
		self.categories = categories

	def serialize(self):
		return self.__dict__
	
	def unserialize(self, struct):
		for key, val in struct.items():
			self.__dict__[key] = val
		
	def get(self, conn, id = ''):
		"""Retrieves the entry data via a metaWeblog.getPost method call."""
		if id != '':
			self.id = id
		ret = conn.server.metaWeblog.getPost(self.id, conn.username, conn.password)
		self.unserialize(ret)

	def new(self, conn, blog='', publish = True):
		"""Calls metaWeblog.newPost to create a new blog entry based on the 
		current object."""
		if blog != '':
			conn.blog = blog
		ret = conn.server.metaWeblog.newPost(conn.blog, conn.username, conn.password, self.serialize(), publish)
		self.id = ret
		return ret
		 
	def edit(self, conn, publish = True):
		"""Calls metaWeblog.editPost to change the contents of the current 
		object on the server.  The content of this object should reflect the 
		desired new content of the post."""
		ret = conn.server.metaWeblog.editPost(self.id, conn.username, conn.password, self.serialize(), publish)
		return ret

	def printPost(self):
		data = ''
		for key,val in post.items():
			if key == 'description':
				data = val
			elif type(val) == types.ListType:
				print key+': '+string.join(val, ',')
			else:
				print key+': '+str(val)
		print data

		
class Blog:
	def __init__(self):
		self.id = ''
		self.categories = {}
		self.posts = []
		
	def newMediaObject(self, conn, path, name = '', mimetype = ''):
		hnd = file(infile)
		data = hnd.read()
		data = string.strip(base64.encodestring(data))
		
		if name == '':
			name = os.path.basename(hnd.name)
		
		hnd.close()
		
		if mimetype == '':
			mimetype,enc = mimetypes.guess_type(infile)
		
		request = {'name':name, 'type':mimetype, 'bits':data}
		ret = conn.server.metaWeblog.newMediaObject(self.id, conn.username, conn.password, request)
		return ret
		
	def newPost(self, conn, post, publish = True):
		return post.new(conn, self.id, publish)

	def getCategories(self, conn):
		ret = conn.server.metaWeblog.getVategories(self.id, conn.username, conn.password)
		self.categories = ret
		return ret
		
	def getRecentPosts(self, conn, numposts):
		posts = conn.server.metaWeblog.getRecentPosts(self.id, conn.username, conn.password, numposts)
		ret = []
		for p in posts:
			ret.append(BlogEntry(p))
		return ret
		
	def printCategories(self):
		for key, val in self.categories.items():
			print 'Category: ' + key
			for k, v in val.items():
				print k + ": " + v
			print ''

			
def proc_command_line(opts, args):
	"""Converts the command-line arguments into a dictionary for easy lookup."""
	ret = {'action':'', 'title':'', 'categories':'', 'description':'', 
	       'file':'', 'numposts':'', 'id':'', 'profile':'', 
	       'user':'', 'passwd': '', 'help': False, 'version': False}
	for o, a in opts:
		if o in ('-a', '--action'):
			ret['action'] = a.lower()
		elif o in ('-t', '--title'):
			ret['title'] = a
		elif o in ('-c', '--catgeories'):
			ret['categories'] = a.split(',')
		elif o in ('-d', '--description'):
			ret['description'] = a
		elif o in ('-f', '--file'):
			ret['file'] = a
		elif o in ('-n', '--numposts'):
			ret['numposts'] = a
		elif o in ('-i', '--id'):
			ret['id'] = a
		elif o in ('-p', '--profile'):
			ret['profile'] = a
		elif o in ('-h', '--help'):
			ret['help'] = True
		elif o in ('-l', '--login'):
			ret['user'] = 
			ret['passwd'] = 
		elif o in ('-v', '--version'):
			ret['version'] = True
	
	
#def create_connection(opts, args):
	

def main():
	
	short_args = 'a:t:c:d:f:hn:i:p:vl:'
	long_args = ['action=','title=','categories=','description=','file=','help',
	             'numposts=','id=','profile=','version','login=']
	
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
	
	commands = proc_command_line(opts, args)
	
	if 
			
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
-l  --login        Set the username and password to log in.  The single \n\
                   argument is of the form username:password.\n\
\n\
-p  --profile      Use given profile file for the connection information.\n"


if __name__ == "__main__":
    main()
