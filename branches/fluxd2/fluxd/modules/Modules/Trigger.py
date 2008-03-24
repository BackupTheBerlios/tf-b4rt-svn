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
        'OnDownloadStarted',    # For all transfer types. started by e.g. Qmgr
        'OnDownloadStopped',    # For all transfer types. stopped by sharekill or download completed for nzb
        'OnSeedingStarted'     # For torrents only. download is complete, but we're still uploading
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
    Param_TRANSFER = ParamPrefix + 'TRANSFER'   # Transfer name.
    Param_TYPE     = ParamPrefix + 'TYPE'       # Transfer type (torrent, wget, nzb, unknown).

    # path
    TransfersPath = '.transfers/'

    # lock
    InstanceLock = Lock()

    # delim
    DELIM = '/'

    """ -------------------------------------------------------------------- """
    """ __init__                                                             """
    """ -------------------------------------------------------------------- """
    def __init__(self, name, *p, **k):

        # base
        BasicModule.__init__(self, name, *p, **k)

        # jobs hash
        self.jobs = {}

        # transfers path
        self.transfersPath = Config().get('dir', 'pathTf').strip() + Trigger.TransfersPath

        # interval
        self.interval = int(Config().getExt(name, 'interval').strip())

        # jobs File path
        self.pathTrigger = Config().get('dir', 'pathFluxd').strip() + 'trigger/'
        self.fileTrigger = self.pathTrigger + 'trigger.jobs'

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

        elif cmd == 'reloadConfig':
            self.interval = int(Config().getExt('Trigger', 'interval').strip())

            # message
            msg = 'Config reloaded (%d)' % \
            ( \
                self.interval \
            )

            # info
            self.logger.info(msg)

            # return
            return msg

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

        # main-path
        if not os.path.isdir(self.pathTrigger):
            try:
                self.logger.info("main-path %s does not exist, trying to create ..." % self.pathTrigger)
                os.mkdir(self.pathTrigger, 0700)
                self.logger.info("done.")
            except:
                self.logger.error("Failed to create main-path %s" % self.pathTrigger)
                return False

        # initialize
        self.logger.info('initializing transfers state...')
        self._transfers = self._takeSnapshot()
        self.logger.info('...done (tracking %d transfers)' % len(self._transfers))

        # load up saved jobs
        self._loadJobs()

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
            name = os.path.splitext(os.path.basename(statfile))[0]

            # and load stat file.
            try:
                ret[name] = StatFile(statfile)
            except Exception, e:
                self.logger.warning("Error loading state for transfer %s (%s)" % (name, e))
                ret[name] = False

        return ret

    """ -------------------------------------------------------------------- """
    """ _detectChanges                                                       """
    """ -------------------------------------------------------------------- """
    def _detectChanges(self, old, new):

        # On error, don't do anything.
        if old is None or new is None: return

        # For every current transfer,
        for name, newstatfile in new.iteritems():

            # try and get its old state,
            if name in old:
                oldstatfile = old[name]
            else:
                oldstatfile = None

            # and detect changes for this transfer.
            try:
                self._detectChangesOne(name, oldstatfile, newstatfile)
            except Exception, e:
                self.logger.error("Error checking transfer %s (%s)" % (name, e))

    """ -------------------------------------------------------------------- """
    """ _detectChangesOne                                                    """
    """ -------------------------------------------------------------------- """
    def _detectChangesOne(self, name, old, new):

        # On error, don't do anything.
        if old == False or new == False: return

        # Extract type from transfer name.
        type = os.path.splitext(name)[1][1:]
        if type not in ('torrent', 'wget', 'nzb'):
            type = 'unknown'

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

        # transfer started:
        #   * transition of running
        #  from !1 to 1
        if old_running != 1 and new_running == 1:
            if 'transferStarted' in self.jobs[name].keys():
                self._fireEvent('transferStarted', name)

        # transfer stopped:
        #    * transition of running
        # from 1 to !1
        if old_running == 1 and new_running != 1:
            if 'transferStopped' in self.jobs[name].keys():
                self._fireEvent('transferStopped', name)

        # transfer Completed:
        #   * transition of (running, percent_done)
        #       from (*, <100) to ([01], 100)
        #   * with downtotal > 0 (to not interpret a checking->seeding
        #       transition as a download completion)
        if old_percent_done < 100. and \
            new_running in (0, 1) and new_percent_done == 100. and \
            new_downtotal > 0L:
            if 'transferCompleted' in self.jobs[name].keys():
                self._fireEvent('transferCompleted', name)

        # transfer Seeding:
        #   * torrents only
        #   * transition of (running, percent_done)
        #       from (1, *) to (1, 100)
        if type == 'torrent' and \
            old_running == 1 and \
            new_running == 1 and new_percent_done == 100.:
            if 'transferSeeding' in self.jobs[name].keys():
                self._fireEvent('transferSeeding', name)

    """ -------------------------------------------------------------------- """
    """ _fireEvent                                                           """
    """ -------------------------------------------------------------------- """
    def _fireEvent(self, event, name):
        """call each action for this event."""

        for action in self.jobs[name][event]:
        
            # Log.
            self.logger.info("%s: %s (%s)" % (event, name, action))

            # And fire event.
            try:
                self._fireEventCore(event, name, action)
            except Exception, e:
                self.logger.warning("Error running %s event for transfer %s (%s)" % (event, name, e))

    """ -------------------------------------------------------------------- """
    """ _fireEventCore                                                       """
    """ -------------------------------------------------------------------- """
    def _fireEventCore(self, event, name, action):
        """actually call the event."""

        if action.startswith('execute'):
            script = action.split(':')[1]

            # pass stuff to the environment
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
            params[Trigger.Param_TYPE]     = type  

            # Prepare environment (clean up and add params).
            env = dict([(k, v) for k, v in os.environ.iteritems() if not k.startswith(Trigger.ParamPrefix)])
            env.update(params)

            bgShellCmd(self.logger, self.name + ':' + event, script, pathTf, env)

        elif action == 'email':
            # TODO: determine email capabilities. I'd like to have an email
            # address stored in the user's profile, so we could email them
            # there, but I'd also like to be able to fallback to PM
            pass

        elif action == 'unzip':
            # TODO: find the rar/zip'd files we downloaded and unzip them
            pass

        elif action.startswith('move'):
            destination = action.split(':')[1]

            # TODO: move the files to the destination
            pass
        else:
            self.logger.info('inavlid action given: %s' % action)

    """ -------------------------------------------------------------------- """
    """ _loadJobs                                                            """
    """ -------------------------------------------------------------------- """
    def _loadJobs(self):
        """Load up any saved jobs from previous runs."""

        # debug
        self.logger.debug("loading jobs")

        # read in queue-file
        if os.path.isfile(self.fileTrigger):

            # info
            self.logger.info("loading saved jobs %s" % self.fileTrigger)

            try:

                # read file to mem
                f = open(self.fileTrigger, 'r')
                data = f.read()
                f.close()

                # process data
                lines = data.strip().split("\n")
                for line in lines:

                    # strip
                    line = line.strip()

                    # check
                    if line == '':
                        continue

                    # get name and user
                    name = ''
                    event = ''
                    action = ''
                    tAry = line.split(Trigger.DELIM)
                    if len(tAry) == 3:
                        name = tAry[0].strip()
                        event = tAry[1].strip()
                        action = tAry[2].strip()
                    else:
                         # debug
                        self.logger.debug("skipping transfer in wrong format: %s" % line)
                        # continue
                        continue

                    # check name
                    if name == '':
                        # debug
                        self.logger.debug("skipping transfer with empty name: %s" % line)
                        # continue

                    # process transfer
                    file = "%s%s.stat" % (self.transfersPath, name)
                    try:

                        # add transfer
                        self.addJob(name, event, action)

                    except Exception, e:
                        self.logger.error("error when loading statfile %s for transfer %s (%s)" % (file, name, e))

            except Exception, e:
                raise Exception, "_loadJobs: Failed to process file %s (%s)" % (self.fileTrigger, e)

        else:

            # info
            self.logger.info("no saved queue present %s" % self.fileTrigger)

    """ -------------------------------------------------------------------- """
    """ _saveJobs                                                            """
    """ -------------------------------------------------------------------- """
    def _saveJobs(self):
        """saves jobs dict for later use."""

        # debug
        self.logger.debug("saving jobs")

        # content
        content = ''
        for transfer in self.jobs.keys():
            for event in self.jobs[transfer].keys():
                content += '%s%s%s%s%s\n' % (transfer, Trigger.DELIM, event, Trigger.DELIM, self.jobs[transfer][event])

        # write file
        try:

            # write
            f = open(self.fileTrigger, 'w')
            f.write(content)
            f.flush()
            f.close()

            # return
            return True

        except Exception, e:

            # log
            self.logger.error("Error when saving queue file (%s)" % e)

        # return
        return False

    """ -------------------------------------------------------------------- """
    """ addJob                                                               """
    """ -------------------------------------------------------------------- """
    def addJob(self, transfer, event, action):
        """Adds a job to the jobs hash.

        action should be a list, even if it only contains one item!"""

        # debug
        self.logger.debug('Adding to jobs t: %s e: %s a: %s' % (transfer, event, action))

        if event in self.jobs[transfer]:
            # this event is already defined
            self.logger.debug('Attempted to add an event that already exists for this transfer: %s (%s)' % (transfer, event))
            return False
        else:
            self.jobs[transfer]={event: action}
            self.logger.debug('Added job for %s' % transfer)

    """ -------------------------------------------------------------------- """
    """ removeJob                                                            """
    """ -------------------------------------------------------------------- """
    def removeJob(self, transfer, event, action):
        """removes a job from the jobs hash"""

        # debug
        self.logger.debug('removing job from jobs for %s' % transfer)

        if transfer in self.jobs.keys():
            if event in self.jobs[transfer].keys():
                if action in self.jobs[transfer][event]:
                    self.jobs[transfer][event].pop(self.jobs[transfer][event].index(action))
                else:
                    self.logger.debug('No job defined for action: %s' % action)
                    return False
            else:
                self.logger.debug('No job defined for event: %s' % event)
            return False
        else:
            self.logger.debug('No job defined for transfer: %s' % transfer)
            return False

        if self.jobs[transfer][event] == []:
            # the jobs hash is now empty for this event, remove the event key
            self.logger.debug('No actions defined for event, removing event key for transfer: %s (%s)' % (transfer, event))
            del self.jobs[transfer][event]

        if self.jobs[transfer] == {}:
            # the jobs hash is now empty for this transfer, remove the transfer
            self.logger.debug('No events defined for transfer, removing transfer: %s' % transfer)
            del self.jobs[transfer]

