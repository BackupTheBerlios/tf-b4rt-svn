# The contents of this file are subject to the BitTorrent Open Source License
# Version 1.1 (the License).  You may not copy or use this file, in either
# source code or executable form, except in compliance with the License.  You
# may obtain a copy of the License at http://www.bittorrent.com/license/.
#
# Software distributed under the License is distributed on an AS IS basis,
# WITHOUT WARRANTY OF ANY KIND, either express or implied.  See the License
# for the specific language governing rights and limitations under the
# License.

# Written by Greg Hazel

import os
import sys
import socket
import signal
import string
import struct
import thread
import logging
import threading
import traceback

from BitTorrent.translation import _
from BitTorrent import BTFailure
from BitTorrent.obsoletepythonsupport import set
from BitTorrent.DictWithLists import DictWithLists
from BitTorrent.defer import DeferredEvent, Deferred, run_deferred

##############################################################
#profile = True
profile = False

if profile:
    try:
        import hotshot
        import hotshot.stats
        prof_file_name = 'rawserver.prof'
    except ImportError, e:
        print "profiling not available:", e
        profile = False
##############################################################

if 'twisted.internet.reactor' in sys.modules:
    print ("twisted.internet.reactor was imported before BitTorrent.RawServer_twisted!\n"
           "I'll clean it up for you, but don't do that!\n"
           "Existing reference may be for the wrong reactor!\n"
           "!")
    del sys.modules['twisted.internet.reactor']

from twisted.python import threadable
# needed for twisted 1.3
# otherwise the 'thread safety' functions are not 'thread safe'
threadable.init(1)

letters = set(string.letters)

main_thread = thread.get_ident()

noSignals = True
iocpreactor = False

if os.name == 'nt':
    try:
        from twisted.internet import iocpreactor
        iocpreactor.proactor.install()
        noSignals = False
        iocpreactor = True
    except:
        # just as limited (if not more) as select, and also buggy
        #try:
        #    from twisted.internet import win32eventreactor
        #    win32eventreactor.install()
        #except:
        #    pass
        pass
else:
    try:
        from twisted.internet import kqreactor
        kqreactor.install()
    except:
        try:
            from twisted.internet import pollreactor
            pollreactor.install()
        except:
            pass

rawserver_logger = logging.getLogger('RawServer')

#the default reactor is select-based, and will be install()ed if another has not
from twisted.internet import reactor, task, error

# as far as I know, we work with twisted 1.3 and >= 2.0
#import twisted.copyright
#if twisted.copyright.version.split('.') < 2:
#    raise ImportError(_("RawServer_twisted requires twisted 2.0.0 or greater"))

from twisted.internet.protocol import DatagramProtocol, Protocol, ClientFactory
from twisted.protocols.policies import TimeoutMixin
from twisted.internet import interfaces, address

from BitTorrent.ConnectionRateLimitReactor import connectionRateLimitReactor

NOLINGER = struct.pack('ii', 1, 0)

# python sucks.
SHUT_RD = getattr(socket, 'SHUT_RD', 0)
SHUT_WR = getattr(socket, 'SHUT_WR', 1)

# this is a base class for all the callbacks the server could use
class Handler(object):

    # there is no connection_started.
    # a connection request can result in either connection_failed or
    # connection_made

    # called when the connection is ready for writiing
    def connection_made(self, s):
        pass

    # called when a connection request failed (failed, refused, or requested to close)
    def connection_failed(self, addr, exception):
        pass

    def data_came_in(self, addr, data):
        pass

    # called once when the current write buffer empties completely
    def connection_flushed(self, s):
        pass

    # called when a connection dies (lost or requested to close)
    def connection_lost(self, s):
        pass
        

