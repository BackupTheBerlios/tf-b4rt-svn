#!/usr/bin/env python
################################################################################
# $Id$
# $Revision$
# $Date$
################################################################################
#
# Written by Bram Cohen
# see LICENSE.txt for license information
#
################################################################################
#
# btphptornado.py - use BitTornado with torrentflux
#
################################################################################
from BitTornado import PSYCO
if PSYCO.psyco:
    try:
        import psyco
        assert psyco.__version__ >= 0x010100f0
        psyco.full()
    except:
        pass
from BitTornado.download_bt1 import BT1Download, defaults, parse_params, get_usage, get_response
from BitTornado.RawServer import RawServer, UPnP_ERROR
from random import seed
from socket import error as socketerror
from BitTornado.bencode import bencode
from BitTornado.natpunch import UPnP_test
from threading import Event
from os.path import abspath
from os import getpid, remove
from sys import argv, stdout
import sys
from sha import sha
from time import strftime
from BitTornado.clock import clock
from BitTornado import createPeerID, version

assert sys.version >= '2', "Install Python 2.0 or greater"
try:
    True
except:
    True = 1
    False = 0

PROFILER = False

if __debug__: LOGFILE=open(argv[3]+"."+str(getpid()),"w")

def traceMsg(msg):
    try:
        if __debug__:
           LOGFILE.write(msg + "\n")
           LOGFILE.flush()
    except:
        return

def fmttime(n):
    # short format :
    return fmttimeshort(n)
    # long format :
    # return fmttimelong(n)

def fmttimeshort(n):
    if n == 0:
        return 'complete!'
    try:
        n = int(n)
        assert n >= 0 and n < 5184000  # 60 days
    except:
        return '<unknown>'
    m, s = divmod(n, 60)
    h, m = divmod(m, 60)
    d, h = divmod(h, 24)
    if d >= 7:
        return '-'
    elif d > 0:
        return '%dd %02d:%02d:%02d' % (d, h, m, s)
    else:
        return '%02d:%02d:%02d' % (h, m, s)

def fmttimelong(n):
    if n == 0:
        return 'complete!'
    try:
        n = int(n)
        assert n >= 0 and n < 5184000  # 60 days
    except:
        return '<unknown>'
    m, s = divmod(n, 60)
    h, m = divmod(m, 60)
    d, h = divmod(h, 24)
    y, d = divmod(d, 365)
    dec, y = divmod(y, 10)
    cent, dec = divmod(dec, 10)
    if cent > 0:
        return '%dcent %ddec %dy %dd %02d:%02d:%02d' % (cent, dec, y, d, h, m, s)
    elif dec > 0:
        return '%ddec %dy %dd %02d:%02d:%02d' % (dec, y, d, h, m, s)
    elif y > 0:
        return '%dy %dd %02d:%02d:%02d' % (y, d, h, m, s)
    elif d > 0:
        return '%dd %02d:%02d:%02d' % (d, h, m, s)
    else:
        return '%02d:%02d:%02d' % (h, m, s)

def transferLog(message):
    try:
        FILE = open(transferLogFile,"a+")
        FILE.write(message)
        FILE.flush()
        FILE.close()
    except Exception, e:
        sys.stderr.write("Failed to write log-file : " + transferLogFile + "\n")

