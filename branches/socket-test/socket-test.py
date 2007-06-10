#!/usr/bin/env python
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
import sys
import socket
################################################################################

""" ------------------------------------------------------------------------ """
""" recvall                                                                  """
""" ------------------------------------------------------------------------ """
def recvall(sock):
    total_data=[]
    while True:
        data = sock.recv(8192)
        if not data: break
        total_data.append(data)
    return ''.join(total_data)

""" ------------------------------------------------------------------------ """
""" sendall                                                                  """
""" ------------------------------------------------------------------------ """
def sendall(sock, data, delim):
	sock.sendall('%s%s' % (data, delim))

""" ------------------------------------------------------------------------ """
""" __main__                                                                 """
""" ------------------------------------------------------------------------ """
if __name__ == "__main__":

	# check args
	if len(sys.argv) < 4:
		print 'Examples: '
		print 'python socket-test.py unix /usr/local/torrentflux/.fluxd/fluxd.sock status'
		print 'python socket-test.py inet localhost 45454 status'
		print 'python socket-test.py inet auto 45454 status'
		sys.exit(1)

	# action
	try:

		# create socket
		sock = None
		if sys.argv[1] == 'unix':
			sock = socket.socket(socket.AF_UNIX, socket.SOCK_STREAM)
		elif sys.argv[1] == 'inet':
			sock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
		else:
			raise Exception, "Error: invalid arg: %s" % sys.argv[1]
		print 'created'

		# timeout
		#sock.settimeout(5.0)

		# connect
		if sys.argv[1] == 'unix':
			sock.connect(sys.argv[2])
		elif sys.argv[1] == 'inet':
			if sys.argv[2] == 'auto':
				sock.connect((socket.gethostname(), int(sys.argv[3])))
			else:
				sock.connect((sys.argv[2], int(sys.argv[3])))
		else:
			raise Exception, "Error: invalid arg: %s" % sys.argv[1]
		print 'connected'

		# send
		data = ''
		if sys.argv[1] == 'unix':
			data = sys.argv[3]
		elif sys.argv[1] == 'inet':
			data = sys.argv[4]
		else:
			raise Exception, "Error: invalid arg: %s" % sys.argv[1]
		sendall(sock, data, '\n')
		print 'sent %s' % data

		# receive
		print 'receiving...'
		received = recvall(sock)
		print 'received:\n%s' % received

		# close
		sock.close()

		# exit
		sys.exit(0)

	except Exception, e:
		print e
		sys.exit(1)
