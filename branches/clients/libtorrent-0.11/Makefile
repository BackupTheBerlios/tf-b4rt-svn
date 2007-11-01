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
# In addition, as a special exception, the copyright holders give              #
# permission to link the code of portions of this program with the             #
# OpenSSL library under certain conditions as described in each                #
# individual source file, and distribute linked combinations                   #
# including the two.                                                           #
#                                                                              #
# You must obey the GNU General Public License in all respects                 #
# for all of the code used other than OpenSSL.  If you modify                  #
# file(s) with this exception, you may extend this exception to your           #
# version of the file(s), but you are not obligated to do so.  If you          #
# do not wish to do so, delete this exception statement from your              #
# version.  If you delete this exception statement from all source             #
# files in the program, then also delete it here.                              #
#                                                                              #
################################################################################
# tfcli-libtorrent makefile.
################################################################################


# Tools.
CP:=cp
LN:=ln
RM:=rm
SED:=sed
STRIP:=strip
TAR:=tar
WGET:=wget

# URLs.
LT_BASENAME := libtorrent-0.11.9
LT_FILENAME := $(LT_BASENAME).tar.gz
LT_URL      := http://libtorrent.rakshasa.no/downloads/$(LT_FILENAME)

# Paths.
CLI_ROOT := tfcli/
CLI_NAME := tfcli-libtorrent
LT_ROOT  := $(LT_BASENAME)/
LT_INC   := $(LT_ROOT) $(LT_ROOT)src/
LT_LIB   := $(LT_ROOT)src/.libs/
LT_NAME  := libtorrent.a

# Base compile/link flags.
CFLAGS  := -pipe
LDFLAGS :=

# Debug flags.
#DEBUG := 1
ifeq ($(DEBUG),)
CFLAGS  += -O3 -DNDEBUG
else
CFLAGS  += -g -DDEBUG
LDFLAGS += -g
endif


# Check for curl.
$(if $(shell which curl-config            2>/dev/null),,$(error curl not found))

# Check for pkg-config.
$(if $(shell which pkg-config             2>/dev/null),,$(error pkg-config not found))

# Check for sigc++-2.0.
$(if $(shell pkg-config --libs sigc++-2.0 2>/dev/null),,$(error sigc++-2.0 not found))


# Compile/link flags.
CLICFLAGS  := $(CFLAGS) -Wall -Werror -D"LIBSIGC_DISABLE_DEPRECATED" -I"$(CLI_ROOT)" $(addprefix -I,$(LT_INC)) `pkg-config --cflags sigc++-2.0` `curl-config --cflags`
CLILDFLAGS := $(LDFLAGS)
CLILIBS    := `pkg-config --libs sigc++-2.0` `curl-config --libs`


# Internals.
DEPFILE:=.depends
BUILDDIR:=.build/
OBJECTS:=cli.o cmd_file.o common.o create.o curl_get.o curl_stack.o info.o opts.o pid_file.o poller.o signals.o stat_file.o transfer.o transfer_context.o


# Phony targets.
.PHONY: Makefile all cli depends lib libtorrent clean cleaner cleanest

# Default target.
all: $(CLI_NAME)


#########
## cli ##
#########

# Final executable.
cli $(CLI_NAME): $(CLI_ROOT)$(BUILDDIR)$(CLI_NAME).stripped
	@echo "    CP      $(notdir $@)"
	@$(CP) -f "$<" "$(CLI_NAME)"

# Stripped executable (only if not in debug).
$(CLI_ROOT)$(BUILDDIR)$(CLI_NAME).stripped: $(CLI_ROOT)$(BUILDDIR)$(CLI_NAME).full
ifeq ($(DEBUG),)
	@echo "    STRIP   $(CLI_NAME).stripped"
	@$(STRIP) --strip-unneeded -o "$@" "$<"
else
	@echo "    LN      $(CLI_NAME).stripped"
	@$(LN) -f "$<" "$@"
endif

# Executable.
$(CLI_ROOT)$(BUILDDIR)$(CLI_NAME).full: $(addprefix $(CLI_ROOT)$(BUILDDIR),$(OBJECTS)) $(LT_LIB)$(LT_NAME)
	@echo "    LD      $(CLI_NAME).full"
