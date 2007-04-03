<?php

/* $Id$ */

/*******************************************************************************

 LICENSE

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License (GPL)
 as published by the Free Software Foundation; either version 2
 of the License, or (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 GNU General Public License for more details.

 To read the license please visit http://www.gnu.org/copyleft/gpl.html

*******************************************************************************/

// main.internal
require_once("inc/main.internal.php");

// all functions
require_once('inc/functions/functions.all.php');

// time-limit
@set_time_limit(0);

// action-switch
$action = (isset($_REQUEST['action'])) ? $_REQUEST['action'] : "---";
switch ($action) {

/*******************************************************************************
 * dummy
 ******************************************************************************/
    case "---":
    	break;

/*******************************************************************************
 * single transfer ops
 ******************************************************************************/
    case "start":
		dispatcher_startTransfer(urldecode(getRequestVar('transfer')));
    	break;
    case "delete":
    	dispatcher_deleteTransfer(urldecode(getRequestVar('transfer')));
    	break;
    case "stop":
    	dispatcher_stopTransfer(urldecode(getRequestVar('transfer')));
    	break;
    case "forceStop":
    	dispatcher_forceStopTransfer(urldecode(getRequestVar('transfer')), getRequestVar('pid'));
    	break;
    case "restart":
    	dispatcher_restartTransfer(urldecode(getRequestVar('transfer')));
    	break;
    case "deQueue":
    	dispatcher_deQueueTransfer(urldecode(getRequestVar('transfer')));
    	break;
    case "setFilePriority":
		dispatcher_setFilePriority(urldecode(getRequestVar('transfer')));
    	break;

/*******************************************************************************
 * injects
 ******************************************************************************/
	case "fileUpload":
		dispatcher_processUpload();
    	break;
    case "urlUpload":
		dispatcher_processDownload(getRequestVarRaw('url'), getRequestVar('type'));
    	break;
    case "wget":
		dispatcher_injectWget(getRequestVar('url'));
    	break;

/*******************************************************************************
 * metafile-download
 ******************************************************************************/
	case "metafileDownload":
		dispatcher_sendMetafile(getRequestVar('transfer'));
    	break;

/*******************************************************************************
 * set
 ******************************************************************************/
    case "set":
    	dispatcher_set(getRequestVar('key'), getRequestVar('val'));
    	break;

/*******************************************************************************
 * Maintenance
 ******************************************************************************/
    case "maintenance":
		require_once("inc/classes/MaintenanceAndRepair.php");
		MaintenanceAndRepair::maintenance((getRequestVar('trestart') == "true") ? true : false);
		// set transfers-cache
		cacheTransfersSet();
    	break;

/*******************************************************************************
 * Cache-Flush
 ******************************************************************************/
    case "cacheFlush":
    	// flush session-cache
		cacheFlush();
		// flush transfers-cache (not really needed as reload is triggered)
		cacheTransfersFlush();
    	break;

/*******************************************************************************
 * Cookie-Flush
 ******************************************************************************/
    case "cookieFlush":
		@setcookie("autologin", "", time() - 3600);
    	break;

/*******************************************************************************
 * bulk operations
 ******************************************************************************/
    case "bulkStop":
    	dispatcher_bulk("stop");
    	break;
    case "bulkResume":
    	dispatcher_bulk("resume");
    	break;
    case "bulkStart":
    	dispatcher_bulk("start");
    	break;

/*******************************************************************************
 * multi operations
 ******************************************************************************/
    default:
    	dispatcher_multi($action);
    	break;
}

/*******************************************************************************
 * exit
 ******************************************************************************/
dispatcher_exit();

?>