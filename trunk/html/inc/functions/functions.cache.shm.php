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

// size of shared memory to allocate (ok : 16384)
define("_WEBAPP_CACHE_SHM_SIZE", 16384);

// shm-id
//define("_WEBAPP_CACHE_SHM_ID", ftok(__FILE__, 'b'));
define("_WEBAPP_CACHE_SHM_ID", 0x8457);

/**
 * check if cache set
 *
 * @param $username
 * @return boolean
 */
function cacheIsSet($username) {
	return isset($_SESSION['shm_key']);
}

/**
 * init cfg from cache
 *
 * @param $username
 */
function cacheInit($username) {
	global $cfg;

	// attach
	if (!($mkey = shm_attach(_WEBAPP_CACHE_SHM_ID)))
	    die("shmem_attach failed\n");

	// get cfg from shared mem
	$cfg = shm_get_var($mkey, 1);
}

/**
 * set the cache
 *
 * @param $username
 */
function cacheSet($username) {
	global $cfg;

	// attach
	if (!($mkey = shm_attach(_WEBAPP_CACHE_SHM_ID, _WEBAPP_CACHE_SHM_SIZE, 0666)))
	    die("shmem_attach failed\n");

	// save id in session-var
	$_SESSION['shm_key'] = $mkey;

	// get sem
	if (!($skey = sem_get(_WEBAPP_CACHE_SHM_ID, 1, 0666)))
	    die("sem_get failed\n");

	// acquire sem
	if (!sem_acquire($skey))
	    die("sem_acquire failed\n");

	// put cfg to shared mem
	if (!shm_put_var($mkey, 1, $cfg))
		die("Fail to put cfg to Shared memory ".$mkey.".\n");

	// release sem
	if (!sem_release($skey))
		die("sem_release failed\n");
}

/**
 * flush the cache
 *
 * @param $username
 */
function cacheFlush($username = "") {

	// keys
	if (!($skey = sem_get(_WEBAPP_CACHE_SHM_ID, 1)))
	    die("sem_get failed\n");

	if (!($mkey = shm_attach(_WEBAPP_CACHE_SHM_ID)))
	    die("shmem_attach failed\n");

	// error-array
	$errors = array();

	// remove var
	shm_remove_var(_WEBAPP_CACHE_SHM_ID, 1);

	// Release semaphore
	sem_release($skey);

	// remove shared memory segment from SysV
	if (!(shm_remove($mkey) === true))
		array_push($errors, "Failed : shm_remove");

	// detach
	if (!(shm_detach($mkey) === true))
		array_push($errors, "Failed : shm_detach");

	// Remove semaphore
	if (!(sem_remove($skey) === true))
		array_push($errors, "Failed : sem_remove");

	// session-id
	unset($_SESSION['shm_key']);

	// check for errors
	if (count($errors) > 0) {
		foreach ($errors as $errorMessage)
			echo $errorMessage."\n";
	}
}

?>