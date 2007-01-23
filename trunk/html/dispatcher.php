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
 * index-page ops
 ******************************************************************************/
    case "indexStart":
		indexStartTransfer(urldecode(getRequestVar('transfer')));
    	break;
    case "indexUrlUpload":
		indexProcessDownload(urldecode(getRequestVar('url')), getRequestVar('type'));
    	break;
    case "indexDelete":
    	indexDeleteTransfer(urldecode(getRequestVar('transfer')));
    	break;
    case "indexStop":
    	indexStopTransfer(urldecode(getRequestVar('transfer')));
    	break;
    case "indexDeQueue":
    	indexDeQueueTransfer(urldecode(getRequestVar('transfer')));
    	break;
    case "wget":
		indexInjectWget(getRequestVar('url'));
    	break;

/*******************************************************************************
 * force-Stop
 ******************************************************************************/
    case "forceStop":
    	forceStopTransfer(urldecode(getRequestVar('transfer')), getRequestVar('pid'));
    	break;

/*******************************************************************************
 * set prio
 ******************************************************************************/
    case "setFilePriority":
		dispatcherSetFilePriority(getRequestVar('transfer'));
    	break;

/*******************************************************************************
 * file-upload
 ******************************************************************************/
	case "fileUpload":
		processFileUpload();
    	break;

/*******************************************************************************
 * metafile-download
 ******************************************************************************/
	case "metafileDownload":
		sendMetafile(getRequestVar('transfer'));
    	break;

/*******************************************************************************
 * set
 ******************************************************************************/
    case "set":
    	dispatcherSet(getRequestVar('key'), getRequestVar('val'));
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
    	dispatcherBulk("stop");
    	break;
    case "bulkResume":
    	dispatcherBulk("resume");
    	break;
    case "bulkStart":
    	dispatcherBulk("start");
    	break;

/*******************************************************************************
 * multi operations
 ******************************************************************************/
    default:
    	dispatcherMulti($action);
    	break;
}

/*******************************************************************************
 * redirect
 ******************************************************************************/
if (isset($_SERVER["HTTP_REFERER"]))
	@header("location: ".$_SERVER["HTTP_REFERER"]);
else
	@header("location: index.php?iid=index");

?>