class ConnectionWrapper(object):

    def __init__(self, rawserver, handler, context, tos=0):
        self.ip = None             # peer ip
        self.tos = tos
        self.port = None           # peer port
        self.dying = False
        self.paused = False
        self.encrypt = None
        self.connector = None
        self.transport = None
        self.reset_timeout = None
        self.callback_connection = None

        self.buffer = OutputBuffer(self._flushed)

        self.post_init(rawserver, handler, context)

    def post_init(self, rawserver, handler, context):
        self.rawserver = rawserver
        self.handler = handler
        self.context = context
        if self.rawserver:
            self.rawserver.single_sockets.add(self)

    def get_socket(self):
        s = None
        try:
            s = self.transport.getHandle()
        except:
            try:
                # iocpreactor doesn't implement ISystemHandle like it should
                s = self.transport.socket
            except:
                pass
        return s

    def pause_reading(self):
        # interfaces are the stupedist crap ever
        if (hasattr(interfaces.IProducer, "providedBy") and
            not interfaces.IProducer.providedBy(self.transport)):
            print "No producer", self.ip, self.port, self.transport
            return
        # not explicitly needed, but iocpreactor has a bug where the author is a moron
        if self.paused:
            return
        self.transport.pauseProducing()
        self.paused = True

    def resume_reading(self):
        if (hasattr(interfaces.IProducer, "providedBy") and
            not interfaces.IProducer.providedBy(self.transport)):
            print "No producer", self.ip, self.port, self.transport
            return
        # not explicitly needed, but iocpreactor has a bug where the author is a moron
        if not self.paused:
            return
        self.paused = False
        try:
            self.transport.resumeProducing()
        except Exception, e:
            # I bet these are harmless
            print "resumeProducing error", type(e), e

    def attach_transport(self, callback_connection, transport, reset_timeout):
        self.transport = transport
        self.callback_connection = callback_connection
        self.reset_timeout = reset_timeout

        if hasattr(transport, 'addBufferCallback'):
            self.buffer = PassBuffer(self.transport, self._flushed, self.buffer)
        elif hasattr(transport, 'registerProducer'):
            # Multicast uses sendto, which does not buffer.
            # It has no producer api
            self.buffer.attachConsumer(self.transport)

        try:
            address = self.transport.getPeer()
        except:
            # udp, for example
            address = self.transport.getHost()

        try:
            self.ip = address.host
            self.port = address.port
        except:
            # unix sockets, for example
            pass

        if self.tos != 0:
            s = self.get_socket()

            try:
                s.setsockopt(socket.IPPROTO_IP, socket.IP_TOS, self.tos)
            except socket.error:
                pass

    def sendto(self, packet, flags, addr):
        ret = None
        try:
            ret = self.transport.write(packet, addr)
        except Exception, e:
            rawserver_logger.warning("UDP sendto failed: %s" % unicode(e.args[0]))

        return ret

    def write(self, b):
        if self.encrypt is not None:
            b = self.encrypt(b)
        # bleh
        if isinstance(b, buffer):
            b = str(b)
        self.buffer.add(b)

    def _flushed(self):
        s = self
        # why do you tease me so?
        if s.handler is not None:
            # calling flushed from the write is bad form
            self.rawserver.add_task(0, s.handler.connection_flushed, s)

    def is_flushed(self):
        return self.buffer.is_flushed()

    def shutdown(self, how):
        if how == SHUT_WR:
            if hasattr(self.transport, "loseWriteConnection"):
                self.transport.loseWriteConnection()
            else:
                # twisted 1.3 sucks
                try:
                    self.transport.socket.shutdown(how)
                except:
                    pass
            self.buffer.stopWriting()
        elif how == SHUT_RD:
            self.transport.stopListening()
        else:
            self.close()

    def close(self):
        if self.buffer:
            self.buffer.stopWriting()

        if self.rawserver.config['close_with_rst']:
            try:
                s = self.get_socket()
                s.setsockopt(socket.SOL_SOCKET, socket.SO_LINGER, NOLINGER)
            except:
                pass

        if self.transport:
            if self in self.rawserver.udp_sockets:
                # udp connections should only call stopListening
                self.transport.stopListening()
            else:
                self.transport.loseConnection()
        else:
            if self.connector:
                self.connector.disconnect()

    def _cleanup(self):

        if self.buffer:
            self.buffer.consumer = None
            del self.buffer

        self.handler = None

        del self.transport

        if self.callback_connection:
            if self.callback_connection.can_timeout:
                self.callback_connection.setTimeout(None)
            self.callback_connection.connection = None
            del self.callback_connection

# hint: not actually a buffer
class PassBuffer(object):

    def __init__(self, consumer, callback_onflushed, old_buffer):
        self.consumer = consumer
        self.callback_onflushed = callback_onflushed
        self._is_flushed = False
        self.consumer.addBufferCallback(self._flushed, "buffer empty")
        # swallow the data written to the old buffer
        if old_buffer._buffer_list:
            self.consumer.writeSequence(old_buffer._buffer_list)
            old_buffer._buffer_list[:] = []

    def add(self, b):
        self._is_flushed = False
        self.consumer.write(b)

    def stopWriting(self):
        pass
    
    def is_flushed(self):
        return self._is_flushed
    
    def _flushed(self):
        self._is_flushed = True
        self.callback_onflushed()

        
