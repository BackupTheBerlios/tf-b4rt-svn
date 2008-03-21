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
import signal
import os
import time
from threading import activeCount
# fluxd-imports
from fluxd.Config import Config
from fluxd.activator.Activator import Activator
from fluxd.activator.activate import activate
from fluxd.daemon.functions import daemonIsRunning, daemonStop, daemonize
################################################################################

""" ------------------------------------------------------------------------ """
""" Daemon                                                                   """
""" ------------------------------------------------------------------------ """
class Daemon(object):

    """ -------------------------------------------------------------------- """
    """ __new__                                                              """
    """ -------------------------------------------------------------------- """
    def __new__(cls, *p, **k):
        if not '_the_instance' in cls.__dict__:
            cls._the_instance = object.__new__(cls, *p, **k)
        return cls._the_instance

    """ -------------------------------------------------------------------- """
    """ __init__                                                             """
    """ -------------------------------------------------------------------- """
    def __init__(self):

        # logger
        self.logger = None

        # pid-file
        self.__pidFile = Config().get('file', 'pid').strip()

        # running-flag
        self.__running = False

    """ -------------------------------------------------------------------- """
    """ start                                                                """
    """ -------------------------------------------------------------------- """
    def start(self):

        # check if already running
        if daemonIsRunning(self.__pidFile):
            raise Exception, 'already running'

        # main try
        try:

            # daemonize
            daemonize()

            # start
            self.__start()

            # main
            self.__main()

            # stop
            self.__stop()

            # return
            return 0

        # last catch
        except Exception, e:

            # error-message
            msg = "Fatal Error, shutting down (%s)" % (e)

            # log via logger
            if not self.logger is None:
                # log exception
                self.logger.error(msg)

            # write exception to (log)file if no logger available
            else:
                # prefix
                prefix = ''
                try:
                    prefix = "[%s] " % time.strftime(Config().get('logging', 'Dateformat'))
                except Exception, e:
                    prefix = "[%s] " % time.strftime('%Y/%m/%d - %H:%M:%S')
                # file
                file = ''
                try:
                    file = Config().get('file', 'log').strip()
                except Exception, e:
                    file = '/tmp/fluxd.log'
                # write
                try:
                    f = open(file, 'a')
                    f.write("%s%s\n" % (prefix, msg))
                    f.flush()
                    f.close()
                except Exception, e:
                    pass

            # stop
            self.__stop()

            # return
            return 1

    """ -------------------------------------------------------------------- """
    """ stop                                                                 """
    """ -------------------------------------------------------------------- """
    def stop(self):

        # stop if running
        if daemonIsRunning(self.__pidFile):
            daemonStop(self.__pidFile)
        else:
            raise Exception, 'not running'

    """ -------------------------------------------------------------------- """
    """ __shutdown                                                           """
    """ -------------------------------------------------------------------- """
    def __shutdown(self):

        # running-flag
        self.__running = False

    """ -------------------------------------------------------------------- """
    """ __start                                                              """
    """ -------------------------------------------------------------------- """
    def __start(self):

        # activate
        activate()

        # get logger
        self.logger = Activator().getInstance('LoggerFactory').getLogger('Daemon')

        # log
        self.logger.info('Starting...')

        # write pid-file
        self.__pidWrite()

        # set signal handler
        self.__setSignalHandler()

        # get Fluxcli-instance
        fluxcli = Activator().getInstance('Fluxcli')

        # get DatabaseManager-instance
        databaseManager = Activator().getInstance('DatabaseManager')

        # get ServerManager-instance
        serverManager = Activator().getInstance('ServerManager')

        # get ModuleManager-instance
        moduleManager = Activator().getInstance('ModuleManager')

        # get Dispatcher-instance
        dispatcher = Activator().getInstance('Dispatcher')

        # add onStop-delegate to dispatcher
        dispatcher.addOnStopDelegate(self.__shutdown)

        # Fluxcli test
        try:
            # invoke
            self.logger.info(fluxcli.invoke(['-v'], True).strip())
        except Exception, e:
            raise Exception, "Failed to test fluxcli (%s)" % (e)

        # DatabaseManager load
        try:
            # load
            databaseManager.load()
        except Exception, e:
            raise Exception, "DatabaseManager failed to load (%s)" % (e)

        # Dispatcher start
        try:
            # start
            dispatcher.start()
            # check if we got a running dispatcher-thread
            tries = 0
            triesMax = 20
            nap = 0.1
            isRunning = False
            while not isRunning and tries < triesMax:
                time.sleep(nap)
                tries += 1
                isRunning = dispatcher.isRunning()
            if not isRunning:
                raise Exception, "Dispatcher-Thread not running after %d seconds" % (triesMax * nap)
        except Exception, e:
            raise Exception, "Error when starting Dispatcher (%s)" % (e)

        # ServerManager start
        try:
            # start
            serverManager.startServer(dispatcher.requestHandler)
            # check if we got a running server
            tries = 0
            triesMax = 25
            nap = 0.2
            isRunning = False
            while not isRunning and tries < triesMax:
                time.sleep(nap)
                tries += 1
                isRunning = serverManager.isServerRunning()
            if not isRunning:
                raise Exception, "No Servers running after %d seconds" % (triesMax * nap)
        except Exception, e:
            raise Exception, "Error when starting ServerManager (%s)" % (e)

        # ModuleManager start
        try:
            # start
            moduleManager.startModule(dispatcher.requestHandler)
        except Exception, e:
            raise Exception, "Error when starting ModuleManager (%s)" % (e)

        # log
        self.logger.info('start complete (%d active threads)' % activeCount())

    """ -------------------------------------------------------------------- """
    """ __main                                                               """
    """ -------------------------------------------------------------------- """
    def __main(self):

        try:

            # get Dispatcher-instance
            dispatcher = Activator().getInstance('Dispatcher')

            # get ServerManager-instance
            serverManager = Activator().getInstance('ServerManager')

            # running-flag
            self.__running = True

            # daemon-guardian-loop
            while self.__running:

                # check if dispatcher-thread is running
                if not dispatcher.isRunning():
                    raise Exception, "Dispatcher-Thread not running"

                # check if we got a running server
                if not serverManager.isServerRunning():
                    raise Exception, "No Server running"

                # sleep
                time.sleep(2)

                # DEBUG
                #self.logger.debug('%d Threads active' % activeCount())

        except Exception, e:
            raise Exception, "Error in daemon (%s)" % (e)

    """ -------------------------------------------------------------------- """
    """ __stop                                                           """
    """ -------------------------------------------------------------------- """
    def __stop(self):

        # log shutdown
        self.logger.info('shutting down...')

        # get Dispatcher-instance
        dispatcher = Activator().getInstance('Dispatcher')

        # get ServerManager-instance
        serverManager = Activator().getInstance('ServerManager')

        # get ModuleManager-instance
        moduleManager = Activator().getInstance('ModuleManager')

        # stop ModuleManager if running
        try:
            if moduleManager.isModuleRunning():
                # stop
                moduleManager.stopModule()
                # check if we got a running module
                tries = 0
                triesMax = 25
                nap = 0.2
                isRunning = True
                while isRunning and tries < triesMax:
                    time.sleep(nap)
                    tries += 1
                    isRunning = moduleManager.isModuleRunning()
                if isRunning:
                    raise Exception, "Modules running after %d seconds" % (triesMax * nap)
        except Exception, e:
            self.logger.error("Error when stopping ModuleManager (%s)" % (e))

        # stop serverManager if running
        try:
            if serverManager.isServerRunning():
                # stop
                serverManager.stopServer()
                # check if we got a running server
                tries = 0
                triesMax = 25
                nap = 0.2
                isRunning = True
                while isRunning and tries < triesMax:
                    time.sleep(nap)
                    tries += 1
                    isRunning = serverManager.isServerRunning()
                if isRunning:
                    raise Exception, "Servers running after %d seconds" % (triesMax * nap)
        except Exception, e:
            self.logger.error("Error when stopping ServerManager (%s)" % (e))

        # stop dispatcher if running
        try:
            if dispatcher.isRunning():
                # stop
                dispatcher.stop()
                # check if we got a running dispatcher-thread
                tries = 0
                triesMax = 25
                nap = 0.2
                isRunning = True
                while isRunning and tries < triesMax:
                    time.sleep(nap)
                    tries += 1
                    isRunning = dispatcher.isRunning()
                if isRunning:
                    raise Exception, "Dispatcher-Thread still running after %d seconds" % (triesMax * nap)
        except Exception, e:
            self.logger.error("Error when stopping Dispatcher (%s)" % (e))

        # delete pid-file
        self.__pidDelete()

        # log shutdown
        self.logger.info('exit')

    """ -------------------------------------------------------------------- """
    """ __pidWrite                                                           """
    """ -------------------------------------------------------------------- """
    def __pidWrite(self):

        # get pid and write to file
        try:
            pid = (str(os.getpid())).strip()
            self.logger.info('writing pid-file %s (%s)' % (self.__pidFile, pid))
            f = open(self.__pidFile, 'w')
            f.write(pid + "\n")
            f.flush()
            f.close()
        except Exception, e:
            self.logger.error('Failed to write pid-file %s' % self.__pidFile)
            raise e

    """ -------------------------------------------------------------------- """
    """ __pidDelete                                                          """
    """ -------------------------------------------------------------------- """
    def __pidDelete(self):

        # delete pid-file if exists
        if os.path.isfile(self.__pidFile):
            try:
                self.logger.info("deleting pid-file %s ..." % self.__pidFile)
                os.remove(self.__pidFile)
            except Exception, e:
                self.logger.error("Failed to delete pid-file %s " % self.__pidFile)

    """ -------------------------------------------------------------------- """
    """ __setSignalHandler                                                   """
    """ -------------------------------------------------------------------- """
    def __setSignalHandler(self):
        # log
        self.logger.info("setting up signal-handler...")
        # set up handler
        try:
            signal.signal(signal.SIGHUP, self.__onSigHup)
            signal.signal(signal.SIGINT, self.__onSigInt)
            signal.signal(signal.SIGTERM, self.__onSigTerm)
            signal.signal(signal.SIGQUIT, self.__onSigQuit)
        except Exception, e:
            self.logger.error("error when setting up signal-handler (%s)" % e)

    """ -------------------------------------------------------------------- """
    """ __onSigHup                                                           """
    """ -------------------------------------------------------------------- """
    def __onSigHup(self, signum, frame):
        # log
        self.logger.info('got SIGHUP, initialize shutdown...')
        # shutdown
        self.__shutdown()

    """ -------------------------------------------------------------------- """
    """ __onSigInt                                                          """
    """ -------------------------------------------------------------------- """
    def __onSigInt(self, signum, frame):
        # log
        self.logger.info('got SIGINT, initialize shutdown...')
        # shutdown
        self.__shutdown()

    """ -------------------------------------------------------------------- """
    """ __onSigTerm                                                           """
    """ -------------------------------------------------------------------- """
    def __onSigTerm(self, signum, frame):
        # log
        self.logger.info('got SIGTERM, initialize shutdown...')
        # shutdown
        self.__shutdown()

    """ -------------------------------------------------------------------- """
    """ __onSigQuit                                                          """
    """ -------------------------------------------------------------------- """
    def __onSigQuit(self, signum, frame):
        # log
        self.logger.info('got SIGQUIT, initialize shutdown...')
        # shutdown
        self.__shutdown()
