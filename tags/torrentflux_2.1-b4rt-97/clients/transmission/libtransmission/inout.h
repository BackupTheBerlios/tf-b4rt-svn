/******************************************************************************
 * $Id: inout.h 346 2006-06-13 00:28:03Z titer $
 *
 * Copyright (c) 2005-2006 Transmission authors and contributors
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

#ifndef TR_IO_H
#define TR_IO_H 1

typedef struct tr_io_s tr_io_t;

void      tr_ioLoadResume  ( tr_torrent_t * );

tr_io_t * tr_ioInit        ( tr_torrent_t * );
int       tr_ioRead        ( tr_io_t *, int, int, int, uint8_t * );
int       tr_ioWrite       ( tr_io_t *, int, int, int, uint8_t * );
void      tr_ioClose       ( tr_io_t * );
void      tr_ioSaveResume  ( tr_io_t * );

#endif
