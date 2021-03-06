/******************************************************************************
 * $Id: platform.h 920 2006-09-25 18:37:45Z joshe $
 *
 * Copyright (c) 2005 Transmission authors and contributors
 *
 * Permission is hereby granted, free of charge, to any person obtaining a
 * copy of this software and associated documentation files (the "Software"),
 * to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense,
 * and/or sell copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
 * DEALINGS IN THE SOFTWARE.
 *****************************************************************************/
#ifndef TR_PLATFORM_H
#define TR_PLATFORM_H 1

#ifdef SYS_BEOS
  #include <kernel/OS.h>
  typedef thread_id tr_thread_t;
  typedef sem_id    tr_lock_t;
#else
  #include <pthread.h>
  typedef pthread_t       tr_thread_t;
  typedef pthread_mutex_t tr_lock_t;
#endif

char * tr_getCacheDirectory();
char * tr_getTorrentsDirectory();

void tr_threadCreate ( tr_thread_t *, void (*func)(void *), void * arg );
void tr_threadJoin   ( tr_thread_t * );
void tr_lockInit     ( tr_lock_t * );
void tr_lockClose    ( tr_lock_t * );

static inline void tr_lockLock( tr_lock_t * l )
{
#ifdef SYS_BEOS
    acquire_sem( *l );
#else
    pthread_mutex_lock( l );
#endif
}

static inline void tr_lockUnlock( tr_lock_t * l )
{
#ifdef SYS_BEOS
    release_sem( *l );
#else
    pthread_mutex_unlock( l );
#endif
}

int
tr_getDefaultRoute( struct in_addr * addr );

#endif
