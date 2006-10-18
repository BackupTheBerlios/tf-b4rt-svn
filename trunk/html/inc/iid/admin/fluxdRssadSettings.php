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

// prevent direct invocation
if (!isset($cfg['user'])) {
	@ob_end_clean();
	header("location: ../../../index.php");
	exit();
}

/******************************************************************************/

// create template-instance
$tmpl = tmplGetInstance($cfg["theme"], "page.admin.fluxdRssadSettings.tmpl");

// message section
$message = getRequestVar('m');
if ((isset($message)) && ($message != "")) {
	$tmpl->setvar('new_msg', 1);
	$tmpl->setvar('message', urldecode($message));
} else {
	$tmpl->setvar('new_msg', 0);
}

// Rssad
$rssad = FluxdServiceMod::getFluxdServiceModInstance($cfg, $fluxd, 'Rssad');

// pageop
//
// * default
//
// * addFilter
// * editFilter
// * saveFilter
// * deleteFilter 
//
// * addJob
// * editJob
// * saveJob
// * deleteJob
//
$pageop = getRequestVar('pageop');
if (empty($pageop))
	$tmpl->setvar('pageop', "default");
else
	$tmpl->setvar('pageop', $pageop);

// op-switch
switch ($pageop) {
	default:
	case "default":
		// filters
		$filters = $rssad->filterGetList();
		if ($filters !== false) {
			$filterlist = array();
			foreach ($filters as $filter) {
				$filt = trim($filter);
				if (strlen($filt) > 0)
					array_push($filterlist, array("filtername" => $filt));
			}
			$tmpl->setloop('rssad_filters', $filterlist);
		}
		// jobs
		$jobs = $rssad->jobsGetList();
		if ($jobs !== false)
			$tmpl->setloop('rssad_jobs', $jobs);
		// title-bar
		tmplSetTitleBar("Administration - Fluxd Rssad Settings");
		break;
	case "addFilter":
		$filtername = getRequestVar('filtername');
		if (empty($filtername)) {
			$tmpl->setvar('new_msg', 1);
			$tmpl->setvar('message', "Error : No Filtername.");
		} else {
			if ($rssad->filterIdCheck($filtername, true) === true) {
				$filterstring = $filtername;
				$maxFiles = 100;
				$noMatch = true;
				$idx = 1;
				while ($noMatch) {
					if ($rssad->filterExists($filtername) === false) {
						$tmpl->setvar('filtername', $filtername);
						$tmpl->setvar('rssad_filtercontent', "");
						$noMatch = false;
					} else {
						$filtername = $filterstring."_".$idx;
					}
					$idx++;
					if ($idx >= $maxFiles) {
						$noMatch = false;
						$tmpl->setvar('new_msg', 1);
						$tmpl->setvar('message', "Error : Invalid Filtername.");
					}
				}
			} else {
				$tmpl->setvar('new_msg', 1);
				$tmpl->setvar('message', "Error : Invalid Filtername.");
			}
		}
		// title-bar
		tmplSetTitleBar("Administration - Fluxd Rssad - Add Filter");
		break;
	case "editFilter":
		$filtername = getRequestVar('filtername');
		if (empty($filtername)) {
			$tmpl->setvar('new_msg', 1);
			$tmpl->setvar('message', "Error : No Filtername.");
		} else {
			if ($rssad->filterIdCheck($filtername, false) === true) {
				// create the filter
				if ($rssad->filterExists($filtername) === true) {
					$tmpl->setvar('filtername', $filtername);
					$content = trim($rssad->filterGetContent($filtername));
					$tmpl->setvar('rssad_filtercontent', $content);
					$filterlines = explode("\n", $content);
					if (count($filterlines) > 0) {
						$filterlist = array();
						foreach ($filterlines as $filterline) {
							$filt = trim($filterline);
							if (strlen($filt) > 0)
								array_push($filterlist, array("filter" => $filt));
						}
						$tmpl->setloop('rssad_filter_list', $filterlist);
					}
				} else {
					$tmpl->setvar('new_msg', 1);
					$tmpl->setvar('message', "Error : Filter does not exist.");
				}
			} else {
				$tmpl->setvar('new_msg', 1);
				$tmpl->setvar('message', "Error : Invalid Filtername.");
			}
		}
		// title-bar
		tmplSetTitleBar("Administration - Fluxd Rssad - Edit Filter");
		break;		
	case "saveFilter":
		$filtername = getRequestVar('filtername');
		$filtercontent = getRequestVar('rssad_filtercontent');
		$new = getRequestVar('new');
		if (empty($filtername)) {
			$tmpl->setvar('new_msg', 1);
			$tmpl->setvar('message', "Error : No Filtername.");
		} else {
			$isnew = false;
			if ($new == true)
				$isnew = true;
			else
				$isnew = false;
			if ($rssad->filterIdCheck($filtername, $isnew) === true) {
				// save the filter
				$tmpl->setvar('filtername', $filtername);
				if (($rssad->filterSave($filtername, $filtercontent)) === true) {
					$tmpl->setvar('filter_saved', 1);
					$tmpl->setvar('filtercontent', $filtercontent);
				} else {
					$tmpl->setvar('filter_saved', 0);
					$tmpl->setvar('messages', $rssad->messages);
				}
			} else {
				$tmpl->setvar('new_msg', 1);
				$tmpl->setvar('message', "Error : Invalid Filtername.");
			}
		}
		// title-bar
		tmplSetTitleBar("Administration - Fluxd Rssad - Save Filter");
		break;
	case "deleteFilter":
		$filtername = getRequestVar('filtername');
		if (empty($filtername)) {
			$tmpl->setvar('new_msg', 1);
			$tmpl->setvar('message', "Error : No Filtername.");
		} else {
			if ($rssad->filterIdCheck($filtername, false) === true) {
				// delete the filter
				$tmpl->setvar('filtername', $filtername);
				if (($rssad->filterDelete($filtername)) === true) {
					$tmpl->setvar('filter_deleted', 1);
				} else {
					$tmpl->setvar('filter_deleted', 0);
					$tmpl->setvar('messages', $rssad->messages);
				}
			} else {
				$tmpl->setvar('new_msg', 1);
				$tmpl->setvar('message', "Error : Invalid Filtername.");
			}
		}
		// title-bar
		tmplSetTitleBar("Administration - Fluxd Rssad - Delete Filter");
		break;
		
	case "addJob":
		
		// title-bar
		tmplSetTitleBar("Administration - Fluxd Rssad - Add Job");		
		break;
		
	case "editJob":
		$jobNumber = getRequestVar('job');
		if (empty($jobNumber)) {
			$tmpl->setvar('new_msg', 1);
			$tmpl->setvar('message', "Error : No Job-Number.");
			$tmpl->setvar('rssad_job_loaded', 0);
		} else {
			// TODO
			$tmpl->setvar('rssad_job_loaded', 1);
		}		
		// title-bar
		tmplSetTitleBar("Administration - Fluxd Rssad - Edit Job");		
		break;
		
	case "saveJob":
		
		// title-bar
		tmplSetTitleBar("Administration - Fluxd Rssad - Save Job");		
		break;
		
	case "deleteJob":
		
		// title-bar
		tmplSetTitleBar("Administration - Fluxd Rssad - Delete Job");		
		break;						
		
}

//
$tmpl->setvar('enable_dereferrer', $cfg["enable_dereferrer"]);
//
tmplSetAdminMenu();
tmplSetFoot();

// parse template
$tmpl->pparse();

?>