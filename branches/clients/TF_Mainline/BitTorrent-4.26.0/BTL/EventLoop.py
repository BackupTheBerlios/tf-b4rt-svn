import sys
import Queue
import BTL.stackthreading as threading
from BTL import defer
from BTL.yielddefer import launch_coroutine, wrap_task

class EventLoop(object):
    def __init__(self):
        self.thread = threading.Thread(target=self.run)
        self.queue = Queue.Queue()
        self.killswitch = threading.Event()

    def __getattr__(self, attr):
        return getattr(self.thread, attr)
    
    def add_task(self, _f, *a, **kw):
        self.queue.put((_f, a, kw))

    def exit(self):
        self.killswitch.set()
        self.add_task(lambda : None)

    def run(self):

        while not self.killswitch.isSet():
            func, args, kwargs = self.queue.get(True)

            try:
                v = func(*args, **kwargs)
            except:
                # interpreter shutdown
                if not sys:
                    return
                exc_type, value, tb = sys.exc_info()
                threading._print_traceback(sys.stderr, self.stack,
                                           "thread %s" % self.getName(), 1,
                                           exc_type, value, tb)
                del tb


class RoutineLoop(object):

    def __init__(self, queue_task):
        self.killswitch = threading.Event()
        self.queue = defer.DeferredQueue()
        self.main_df = launch_coroutine(queue_task, self.run)

    def add_task(self, _f, *a, **kw):
        df = _f(*a, **kw)
        self.queue.put((df,))

    def add_deferred(self, df):
        self.queue.put((df,))

    def exit(self):
        self.killswitch.set()
        self.add_deferred(defer.succeed(True))

    def run(self):

        while not self.killswitch.isSet():
            event_df = self.queue.get()
            yield event_df
            (df,) = event_df.getResult()
            
            yield df
            try:
                r = df.getResult()
            except:
                # interpreter shutdown
                if not sys:
                    return
                exc_type, value, tb = sys.exc_info()
                threading._print_traceback(sys.stderr, self.stack,
                                           "thread %s" % self.getName(), 1,
                                           exc_type, value, tb)
                del tb
        
    