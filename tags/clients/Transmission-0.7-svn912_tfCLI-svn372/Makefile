# $Id: Makefile 620 2006-07-16 19:39:23Z titer $

include mk/common.mk

default: all

# Before we do anything else, make sure we have config.mk and an
# updated version.mk

required: mk/config.mk mk/version.mk
mk/config.mk:
	@echo "Please run ./configure first."
	@false
mk/version.mk: FORCE
	@echo "Checking SVN revision..."
	@./version.sh
FORCE:

# Now call the Makefile that'll really build

-include mk/config.mk
REALMAKE = $(MAKE) -f mk/default.mk

all: required
	@$(REALMAKE) all
clean: required
	@$(REALMAKE) clean
install: required
	@$(REALMAKE) install
package: required
	@$(REALMAKE) package
package-release: required
	@$(REALMAKE) package-release
