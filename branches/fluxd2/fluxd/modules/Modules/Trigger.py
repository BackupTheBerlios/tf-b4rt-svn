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
import os
import glob
import re
import time
from threading import Lock
# fluxd-imports
from fluxd.Config import Config
from fluxd.activator.Activator import Activator
from fluxd.modules.Modules.BasicModule import BasicModule
from fluxd.decorators.synchronized import synchronized
from fluxd.classes.StatFile import StatFile
from fluxd.functions.string import parseInt, parseLong, parseFloat
from fluxd.functions.psutils import bgShellCmd
################################################################################

__version__ = (0, 0, 1)
__version_str__ = '%s.%s' % (__version__[0], ''.join([str(part) for part in __version__[1:]]))

""" ------------------------------------------------------------------------ """
""" Trigger                                                                  """
""" ------------------------------------------------------------------------ """
class Trigger(BasicModule):

    # events
    Events = [
        'OnDownloadCompleted',
    ]

    # params
    ParamPrefix = 'TFB_'
    Param_CURDATE  = ParamPrefix + 'CURDATE'    # _Current_ date (not real event date) formatted in fluxd logging format.
    Param_DOCROOT  = ParamPrefix + 'DOCROOT'    # Document root path (dir.docroot).
    Param_FLUXCLI  = ParamPrefix + 'FLUXCLI'    # fluxcli script path.
    Param_FLUXD    = ParamPrefix + 'FLUXD'      # fluxd path (dir.pathFluxd).
    Param_EVENT    = ParamPrefix + 'EVENT'      # Event name.
    Param_OWNER    = ParamPrefix + 'OWNER'      # Transfer owner.
    Param_PATH     = ParamPrefix + 'PATH'       # Transfer parent path (dir.pathTf).
    Param_PHP      = ParamPrefix + 'PHP'        # php-cli path (the one to use for fluxcli invocations).
    Param_TRANSFER = ParamPrefix + 'TRANSFER'   # Name of transfer.

    # path
    TransfersPath = '.transfers/'

    # lock
    InstanceLock = Lock()

    """ -------------------------------------------------------------------- """
    """ __init__                                                             """
    """ -------------------------------------------------------------------- """
    def __init__(self, name, *p, **k):

        # base
        BasicModule.__init__(self, name, *p, **k)

        # transfers path
        self.transfersPath = Config().get('dir', 'pathTf').strip() + Trigger.TransfersPath

        # interval
        self.interval = int(Config().getExt(name, 'interval').strip())

        # commands
        self.commands = dict(
            [(event, Config().get(name, 'cmd-' + event).strip()) for event in Trigger.Events]
        )

        # invocation-count
        self.runCount = 0

    """ -------------------------------------------------------------------- """
    """ status                                                               """
    """ -------------------------------------------------------------------- """
    def status(self):
        data = {}
        data['version'] = __version_str__
        data['interval'] = str(self.interval)
        data['runCount'] = str(self.runCount)
        if self._transfers is not None:
            data['transfers'] = len(self._transfers)
        else:
            data['transfers'] = 0
        return data

    """ -------------------------------------------------------------------- """
    """ command                                                              """
    """ -------------------------------------------------------------------- """
    @synchronized(InstanceLock)
    def command(self, cmd):

        # log
        self.logger.debug('command: %s' % cmd)

        # stop
        if cmd == 'stop':
            if self.running:
                self.running = False
                return 'initialize Module-shutdown...'
            else:
                return 'Module not running'

        # invoke
        elif cmd == 'invoke':
            return self.invoke()

        # return
        return cmd

    """ -------------------------------------------------------------------- """
    """ getVersion                                                           """
    """ -------------------------------------------------------------------- """
    def getVersion(self):
        return __version_str__

    """ -------------------------------------------------------------------- """
    """ onStart                                                              """
    """ -------------------------------------------------------------------- """
    def onStart(self):

        # config
        self.logger.info('interval: %d' % self.interval)

        # initialize
        self.logger.info('initializing transfers state...')
        self._transfers = self._takeSnapshot()
        self.logger.info('...done (tracking %d transfers)' % len(self._transfers))

    """ -------------------------------------------------------------------- """
    """ main                                                                 """
    """ -------------------------------------------------------------------- """
    def main(self):

        # main-loop
        ticks = 0
        while self.running:

            try:

                # invoke if interval reached
                if ticks >= self.interval:
                    # invoke
                    self.invoke()
                    # reset tick-count
                    ticks = 0

                # sleep
                time.sleep(1)

                # increment tick-count
                ticks += 1

            except Exception, e:
                if self.running:
                    self.logger.error("Exception in Module-Thread (%s)" % (e))

    """ -------------------------------------------------------------------- """
    """ onStop                                                               """
    """ -------------------------------------------------------------------- """
    def onStop(self):

        # cleanup
        del self._transfers

        # DEBUG
        self.logger.debug('onStop')

    """ -------------------------------------------------------------------- """
    """ invoke                                                               """
    """ -------------------------------------------------------------------- """
    def invoke(self):

        # invocation-count
        self.runCount += 1

        try:

            # log
            self.logger.debug('scanning transfers...')

            # get snapshot of transfers stat files
            transfers = self._takeSnapshot()

            try:
                # scan all transfers to detect changes
                self._detectChanges(self._transfers, transfers)
            finally:
                # save current state for next invocation
                if transfers is not None:
                    self._transfers = transfers

            # return
            return True

        except Exception, e:
            self.logger.error("Error when calling trigger (%s)" % (e))

    """ -------------------------------------------------------------------- """
    """ _takeSnapshot                                                        """
    """ -------------------------------------------------------------------- """
    def _takeSnapshot(self):

        # glob silences access errors -- but loss of access to transfers path needs to
        # be flagged explicitely in order to avoid event storms when it comes back.
        if not os.access(self.transfersPath, os.R_OK | os.X_OK):
            self.logger.warning("Cannot access transfers path: %s" % self.transfersPath)
            return None

        ret = {}

        # For every .stat file in transfers path,
        for statfile in glob.glob(self.transfersPath + '*.stat'):

            # extract transfer name,
            transfername = os.path.splitext(os.path.basename(statfile))[0]

            # and load stat file.
            try:
                ret[transfername] = StatFile(statfile)
            except Exception, e:
                self.logger.warning("Error when loading transfer state (%s)" % e)
                ret[transfername] = False

        return ret

    """ -------------------------------------------------------------------- """
    """ _detectChanges                                                       """
    """ -------------------------------------------------------------------- """
    def _detectChanges(self, old, new):

        # On error, don't do anything.
        if old is None or new is None: return

        # For every current transfer,
        for transfername, newstatfile in new.iteritems():

            # try and get its old state,
            if transfername in old:
                oldstatfile = old[transfername]
            else:
                oldstatfile = None

            # and detect changes for this transfer.
            try:
                self._detectChangesOne(transfername, oldstatfile, newstatfile)
            except Exception, e:
                self.logger.error("Error checking transfer %s (%s)" % (transfername, e))

    """ -------------------------------------------------------------------- """
    """ _detectChangesOne                                                    """
    """ -------------------------------------------------------------------- """
    def _detectChangesOne(self, name, old, new):

        # On error, don't do anything.
        if old == False or new == False: return

        # Extract running, percent_done and downtotal values.
        if old is not None:
            old_running      = parseInt(old.running, -1)
            old_percent_done = parseFloat(old.percent_done)
        else:
            old_running      = -1
            old_percent_done = 0.
        new_running      = parseInt(new.running, -1)
        new_percent_done = parseFloat(new.percent_done)
        new_downtotal    = parseLong(new.downtotal)

        # OnDownloadCompleted:
        #   * transition of (running, percent_done)
        #       from (*, <100) to ([01], 100)
        #   * with downtotal > 0 (to not interpret a checking->seeding
        #       transition as a download completion)
        if old_percent_done < 100. and \
           new_running in (0, 1) and new_percent_done == 100. and \
           new_downtotal > 0L:
            self._fireEvent('OnDownloadCompleted', name, old, new)

    """ -------------------------------------------------------------------- """
    """ _fireEvent                                                           """
    """ -------------------------------------------------------------------- """
    def _fireEvent(self, event, name, old, new):

        # Get command.
        if event not in self.commands:
            self.logger.error('Unknown event: %s' % event)
            return
        command = self.commands[event]
        if not command:
            # Event is not bound, nothing to do.
            return

        # Log.
        self.logger.info("%s: %s" % (event, name))

        # And fire event.
        try:
            self._fireEventCore(event, name, old, new, command)
        except Exception, e:
            self.logger.warning("Error running %s command for transfer %s (%s)" % (event, name, e))

    """ -------------------------------------------------------------------- """
    """ _fireEventCore                                                       """
    """ -------------------------------------------------------------------- """
    def _fireEventCore(self, event, name, old, new, command):

        # Store transfer parent path, will be command's cwd for convenience.
        pathTf = Config().get('dir', 'pathTf').strip()

        # Prepare parameters.
        params = {}
        params[Trigger.Param_CURDATE]  = time.strftime(Config().get('logging', 'Dateformat'))
        params[Trigger.Param_DOCROOT]  = Config().get('dir', 'docroot').strip()
        params[Trigger.Param_EVENT]    = event
        params[Trigger.Param_FLUXCLI]  = Activator().getInstance('Fluxcli').getPath()
        params[Trigger.Param_FLUXD]    = Config().get('dir', 'pathFluxd').strip()
        params[Trigger.Param_OWNER]    = new.transferowner.strip()
        params[Trigger.Param_PATH]     = pathTf
        params[Trigger.Param_PHP]      = Config().get('file', 'php').strip()
        params[Trigger.Param_TRANSFER] = name

        # Prepare command (replace params).
        command = self._prepareCommand(command, params)

        # Prepare environment (clean up and add params).
        env = dict([(k, v) for k, v in os.environ.iteritems() if not k.startswith(Trigger.ParamPrefix)])
        env.update(params)

        # Run command.
        bgShellCmd(self.logger, self.name + ':' + event, command, pathTf, env)

    """ -------------------------------------------------------------------- """
    """ _prepareCommand                                                      """
    """ -------------------------------------------------------------------- """
    def _prepareCommand(self, command, params):

        # Go from beginning to end, stopping at each '%', and test
        # if it is a %PARAMNAME% construct, PARAMNAME being valid.
        # If yes replace it, otherwise leave it alone.

        # Find beginning of first param.
        start = command.find('%')
        while start != -1:

            # Find end of param.
            start += 1
            end = command.find('%', start)
            if end == -1: break

            # Extract param name.
            name = Trigger.ParamPrefix + command[start:end]

            if name in params:
                # Valid param, replace it by shell-escaped version of param's
                # value (re.escape does more than needed, but it does the job).
                value = re.escape(params[name])
                start -= 1
                command = command[:start] + value + command[end+1:]
                # Find beginning of next param.
                start = command.find('%', start + len(value))

            else:
                # Not a valid param, skip it.
                start = end

        return command
