#!/usr/bin/env python
################################################################################
# $Id$
# $Revision$
# $Date$
################################################################################
#
# The contents of this file are subject to the BitTorrent Open Source License
# Version 1.1 (the License).  You may not copy or use this file, in either
# source code or executable form, except in compliance with the License.  You
# may obtain a copy of the License at http://www.bittorrent.com/license/.
#
# Software distributed under the License is distributed on an AS IS basis,
# WITHOUT WARRANTY OF ANY KIND, either express or implied.  See the License
# for the specific language governing rights and limitations under the
# License.
#
# Written by Bram Cohen, Uoti Urpala, John Hoffman, and David Harrison
#
################################################################################
#
# tfmainline.py - use mainline with torrentflux
#
################################################################################
from __future__ import division
app_name = "BitTorrent"
from BitTorrent.translation import _
import sys
import os
from os import getpid, remove
from cStringIO import StringIO
import logging
from logging import ERROR, WARNING
from time import strftime, sleep
import traceback
import BTL.stackthreading as threading
from BTL.platform import decode_from_filesystem, encode_for_filesystem
from BitTorrent.platform import get_dot_dir
from BTL.defer import DeferredEvent
from BitTorrent import inject_main_logfile
from BitTorrent.MultiTorrent import Feedback, MultiTorrent
from BitTorrent.defaultargs import get_defaults
from BitTorrent.parseargs import printHelp
from BitTorrent.prefs import Preferences
from BitTorrent import configfile
from BitTorrent import BTFailure, UserFailure
from BitTorrent import version
from BitTorrent import GetTorrent
from BTL.ConvertedMetainfo import ConvertedMetainfo
from BitTorrent.MultiTorrent import TorrentNotInitialized
from BitTorrent.RawServer_twisted import RawServer
from twisted.internet import task
from BitTorrent.UI import Size, Duration
inject_main_logfile()
from BitTorrent import console
from BitTorrent import stderr_console

def wrap_log(context_string, logger):
    """Useful when passing a logger to a deferred's errback.  The context
       specifies what was being done when the exception was raised."""
    return lambda e, *args, **kwargs : logger.error(context_string, exc_info=e)

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