class OutputBuffer(object):

    # This is an IPullProducer which has an unlimited buffer size,
    # and calls a callback when the buffer is completely flushed

    def __init__(self, callback_onflushed):
        self.consumer = None
        self.registered = False
        self.callback_onflushed = callback_onflushed
        self._buffer_list = []

    def is_flushed(self):
        return (len(self._buffer_list) == 0)

    def attachConsumer(self, consumer):
        self.consumer = consumer
        if not self.registered:
            self.beginWriting()
            
    def add(self, b):
        self._buffer_list.append(b)

        if self.consumer and not self.registered:
            self.beginWriting()

    def beginWriting(self):
        self.stopWriting()
        assert self.consumer, "You must attachConsumer before you beginWriting"
        self.registered = True
        self.consumer.registerProducer(self, False)

    def stopWriting(self):
        if not self.registered:
            return

        self.registered = False
        if self.consumer:
            try:
                self.consumer.unregisterProducer()
            except KeyError:
                # bug in iocpreactor: http://twistedmatrix.com/trac/ticket/1657
                pass

    def resumeProducing(self):
        if not self.registered:
            return
        assert self.consumer, "You must attachConsumer before you resumeProducing"

        to_write = len(self._buffer_list)
        if to_write > 0:
            self.consumer.writeSequence(self._buffer_list)
            del self._buffer_list[:]
            self.callback_onflushed()
        else:
            self.stopWriting()

    def pauseProducing(self):
        pass

    def stopProducing(self):
        pass



class CallbackConnection(object):

    def attachTransport(self, transport, s):
        s.attach_transport(self, transport=transport, reset_timeout=self.optionalResetTimeout)
        self.connection = s

    def connectionMade(self):
        s = self.connection
        s.handler.connection_made(s)
        self.optionalResetTimeout()

        self.factory.rawserver.connectionMade(s)

    def connectionLost(self, reason):
        reactor.callLater(0, self.post_connectionLost, reason)

    # twisted api inconsistancy workaround
    # sometimes connectionLost is called (not queued) from inside write()
    def post_connectionLost(self, reason):
        s = self.connection
        #print s.ip, s.port, reason.getErrorMessage()
        self.factory.rawserver._remove_socket(self.connection, was_connected=True)

    def dataReceived(self, data):
        self.optionalResetTimeout()

        s = self.connection
        s.rawserver._make_wrapped_call(s.handler.data_came_in,
                                       s, data, wrapper=s)

    def datagramReceived(self, data, (host, port)):
        s = self.connection
        s.rawserver._make_wrapped_call(s.handler.data_came_in,
                                       (host, port), data, wrapper=s)

    def optionalResetTimeout(self):
        if self.can_timeout:
            self.resetTimeout()


class CallbackProtocol(CallbackConnection, TimeoutMixin, Protocol):

    def makeConnection(self, transport):
        self.can_timeout = True
        self.setTimeout(self.factory.rawserver.config['socket_timeout'])

        outgoing = False

        key = None
        host = transport.getHost()
        if isinstance(host, address.UNIXAddress):
            key = host.name
        else:
            key = host.port

        try:
            addr = (transport.getPeer().host, transport.getPeer().port)
            c = self.factory.pop_connection_data(addr)
            outgoing = True
        except KeyError:
            # happens when this was an incoming connection
            pass
        except AttributeError:
            # happens when the peer is a unix socket
            pass

        if not outgoing:
            args = self.factory.get_connection_data(key)
            c = ConnectionWrapper(*args)

        self.attachTransport(transport, c)
        Protocol.makeConnection(self, transport)


class CallbackDatagramProtocol(CallbackConnection, DatagramProtocol):

    def startProtocol(self):
        self.can_timeout = False
        self.attachTransport(self.transport, self.connection)
        DatagramProtocol.startProtocol(self)


class ConnectionFactory(ClientFactory):

    def __init__(self):
        self.connection_data = DictWithLists()

    def add_connection_data(self, key, data):
        self.connection_data.push_to_row(key, data)

    def get_connection_data(self, key):
        return self.connection_data.get_from_row(key)

    def pop_connection_data(self, key):
        return self.connection_data.pop_from_row(key)

    def clientConnectionFailed(self, connector, reason):
        peer = connector.getDestination()
        addr = (peer.host, peer.port)
        s = self.pop_connection_data(addr)

        # opt-out
        if not s.dying:
            # this might not work - reason is not an exception
            s.handler.connection_failed(addr, reason)
            s.dying = True

        self.rawserver._remove_socket(s)


