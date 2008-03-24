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
import time
from threading import activeCount, enumerate
# fluxd-imports
from fluxd.Config import Config
from fluxd.activator.Activator import Activator
from fluxd.classes.Request import Request
from fluxd.classes.Result import Result
################################################################################

""" ------------------------------------------------------------------------ """
""" RequestHandler                                                           """
""" ------------------------------------------------------------------------ """
class RequestHandler(object):

    # delims
    DELIM_REQUESTARGS = ' '
    DELIM_MOD = ';'
    DELIM_MODSTATE = ':'
    DELIM_MODCOMMAND = ':'

    """ -------------------------------------------------------------------- """
    """ __init__                                                             """
    """ -------------------------------------------------------------------- """
    def __init__(self, logger):

        # set logger
        self.logger = logger

        # request-map
        self.__requestMap = {
            'modlist': self.modlist,
            'modstate': self.modstate,
            'modstart': self.modstart,
            'modstop': self.modstop,
            'status': self.status,
            'set': self.set,
            'reloadDBCache': self.reloadDBCache,
            'reloadModules': self.reloadModules,
            'check': self.check
        }

    """ -------------------------------------------------------------------- """
    """ handleRequest                                                        """
    """ -------------------------------------------------------------------- """
    def handleRequest(self, request):

        # debug-log
        self.logger.debug("handle request: %s (%s)" % (request.data, request.sender))

        # running-flag
        running = True

        # result
        result = None

        try:

            # get args
            args = request.data.split(RequestHandler.DELIM_REQUESTARGS)

            # check if arg present
            if len(args) < 1:
                result = Result(request.data, Exception('no args'))

            else:

                # get op
                op = args[0].strip()

                # modcommand
                if len(op) > 1 and op[0] == '!':
                    try:
                        # result
                        result = self.modcommand(request.data[1:])
                    except Exception, e:
                        # result
                        result = Result('modcommand failed', e)

                # standard-call
                elif self.__requestMap.has_key(op):
                    callargs = []
                    if len(args) > 1:
                        callargs = args[1:]
                    try:
                        # result
                        result = self.__requestMap[op](callargs)
                    except Exception, e:
                        # result
                        result = Result('failed to process request', e)

                # die
                elif op == 'die':
                    # running-flag
                    running = False
                    # result
                    result = Result('initialize shutdown...', None)

                # unknown
                else:
                    # result
                    result = Result(request.data, Exception('unknown op'))

        except Exception, e:
            # log
            self.logger.error('error when processing request' % e)
            # result
            result = Result('failed to process request', e)

        # call callback
        try:
            # log
            self.logger.debug("done with request: %s (%s)" % (request.data, request.sender))
            # callback
            if request.callback is not None and result is not None:
                request.callback(result)
        except Exception, e:
            # log
            self.logger.error('error when calling request-callback' % e)

        # return
        return running

    """ -------------------------------------------------------------------- """
    """ modcommand                                                           """
    """ -------------------------------------------------------------------- """
    def modcommand(self, arg):

        # get args
        args = arg.split(RequestHandler.DELIM_MODCOMMAND)

        # check arg-count
        if len(args) != 2:
            return Result(arg, Exception('invalid arg-count for modcommand'))

        # get vars from args
        moduleName = args[0]
        moduleCommand = args[1]

        # get ModuleManager-instance
        moduleManager = Activator().getInstance('ModuleManager')

        # call command
        data = moduleManager.moduleCommand(moduleName, moduleCommand)

        # if data is none, return none
        if data is None:
            return None
 
        # return result
        return Result(data, None)

    """ -------------------------------------------------------------------- """
    """ modlist                                                              """
    """ -------------------------------------------------------------------- """
    def modlist(self, args):

        # get ModuleManager-instance
        moduleManager = Activator().getInstance('ModuleManager')

        # get module-list
        modules = Config().get('modules', 'Modules').strip().split(',')

        # build list
        data = ''
        for module in modules:
            module = module.strip()
            mstate = '0'
            if moduleManager.isModuleRunning(module):
                mstate = '1'
            data += '%s%s%s%s' % (RequestHandler.DELIM_MOD, module, RequestHandler.DELIM_MODSTATE, mstate)
        if len(data) > 1:
            data = data[1:]

        # return result
        return Result(data, None)

    """ -------------------------------------------------------------------- """
    """ modstate                                                             """
    """ -------------------------------------------------------------------- """
    def modstate(self, args):

        # mod-name
        module = args[0].strip()

        # get ModuleManager-instance
        moduleManager = Activator().getInstance('ModuleManager')

        mstate = '0'
        if moduleManager.isModuleRunning(module):
            mstate = '1'

        # return result
        return Result(mstate, None)

    """ -------------------------------------------------------------------- """
    """ modstart                                                             """
    """ -------------------------------------------------------------------- """
    def modstart(self, args):

        # mod-name
        module = args[0].strip()

        # get ModuleManager-instance
        moduleManager = Activator().getInstance('ModuleManager')

        # check if running
        if moduleManager.isModuleRunning(module):
            return Result('Module already running: %s' % module, None)

        # get Dispatcher-instance
        dispatcher = Activator().getInstance('Dispatcher') 

        # start it
        try:
            moduleManager.startModule(dispatcher.requestHandler, module)
        except Exception, e:
            # return result
            return Result('Error when starting Module: %s' % module, e)

        # return result
        return Result('Module started: %s' % module, None)

    """ -------------------------------------------------------------------- """
    """ modstop                                                             """
    """ -------------------------------------------------------------------- """
    def modstop(self, args):

        # mod-name
        module = args[0].strip()

        # get ModuleManager-instance
        moduleManager = Activator().getInstance('ModuleManager')

        # check if running
        if not moduleManager.isModuleRunning(module):
            return Result('Module not running: %s' % module, None)

        # stop it
        try:
            moduleManager.stopModule(module)
        except Exception, e:
            # return result
            return Result('Error when stopping Module: %s' % module, e)

        # return result
        return Result('Module stopped: %s' % module, None)

    """ -------------------------------------------------------------------- """
    """ set                                                                  """
    """ -------------------------------------------------------------------- """
    def set(self, args):

        # check args
        if len(args) == 3:

            # get vars from args
            section = args[0]
            option = args[1]
            value = args[2]

            # set config
            Config().set(section, option, value)

            # return result
            return Result('set %s.%s to %s' % (section, option, value), None)

        # wrong args
        else:

            # return result
            return Result('set: wrong args', Exception('set: wrong args'))

    """ -------------------------------------------------------------------- """
    """ status                                                               """
    """ -------------------------------------------------------------------- """
    def status(self, args):

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

        # get server-list
        servers = serverManager.runningServers()

        # get module-list
        modules = moduleManager.runningModules()

        # data
        data = ''

        # status
        data += '----------------------------------------------------------------\n'
        data += ' Status\n'
        data += '----------------------------------------------------------------\n'

        # thread-count
        data += '%d Threads active\n' % activeCount()

        # server-count
        data += '%d Servers running\n' % len(servers)

        # module-count
        data += '%d Modules running\n' % len(modules)

        # dispatcher request-count
        data += '%d Dispatcher-Requests\n' % dispatcher.requestCount

        # fluxcli invocations
        data += '%d Fluxcli invocations\n' % fluxcli.invocationCount

        # database-settings
        data += '%d Database-Settings loaded\n' % databaseManager.getSettingsCount()

        # threads
        data += '\n----------------------------------------------------------------\n'
        data += ' Threads\n'
        data += '----------------------------------------------------------------\n'
        for thr in enumerate():
            data += '%s (isDaemon: %s)\n' % (thr.getName(), str(thr.isDaemon()))

        # servers
        data += '\n----------------------------------------------------------------\n'
        data += ' Servers\n'
        data += '----------------------------------------------------------------\n'
        for server in servers:
            data += '\n[%s]\n' % server
            serverStatus = serverManager.serverStatus(server)
            if serverStatus is not None:
                for key, val in serverStatus.iteritems():
                    data += '%s: %s\n' % (key, val)

        # modules
        data += '\n----------------------------------------------------------------\n'
        data += ' Modules\n'
        data += '----------------------------------------------------------------\n'
        for module in modules:
            data += '\n[%s]\n' % module
            moduleStatus = moduleManager.moduleStatus(module)
            if moduleStatus is not None:
                for key, val in moduleStatus.iteritems():
                    data += '%s: %s\n' % (key, val)

        # config
        data += '\n----------------------------------------------------------------\n'
        data += ' Config\n'
        data += '----------------------------------------------------------------\n'
        data += Config().currentConfigAsIniString()

        # return result
        return Result(data, None)

    """ -------------------------------------------------------------------- """
    """ reloadDBCache                                                        """
    """ -------------------------------------------------------------------- """
    def reloadDBCache(self, args):

        # get DatabaseManager-instance
        databaseManager = Activator().getInstance('DatabaseManager')

        # database-load
        databaseManager.load()

        # get ModuleManager-instance
        moduleManager = Activator().getInstance('ModuleManager')

        # Signal the loaded modules to come and check if they need to update
        # themselves
        modmsg = ''
        for module in moduleManager.runningModules():
            data = moduleManager.moduleCommand(module, 'reloadConfig')
            modmsg += '%s: %s\n' % (module, data)

        # return result
        return Result('Database-Config reloaded (%s)\n%s' % (str(databaseManager.getSettingsCount()), modmsg), None)

    """ -------------------------------------------------------------------- """
    """ reloadModules                                                        """
    """ -------------------------------------------------------------------- """
    def reloadModules(self, args):

        # get ModuleManager-instance
        moduleManager = Activator().getInstance('ModuleManager')

        # get Dispatcher-instance
        dispatcher = Activator().getInstance('Dispatcher')

        # stop ModuleManager if running
        try:
            if moduleManager.isModuleRunning():
                # stop
                moduleManager.stopModule()
                # check if we got a running module
                tries = 0
                triesMax = 75
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
            # return result
            return Result('Error when stopping ModuleManager', e)

        # ModuleManager start
        try:
            if moduleManager.isModuleRunning():
                raise Exception, "Modules still running, skipping start"
            # start
            moduleManager.startModule(dispatcher.requestHandler)
        except Exception, e:
            self.logger.error("Error when starting ModuleManager (%s)" % (e))
            # return result
            return Result('Error when starting ModuleManager', e)

        # return result
        return Result('Modules reloaded', None)

    """ -------------------------------------------------------------------- """
    """ check                                                                """
    """ -------------------------------------------------------------------- """
    def check(self, args):

        # return result
        return Result('check', Exception('Not implemented'))
