/******************************************************************************
 * $Id: Badger.h 1913 2007-05-23 05:01:23Z livings124 $
 *
 * Copyright (c) 2006-2007 Transmission authors and contributors
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

#ifndef BADGER_H
#define BADGER_H

#import <Cocoa/Cocoa.h>
#import <transmission.h>

@interface Badger : NSObject
{
    tr_handle_t     * fLib;

    NSImage         * fDockIcon, * fBadge, * fUploadBadge, * fDownloadBadge;
    NSDictionary    * fAttributes;
    int             fCompleted, fCompletedBadged;
    BOOL            fSpeedBadge;
}

- (id) initWithLib: (tr_handle_t *) lib;

- (void) updateBadge;
- (void) incrementCompleted;
- (void) clearCompleted;
- (void) clearBadge;

@end

#endif