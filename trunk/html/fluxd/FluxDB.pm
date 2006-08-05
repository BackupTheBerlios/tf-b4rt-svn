################################################################################
# $Id$
# $Date$
# $Revision$
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
# do we use persistent connection ? (yes we do and var is not used (yet))
my $dbPersConn = 1;

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
		# message
		$message = "error loading database-config";
		# set state
		$state = -1;
		# return
		return 0;
	}

	# connect
	if (dbConnect() == 0) {
		# return
		return 0;
	}

	# load config
	if (loadFluxConfig() == 0) {
		# return
		return 0;
	}

	# load users
	if (loadFluxUsers() == 0) {
		# return
		return 0;
	}

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
# Sub: getDatabaseHandle                                                       #
# Arguments: null                                                              #
# Returns: database-handle                                                     #
#------------------------------------------------------------------------------#
sub getDatabaseHandle {
	return $dbHandle;
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
# Sub: loadFluxConfig                                                          #
# Arguments: null                                                              #
# Returns: 0|1                                                                 #
#------------------------------------------------------------------------------#
sub loadFluxConfig {
	# undef first
	undef %fluxConf;
	# load from db
	my $sth = $dbHandle->prepare(q{ SELECT tf_key, tf_value FROM tf_settings });
	$sth->execute();
	my ($tfKey, $tfValue);
	my $rv = $sth->bind_columns(undef, \$tfKey, \$tfValue);
	while ($sth->fetch()) {
		$fluxConf{$tfKey} = $tfValue;
	}
	$sth->finish();
	# return
	return 1;
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
		$names{$userid} = $uid;
		$index++;
	}
	$sth->finish();
	# return
	return 1;
}

#------------------------------------------------------------------------------#
# Sub: reload                                                                  #
# Arguments: null                                                              #
# Returns: 0|1                                                                 #
#------------------------------------------------------------------------------#
sub reload {

	# load config
	if (loadFluxConfig() == 0) {
		# return
		return 0;
	}

	# load users
	if (loadFluxUsers() == 0) {
		# return
		return 0;
	}

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
		if (/db_type.*[^\[]\"(\w+)\"[^\]]/) {
			$dbType = $1;
		}
		if (/db_host.*[^\[]\"(\w+)\"[^\]]/) {
			$dbHost = $1;
		}
		if (/db_name.*[^\[]\"(\w+)\"[^\]]/) {
			$dbName = $1;
		}
		if (/db_user.*[^\[]\"(\w+)\"[^\]]/) {
			$dbUser = $1;
		}
		if (/db_pass.*[^\[]\"(\w+)\"[^\]]/) {
			$dbPass = $1;
		}
	}
	$/ = '\n';
	return 1;
}

#------------------------------------------------------------------------------#
# Sub: dbConnect                                                               #
# Arguments: null                                                              #
# Returns: 0|1                                                                 #
#------------------------------------------------------------------------------#
sub dbConnect {
	# build dsn
	my $dsn = "DBI:".$dbType.":".$dbName.":".$dbHost;
	if ($dbPort > 0) {
		$dsn .= $dbPort;
	}
	# connect
	$dbHandle = DBI->connect(
		$dsn, $dbUser, $dbPass, { PrintError => 0, AutoCommit => 1 }
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


################################################################################
# make perl happy                                                              #
################################################################################
1;
