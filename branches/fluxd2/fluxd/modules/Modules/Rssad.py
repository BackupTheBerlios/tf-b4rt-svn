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
from threading import Lock
# fluxd-imports
from fluxd.Config import Config
from fluxd.activator.Activator import Activator
from fluxd.modules.Modules.BasicModule import BasicModule
from fluxd.decorators.synchronized import synchronized
################################################################################

__version__ = (0, 0, 1)
__version_str__ = '%s.%s' % (__version__[0], ''.join([str(part) for part in __version__[1:]]))

""" ------------------------------------------------------------------------ """
""" Rssad                                                                    """
""" ------------------------------------------------------------------------ """
class Rssad(BasicModule):

    # delim jobs
    DELIM_JOBS = '|'
    
    # delim jobentry
    DELIM_JOBENTRY = '#'
    
    # data-dir
    DIR_DATA = 'rssad'

    # lock
    InstanceLock = Lock()

    """ -------------------------------------------------------------------- """
    """ __init__                                                             """
    """ -------------------------------------------------------------------- """
    def __init__(self, name, *p, **k):

        # base
        BasicModule.__init__(self, name, *p, **k)

        # data-dir
        self.dataDir = '%s%s/' % (Config().get('dir', 'pathFluxd').strip(), Rssad.DIR_DATA)

        # interval
        self.interval = int(Config().getExt(name, 'interval').strip())

        # jobs
        self.jobs = []
        self.initializeJobs(Config().getExt(name, 'jobs').strip())

        # invocation-count
        self.runCount = 0

    """ -------------------------------------------------------------------- """
    """ status                                                               """
    """ -------------------------------------------------------------------- """
    def status(self):
        data = {}
        data['version'] = __version_str__
        data['dataDir'] = self.dataDir
        data['interval'] = str(self.interval)
        data['jobs'] = self.jobs.__str__()
        data['runCount'] = str(self.runCount)
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

        # reloadConfig   
        elif cmd.startswith('reloadConfig'):

            # interval
            self.interval = int(Config().getExt(self.name, 'interval').strip())

            # jobs
            self.initializeJobs(Config().getExt(self.name, 'jobs').strip())

            # message
            msg = 'Config reloaded (interval: %d; jobs: %s)' % (self.interval, self.jobs.__str__())

            # info
            self.logger.info(msg)

            # return
            return msg

        # unknown
        return 'Command unknown: %s' % cmd

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
        self.logger.info('dataDir: %s' % self.dataDir)
        self.logger.info('interval: %d' % self.interval)
        self.logger.info('jobs: %s' % self.jobs.__str__())

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

        # DEBUG
        self.logger.debug('onStop')

    """ -------------------------------------------------------------------- """
    """ initializeJobs                                                       """
    """ -------------------------------------------------------------------- """
    def initializeJobs(self, jobs):

        # parse job-def and init array
        self.jobs = []
        # job1|job2|job3
        jobsAry = jobs.split(Rssad.DELIM_JOBS)
        for job in jobsAry:
            # savedir#url#filtername
            jobAry = job.strip().split(Rssad.DELIM_JOBENTRY)
            if len(jobAry) == 3:
                filtername = jobAry.pop().strip()
                url = jobAry.pop().strip()
                savedir = jobAry.pop().strip()
                self.jobs.append(
                                 {
                                  'filtername': filtername,
                                  'url': url,
                                  'savedir': savedir
                                  }
                                 )
            else:
                self.logger.error('Wrong Job-Format: %s' % job)

    """ -------------------------------------------------------------------- """
    """ invoke                                                               """
    """ -------------------------------------------------------------------- """
    def invoke(self):

        # invocation-count
        self.runCount += 1

        try:

            # get Fluxcli instance
            fluxcli = Activator().getInstance('Fluxcli')
            
            # process jobs
            jobCopy = self.jobs[:]
            for job in jobCopy:
            
                # build arg-array
                args = []
                args.append('rss')
                args.append(job['savedir'])
                args.append('%s%s.dat' % (self.dataDir, job['filtername']))
                args.append('%s%s.hist' % (self.dataDir, job['filtername']))
                args.append(job['url'])
                
                # execute job
                try:
                    # log run
                    self.logger.debug('running rssad-job: %s' % job.__str__())
                    # invoke fluxcli
                    result = fluxcli.invoke(args, True).strip()
                    # log result
                    self.logger.debug('rssad-run-result:\n%s' % result)
                except Exception, e:
                    self.logger.error("Error when calling rssad (%s)" % (e))

            # return
            return True

        except Exception, e:
            self.logger.error("Error when processing rssad-jobs (%s)" % (e))
