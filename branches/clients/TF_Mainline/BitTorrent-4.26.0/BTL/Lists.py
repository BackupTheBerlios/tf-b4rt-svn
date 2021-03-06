# QList:
# basically a python 2.3 compatible interface if you want deque
#
# SizedList:
# handy class for keeping a fixed-length history
# uses deque if available
#
# by Greg Hazel

try:
    from collections import deque
    base_list_class = deque
    popleft = deque.popleft
    clear = deque.clear
    def pop(q, n):
        q.rotate(-n)
        q.popleft()
        q.rotate(n) 
    def remove(q, item):
        for i, v in enumerate(q):
            if v == item:
                q.pop(i)
                break
        else:
            raise ValueError(q.__class__ + ".remove(x): x not in list")
except ImportError:
    from UserList import UserList
    base_list_class = UserList
    def popleft(l):
        return l.pop(0)
    def clear(l):
        l[:] = []
    pop = UserList.pop
    remove = UserList.remove
    

class QList(base_list_class):

    def __init__(self, *a, **kw):
        base_list_class.__init__(self, *a, **kw)

    def clear(self): return clear(self)
    def pop(self, n): return pop(self, n)
    def popleft(self): return popleft(self)    
    def remove(self, item): return remove(self, item)

    # dequeu doesn't have __add__ ?
    # overload anyway to get a base_list_class
    def __add__(self, l):
        n = base_list_class(self)
        n.extend(l)
        return n


# I use QList becuase deque.popleft is faster than list.pop(0)
class SizedList(QList):

    def __init__(self, max_items):
        self.max_items = max_items
        QList.__init__(self)

    def append(self, v):
        QList.append(self, v)
        if len(self) > self.max_items:
            self.popleft()        


def collapse(seq):
    start = None
    current = None
    for i in seq:
        if start is not None and i > (current + 1):
            yield start, current + 1
            start = i
        elif start is None:
            start = i
        current = i
    if start is not None:
        yield start, current + 1
        
            
if __name__ == '__main__':
    l = SizedList(10)
    for i in xrange(50):
        l.append(i)
    assert list(l) == range(40, 50)