def fmtsize(n):
    s = str(n)
    size = s[-3:]
    while len(s) > 3:
        s = s[:-3]
        size = '%s,%s' % (s[-3:], size)
    size = '%s (%s)' % (size, str(Size(n)))
    return size

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
class HeadlessDisplayer(object):

    def __init__(self):
        self.done = False
        self.state = 1
        self.percentDone = ''
        self.timeEst = ''
        self.downRate = '---'
        self.upRate = '---'
        self.shareRating = ''
        self.seedStatus = ''
        self.peerStatus = ''
        self.errors = []
        self.file = ''
        self.downloadTo = ''
        self.fileSize = ''
        self.fileSize_stat = ''
        self.numpieces = 0
        self.tfOwner = config['tf_owner']
        self.seedLimit = config['seed_limit']
        self.statFile = config['stat_file']
        self.dieWhenDone = config['die_when_done']
        self.isInShutDown = 0
        self.running = '1'

    def set_torrent_values(self, name, path, size, numpieces):
        self.file = name
        self.downloadTo = path
        self.fileSize = fmtsize(size)
        self.fileSize_stat = int(size)
        self.numpieces = numpieces

    def finished(self):
        """ this method is never called """
        self.done = True
        self.downRate = '---'
        self.display({'activity':_("download succeeded"), 'fractionDone':1})

    def error(self, errormsg):
        newerrmsg = strftime('[%H:%M:%S] ') + errormsg
        self.errors.append(newerrmsg)

    def display(self, statistics):

        # only process when not in shutdown-sequence
        if self.isInShutDown == 0:

            # eta
            activity = statistics.get('activity')
            timeEst = statistics.get('timeEst')
            if timeEst is not None:
                self.timeEst = fmttime(timeEst)
            elif activity is not None:
                self.timeEst = activity

            # fractionDone
            fractionDone = statistics.get('fractionDone')
            if fractionDone is not None:
                self.percentDone = str(int(fractionDone * 1000) / 10)

            # downRate
            downRate = statistics.get('downRate')
            if downRate is not None:
                self.downRate = '%.1f kB/s' % (downRate / (1 << 10))

            # upRate
            upRate = statistics.get('upRate')
            if upRate is not None:
                self.upRate = '%.1f kB/s' % (upRate / (1 << 10))

            # totals
            downTotal = statistics.get('downTotal')
            upTotal = statistics.get('upTotal')
            if upTotal is None:
                upTotal = 0
            if downTotal is None:
                downTotal = 0

            # share-rating
            if downTotal > 0:
                if upTotal > 0:
                    self.shareRating = _("%.3f") % ((upTotal * 100) / downTotal)
                else:
                    self.shareRating = "0"
            else:
                self.shareRating = "oo"

            # seeds
            seeds = statistics.get('numSeeds')
            if seeds is None:
                seeds = 0
            # dht :
            numCopies = statistics.get('numCopies')
            if numCopies is not None:
                seeds += numCopies
            # set status
            self.seedStatus = _("%d") % seeds

            # peers
            peers = statistics.get('numPeers')
            if peers is not None:
                self.peerStatus = _("%d") % peers
            else:
                self.peerStatus = "0"

            # set some fields in app which we need in shutdown
            app.percentDone = self.percentDone
            app.shareRating = self.shareRating
            app.upTotal = upTotal
            app.downTotal = downTotal

            # die-on-seed-limit / die-when-done
            if app.multitorrent.isDone:
                if self.dieWhenDone == 'True':
                    transferLog("die-when-done set, setting shutdown-flag...\n")
                    self.running = '0'
                else:
                    seedLimitMax = int(self.seedLimit)
                    if seedLimitMax > 0:
                        totalShareRating = int(((upTotal * 100) / self.fileSize_stat))
                        if totalShareRating >= seedLimitMax:
                            transferLog("seed-limit "+str(self.seedLimit)+" reached, setting shutdown-flag...\n")
                            self.running = '0'

            # read state from stat-file
            if self.running == '1':
                try:
                    FILE = open(self.statFile, 'r')
                    self.running = FILE.read(1)
                    FILE.close()
                except Exception, e:
                    self.running = '1'
                    transferLog("Failed to read stat-file : " + self.statFile + "\n")

            # shutdown or write stat-file
            if self.running == '0':
                # log
                transferLog("mainline shutting down...\n")
                # set flags
                self.state = 0
                self.isInShutDown = 1
                self.running = '0'
                # shutdown
                df = app.multitorrent.shutdown()
                stop_rawserver = lambda *a : app.multitorrent.rawserver.stop()
                df.addCallbacks(stop_rawserver, stop_rawserver)
            else:
                try:
                    FILE = open(self.statFile,"w")
                    # write stats to stat-file
                    FILE.write(repr(self.state)+"\n")
                    FILE.write(self.percentDone+"\n")
                    FILE.write(self.timeEst+"\n")
                    FILE.write(self.downRate+"\n")
                    FILE.write(self.upRate+"\n")
                    FILE.write(self.tfOwner+"\n")
                    FILE.write(self.seedStatus+"\n")
                    FILE.write(self.peerStatus+"\n")
                    FILE.write(self.shareRating+"\n")
                    FILE.write(self.seedLimit+"\n")
                    FILE.write(repr(upTotal)+"\n")
                    FILE.write(repr(downTotal)+"\n")
                    FILE.write(repr(self.fileSize_stat))
                    # log errors and append to stat-file
                    if self.errors:
                        # FILE.write("\n")
                        #for err in self.errors[-4:]:
                        errorMessage = "\n"
                        for err in self.errors[0:]:
                            errorMessage += err + "\n"
                            # FILE.write(err)
                        FILE.write(errorMessage)
                        transferLog("self.errors : \n" + errorMessage)
                    FILE.flush()
                    FILE.close()
                except Exception, e:
                    transferLog("Failed to write stat-file : " + self.statFile + "\n")

    def print_spew(self, spew):
        s = StringIO()
        s.write('\n\n\n')
        for c in spew:
            s.write('%20s ' % c['ip'])
            if c['initiation'] == 'L':
                s.write('l')
            else:
                s.write('r')
            total, rate, interested, choked = c['upload']
            s.write(' %10s %10s ' % (str(int(total/10485.76)/100),
                                     str(int(rate))))
            if c['is_optimistic_unchoke']:
                s.write('*')
            else:
                s.write(' ')
            if interested:
                s.write('i')
            else:
                s.write(' ')
            if choked:
                s.write('c')
            else:
                s.write(' ')
            total, rate, interested, choked, snubbed = c['download']
            s.write(' %10s %10s ' % (str(int(total/10485.76)/100),
                                     str(int(rate))))
            if interested:
                s.write('i')
            else:
                s.write(' ')
            if choked:
                s.write('c')
            else:
                s.write(' ')
            if snubbed:
                s.write('s')
            else:
                s.write(' ')
            s.write('\n')
        print s.getvalue()

