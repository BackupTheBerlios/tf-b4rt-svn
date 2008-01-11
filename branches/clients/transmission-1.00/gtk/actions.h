/*
 * This file Copyright (C) 2007-2008 Charles Kerr <charles@rebelbase.com>
 *
 * This file is licensed by the GPL version 2.  Works owned by the
 * Transmission project are granted a special exemption to clause 2(b)
 * so that the bulk of its code can remain under the MIT license. 
 * This exemption does not extend to derived works not owned by
 * the Transmission project.
 * 
 * $Id: actions.h 4404 2008-01-01 17:20:20Z charles $
 */

#ifndef TR_ACTIONS_H
#define TR_ACTIONS_H

#include <gtk/gtk.h>
#include "tr_core.h"

void actions_init ( GtkUIManager * ui_manager, gpointer callback_user_data );

void actions_set_core( TrCore * core );

void action_activate ( const char * name );

void action_sensitize ( const char * name, gboolean b );

void action_toggle ( const char * name, gboolean b );

GtkWidget* action_get_widget ( const char * path );

#endif
