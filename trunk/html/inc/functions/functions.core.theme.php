<?php

/* $Id: functions.core.theme.php 2835 2007-04-08 13:20:05Z b4rt $ */

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

/**
 * Get the theme that is avaible and can be used
 *
 * @return string
 */
function CheckandSetUserTheme()
{
	global $cfg;
	
	if( isset($cfg["theme"]) && is_dir("themes/".$cfg["theme"]))
	{
		$theme = $cfg["theme"];
	}
	elseif( isset($cfg["default_theme"]) && is_dir("themes/".$cfg["default_theme"]))
	{
		$theme = $cfg["default_theme"];
		$msg = "Your choosen theme does not exist any more. Please got to your Profile Settings and change your theme.";
	}
	elseif ( is_dir("themes/default") )
	{
		$theme = "default";
		$msg = "Your choosen theme does not exist any more. Please got to your Profile Settings and change your theme.<br>
				The default theme does not exist any more. System Administrator has to change default theme.";
	}
	else
		die("Fatal Error: No suitable theme could be found and included.<br />Please check your Files.");
	
	// This complettely breaks theme validation, but i haven't found a quick solution to get 
	// an error message displayed on all sites. I think we first need to change the theme-engine to be more flexible. -danez
	if( isset($msg) ) echo $msg; 	
	return $theme;
}

/**
 * Get the  default theme that is avaible and can be used
 *
 * @return string
 */
function CheckandSetDefaultTheme()
{
	global $cfg;
	
	if( isset($cfg["default_theme"]) && is_dir("themes/".$cfg["default_theme"]))
	{
		$theme = $cfg["default_theme"];
	}
	elseif ( is_dir("themes/default") )
	{
		$theme = "default";
		$msg = "The default theme does not exist any more. System Administrator has to change default theme.";
	}
	else
		die("Fatal Error: No suitable theme could be found and included.<br />Please check your Files.");
	
	// This complettely breaks theme validation, but i haven't found a quick solution to get 
	// an error message displayed on all sites. I think we first need to change the theme-engine to be more flexible. -danez
	if( isset($msg) ) echo $msg; 	
	return $theme;
}

?>