# Detect whether openssl is needed, check pkg-config knows about it and add it to linker flags
# (actually only -lcrypto is needed, for DH / RC4 / SHA1). Do this inline and not in variables
# above because it depends on lt's config.h which is not necessarily present when make starts.
	@$(CXX) $(CLICFLAGS) $(CLILDFLAGS) $(CLILIBS)$(if $(shell grep '^\#define[[:space:]]\+USE_OPENSSL[[:space:]]\+1' "$(LT_ROOT)/config.h" >/dev/null && echo needed),$(if $(shell pkg-config --libs-only-L --libs-only-other openssl >/dev/null 2>&1 || echo notfound),$(error openssl not found), `pkg-config --libs-only-L --libs-only-other openssl` -lcrypto)) -o "$@" $(addprefix $(CLI_ROOT)$(BUILDDIR),$(OBJECTS)) $(LT_LIB)$(LT_NAME)

# Build directory rule.
$(CLI_ROOT)$(BUILDDIR):
	@echo "    MKDIR   $(BUILDDIR:/=)"
	@-mkdir -p "$(CLI_ROOT)$(BUILDDIR)"

# Generic C++ rule.
$(CLI_ROOT)$(BUILDDIR)%.o: $(CLI_ROOT)%.cc $(LT_ROOT)config.h | $(CLI_ROOT)$(BUILDDIR) $(CLI_ROOT)$(BUILDDIR)$(DEPFILE)
	@echo "    CXX     $(notdir $<)"
	@$(CXX) $(CLICFLAGS) $(CXXFLAGS) -o "$@" -c "$<"

# Dependencies.
depends $(CLI_ROOT)$(BUILDDIR)$(DEPFILE): $(LT_ROOT)config.h | $(CLI_ROOT)$(BUILDDIR)
	@echo "    DEPS"
	@$(CXX) $(CLICFLAGS) $(CXXFLAGS) -MM $(addprefix $(CLI_ROOT),$(OBJECTS:%.o=%.cc)) | $(SED) 's#^[^ .:/][^ .:/]*.o:#$(CLI_ROOT)$(BUILDDIR)&#' > $(CLI_ROOT)$(BUILDDIR)$(DEPFILE)

# Include dependencies sub-Makefile without adding a dependency
# on it if it doesn't exist (e.g. make clean).
ifneq ($(wildcard $(CLI_ROOT)$(BUILDDIR)$(DEPFILE)),)
-include $(CLI_ROOT)$(BUILDDIR)$(DEPFILE)
endif


################
## libtorrent ##
################

# Library.
lib libtorrent $(LT_LIB)$(LT_NAME): $(LT_ROOT)config.h
	@echo "    MAKE    $(LT_BASENAME)    (patience...)"
	@cd "$(LT_ROOT)" && $(MAKE) > /dev/null

# Config file.
$(LT_ROOT)config.h: $(LT_ROOT)configure
	@echo "    CONFIG  $(LT_BASENAME)"
	@cd "$(LT_ROOT)" && ./configure $(if $(DEBUG),--enable-debug,--disable-debug) --enable-static --disable-shared CXXFLAGS="$(CFLAGS)" LDFLAGS="$(LDFLAGS)" > /dev/null

# Configure script.
$(LT_ROOT)configure: | $(LT_FILENAME)
	@echo "    UNTAR   $(LT_BASENAME)"
	@$(TAR) -xzf "$(LT_FILENAME)"

# Libtorrent package.
$(LT_FILENAME):
	@echo "    WGET    $(LT_BASENAME)"
	@$(WGET) -q -c -O "$(LT_FILENAME)" "$(LT_URL)"


# Cleanup.
clean:
	@echo "    RM"
	@-$(RM) -f "$(CLI_NAME)" "$(CLI_ROOT)"*~
	@-$(RM) -Rf "$(CLI_ROOT)$(BUILDDIR)"

cleaner: clean
	@echo "    RM      $(LT_BASENAME)"
	@-$(RM) -Rf "$(LT_ROOT)"

cleanest: cleaner
	@echo "    RM      $(LT_FILENAME)"
	@-$(RM) -f "$(LT_FILENAME)"
