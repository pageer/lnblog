#!/bin/sh

# Get working copy directory.
if [ -d "$1" ] ; then
	LNBLOG_DIR="$1"
else
	LNBLOG_DIR=`pwd`
fi

if [ -d "$2" ] ; then
	DIST_DIR="$2"
else
	DIST_DIR="$HOME/www/dist"
fi

if [ ! -e "$LNBLOG_DIR/blogconfig.php" ] ; then
	echo "$LNBLOG_DIR is not a copy of LnBlog!"
	exit
fi

# Extract package version number.
VERSION=`grep 'define("PACKAGE_VERSION' $LNBLOG_DIR/blogconfig.php | sed 's/define("PACKAGE_VERSION", *"\(.\+\)");/\1/'`

if [ -d "$DIST_DIR/LnBlog-$VERSION" ] ; then
	echo "There is already a distribution directory for LnBlog-$VERSION!"
	exit
fi

NEW_COPY="$DIST_DIR/LnBlog-$VERSION"

# Make a copy of the directory to use for the distribution
cd -R "$LNBLOG_DIR" "$NEW_COPY"

# Make a fresh copy of the documentation
mkdir "$DIST_DIR/documentation"
naturaldocs -p "$LNBLOG_DIR/doc_project" -i "$LNBLOG_DIR" -o HTML "$DIST_DIR/documentaiton"

# Remove the test server data, .svn folders, and other files end users don't need to see.
find "$NEW_COPY" -name '.svn' -exec rm -rf {} \;
rm -r "$NEW_COPY/userdata"
mkdir "$NEW_COPY/userdata"
rm -r "$NEW_COPY/doc_project"
rm -r "$NEW_COPY/docs"
rm "$NEW_COPY/mkdist.sh"
rm "$NEW_COPY/lnblog.webprj"

# Make the distribution archive and the associated checksums and signature.
zip -r "$DIST_DIR/LnBlog-$VERSION.zip" "$DIST_DIR/LnBlog-$VERSION"
sha1sum -b "$DIST_DIR/LnBlog-$VERSION.zip" "$DIST_DIR/LnBlog-$VERSION.sha1"
md5sum -b "$DIST_DIR/LnBlog-$VERSION.zip" "$DIST_DIR/LnBlog-$VERSION.md5"
kgpg -S "$DIST_DIR/LnBlog-$VERSION.zip"