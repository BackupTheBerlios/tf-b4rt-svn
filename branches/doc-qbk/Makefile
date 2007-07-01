################################################################################
# $Id$
# $Date$
# $Revision$
################################################################################
#                                                                              #
# LICENSE                                                                      #
#                                                                              #
# This program is free software; you can redistribute it and/or                #
# modify it under the terms of the GNU General Public License (GPL)            #
# as published by the Free Software Foundation; either version 2               #
# of the License, or (at your option) any later version.                       #
#                                                                              #
# This program is distributed in the hope that it will be useful,              #
# but WITHOUT ANY WARRANTY; without even the implied warranty of               #
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the                 #
# GNU General Public License for more details.                                 #
#                                                                              #
# To read the license please visit http://www.gnu.org/copyleft/gpl.html        #
#                                                                              #
#                                                                              #
################################################################################
# Makefile for torrentflux-b4rt doc.
################################################################################


# Boost URL.
BOOSTBASENAME:=boost_1_34_0
BOOSTFILENAME:=$(BOOSTBASENAME).tar.bz2
BOOSTURL:=http://dl.sourceforge.net/sourceforge/boost/$(BOOSTFILENAME)

# DocBook URLs.
DOCBOOKDTDBASENAME:=docbook-xml-4.2
DOCBOOKDTDFILENAME:=$(DOCBOOKDTDBASENAME).zip
DOCBOOKDTDURL:=http://www.oasis-open.org/docbook/xml/4.2/$(DOCBOOKDTDFILENAME)
DOCBOOKXSLBASENAME:=docbook-xsl-1.72.0
DOCBOOKXSLFILENAME:=$(DOCBOOKXSLBASENAME).tar.bz2
DOCBOOKXSLURL:=http://dl.sourceforge.net/sourceforge/docbook/$(DOCBOOKXSLFILENAME)



# Default target: build doc.
default: doc

# Phony targets.
.PHONY: Makefile default boost-auto boost boost-core docbook-auto docbook docbook-core bjam-auto bjam bjam-core tools-auto tools tools-core doc clean cleaner cleanest


#
# Boost targets.
#

boost-auto: tools/boost.ok

boost: boost-core

	# Yes, this is prolly retarded, but I don't know how
	# to write makefiles, so I just do what I can :-)
tools/boost.ok: $(if $(wildcard tools/boost.ok),,boost-core)

boost-core:
	@echo ''
	@echo '=> Downloading Boost v1.34.0'
	@echo ''
	rm -Rf tools/boost.ok tools/boost tools/$(BOOSTBASENAME) tools/$(BOOSTFILENAME)
	wget -c -O tools/$(BOOSTFILENAME) $(BOOSTURL)
	@echo ''
	@echo '=> Unpacking Boost'
	@echo ''
	( cd tools && tar -xjf $(BOOSTFILENAME) )
	rm -f tools/$(BOOSTFILENAME)
	mv tools/$(BOOSTBASENAME) tools/boost
	rm -Rf tools/boost/tools/quickbook
	cp -R tools/delta/boost/tools/quickbook tools/boost/tools/
	( cd tools && patch boost/tools/build/v2/user-config.jam < delta/boost/user-config.jam.diff )
	touch tools/boost.ok



#
# DocBook targets.
#

docbook-auto: tools/docbook.ok

docbook: docbook-core

tools/docbook.ok: $(if $(wildcard tools/docbook.ok),,docbook-core)

docbook-core:
	@echo ''
	@echo '=> Downloading DocBook dtd v4.2 and xsl v1.72.0'
	@echo ''
	rm -Rf tools/docbook.ok tools/docbook
	mkdir tools/docbook
	wget -c -O tools/docbook/$(DOCBOOKDTDFILENAME) $(DOCBOOKDTDURL)
	wget -c -O tools/docbook/$(DOCBOOKXSLFILENAME) $(DOCBOOKXSLURL)
	@echo ''
	@echo '=> Unpacking DocBook'
	@echo ''
	mkdir tools/docbook/dtd
	( cd tools/docbook/dtd && unzip -q ../$(DOCBOOKDTDFILENAME) )
	rm -f tools/docbook/$(DOCBOOKDTDFILENAME)
	( cd tools/docbook && tar -xjf $(DOCBOOKXSLFILENAME) )
	rm -f tools/docbook/$(DOCBOOKXSLFILENAME)
	mv tools/docbook/$(DOCBOOKXSLBASENAME) tools/docbook/xsl
	touch tools/docbook.ok



# BJam toolset to use. Default to gcc, set environment variable TOOLSET if you want to change it.
TOOLSET?=gcc

# BJam executable. Detect whether there is no bjam available (build it),
# one available (use it), or several lying around (choke).
bjamexe = $(wildcard tools/boost/tools/jam/src/bin.*/bjam)
bjamcnt = $(words $(bjamexe))
bjamdep = $(if $(filter-out 1,$(bjamcnt)), bjam-core)
getbjam = $(if $(filter     0,$(bjamcnt)), $(error bjam not found in tools/boost/tools/jam/src/bin.*, weird), \
          $(if $(filter-out 1,$(bjamcnt)), $(error Multiple bjam executables in tools/boost/tools/jam/src/bin.*, please fix), \
          $(bjamexe)))

bjamargs:=--v2 --toolset=$(TOOLSET) --layout=system -j2 variant=release


#
# BJam targets. If bjam rebuild not requested explicitely,
# only build if bjam was not already found.
#

bjam-auto: boost-auto $(bjamdep)

bjam: bjam-core

# Call bjam build script (simple shell script, won't
# do anything if bjam is already built).
bjam-core: boost-auto
	@echo ''
	@echo '=> Building bjam'
	@echo ''
	( cd tools/boost/tools/jam/src && ./build.sh --update )
ifneq ($(MAKECMDGOALS),bjam)
	@echo ''
	@echo '======================================================================'
	@echo '=> GNU make bug, cannot go on in this instance. Please run make again.'
	@echo "   (This only happens the first time, don't worry :D)"
	@echo '======================================================================'
	@echo ''
	@false
# Bug is: result of $(wildcard ...) is cached -- so even though previous
# command just built a bjam, $(bjamexe) still doesn't show it.
endif



#
# Tools targets. If tools rebuild not requested explicitely,
# don't build anything -- bjam invocation in doc target will
# have Boost.Build build everything that is needed.
#

#tools-auto: bjam-auto tools-core
tools-auto: bjam-auto

tools: tools-core

tools-core: bjam-auto
	@echo ''
	@echo '=> Building tools'
	@echo ''
	( cd tools/boost/tools/quickbook && ../../../../$(strip $(getbjam)) $(bjamargs) )



#
# Doc target.
#

doc: tools-auto docbook-auto
	@echo ''
	@echo '=> Building doc'
	@echo ''
	tools/validate.sh src
	$(strip $(getbjam)) $(bjamargs)



#
# Cleaning targets.
#

clean:
	@echo ''
	@echo '=> Cleaning doc'
	@echo ''
	rm -Rf bin html

cleaner: clean
	@echo ''
	@echo '=> Cleaning tools'
	@echo ''
	rm -Rf tools/boost/bin.v2 tools/boost/dist

cleanest: cleaner
	@echo ''
	@echo '=> Cleaning DocBook'
	@echo ''
	rm -Rf tools/docbook.ok tools/docbook
	@echo ''
	@echo '=> Cleaning Boost'
	@echo ''
	rm -Rf tools/boost.ok tools/boost tools/$(BOOSTBASENAME) tools/$(BOOSTFILENAME)
