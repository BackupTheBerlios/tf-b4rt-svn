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
# root
SVNFILES_ROOT="AUTHORS
		CHANGES
		FAQ
		FEATURES
		INSTALL
		README
		SUPPORT
		TODO"
for SVNFILE in $SVNFILES_ROOT
do
	/usr/bin/svn export --quiet --non-interactive http://svn.berlios.de/svnroot/repos/tf-b4rt/trunk/$SVNFILE
	/bin/mv -f ~/tf-b4rt/$SVNFILE /home/groups/tf-b4rt/htdocs/$SVNFILE
done
# doc
SVNFILES_DOC="azureus.txt
		manual.txt"
for SVNFILE in $SVNFILES_DOC
do
	/usr/bin/svn export --quiet --non-interactive http://svn.berlios.de/svnroot/repos/tf-b4rt/trunk/doc/$SVNFILE
	/bin/mv -f ~/tf-b4rt/$SVNFILE /home/groups/tf-b4rt/htdocs/$SVNFILE
done
# xml-schema
SVNFILES_XML="tfbserver.xsd
		tfbstats.xsd
		tfbtransfer.xsd
		tfbtransfers.xsd
		tfbusers.xsd
		tfbxfer.xsd"
for SVNFILE in $SVNFILES_XML
do
	/usr/bin/svn export --quiet --non-interactive http://svn.berlios.de/svnroot/repos/tf-b4rt/trunk/html/xml/$SVNFILE
	/bin/mv -f ~/tf-b4rt/$SVNFILE /home/groups/tf-b4rt/htdocs/$SVNFILE
done
