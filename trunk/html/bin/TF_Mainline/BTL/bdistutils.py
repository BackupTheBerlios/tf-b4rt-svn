# The contents of this file are subject to the BitTorrent Open Source License
# Version 1.1 (the License).  You may not copy or use this file, in either
# source code or executable form, except in compliance with the License.  You
# may obtain a copy of the License at http://www.bittorrent.com/license/.
#
# Software distributed under the License is distributed on an AS IS basis,
# WITHOUT WARRANTY OF ANY KIND, either express or implied.  See the License
# for the specific language governing rights and limitations under the
# License.

# by David Harrison
import sys, os, shutil, pwd
from distutils import core
from distutils.sysconfig import get_python_lib
import distutils.sysconfig
from stat import S_IMODE, S_IRUSR, S_IXUSR, S_IRGRP, S_IXGRP, S_IROTH, S_IXOTH
from daemon import getuid_from_username, getgid_from_username
from daemon import getgid_from_groupname

class SetupException(Exception):
    pass

def getuid_for_path(path):
    return os.stat(path).st_uid

def seteugid_to_login():
    """set effective user id and effective group id to the user and group ids
       of the user logged into this terminal."""
    uid = pwd.getpwnam(os.getlogin())[2]  # search /etc/passwd for uid and
    gid = pwd.getpwnam(os.getlogin())[3]  # gid of user logged into this
                                          # terminal.
    os.setegid(gid)
    os.seteuid(uid)                       # Is there a better way? --Dave

def get_cdv_change_code():
    months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul',
              'Aug', 'Sep', 'Oct', 'Nov', 'Dec']

    # cdv won't run on the dev machines as root.  nfs does not allow
    # root access to mounted drives.  --Dave
    if os.getuid() == 0 and getuid_for_path(".") != 0:
        seteugid_to_login()        

    # fragile. XXXX
    l = os.popen("cdv history -c 1").readlines()[0].split(" ")
    if os.getuid() == 0:
        os.seteuid(0)
        #os.setegid(oldgid)
        
    l = [x.strip() for x in l if x.strip() != '']  # remove empty strings.
    x,code,x,x,x,x,dow,mo,dom,t,y = l
    month = "%.2d" % (months.index(mo)+1)
    dom = "%.2d" % int(dom)    # single digit day of month like 3 becomes 03
    t = "_".join(t.split(':')) # convert ':' to underscores in time.
    return y+"_"+month+"_"+dom+"_"+t+"_"+code

def get_install_prefix( appname ):
    """Generates directory name /opt/appname_YYYY_MM_DD_HH_MM_SS_CODE"""

    # fragile. XXXX
    change = get_cdv_change_code()
    path = os.path.join("/opt", appname+"_"+change)
    return os.path.normpath(path)

def get_unique_install_prefix( appname ):
    """Generates a directory name /opt/appname_YYYY_MM_DD_HH_SS_CODE or
       /opt/appname_YYYY_MM_DD_HH_SS_CODE_vXXX if the prior exists.
       XXX is a counter that is incremented with each install of
       the distribution with the same cdv change code.

       Unlike get_install_prefix, this does not assume that cdv exists
       on the system, but instead assumes there is a version.txt
       file in the distribution root directory containing the cdv change
       date and code information.  This file is created in the install
       directory whenever bdistutils is run with the installdev option."""
    vfile = os.path.join(sys.path[0], "version.txt")
    if not os.path.exists(vfile):
        raise SetupException( "Cannot derive install prefix from cdv change date "
                              "code, because there is no version.txt file in the "
                              "root of the distribution tree." )
    cfp = open(vfile, 'r')
    change_str = cfp.readline().strip()
    prefix = os.path.join("/opt", appname+"_"+change_str)
    while os.path.exists(prefix):
        path, name = os.path.split(prefix)
        code_or_cnt = prefix.split("_")[-1]
        if code_or_cnt[0] == 'v':
            cnt = int(code_or_cnt[1:])
            cnt += 1
            prefix = "_".join(prefix.split("_")[:-1])
        else:
            cnt = 1
        prefix = "%s_v%03.f" % (prefix, cnt)
    return os.path.normpath(prefix)