#------------------------------------------------------------------------------#
# HeadlessDisplayer                                                            #
#------------------------------------------------------------------------------#
class HeadlessDisplayer:
    def __init__(self):
        self.done = False
        self.file = ''
        self.percentDone = ''
        self.timeEst = 'Connecting to Peers'
        self.downloadTo = ''
        self.downRate = ''
        self.upRate = ''
        self.shareRating = ''
        self.percentShare = ''
        self.upTotal = 0
        self.downTotal = 0
        self.seedStatus = ''
        self.peerStatus = ''
        self.seeds = ''
        self.peers = ''
        self.errors = []
        self.last_update_time = -1
        self.statFile = 'percent.txt'
        self.autoShutdown = 'False'
        self.user = 'unknown'
        self.size = 0
        self.shareKill = '100'
        self.distcopy = ''
        self.stoppedAt = ''

    def finished(self):
        if __debug__: traceMsg('finished - begin')
        self.done = True
        self.percentDone = '100'
        self.timeEst = 'Download Succeeded!'
        self.downRate = ''
        self.display()
        if self.autoShutdown == 'True':
            self.upRate = ''
            if self.stoppedAt == '':
                self.writeStatus()
            if __debug__: traceMsg('finished - end - raising ki')
            raise KeyboardInterrupt
        if __debug__: traceMsg('finished - end')

    def failed(self):
        if __debug__: traceMsg('failed - begin')
        self.done = True
        self.percentDone = '0'
        self.timeEst = 'Download Failed!'
        self.downRate = ''
        self.display()
        if self.autoShutdown == 'True':
            self.upRate = ''
            if self.stoppedAt == '':
                self.writeStatus()
            if __debug__:traceMsg('failed - end - raising ki')
            raise KeyboardInterrupt
        if __debug__: traceMsg('failed - end')

    def error(self, errormsg):
        self.errors.append(errormsg)
        self.display()

    def display(self, dpflag = Event(), fractionDone = None, timeEst = None,
        downRate = None, upRate = None, activity = None,
        statistics = None,  **kws):
        if __debug__: traceMsg('display - begin')
        if self.last_update_time + 0.1 > clock() and fractionDone not in (0.0, 1.0) and activity is not None:
            return
        self.last_update_time = clock()
        if fractionDone is not None:
            self.percentDone = str(float(int(fractionDone * 1000)) / 10)
        if timeEst is not None:
            self.timeEst = fmttime(timeEst)
        if activity is not None and not self.done:
            self.timeEst = activity
        if downRate is not None:
            self.downRate = '%.1f kB/s' % (float(downRate) / (1 << 10))
        if upRate is not None:
            self.upRate = '%.1f kB/s' % (float(upRate) / (1 << 10))
        if statistics is not None:
           if (statistics.shareRating < 0) or (statistics.shareRating > 100):
               self.shareRating = 'oo  (%.1f MB up / %.1f MB down)' % (float(statistics.upTotal) / (1<<20), float(statistics.downTotal) / (1<<20))
               self.downTotal = statistics.downTotal
               self.upTotal = statistics.upTotal
           else:
               self.shareRating = '%.3f  (%.1f MB up / %.1f MB down)' % (statistics.shareRating, float(statistics.upTotal) / (1<<20), float(statistics.downTotal) / (1<<20))
               self.downTotal = statistics.downTotal
               self.upTotal = statistics.upTotal
           if not self.done:
              self.seedStatus = '%d seen now, plus %.3f distributed copies' % (statistics.numSeeds,0.001*int(1000*statistics.numCopies))
              self.seeds = (str(statistics.numSeeds))
           else:
              self.seedStatus = '%d seen recently, plus %.3f distributed copies' % (statistics.numOldSeeds,0.001*int(1000*statistics.numCopies))
              self.seeds = (str(statistics.numOldSeeds))

           self.peers = '%d' % (statistics.numPeers)
           self.distcopy = '%.3f' % (0.001*int(1000*statistics.numCopies))
           self.peerStatus = '%d seen now, %.1f%% done at %.1f kB/s' % (statistics.numPeers,statistics.percentDone,float(statistics.torrentRate) / (1 << 10))

        dpflag.set()

        if __debug__: traceMsg('display - prior to self.write')

        if self.stoppedAt == '':
           self.writeStatus()

        if __debug__: traceMsg('display - end')

    def chooseFile(self, default, size, saveas, dir):
        if __debug__: traceMsg('chooseFile - begin')

        self.file = '%s (%.1f MB)' % (default, float(size) / (1 << 20))
        self.size = size
        if saveas != '':
            default = saveas
        self.downloadTo = abspath(default)
        if __debug__: traceMsg('chooseFile - end')
        return default

    def writeStatus(self):
        if __debug__: traceMsg('writeStatus - begin')
        downRate = self.downTotal
        die = False

        try:
            f=open(self.statFile,'r')
            running = f.read(1)
            f.close
        except:
            running = 0
            self.timeEst = 'Failed To Open StatFile'
            if __debug__: traceMsg('writeStatus - Failed to Open StatFile')
            transferLog("Failed to read stat-file : " + self.statFile + "\n")

        if __debug__: traceMsg('writeStatus - running :' + str(running))
        if __debug__: traceMsg('writeStatus - stoppedAt :' + self.stoppedAt)

        if running == '0':
            if self.stoppedAt == '':
                if self.percentDone == '100':
                    self.stoppedAt = '100'
                else:
                    self.stoppedAt = str((float(self.percentDone)+100)*-1)
                    self.timeEst = 'Torrent Stopped'
            die = True
            self.upRate = ''
            self.downRate = ''
            self.percentDone = self.stoppedAt
        else:
            if downRate == 0 and self.upTotal > 0:
                downRate = self.size
            if self.done:
                self.percentDone = '100'
                downRate = self.size
                if self.autoShutdown == 'True':
                    transferLog("die-when-done set, setting shutdown-flag...\n")
                    running = '0'
            if self.upTotal > 0:
                self.percentShare = '%.1f' % ((float(self.upTotal)/float(downRate))*100)
            else:
                self.percentShare = '0.0'
            if self.done and self.percentShare is not '' and self.autoShutdown == 'False':
                if (float(self.percentShare) >= float(self.shareKill)) and (self.shareKill != '0'):
                    transferLog("seed-limit "+str(self.shareKill)+" reached, setting shutdown-flag...\n")
                    die = True
                    running = '0'
                    self.upRate = ''
            elif (not self.done) and (self.timeEst == 'complete!') and (self.percentDone == '100.0'):
                if (float(self.percentShare) >= float(self.shareKill)) and (self.shareKill != '0'):
                    transferLog("seed-limit "+str(self.shareKill)+" reached, setting shutdown-flag...\n")
                    die = True
                    running = '0'
                    self.upRate = ''
                    #self.finished()

        lcount = 0

        while 1:
            lcount += 1
            try:
                f=open(self.statFile,'w')
                f.write(running + '\n')
                f.write(self.percentDone + '\n')
                f.write(self.timeEst + '\n')
                f.write(self.downRate + '\n')
                f.write(self.upRate + '\n')
                f.write(self.user + '\n')
                f.write(self.seeds + '+' + self.distcopy + '\n')
                f.write(self.peers + '\n')
                f.write(self.percentShare + '\n')
                f.write(self.shareKill + '\n')
                f.write(str(self.upTotal) + '\n')
                f.write(str(self.downTotal) + '\n')
                f.write(str(self.size))
                # log errors and append to stat-file
                try:
                    errs = []
                    errs = self.scrub_errs()
                    #for errmsg in errs:
                    #    f.write('\n' + errmsg)
                    if errs:
                        errorMessage = ""
                        for errmsg in errs:
                            errorMessage += "\n" + errmsg
                        f.write(errorMessage)
                        transferLog("self.scrub_errs() : \n" + errorMessage + "\n")
                except:
                    if __debug__: traceMsg('writeStatus - Failed during writing Errors')
                    transferLog("Failed to write stat-file : " + self.statFile + "\n")
                    pass

                f.flush()
                f.close()

                break
            except:
                if __debug__: traceMsg('writeStatus - Failed to Open StatFile for Writing')
                transferLog("Failed to open stat-file for writing : " + self.statFile + "\n")
                if lcount > 30:
                    break
                pass

        if die:
            if __debug__: traceMsg('writeStatus - dieing - raising ki')
            transferLog("tornado shutting down...\n")
            raise KeyboardInterrupt

    def newpath(self, path):
        self.downloadTo = path

    def scrub_errs(self):
        new_errors = []

        try:
            if self.errors:
                last_errMsg = ''
                errCount = 0
                for err in self.errors:
                    try:
                        if last_errMsg == '':
                            last_errMsg = err
                        elif last_errMsg == err:
                            errCount += 1
                        elif last_errMsg != err:
                            if errCount > 0:
                                new_errors.append(last_errMsg + ' (x' + str(errCount+1) + ')')
                            else:
                                new_errors.append(last_errMsg)
                            errCount = 0
                            last_errMsg = err
                    except:
                        if __debug__: traceMsg('scrub_errs - Failed scrub')
                        pass

            try:
                if len(new_errors) > 0:
                    if last_errMsg != new_errors[len(new_errors)-1]:
                        if errCount > 0:
                            new_errors.append(last_errMsg + ' (x' + str(errCount+1) + ')')
                        else:
                            new_errors.append(last_errMsg)
                    else:
                        if errCount > 0:
                            new_errors.append(last_errMsg + ' (x' + str(errCount+1) + ')')
                        else:
                            new_errors.append(last_errMsg)
            except:
                if __debug__: traceMsg('scrub_errs - Failed during scrub last Msg ')
                pass

            if len(self.errors) > 100:
                while len(self.errors) > 100 :
                    del self.errors[0:99]
                self.errors = new_errors

        except:
            if __debug__: traceMsg('scrub_errs - Failed during scrub Errors')
            pass

        return new_errors

