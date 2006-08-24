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
#                                                                              #
#  Requirements :                                                              #
#   * DBI                      ( perl -MCPAN -e "install Bundle::DBI" )        #
#   * DBD::mysql for MySQL     ( perl -MCPAN -e "install DBD::mysql" )         #
#   * DBD::SQLite for SQLite   ( perl -MCPAN -e "install DBD::SQLite" )        #
#                                                                              #
################################################################################
package FluxDB;
use DBI;
use strict;
################################################################################

################################################################################
# fields                                                                       #
################################################################################

# version in a var
my $VERSION = do {
	my @r = (q$Revision$ =~ /\d+/g); sprintf "%d"."%02d" x $#r, @r };

# state
# -1 error
#  0 null (disconnected)
#  1 initialized ((connected +) loaded)
my $state = 0;

# message, error etc. keep it in one string for simplicity atm.
my $message = "";

# database-handle
my $dbHandle = undef;

# database-conf-file
my $dbConfig = "";

# database-settings
my $dbType = "";
my $dbName = "";
my $dbHost = "";
my $dbPort = 0;
my $dbUser = "";
my $dbPass = "";
my $dbDSN = "";

# flux-config-hash
my %fluxConf = undef;

# users + usernames
use vars qw( @users %names );

################################################################################
# constructor + destructor                                                     #
################################################################################

#------------------------------------------------------------------------------#
# Sub: new                                                                     #
# Arguments: null                                                              #
# Returns: object reference                                                    #
#------------------------------------------------------------------------------#
sub new {

	# class
	my $class = shift;

	# return
	my $self = bless {}, $class;
	return $self;
}

#------------------------------------------------------------------------------#
# Sub: destroy                                                                 #
# Arguments: null                                                              #
# Returns: null                                                                #
#------------------------------------------------------------------------------#
sub destroy {

	# set state
	$state = 0;

	# close connection
	dbDisconnect();

	# undef
	undef $dbHandle;
	undef %fluxConf;
	undef @users;
	undef %names;
}

################################################################################
# public methods                                                               #
################################################################################

#------------------------------------------------------------------------------#
# Sub: initialize. this is separated from constructor to call it independent   #
#      from object-creation.                                                   #
# Arguments: db-config-file (config.db.php)                                    #
# Returns: 0|1                                                                 #
#------------------------------------------------------------------------------#
sub initialize {

	shift; # class

	# db-config
	$dbConfig = shift;
	if (!(defined $dbConfig)) {
		# message
		$message = "db-config not defined";
		# set state
		$state = -1;
		# return
		return 0;
	}
	if (!(-f $dbConfig)) {
		# message
		$message = "no file";
		# set state
		$state = -1;
		# return
		return 0;
	}

	# load Database-Config
	if (loadDatabaseConfig($dbConfig) == 0) {
		# return
		return 0;
	}

	# connect
	if (dbConnect() == 0) {
		# close connection
		dbDisconnect();
		# return
		return 0;
	}

	# load config
	if (loadFluxConfig() == 0) {
		# close connection
		dbDisconnect();
		# return
		return 0;
	}

	# load users
	if (loadFluxUsers() == 0) {
		# close connection
		dbDisconnect();
		# return
		return 0;
	}

	# close connection
	dbDisconnect();

	# set state
	$state = 1;

	# return
	return 1;
}

#------------------------------------------------------------------------------#
# Sub: getVersion                                                              #
# Arguments: null                                                              #
# Returns: VERSION                                                             #
#------------------------------------------------------------------------------#
sub getVersion {
	return $VERSION;
}

#------------------------------------------------------------------------------#
# Sub: getState                                                                #
# Arguments: null                                                              #
# Returns: state                                                               #
#------------------------------------------------------------------------------#
sub getState {
	return $state;
}

#------------------------------------------------------------------------------#
# Sub: getMessage                                                              #
# Arguments: null                                                              #
# Returns: message                                                             #
#------------------------------------------------------------------------------#
sub getMessage {
	return $message;
}

#------------------------------------------------------------------------------#
# Sub: getDatabaseType                                                         #
# Arguments: null                                                              #
# Returns: Database-Type                                                       #
#------------------------------------------------------------------------------#
sub getDatabaseType {
	return $dbType;
}

#------------------------------------------------------------------------------#
# Sub: getDatabaseName                                                         #
# Arguments: null                                                              #
# Returns: Database-Name                                                       #
#------------------------------------------------------------------------------#
sub getDatabaseName {
	return $dbName;
}

#------------------------------------------------------------------------------#
# Sub: getDatabaseHost                                                         #
# Arguments: null                                                              #
# Returns: Database-Host                                                       #
#------------------------------------------------------------------------------#
sub getDatabaseHost {
	return $dbHost;
}

#------------------------------------------------------------------------------#
# Sub: getDatabasePort                                                         #
# Arguments: null                                                              #
# Returns: Database-Port                                                       #
#------------------------------------------------------------------------------#
sub getDatabasePort {
	return $dbPort;
}

#------------------------------------------------------------------------------#
# Sub: getDatabaseUser                                                         #
# Arguments: null                                                              #
# Returns: Database-User                                                       #
#------------------------------------------------------------------------------#
sub getDatabaseUser {
	return $dbUser;
}

#------------------------------------------------------------------------------#
# Sub: getDatabasePassword                                                     #
# Arguments: null                                                              #
# Returns: Database-Password                                                   #
#------------------------------------------------------------------------------#
sub getDatabasePassword {
	return $dbPass;
}

#------------------------------------------------------------------------------#
# Sub: getDatabaseDSN                                                          #
# Arguments: null                                                              #
# Returns: Database-DSN                                                        #
#------------------------------------------------------------------------------#
sub getDatabaseDSN {
	return $dbDSN;
}

#------------------------------------------------------------------------------#
# Sub: getFluxConfig                                                           #
# Arguments: key                                                               #
# Returns: conf-value                                                          #
#------------------------------------------------------------------------------#
sub getFluxConfig {
	shift; # class
	my $key = shift;
	return $fluxConf{$key};
}

#------------------------------------------------------------------------------#
# Sub: setFluxConfig                                                           #
# Arguments: key,value                                                         #
# Returns: null                                                                #
#------------------------------------------------------------------------------#
sub setFluxConfig {
	shift; # class
	my $key = shift;
	$fluxConf{$key} = shift;
}

#------------------------------------------------------------------------------#
# Sub: reload                                                                  #
# Arguments: null                                                              #
# Returns: 0|1                                                                 #
#------------------------------------------------------------------------------#
sub reload {

	# connect
	if (dbConnect() == 0) {
		# close connection
		dbDisconnect();
		# return
		return 0;
	}

	# load config
	if (loadFluxConfig() == 0) {
		# close connection
		dbDisconnect();
		# return
		return 0;
	}

	# load users
	if (loadFluxUsers() == 0) {
		# close connection
		dbDisconnect();
		# return
		return 0;
	}

	# close connection
	dbDisconnect();

	# return
	return 1;
}

################################################################################
# private methods                                                              #
################################################################################

#------------------------------------------------------------------------------#
# Sub: loadDatabaseConfig                                                      #
# Arguments: db-config-file (config.db.php)                                    #
# Returns: 0|1                                                                 #
#------------------------------------------------------------------------------#
sub loadDatabaseConfig {
	my $file = shift;
	open(CONFIG, $file) || return 0;
	undef $/;
	while (<CONFIG>) {
		if (/db_type.*[^\[]\"(.*)\"[^\]]/) {
			$dbType = $1;
		}
		if (/db_host.*[^\[]\"(.*)\"[^\]]/) {
			$dbHost = $1;
		}
		if (/db_name.*[^\[]\"(.*)\"[^\]]/) {
			$dbName = $1;
		}
		if (/db_user.*[^\[]\"(.*)\"[^\]]/) {
			$dbUser = $1;
		}
		if (/db_pass.*[^\[]\"(.*)\"[^\]]/) {
			$dbPass = $1;
		}
	}
	$/ = '\n';
	close(CONFIG);

	# build dsn
	$dbDSN = "DBI:";
	SWITCH: {
		$_ = $dbType;

		# MySQL
		/^mysql/i && do {
			$dbDSN .= "mysql:".$dbName.":".$dbHost;
			if ($dbPort > 0) {
				$dbDSN .= $dbPort;
			}
			last SWITCH;
		};

		# SQLite
		/^sqlite/i && do {
			$dbDSN .= "SQLite:dbname=".$dbHost;
			$dbUser = "";
			$dbPass = "";
			last SWITCH;
		};

		# no valid db-type. bail out
		# message
		$message = "no valid db-type : ".$dbType;
		# set state
		$state = -1;
		# return
		return 0;
	}

	return 1;
}

#------------------------------------------------------------------------------#
# Sub: dbConnect                                                               #
# Arguments: null                                                              #
# Returns: 0|1                                                                 #
#------------------------------------------------------------------------------#
sub dbConnect {

	# connect
	$dbHandle = DBI->connect(
		$dbDSN, $dbUser, $dbPass, { PrintError => 0, AutoCommit => 1 }
	);

	# check
	if (!(defined $dbHandle)) {
		# message
		$message = "error connecting to database :\n".$DBI::errstr;
		# set state
		$state = -1;
		# return
		return 0;
	}
	return 1;
}

#------------------------------------------------------------------------------#
# Sub: dbDisconnect                                                            #
# Arguments: null                                                              #
# Returns: null                                                                #
#------------------------------------------------------------------------------#
sub dbDisconnect {
	# disconnect
	if (defined $dbHandle) {
		$dbHandle->disconnect();
		undef $dbHandle;
	}
}


#------------------------------------------------------------------------------#
# Sub: loadFluxConfig                                                          #
# Arguments: null                                                              #
# Returns: 0|1                                                                 #
#------------------------------------------------------------------------------#
sub loadFluxConfig {
	if (defined $dbHandle) {
		# undef first
		undef %fluxConf;
		# load from db
		my $sth = $dbHandle->prepare(q{ SELECT tf_key, tf_value FROM tf_settings });
		$sth->execute();
		my ($tfKey, $tfValue);
		my $rv = $sth->bind_columns(undef, \$tfKey, \$tfValue);
		while ($sth->fetch()) {
			#print STDERR "fluxconf : ".$tfKey."=".$tfValue."\n"; # DEBUG
			$fluxConf{$tfKey} = $tfValue;
		}
		$sth->finish();
		# return
		return 1;
	} else {
		return 0;
	}
}

#------------------------------------------------------------------------------#
# Sub: saveFluxConfig                                                          #
# Arguments: null                                                              #
# Returns: 0|1                                                                 #
#------------------------------------------------------------------------------#
sub saveFluxConfig {
	return 1;
}

#------------------------------------------------------------------------------#
# Sub: loadFluxUsers                                                           #
# Arguments: null                                                              #
# Returns: 0|1                                                                 #
#------------------------------------------------------------------------------#
sub loadFluxUsers {
	if (defined $dbHandle) {
		# undef first
		undef @users;
		undef %names;
		# load from db
		my $sth = $dbHandle->prepare(q{ SELECT uid, user_id FROM tf_users });
		$sth->execute();
		my ($uid, $userid);
		my $rv = $sth->bind_columns(undef, \$uid, \$userid);
		my $index = 0;
		while ($sth->fetch()) {
			$users[$index] = {
				uid => $uid,
				username => $userid,
			};
			$names{$userid} = $index;
			$index++;
		}
		$sth->finish();
		# return
		return 1;
	} else {
		return 0;
	}
}


################################################################################
# make perl happy                                                              #
################################################################################
1;