#------------------------------------------------------------------------------#
# TorrentApp                                                                   #
#------------------------------------------------------------------------------#
class TorrentApp(object):

    class LogHandler(logging.Handler):
        def __init__(self, app, level=logging.NOTSET):
            logging.Handler.__init__(self,level)
            self.app = app

        def emit(self, record):
            self.app.display_error(record.getMessage() )
            if record.exc_info is not None:
                self.app.display_error( " %s: %s" %
                    ( str(record.exc_info[0]), str(record.exc_info[1])))
                tb = record.exc_info[2]
                stack = traceback.extract_tb(tb)
                l = traceback.format_list(stack)
                for s in l:
                    self.app.display_error( " %s" % s )

    class LogFilter(logging.Filter):
        def filter( self, record):
            if record.name == "NatTraversal":
                return 0
            return 1  # allow.

    def __init__(self, metainfo, config):
        assert isinstance(metainfo, ConvertedMetainfo )
        self.metainfo = metainfo
        self.config = Preferences().initWithDict(config)
        self.torrent = None
        self.multitorrent = None
        self.logger = logging.getLogger("bittorrent-console")
        log_handler = TorrentApp.LogHandler(self)
        log_handler.setLevel(WARNING)
        logger = logging.getLogger()
        logger.addHandler(log_handler)

        # some fields we need in shutdown
        self.percentDone = "0"
        self.shareRating = "0"
        self.upTotal = "0"
        self.downTotal = "0"

        # disable stdout and stderr error reporting to stderr.
        global stderr_console
        logging.getLogger('').removeHandler(console)
        if stderr_console is not None:
            logging.getLogger('').removeHandler(stderr_console)
        logging.getLogger().setLevel(WARNING)

    def start_torrent(self,metainfo,save_incomplete_as,save_as):
        """Tells the MultiTorrent to begin downloading."""
        try:
            self.d.display({'activity':_("initializing"), 'fractionDone':0})
            multitorrent = self.multitorrent
            df = multitorrent.create_torrent(metainfo, save_incomplete_as,
                                             save_as)
            df.addErrback( wrap_log('Failed to start torrent', self.logger))
            def create_finished(torrent):
                self.torrent = torrent
                if self.torrent.is_initialized():
                   multitorrent.start_torrent(self.torrent.infohash)
                else:
                    # HEREDAVE: why should this set the doneflag?
                   self.core_doneflag.set()  # e.g., if already downloading...
            df.addCallback( create_finished )
        except KeyboardInterrupt:
            raise
        except UserFailure, e:
            self.logger.error( "Failed to create torrent: " + unicode(e.args[0]) )
        except Exception, e:
            self.logger.error( "Failed to create torrent", exc_info = e )
            return

    def run(self):
        self.core_doneflag = DeferredEvent()
        rawserver = RawServer(self.config)
        self.d = HeadlessDisplayer()

        # set up shut-down procedure before we begin doing things that
        # can throw exceptions.
        def shutdown():
            print "shutdown."
            self.d.display({'activity':_("shutting down"), 'fractionDone':0})
            if self.multitorrent:
                df = self.multitorrent.shutdown()
                stop_rawserver = lambda *a : rawserver.stop()
                df.addCallbacks(stop_rawserver, stop_rawserver)
            else:
                rawserver.stop()

        # write pid-file
        currentPid = (str(getpid())).strip()
        transferLog("writing pid-file : " + self.config['stat_file'] + ".pid (" + currentPid + ")\n")
        try:
            pidFile = open(self.config['stat_file'] + ".pid", 'w')
            pidFile.write(currentPid + "\n")
            pidFile.flush()
            pidFile.close()
        except Exception, e:
            transferLog("Failed to write pid-file : " + self.config['stat_file'] + ".pid (" + currentPid + ")" + "\n")
            self.logger.error("Failed to write pid-file : " + self.config['stat_file'] + ".pid (" + currentPid + ")", exc_info = e)
            raise BTFailure(_("Failed to write pid-file."))

        # It is safe to addCallback here, because there is only one thread,
        # but even if the code were multi-threaded, core_doneflag has not
        # been passed to anyone.  There is no chance of a race condition
        # between core_doneflag's callback and addCallback.
        self.core_doneflag.addCallback(
            lambda r: rawserver.external_add_task(0, shutdown))
        rawserver.install_sigint_handler(self.core_doneflag)

        # semantics for --save_in vs --save_as:
        #   save_in specifies the directory in which torrent is written.
        #      If the torrent is a batch torrent then the files in the batch
        #      go in save_in/metainfo.name_fs/.
        #   save_as specifies the filename for the torrent in the case of
        #      a non-batch torrent, and specifies the directory name
        #      in the case of a batch torrent.  Thus the files in a batch
        #      torrent go in save_as/.
        metainfo = self.metainfo
        torrent_name = metainfo.name_fs  # if batch then this contains
                                         # directory name.
        if config['save_as']:
            if config['save_in']:
                raise BTFailure(_("You cannot specify both --save_as and "
                                  "--save_in."))
            saveas,bad = encode_for_filesystem(config['save_as'])
            if bad:
                raise BTFailure(_("Invalid path encoding."))
            savein = os.path.dirname(os.path.abspath(saveas))
        elif config['save_in']:
            savein,bad = encode_for_filesystem(config['save_in'])
            if bad:
                raise BTFailure(_("Invalid path encoding."))
            saveas = os.path.join(savein,torrent_name)
        else:
            saveas = torrent_name
        if config['save_incomplete_in']:
            save_incomplete_in,bad = \
                encode_for_filesystem(config['save_incomplete_in'])
            if bad:
                raise BTFailure(_("Invalid path encoding."))
            save_incomplete_as = os.path.join(save_incomplete_in,torrent_name)
        else:
            save_incomplete_as = os.path.join(savein,torrent_name)
        data_dir,bad = encode_for_filesystem(config['data_dir'])
        if bad:
            raise BTFailure(_("Invalid path encoding."))
        try:
            self.multitorrent = \
                MultiTorrent(self.config, rawserver, data_dir,
                             is_single_torrent = True,
                             resume_from_torrent_config = False)
            self.d.set_torrent_values(metainfo.name, os.path.abspath(saveas),
                                metainfo.total_bytes, len(metainfo.hashes))
            self.start_torrent(self.metainfo, save_incomplete_as, saveas)
            self.get_status()
        except UserFailure, e:
            self.logger.error( unicode(e.args[0]) )
            rawserver.add_task(0, self.core_doneflag.set)
        except Exception, e:
            self.logger.error( "", exc_info = e )
            rawserver.add_task(0, self.core_doneflag.set)

        # log that we are done with startup
        transferLog("mainline up and running.\n")

        # always make sure events get processed even if only for
        # shutting down.
        rawserver.listen_forever()

        # overwrite stat-file in "Torrent Stopped"/"Download Succeeded!" format.
        try:
            FILE = open(self.d.statFile,"w")
            FILE.write("0\n")
            pcts = "-"+self.percentDone
            pctf = float(pcts)
            pctf -= 100
            FILE.write(str(pctf))
            FILE.write("\n")
            if self.multitorrent.isDone:
                FILE.write("Download Succeeded!\n")
            else:
                FILE.write("Torrent Stopped\n")
            FILE.write("\n")
            FILE.write("\n")
            FILE.write(self.d.tfOwner+"\n")
            FILE.write("\n")
            FILE.write("\n")
            FILE.write(self.shareRating+"\n")
            FILE.write(self.d.seedLimit+"\n")
            FILE.write(repr(self.upTotal)+"\n")
            FILE.write(repr(self.downTotal)+"\n")
            FILE.write(repr(self.d.fileSize_stat))
            FILE.flush()
            FILE.close()
        except Exception, e:
            transferLog("Failed to write stat-file : " + self.config['stat_file'] + "\n")

    def get_status(self):
        self.multitorrent.rawserver.add_task(self.config['display_interval'],
                                             self.get_status)
        if self.torrent is not None:
            status = self.torrent.get_status(self.config['spew'])
            self.d.display(status)

    def display_error(self, text):
        """Called by the logger via LogHandler to display error messages in the
           curses window."""
        self.d.error(text)