#------------------------------------------------------------------------------#
# run                                                                          #
#------------------------------------------------------------------------------#
def run(autoDie,shareKill,statusFile,userName,params):

    if __debug__: traceMsg('run - begin')

    try:

        h = HeadlessDisplayer()
        h.statFile = statusFile
        h.autoShutdown = autoDie
        h.shareKill = shareKill
        h.user = userName

        while 1:
            try:
                config = parse_params(params)
            except ValueError, e:
                print 'error: ' + str(e) + '\nrun with no args for parameter explanations'
                break
            if not config:
                print get_usage()
                break

            # log what we are starting up
            startupMessage = "\ntornado starting up :\n"
            startupMessage += " - torrentfile : " + config['responsefile'] + "\n"
            startupMessage += " - userName : " + userName + "\n"
            startupMessage += " - statusFile : " + statusFile + "\n"
            startupMessage += " - pid-file : " + statusFile + ".pid" + "\n"
            startupMessage += " - transferLogFile : " + transferLogFile + "\n"
            startupMessage += " - autoDie : " + autoDie + "\n"
            startupMessage += " - shareKill : " + shareKill + "\n"
            startupMessage += " - minport : " + str(config['minport']) + "\n"
            startupMessage += " - maxport : " + str(config['maxport']) + "\n"
            startupMessage += " - max_upload_rate : " + str(config['max_upload_rate']) + "\n"
            startupMessage += " - max_download_rate : " + str(config['max_download_rate']) + "\n"
            startupMessage += " - min_uploads : " + str(config['min_uploads']) + "\n"
            startupMessage += " - max_uploads : " + str(config['max_uploads']) + "\n"
            startupMessage += " - min_peers : " + str(config['min_peers']) + "\n"
            startupMessage += " - max_initiate : " + str(config['max_initiate']) + "\n"
            startupMessage += " - max_connections : " + str(config['max_connections']) + "\n"
            startupMessage += " - super_seeder : " + str(config['super_seeder']) + "\n"
            startupMessage += " - security : " + str(config['security']) + "\n"
            startupMessage += " - auto_kick : " + str(config['auto_kick']) + "\n"
            startupMessage += " - priority : " + str(config['priority']) + "\n"
            startupMessage += " - alloc_type : " + str(config['alloc_type']) + "\n"
            startupMessage += " - alloc_rate : " + str(config['alloc_rate']) + "\n"
            startupMessage += " - buffer_reads : " + str(config['buffer_reads']) + "\n"
            startupMessage += " - write_buffer_size : " + str(config['write_buffer_size']) + "\n"
            startupMessage += " - check_hashes : " + str(config['check_hashes']) + "\n"
            startupMessage += " - max_files_open : " + str(config['max_files_open']) + "\n"
            startupMessage += " - upnp_nat_access : " + str(config['upnp_nat_access']) + "\n"
            transferLog(startupMessage)

            # write pid-file
            currentPid = (str(getpid())).strip()
            transferLog("writing pid-file : " + statusFile + ".pid (" + currentPid + ")\n")
            try:
                pidFile = open(statusFile + ".pid", 'w')
                pidFile.write(currentPid + "\n")
                pidFile.flush()
                pidFile.close()
            except Exception, e:
                if __debug__: traceMsg('run - Failed to Create PID file, shutting down')
                transferLog("Failed to write pid-file, shutting down : " + statusFile + ".pid (" + currentPid + ")" + "\n")
                break

            myid = createPeerID()
            seed(myid)

            doneflag = Event()
            def disp_exception(text):
                print text
            rawserver = RawServer(doneflag, config['timeout_check_interval'],
                              config['timeout'], ipv6_enable = config['ipv6_enabled'],
                              failfunc = h.failed, errorfunc = disp_exception)
            upnp_type = UPnP_test(config['upnp_nat_access'])
            while True:
                try:
                    listen_port = rawserver.find_and_bind(config['minport'], config['maxport'],
                                config['bind'], ipv6_socket_style = config['ipv6_binds_v4'],
                                upnp = upnp_type, randomizer = config['random_port'])
                    break
                except socketerror, e:
                    if upnp_type and e == UPnP_ERROR:
                        print 'WARNING: COULD NOT FORWARD VIA UPnP'
                        upnp_type = 0
                        continue
                    print "error: Couldn't listen - " + str(e)
                    h.failed()
                    return

            response = get_response(config['responsefile'], config['url'], h.error)
            if not response:
                break

            infohash = sha(bencode(response['info'])).digest()

            dow = BT1Download(h.display, h.finished, h.error, disp_exception, doneflag,
                        config, response, infohash, myid, rawserver, listen_port)

            if not dow.saveAs(h.chooseFile, h.newpath):
                break

            if not dow.initFiles(old_style = True):
                break

            if not dow.startEngine():
                dow.shutdown()
                break
            dow.startRerequester()
            dow.autoStats()

            if not dow.am_I_finished():
                h.display(activity = 'connecting to peers')

            # log that we are done with startup
            transferLog("tornado up and running.\n")

            # listen forever
            rawserver.listen_forever(dow.getPortHandler())

            # shutdown
            h.display(activity = 'shutting down')
            dow.shutdown()
            break

        try:
            rawserver.shutdown()
        except:
            pass

        if not h.done:
            h.failed()

    finally:
        if __debug__: traceMsg('run - removing PID file :'+statusFile+".pid")
        transferLog("removing pid-file : " + statusFile + ".pid" + "\n")
        try:
            remove(statusFile+".pid")
        except:
            if __debug__: traceMsg('run - Failed to remove PID file')
            transferLog("Failed to remove pid-file : " + statusFile + ".pid" + "\n")
            pass

    if __debug__: traceMsg('run - end')

#------------------------------------------------------------------------------#
# __main__                                                                     #
#------------------------------------------------------------------------------#
if __name__ == '__main__':
    if argv[1:] == ['--version']:
        print version
        sys.exit(0)

    # check argv-length
    if len(argv) < 5:
        print "Error : missing arguments, exiting. \n"
        sys.exit(0)

    # get/set log-file
    transferLogFile = argv[3]
    transferLogFile = transferLogFile.replace(".stat", ".log")

    if PROFILER:
        import profile, pstats
        p = profile.Profile()
        p.runcall(run, argv[1],argv[2],argv[3],argv[4],argv[5:])
        log = open('profile_data.'+strftime('%y%m%d%H%M%S')+'.txt','a')
        normalstdout = sys.stdout
        sys.stdout = log
#        pstats.Stats(p).strip_dirs().sort_stats('cumulative').print_stats()
        pstats.Stats(p).strip_dirs().sort_stats('time').print_stats()
        sys.stdout = normalstdout
    else:
        run(argv[1],argv[2],argv[3],argv[4],argv[5:])

    # log exit
    transferLog("tornado exit.\n")