# storage for socket creation requestions, and proxy once the connection is made
class SocketRequestProxy(object):
    def __init__(self, port, bind, tos, protocol):
        self.port = port
        self.bind = bind
        self.tos = tos
        self.protocol = protocol
        self.connection = None

    def __getattr__(self, name):
        try:
            return getattr(self.connection, name)
        except:
            raise AttributeError, name

    def close(self):
        # closing the proxy doesn't mean anything.
        # you can stop_listening(), and then start again.
        # the socket only exists while it is listening
        if self.connection:
            self.connection.close()

class RawServerMixin(object):

    def __init__(self, config=None, noisy=True, tos=0):
        self.noisy = noisy
        self.config = config
        if not self.config:
            self.config = {}
        self.tos = tos
        self.sigint_flag = None
        self.sigint_installed = False        

    # going away soon. call _context_wrap on the context.
    def _make_wrapped_call(self, _f, *args, **kwargs):
        wrapper = kwargs.pop('wrapper', None)
        try:
            _f(*args, **kwargs)
        except KeyboardInterrupt:
            raise
        except Exception, e:         # hopefully nothing raises strings
            # Incoming sockets can be assigned to a particular torrent during
            # a data_came_in call, and it's possible (though not likely) that
            # there could be a torrent-specific exception during the same call.
            # Therefore read the context after the call.
            context = None
            if wrapper is not None:
                context = wrapper.context
            if context is not None:
                context.got_exception(*sys.exc_info())
            elif self.noisy:
                rawserver_logger.exception("Error in _make_wrapped_call for %s",
                                           _f.__name__)

   
    # must be called from the main thread
    def install_sigint_handler(self, flag = None):
        if flag is not None:
            self.sigint_flag = flag
        signal.signal(signal.SIGINT, self._handler)
        self.sigint_installed = True

    def _handler(self, signum, frame):
        if self.sigint_flag:
            self.external_add_task(0, self.sigint_flag.set)
        elif self.doneflag:
            self.external_add_task(0, self.doneflag.set)
       
        # Allow pressing ctrl-c multiple times to raise KeyboardInterrupt,
        # in case the program is in an infinite loop
        signal.signal(signal.SIGINT, signal.default_int_handler)


class RawServer(RawServerMixin):
    """RawServer encapsulates I/O and task scheduling.

       I/O corresponds to the arrival data on a file descriptor,
       and a task is a scheduled callback.  A task is scheduled
       using add_task or external_add_task.  add_task is used from within the
       thread running the RawServer, external_add_task from other threads.

       tracker.py provides a simple example of how to use RawServer.

        1. creates an instance of RawServer

            r = RawServer(config)

        2. creates a socket by a call to create_serversocket.

            s = r.create_serversocket(config['port'], config['bind'], True)

        3. tells the raw server to listen to the socket and associate
           a protocol handler with the socket.

            r.start_listening(s,
                HTTPHandler(t.get, config['min_time_between_log_flushes']))

        4. tells the raw_server to listen for I/O or scheduled tasks
           until stop is called.

            r.listen_forever()

           When a remote client opens a connection, a new socket is
           returned from the server socket's accept method and the
           socket is assigned the same handler as was assigned to the
           server socket.

           As data arrives on a socket, the handler's data_came_in
           member function is called.  It is up to the handler to
           interpret the data and/or pass it on to other objects.
           In the tracker, the HTTP protocol handler passes the arriving data
           to an HTTPConnection object which maintains state specific
           to a given connection.

         For outgoing connections, the call start_connection() is used.
         """


    def __init__(self, config=None, noisy=True, tos=0):
        """config is a dict that contains option-value pairs.

           'tos' is passed to setsockopt to set the IP Type of Service (i.e.,
           DiffServ byte), in IPv4 headers."""
        RawServerMixin.__init__(self, config, noisy, tos)

        self.doneflag = None

        # init is fine until the loop starts
        self.ident = thread.get_ident()
        self.associated = False

        self.single_sockets = set()
        self.udp_sockets = set()
        self.listened = False
        self.connections = 0

        ##############################################################
        if profile:
            try:
                os.unlink(prof_file_name)
            except:
                pass
            self.prof = hotshot.Profile(prof_file_name)

            callLater = reactor.callLater
            def _profile_call(delay, _f, *a, **kw):
                return callLater(delay, self.prof.runcall, _f, *a, **kw)
            reactor.callLater = _profile_call
            o = reactor.callFromThread
            def _profile_call2(_f, *a, **kw):
                return o(self.prof.runcall, _f, *a, **kw)
            reactor.callFromThread = _profile_call2
        ##############################################################

        connectionRateLimitReactor(reactor, self.config.get('max_incomplete', 10))
        # bleh
        self.add_pending_connection = reactor.add_pending_connection
        self.remove_pending_connection = reactor.remove_pending_connection
        self.reactor = reactor

        self.factory = ConnectionFactory()
        self.factory.rawserver = self

        self.factory.protocol = CallbackProtocol

        #l2 = task.LoopingCall(self._print_connection_count)
        #l2.start(1)


    ##############################################################
    def _print_connection_count(self):
        def _sl(x):
            if hasattr(x, "__len__"):
                return str(len(x))
            else:
                return str(x)

        c = len(self.single_sockets)
        u = len(self.udp_sockets)
        c -= u
        #s = "Connections(" + str(id(self)) + "): tcp(" + str(c) + ") upd(" + str(u) + ")"
        #rawserver_logger.debug(s)

        d = dict()
        for s in self.single_sockets:
            state = "None"
            if not s.dying and s.transport:
                try:
                    state = s.transport.state
                except:
                    state = "has transport"
            else:
                state = "No transport"
            if state not in d:
                d[state] = 0
            d[state] += 1
        #rawserver_logger.debug(d)
        print d

        sizes = "cc(" + _sl(self.connections)
        sizes += ") ss(" + _sl(self.single_sockets)
        sizes += ") us(" + _sl(self.udp_sockets) + ")"
        #rawserver_logger.debug(sizes)
        print sizes
    ##############################################################
        
    def get_remote_endpoints(self):
        addrs = [(s.ip, s.port) for s in self.single_sockets]
        return addrs

