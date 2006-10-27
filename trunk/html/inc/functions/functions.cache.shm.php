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

// size of shared memory to allocate (ok : 16384 ; max : 131072)
define("_WEBAPP_CACHE_SHM_SIZE", 16384);

/*
// sem-key
define("_WEBAPP_CACHE_SEM_KEY", ftok(__FILE__, 'e'));

// shm-key
define("_WEBAPP_CACHE_SHM_KEY", ftok(__FILE__, 'h'));
*/

// shm-id
define("_WEBAPP_CACHE_SHM_ID", 0x8457);


// vars in session :

// $_SESSION['shm'][$username]['sem_key']
// $_SESSION['shm'][$username]['shm_key']

// $_SESSION['shm'][$username]['sem_id']
// $_SESSION['shm'][$username]['shm_id']


/**
 * check if cache set
 *
 * @param $username
 * @return boolean
 */
function cacheIsSet($username) {
	return isset($_SESSION['shm'][$username]['shm_id']);

	/*
	if (isset($_SESSION['shm'][$username]['shm_id']))
		return (shm_get_var($_SESSION['shm'][$username]['shm_id'], 1) !== FALSE);
	else
		return false;
	*/

}

/**
 * init cfg from cache
 *
 * @param $username
 */
function cacheInit($username) {
	global $cfg;

	/*
	// get config
	$cfg = shm_get_var($_SESSION['shm'][$username]['shm_id'], 1);
	if ($cfg === false)
		die("Fail to get cfg from Shared memory ".$_SESSION['shm'][$username]['shm_id'].".\n");
	*/

	//
	if (!($mkey = shm_attach(_WEBAPP_CACHE_SHM_ID, _WEBAPP_CACHE_SHM_SIZE, OctDec("666")))) {
	    echo "shmem_attach failed<br>\n";
	    exit;
	}

	//
	if (!($skey = sem_get(_WEBAPP_CACHE_SHM_ID, 1, OctDec("666")))) {
	    echo "sem_get failed<br>\n";
	    exit;
	}

	//
	if (!sem_acquire($skey)) {
	    echo "sem_acquire failed<br>\n";
	    exit;
	}

	//
	$cfg = shm_get_var($mkey, 1);

	//
	sem_release($skey);

	// session-id
	$_SESSION['shm'][$username]['shm_id'] = _WEBAPP_CACHE_SHM_ID;
}

/**
 * set the cache
 *
 * @param $username
 */
function cacheSet($username) {
	global $cfg;

	/*
	// init cache
	cacheShmInit($username);

	// put config
	if (!shm_put_var($_SESSION['shm'][$username]['shm_id'], 1, $cfg)) {
		sem_remove($_SESSION['shm'][$username]['sem_id']);
		shm_remove($_SESSION['shm'][$username]['shm_id']);
		die("Fail to put cfg to Shared memory ".$_SESSION['shm'][$username]['shm_id'].".\n");
	}
	*/

	//
	if (!($mkey = shm_attach(_WEBAPP_CACHE_SHM_ID, _WEBAPP_CACHE_SHM_SIZE, OctDec("666")))) {
	    echo "shmem_attach failed<br>\n";
	    exit;
	}

	//
	if (!($skey = sem_get(_WEBAPP_CACHE_SHM_ID, 1, OctDec("666")))) {
	    echo "sem_get failed<br>\n";
	    exit;
	}

	//
	if (!sem_acquire($skey)) {
	    echo "sem_acquire failed<br>\n";
	    exit;
	}

	// put config
	if (!shm_put_var($mkey, 1, $cfg)) {
		die("Fail to put cfg to Shared memory ".$mkey.".\n");
	}

	// Zugriff freigeben
	sem_release($skey);

	// session-id
	$_SESSION['shm'][$username]['shm_id'] = _WEBAPP_CACHE_SHM_ID;
}

/**
 * flush the cache
 *
 * @param $username
 */
function cacheFlush($username = "") {
	if (empty($username))
		return true;

	/*
	// remove var
	shm_remove_var($_SESSION['shm'][$username]['shm_id'], 1);

	// destroy
	cacheShmDestroy($username);
	*/

	// session-id
	unset($_SESSION['shm'][$username]['shm_id']);
}

/**
 * init shared-mem-cache
 *
 * @param $username
 * @return boolean
 */
function cacheShmInit($username) {

	// Get semaphore
	$sem_id = sem_get(_WEBAPP_CACHE_SEM_KEY, 1);
	if ($sem_id === false)
		die("Fail to get semaphore.\n");

	// save id in session-var
	$_SESSION['shm'][$username]['sem_id'] = $sem_id;

	// acquire semaphore
	if (! sem_acquire($sem_id)) {
		sem_remove($sem_id);
		die("Fail to acquire semaphore ".$sem_id.".\n");
	}

	// attach
	$shm_id = shm_attach(_WEBAPP_CACHE_SHM_KEY, _WEBAPP_CACHE_SHM_SIZE);
	if ($shm_id === false) {
		sem_remove($sem_id);
		shm_remove($shm_id);
		die("Fail to attach shared memory.\n");
	}

	// save id in session-var
	$_SESSION['shm'][$username]['shm_id'] = $shm_id;

	// return
	return true;
}

/**
 * destroy shared-mem-cache
 *
 * @param $username
 * @return boolean
 */
function cacheShmDestroy($username = "") {

	$retVal = true;

	// get ids
	$sem_id = $_SESSION['shm'][$username]['sem_id'];
	$shm_id = $_SESSION['shm'][$username]['shm_id'];

	// Release semaphore
	if (!(sem_release($sem_id) === true))
		$retVal = false;

	// remove shared memory segment from SysV
	if (!(shm_remove($shm_id) === true))
		$retVal = false;

	// detach
	//shm_detach($shm_id);

	// Remove semaphore
	if (!(sem_remove($sem_id) === true))
		$retVal = false;

	// return
	return $retVal;
}

?>