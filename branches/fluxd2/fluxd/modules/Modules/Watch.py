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
""" Watch                                                                    """
""" ------------------------------------------------------------------------ """
class Watch(BasicModule):

    # delim jobs
    DELIM_JOBS = ';'

    # delim jobentry
    DELIM_JOBENTRY = ':'

    # delim jobentry-component
    DELIM_COMPONENT = '='

    # lock
    InstanceLock = Lock()

    """ -------------------------------------------------------------------- """
    """ __init__                                                             """
    """ -------------------------------------------------------------------- """
    def __init__(self, name, *p, **k):

        # base
        BasicModule.__init__(self, name, *p, **k)

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
        # job1;job2;job3
        jobsAry = jobs.split(Watch.DELIM_JOBS)
        for job in jobsAry:
            # U=user:[A=action:][P=profile:]D=watchdir

            job = job.strip()
            if len(job) == 0: continue
            jobdef = job

            jobentry = {}
            while True:
                if job.startswith('D'+Watch.DELIM_COMPONENT):   # Dir: final component.
                    jobentry['D'] = job[2:].lstrip()
                    break
                else:                                           # Other component.
                    jobAry = job.split(Watch.DELIM_JOBENTRY, 1)
                    if len(jobAry) != 2 or len(jobAry[0]) < 2 or \
                       not jobAry[0][0].isalpha() or jobAry[0][1] != Watch.DELIM_COMPONENT:
                        jobentry.clear()
                        break
                    jobentry[jobAry[0][0]] = jobAry[0][2:].strip()
                    job = jobAry[1].lstrip()

            if jobentry.has_key('U') and len(jobentry['U']) > 0 and \
               jobentry.has_key('D') and os.path.isdir(jobentry['D']):
                self.jobs.append(jobentry)
            else:
                self.logger.error('Wrong Job-Format: %s' % jobdef)

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
            for job in self.jobs:
            
                # build arg-array
                args = []
                args.append('watch')
                args.append(job['D']) # watchdir
                args.append(job['U']) # user
                if job.has_key('A'):  # action
                    action = job['A']
                else:
                    action = 'ds'
                extraargs = []
                if job.has_key('P'):  # profile
                    action += 'p'
                    extraargs.append(job['P'])
                args.append(action)
                args += extraargs

                # execute job
                try:
                    # log run
                    self.logger.debug('running watch-job: %s' % job.__str__())
                    # invoke fluxcli
                    result = fluxcli.invoke(args, True).strip()
                    # log result
                    self.logger.debug('watch-run-result:\n%s' % result)
                except Exception, e:
                    self.logger.error("Error when calling watch (%s)" % (e))

            # return
            return True

        except Exception, e:
            self.logger.error("Error when processing watch-jobs (%s)" % (e))
