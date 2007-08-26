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
################################################################################

""" ------------------------------------------------------------------------ """
""" bgShellCmd                                                               """
""" ------------------------------------------------------------------------ """
def bgShellCmd(logger, label, command, cwd = None, env = None):

    """ Invoke command in a background shell in fire-and-forget mode
        (stdout/stderr are not collected, no need to reap child). """

    # Debug.
    logger.debug('Starting command: %s' % command)

    try:
        # Build args.
        bin = '/bin/sh'
        args = ['fluxd: [%s] %s' % (label, bin), '-c', command]

        # Do a double fork in order for invoked shell to be orphaned (and
        # become owned by init), so that caller does not have to reap it.

        # First fork.
        pid_middle = os.fork()

        # Middle child: chdir, fork real child (shell) and exit right away.
        if pid_middle == 0:

            try:

                # Optionally chdir for convenience.
                if cwd:
                    os.chdir(cwd)

                # Second fork (fork()+exec() and not spawn(P_NOWAIT)
                # to be able to get and log exec()'s errors).
                pid_child = os.fork()

                # Child: exec command in shell.
                if pid_child == 0:

                    try:

                        # Invoke.
                        if env is not None:
                            os.execve(bin, args, env)
                        else:
                            os.execv(bin, args)

                    except Exception, e:
                        try:
                            logger.warning('Could not run command: %s (%s)' % (command, e))
                        except:
                            pass

                    os._exit(1)

                # Middle child: goodbye.
                else:

                    # Log child pid.
                    logger.debug('Command started, pid: %d' % pid_child)

                    # Done.
                    os._exit(0)

            except Exception, e:
                try:
                    logger.warning('Could not start command: %s (%s)' % (command, e))
                except:
                    pass

            os._exit(1)

        # Parent (daemon): reap middle child.
        else:

            status = os.waitpid(pid_middle, 0)[1]
            if status != 0:
                raise Exception, 'Helper child status: %d/%d' % (os.WEXITSTATUS(status), os.WTERMSIG(status))

    except Exception, e:
        raise Exception, 'Failed to run command: %s (%s)' % (command, e)
