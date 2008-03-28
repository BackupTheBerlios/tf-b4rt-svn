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
# standard-imports
# fluxd-imports
from fluxd.Config import Config
from fluxd.interfaces.IDataAdapter import IDataAdapter
from fluxd.activator.Activator import Activator
from fluxd.functions.tfbconfig import getDatabaseConfig
# ADOdb
import adodb
################################################################################

""" ------------------------------------------------------------------------ """
""" DataAdapterADOdb                                                         """
""" ------------------------------------------------------------------------ """
class DataAdapterADOdb(IDataAdapter):

    """ -------------------------------------------------------------------- """
    """ __init__                                                             """
    """ -------------------------------------------------------------------- """
    def __init__(self):

        # logger
        self.logger = Activator().getInstance('LoggerFactory').getLogger('DataAdapterADOdb')
        
        # db-config
        self.dbConfig = None
        
        # connection
        self.connection = None

    """ -------------------------------------------------------------------- """
    """ loadSettings                                                         """
    """ -------------------------------------------------------------------- """
    def loadSettings(self):
    
        # info
        self.logger.info('loading settings...')

        # try/finally (separate this for python < 2.5)
        try:

            # try
            try:

                # open connection
                self.openConnection()

                # execute select and get cursor
                cursor = self.connection.Execute('SELECT tf_key, tf_value FROM tf_settings')

                # retval
                retVal = {}
                
                # process rows
                while not cursor.EOF:
                
                    # get row-dict
                    rowDict = cursor.GetRowAssoc(0)
                    
                    # add setting
                    retVal[rowDict['tf_key']] = rowDict['tf_value']
                    
                    # next
                    cursor.MoveNext()

                # close cursor
                cursor.Close()

                # check
                if len(retVal) < 1:
                    raise Exception, 'settings-validation failed'

                # return
                return retVal

            # catch, log and rethrow
            except Exception, e:
                self.logger.error('failed to load Settings (%s)' % (e))
                raise e

        # finally close the con
        finally:
            self.closeConnection()

    """ -------------------------------------------------------------------- """
    """ saveSettings                                                         """
    """ -------------------------------------------------------------------- """
    def saveSettings(self, settings = {}):
    
        # info
        self.logger.info('saving settings...')

        # try/finally (separate this for python < 2.5)
        try:

            # try
            try:

                # open connection
                self.openConnection()
                
                # update settings
                try:
                
                    # begin transaction
                    self.connection.BeginTrans()
                
                    # update
                    for key, val in settings.iteritems():
                    
                        # sql
                        sql = 'UPDATE tf_settings SET tf_value = %s WHERE tf_key = %s' % \
                            (self.connection.qstr(val), self.connection.qstr(key))

                        # DEBUG
                        #self.logger.debug(sql)

                        # execute update and get cursor
                        cursor = self.connection.Execute(sql)

                        # close cursor
                        cursor.Close()

                    # commit transaction
                    self.connection.CommitTrans()

                # catch, rollback and rethrow
                except Exception, e:
                    self.connection.RollbackTrans()
                    raise e

            # catch, log and rethrow
            except Exception, e:
                self.logger.error('failed to save Settings (%s)' % (e))
                raise e

        # finally close the con
        finally:
            self.closeConnection()

    """ -------------------------------------------------------------------- """
    """ openConnection                                                       """
    """ -------------------------------------------------------------------- """
    def openConnection(self):
    
        # debug
        self.logger.debug('open connection...')
    
        # get config
        try:
            self.dbConfig = getDatabaseConfig()
        # catch, log and rethrow
        except Exception, e:
            self.logger.error('failed to get database-config (%s)' % (e))
            raise e
 
        # mysql
        if self.dbConfig['db_type'].lower().startswith('mysql'):
        
            # get ado-connection
            try:
                self.connection = adodb.NewADOConnection('mysql')
                if self.connection is None: raise Exception, 'connection is None'
            # catch, log and rethrow
            except Exception, e:
                self.logger.error('failed to get ADOConnection (%s)' % (e))
                raise e

            # connect
            try:
                 self.connection.Connect(self.dbConfig['db_host'], self.dbConfig['db_user'], self.dbConfig['db_pass'], self.dbConfig['db_name'])
            # catch, log and rethrow
            except Exception, e:
                self.logger.error('failed to connect to database (%s)' % (e))
                raise e

        # postgres
        elif self.dbConfig['db_type'].lower().startswith('postgres'):
        
            # get ado-connection
            try:
                self.connection = adodb.NewADOConnection('postgres')
                if self.connection is None: raise Exception, 'connection is None'
            # catch, log and rethrow
            except Exception, e:
                self.logger.error('failed to get ADOConnection (%s)' % (e))
                raise e

            # connect
            try:
                 self.connection.Connect(self.dbConfig['db_host'], self.dbConfig['db_user'], self.dbConfig['db_pass'], self.dbConfig['db_name'])
            # catch, log and rethrow
            except Exception, e:
                self.logger.error('failed to connect to database (%s)' % (e))
                raise e
 
        # sqlite
        elif self.dbConfig['db_type'].lower().startswith('sqlite'):
        
            # get ado-connection
            try:
                self.connection = adodb.NewADOConnection('sqlite')
                if self.connection is None: raise Exception, 'connection is None'
            # catch, log and rethrow
            except Exception, e:
                self.logger.error('failed to get ADOConnection (%s)' % (e))
                raise e

            # connect
            try:
                 self.connection.Connect(database = self.dbConfig['db_host'])
            # catch, log and rethrow
            except Exception, e:
                self.logger.error('failed to connect to database (%s)' % (e))
                raise e
 
        # unknown
        else:
            raise Exception, 'Unsupported Database-Type: %s' % self.dbConfig['db_type']

    """ -------------------------------------------------------------------- """
    """ closeConnection                                                      """
    """ -------------------------------------------------------------------- """
    def closeConnection(self):

        # debug
        self.logger.debug('close connection...')

        # try
        try:

            # close the con
            if self.connection is not None and self.connection.IsConnected():
                self.connection.Close()
            
        # catch and log
        except Exception, e:
            self.logger.error('exception when closing the connection (%s)' % (e))

        # none connection and config
        self.connection = None
        self.dbConfig = None