##    def add_task(self, delay, _f, *args, **kwargs):
##        """Schedule the passed function 'func' to be called after
##           'delay' seconds and pass the 'args'.
##
##           This should only be called by RawServer's thread."""
##        #assert thread.get_ident() == self.ident
##        return reactor.callLater(delay, _f, *args, **kwargs)
    add_task = reactor.callLater

    def external_add_task(self, delay, _f, *args, **kwargs):
        """Schedule the passed function 'func' to be called after
           'delay' seconds and pass 'args'.

           This should be called by threads other than RawServer's thread."""
        if delay == 0:
            return reactor.callFromThread(_f, *args, **kwargs)
        else:
            return reactor.callFromThread(reactor.callLater, delay,
                                          _f, *args, **kwargs)

    def create_unixserversocket(self, filename):
        s = SocketRequestProxy(0, filename, 0, 'unix')

        s.listening_port = reactor.listenUNIX(s.bind, self.factory)
        s.listening_port.listening = True

        return s

    def create_serversocket(self, port, bind='', tos=0):
        s = SocketRequestProxy(port, bind, tos, 'tcp')

        try:
            s.listening_port = reactor.listenTCP(s.port, self.factory,
                                                 interface=s.bind)
        except error.CannotListenError, e:
            if e[0] != 0:
                raise e.socketError
            else:
                raise
        s.listening_port.listening = True

        return s

    def _create_udpsocket(self, port, bind, tos, create_func):
        s = SocketRequestProxy(port, bind, tos, 'udp')

        protocol = CallbackDatagramProtocol()

        c = ConnectionWrapper(None, None, None, tos)
        s.connection = c
        protocol.connection = c

        try:
            s.listening_port = create_func(s.port, protocol, interface=s.bind)
        except error.CannotListenError, e:
            raise e.socketError
        s.listening_port.listening = True

        return s

    def create_udpsocket(self, port, bind='', tos=0):
        return self._create_udpsocket(port, bind, tos,
                                      create_func = reactor.listenUDP)

    def create_multicastsocket(self, port, bind='', tos=0):
        return self._create_udpsocket(port, bind, tos,
                                      create_func = reactor.listenMulticast)

    def _start_listening(self, s):
        if not s.listening_port.listening:
            s.listening_port.startListening()
            s.listening_port.listening = True

    def _get_data_key(self, serversocket):
        if serversocket.protocol == 'tcp':
            key = serversocket.port
        elif serversocket.protocol == 'unix':
            key = serversocket.bind
        else:
            raise TypeError("Unknown protocol: " + str(serversocket.protocol))
        return key        

    def start_listening(self, serversocket, handler, context=None):
        self.factory.add_connection_data(self._get_data_key(serversocket),
                                         (self, handler, context, serversocket.tos))

        self._start_listening(serversocket)

    def start_listening_udp(self, serversocket, handler, context=None):
        c = serversocket.connection
        c.post_init(self, handler, context)

        self._start_listening(serversocket)

        self.udp_sockets.add(c)

    start_listening_multicast = start_listening_udp

    def stop_listening(self, serversocket):
        listening_port = serversocket.listening_port
        try:
            listening_port.stopListening()
        except AttributeError:
            # AttributeError: 'MulticastPort' object has no attribute 'handle_disconnected_stopListening'
            # sigh.
            pass
        listening_port.listening = False
        if serversocket.protocol != 'udp':
            self.factory.pop_connection_data(self._get_data_key(serversocket))

    def stop_listening_udp(self, serversocket):
        self.stop_listening(serversocket)

        self.udp_sockets.remove(serversocket.connection)
        self.single_sockets.remove(serversocket.connection)

    stop_listening_multicast = stop_listening_udp

    def start_connection(self, dns, handler, context=None, do_bind=True):
        addr = dns[0]
        port = int(dns[1])

        if len(letters.intersection(addr)) > 0:
            rawserver_logger.warning("Don't pass host names to RawServer")
            # this blocks, that's why we throw the warning
            addr = socket.gethostbyname(addr)

        bindaddr = None
        if do_bind:
            bindaddr = self.config['bind']
            if isinstance(bindaddr, str) and len(bindaddr) >= 0:
                bindaddr = (bindaddr, 0)
            else:
                bindaddr = None

        c = ConnectionWrapper(self, handler, context, self.tos)

        self.factory.add_connection_data((addr, port), c)

        connector = reactor.connectTCP(addr, port, self.factory, bindAddress=bindaddr)
        c.connector = connector

        self.single_sockets.add(c)
        return c

    def associate_thread(self):
        assert not self.associated, \
               "RawServer has already been associated with a thread"
        self.ident = thread.get_ident()
        reactor.ident = self.ident
        self.associated = True

    def listen_forever(self, doneflag=None):
        """Main event processing loop for RawServer.
           RawServer listens until the doneFlag is set by some other
           thread.  The doneFlag tells all threads to clean-up and then
           exit."""

        if not doneflag:
            doneflag = DeferredEvent()
        assert isinstance(doneflag, DeferredEvent)
        self.doneflag = doneflag

        if not self.associated:
            self.associate_thread()

        if self.listened:
            Exception(_("listen_forever() should only be called once per reactor."))

        if main_thread == thread.get_ident() and not self.sigint_installed:
            self.install_sigint_handler()

        if iocpreactor and main_thread == thread.get_ident():
            def pulse():
                self.add_task(1, pulse)
            pulse()

        reactor.callLater(0, self.doneflag.addCallback, self._safestop)
        self.listened = True

        reactor.suggestThreadPoolSize(3)
        if noSignals:
            reactor.run(installSignalHandlers=False)
        else:
            reactor.run()

        if profile:
            self.prof.close()
            stats = hotshot.stats.load(prof_file_name)
            stats.strip_dirs()
            stats.sort_stats('time', 'calls')
            print "Rawserver MainLoop Profile:"
            stats.print_stats(20)

    def listen_once(self, period=1e9):
        rawserver_logger.warning(_("listen_once() might not return until there is activity, and might not process the event you want. Use listen_forever()."))
        reactor.iterate(period)

    def stop(self):
        if self.doneflag and not self.doneflag.isSet():
            self.doneflag.set()

    def _safestop(self, r=None):
        if not threadable.isInIOThread():
            self.external_add_task(0, self._stop)
        else:
            self._stop()

    def _stop(self, r=None):
        assert thread.get_ident() == self.ident

        connections = list(self.single_sockets)
        for connection in connections:
            try:
                connection.close()
            except:
                pass

        reactor.suggestThreadPoolSize(0)
        try:
            reactor.stop()
        except RuntimeError:
            # exceptions.RuntimeError: can't stop reactor that isn't running
            pass

    def _remove_socket(self, s, was_connected=False):
        # opt-out
        if not s.dying:
            self._make_wrapped_call(s.handler.connection_lost, s, wrapper=s)

        s._cleanup()

        self.single_sockets.remove(s)

        if was_connected:
            self.connections -= 1
            
    def connectionMade(self, s):
        self.connections += 1
        