def setup( **kwargs ):
    """site-specific setup.
    
       If sys.argv[1] is not installdev then this behaves
       as python's distutils.core.setup.

       If sys.argv[1] is installdev then this installs into a
       directory like:
       
       /opt/BTL_2006_08_01_20:47:59_dfb3

       Replace BTL with kwargs['name'], the date and time with
       the commit time for this revision in the cdv repository
       and dfb3 with the code for the revision in cdv.

       Also creates a symbolic link like /opt/BTL pointing to
       /opt/BTL_2006_08_01_20:47:59_dfb3/BTL.

       If kwargs['symlinks'] is defined and is a list
       then this creates a set of symlinks in the directory
       package directory.  For example,
       
          setup( symlinks=['/opt/BTL','/opt/Mitte'], ...)
          
       creates
       
          /opt/hypertracker_2006_08_01_20:47:59_dfb3/hypetracker/BTL
          --> /opt/BTL
          /opt/hypertracker_2006_08_01_20:47:59_dfb3/hypetracker/Mitte
          --> /opt/Mitte

       """
       
    name = kwargs['name']

    # setup doesn't like kwargs it doesn't know.
    username = kwargs.get('username',None)
    if kwargs.has_key('username'): del kwargs['username']  
    groupname = kwargs.get('groupname',None)
    if kwargs.has_key('groupname'): del kwargs['groupname']  
    symlinks = kwargs.get('symlinks',None)
    if kwargs.has_key('symlinks'): del kwargs['symlinks']  
    
    installdev=False
    installprod = False
    old_prefix = None
                 
    if len(sys.argv)>1 and sys.argv[1] == "force-installdev":
        # force install simply installs in a new directory.
        sys.prefix = get_unique_install_prefix(name)
        distutils.sysconfig.PREFIX=sys.prefix
        print "get_unique_install_prefix returned sys.prefix=", sys.prefix
        installdev = True
        sys.argv[1] = "install"
        
        # determine old install directory.
        if os.path.exists( os.path.join("/opt/",name) ):
            old_prefix = os.path.realpath(os.path.join("/opt/", name))
            old_prefix = os.path.split(old_prefix)[0]

    elif len(sys.argv)>1 and sys.argv[1] == "installdev": 
        installdev=True
        sys.argv[1] = "install"

        # create change code file.
        code = get_cdv_change_code()
        if code:
            # may fail if root and destination is nfs mounted.
            try:
                cfp = open(os.path.join(sys.path[0],"version.txt"), 'w')
                cfp.write( code )
                cfp.close()
            except IOError:
                # try again as login username.
                old_uid = os.geteuid() 
                seteugid_to_login()
                cfp = open(os.path.join(sys.path[0],"version.txt"), 'w')
                cfp.write( code )
                cfp.close()
                os.seteuid(old_uid)  # require root access to install into /opt or python site-packages.

        # determine install directory
        sys.prefix = get_install_prefix(name)
        distutils.sysconfig.PREFIX=sys.prefix
        if os.path.exists(sys.prefix):
            raise SetupException( "This code revision has already been installed %s."
                             "  If you want to install it again then move the "
                             "existing directory or use force-installdev." % sys.prefix )

        # determine old install directory.
        if os.path.exists( os.path.join("/opt/",name) ):
            old_prefix = os.path.realpath(os.path.join("/opt/", name))
            old_prefix = os.path.split(old_prefix)[0]
    
    if len(sys.argv)>1 and sys.argv[1] == "install":
        # building with root privilege can fail if the destination of the
        # build is nfs mounted.
        sys.argv[1] = "build"
        try:
            # try as root if I am root.
            core.setup(**kwargs)
        except:
            # try using login username
            old_uid = os.geteuid() 
            seteugid_to_login()
            core.setup(**kwargs)
            os.seteuid(old_uid)
        sys.argv[1] = "install"
  
    try: 
        core.setup(**kwargs)
    except:
        # try using login username
        old_uid = os.geteuid() 
        seteugid_to_login()
        core.setup(**kwargs)
        os.seteuid(old_uid)

    if installdev:
        print "installdev is True."

        # shortened the directory path.
        #long_path = os.path.join(sys.path[0], "build", "lib", name)
        long_path = os.path.join(sys.prefix, "lib", "python2.4", "site-packages", name)
        print "long_path=",long_path
        dest = os.path.join(sys.prefix,name)
        print "dest=", dest
        if os.path.exists(long_path):
            print "copytree from ", long_path, " to ", dest
            shutil.copytree(long_path,dest)
        #shutil.rmtree(os.path.join(sys.prefix, "lib" ))

        # copy all files not in packages into /opt.
        for f in os.listdir('.'):
            if f == "build": continue
            if f == ".cdv": continue
            if f == "lib": continue
            if not os.path.exists( os.path.join(sys.prefix,f)):
                if os.path.isdir(f):
                    shutil.copytree(f,os.path.join(sys.prefix,f),False)
                else:
                    shutil.copyfile(f,os.path.join(sys.prefix,f))

        # create symlink from /opt/blah to /opt/blah_YYYY_MM_DD_HH:MM:SS_code/blah
        link_to = os.path.join(sys.prefix, name)
        symlnk = os.path.join( '/opt', name )
        print "removing symlink from", symlnk
        if os.path.islink(symlnk):
            print "removing", symlnk
            os.remove(symlnk)
        print "creating symlink", symlnk, "to", link_to
        os.symlink(link_to, symlnk)

        if username:
            uid = getuid_from_username(username)
        else:
            uid = -1
        if groupname:
            gid = getgid_from_groupname(groupname)
        elif username:
            gid = getgid_from_username(username)
        else:
            gid = -1
            
        # recursively change owner and group name of install directory.
        ## Turns out that this is a bad idea.  The account in which the
        ## service runs should not own its own install directory, because
        ## it could modify its own code. 
        #if uid != -1 or gid != -1:
        #    os.chown(sys.prefix,uid,gid)
        #    dirs = os.walk(sys.prefix)
        #    for path, dirnames, filenames in dirs:
        #        for dir in dirnames:
        #            os.chown(os.path.join(path, dir),uid,gid)
        #        for fname in filenames:
        #            os.chown(os.path.join(path, fname),uid,gid)

        # make world readable and make directories world cd'able (i.e., world executable)
        dirs = os.walk(sys.prefix)
        for path, dirnames, filenames in dirs:
            for dir in dirnames:
                dir = os.path.join(path,dir)
                mode = os.stat(dir).st_mode
                mode = S_IMODE(mode)
                mode |= S_IRUSR | S_IRGRP | S_IROTH | S_IXUSR | S_IXGRP | S_IXOTH
                os.chmod(dir,mode)
            for fname in filenames:
                fname = os.path.join(path, fname)
                mode = os.stat(fname).st_mode
                mode |= S_IRUSR | S_IRGRP | S_IROTH 
                os.chmod(fname, mode)

        # create symbolic links between /opt packages.
        if symlinks:
            pkg_path = os.path.join(sys.prefix, name)
            for src in symlinks:
                lnk_name = os.path.split(src)[1]
                lnk_path = os.path.join(pkg_path,lnk_name)
                if os.path.islink(lnk_path):
                   os.remove(lnk_path)
                os.symlink(src,lnk_path)

        # create pid dir.
        pid_dir = os.path.join("/var/run/", name )
        if not os.path.exists(pid_dir):
            os.mkdir(pid_dir)
            os.chown(pid_dir,uid,gid)