#------------------------------------------------------------------------------#
# __main__                                                                     #
#------------------------------------------------------------------------------#
if __name__ == '__main__':
    uiname = 'bittorrent-console'

    # args
    defaults = get_defaults(uiname)
    metainfo = None
    if len(sys.argv) <= 1:
        printHelp(uiname, defaults)
        sys.exit(1)
    try:
        # Modifying default values from get_defaults is annoying...
        # Implementing specific default values for each uiname in
        # defaultargs.py is even more annoying.  --Dave
        data_dir = [[name, value,doc] for (name, value, doc) in defaults
                        if name == "data_dir"][0]
        defaults = [(name, value,doc) for (name, value, doc) in defaults
                        if not name == "data_dir"]
        ddir = os.path.join( get_dot_dir(), "console" )
        data_dir[1] = decode_from_filesystem(ddir)
        defaults.append( tuple(data_dir) )
        config, args = configfile.parse_configuration_and_args(defaults,
                                       uiname, sys.argv[1:], 0, 1)
        torrentfile = None
        if len(args):
            torrentfile = args[0]
        if torrentfile is not None:
            try:
                metainfo = GetTorrent.get(torrentfile)
            except GetTorrent.GetTorrentException, e:
                raise UserFailure(_("Error reading .torrent file: ") + '\n' + unicode(e.args[0]))
        else:
            raise UserFailure(_("you must specify a .torrent file"))
    except BTFailure, e:
        print unicode(e.args[0])
        sys.exit(1)
    except KeyboardInterrupt:
        sys.exit(1)

    # get/set log-file
    transferLogFile = config['stat_file']
    transferLogFile = transferLogFile.replace(".stat", ".log")

    # log what we are starting up
    startupMessage = "\nmainline starting up :\n"
    startupMessage += " - torrentfile : " + torrentfile + "\n"
    startupMessage += " - save_in : " + config['save_in'] + "\n"
    startupMessage += " - tf_owner : " + config['tf_owner'] + "\n"
    startupMessage += " - stat_file : " + config['stat_file'] + "\n"
    startupMessage += " - pid-file : " + config['stat_file'] + ".pid" + "\n"
    startupMessage += " - transferLogFile : " + transferLogFile + "\n"
    startupMessage += " - die_when_done : " + str(config['die_when_done']) + "\n"
    startupMessage += " - seed_limit : " + str(config['seed_limit']) + "\n"
    startupMessage += " - minport : " + str(config['minport']) + "\n"
    startupMessage += " - maxport : " + str(config['maxport']) + "\n"
    startupMessage += " - max_upload_rate : " + str(config['max_upload_rate']) + "\n"
    startupMessage += " - max_download_rate : " + str(config['max_download_rate']) + "\n"
    startupMessage += " - min_uploads : " + str(config['min_uploads']) + "\n"
    startupMessage += " - max_uploads : " + str(config['max_uploads']) + "\n"
    startupMessage += " - min_peers : " + str(config['min_peers']) + "\n"
    startupMessage += " - max_initiate : " + str(config['max_initiate']) + "\n"
    startupMessage += " - max_incomplete : " + str(config['max_incomplete']) + "\n"
    startupMessage += " - max_allow_in : " + str(config['max_allow_in']) + "\n"
    startupMessage += " - rerequest_interval : " + str(config['rerequest_interval']) + "\n"
    startupMessage += " - start_trackerless_client : " + str(config['start_trackerless_client']) + "\n"
    startupMessage += " - check_hashes : " + str(config['check_hashes']) + "\n"
    startupMessage += " - max_files_open : " + str(config['max_files_open']) + "\n"
    startupMessage += " - upnp : " + str(config['upnp']) + "\n"
    transferLog(startupMessage)

    # app
    app = TorrentApp(metainfo, config)
    try:
        app.run()
    except KeyboardInterrupt:
        pass
    except BTFailure, e:
        print unicode(e.args[0])
    except Exception, e:
        logging.getLogger().exception(e)

    # if after a reasonable amount of time there are still
    # non-daemon threads hanging around then print them.
    nondaemons = [d for d in threading.enumerate() if not d.isDaemon()]
    if len(nondaemons) > 1:
       sleep(4)
       nondaemons = [d for d in threading.enumerate() if not d.isDaemon()]
       if len(nondaemons) > 1:
           print "non-daemon threads not shutting down:"
           for th in nondaemons:
               print " ", th

    # remove pid-file
    transferLog("removing pid-file : " + app.config['stat_file'] + ".pid" + "\n")
    try:
        remove(app.config['stat_file'] + ".pid")
    except Exception, e:
        transferLog("Failed to remove pid-file : " + app.config['stat_file'] + ".pid" + "\n")
        app.logger.error("Failed to remove pid-file", exc_info = e)

    # log exit
    transferLog("mainline exit.\n")
