#!/bin/sh
################################################################################
# $Id$
# $Revision$
# $Date$
################################################################################
# shell-script to export dynamic content to website
################################################################################

# news-export
/usr/bin/wget -q -O ~/tf-b4rt/newshtml.tmp 'http://developer.berlios.de/export/projnews.php?group_id=7000&limit=10&flat=1&show_summaries=0' > /dev/null
/bin/mv -f ~/tf-b4rt/newshtml.tmp /home/groups/tf-b4rt/htdocs/newshtml.txt

# files from svn
cd ~/tf-b4rt/
SVNFILES="AUTHORS
		CHANGES
		FAQ
		FEATURES
		INSTALL
		README
		SUPPORT
		TODO"
for SVNFILE in $SVNFILES
do
	/usr/bin/svn export --quiet --non-interactive http://svn.berlios.de/svnroot/repos/tf-b4rt/trunk/$SVNFILE
	/bin/mv -f ~/tf-b4rt/$SVNFILE /home/groups/tf-b4rt/htdocs/$SVNFILE
done
