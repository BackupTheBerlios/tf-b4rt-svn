<?php

/*************************************************************
*  TorrentFlux - PHP Torrent Manager
*  www.torrentflux.com
**************************************************************/
/*
    This file is part of TorrentFlux.

    TorrentFlux is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    TorrentFlux is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with TorrentFlux; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

include_once("config.php");
include_once("functions.php");


//******************************************************************************
// showIndex -- main view
//******************************************************************************
function showIndex() {
    global $cfg, $db;
    $hideChecked = "";
    if ($cfg["hide_offline"] == 1) {
        $hideChecked = "checked";
    }

    echo DisplayHead($cfg["user"]."'s "._PROFILE);

    echo "<div align=\"center\">";
    echo "<table border=1 bordercolor=\"".$cfg["table_admin_border"]."\" cellpadding=\"2\" cellspacing=\"0\" width=\"760\">";
    echo "<tr><td colspan=6 bgcolor=\"".$cfg["table_data_bg"]."\" background=\"themes/".$cfg["theme"]."/images/bar.gif\">";
    echo "<img src=\"images/properties.png\" width=18 height=13 border=0>&nbsp;&nbsp;<font class=\"title\">".$cfg["user"]."'s "._PROFILE."</font>";
    echo "</td></tr><tr><td align=\"center\">";

    $total_activity = GetActivityCount();

    $sql= "SELECT user_id, hits, last_visit, time_created, user_level FROM tf_users WHERE user_id=".$db->qstr($cfg["user"]);
    list($user_id, $hits, $last_visit, $time_created, $user_level) = $db->GetRow($sql);

    $user_type = _NORMALUSER;
    if (IsAdmin()) {
        $user_type = _ADMINISTRATOR;
    }
    if (IsSuperAdmin()) {
        $user_type = _SUPERADMIN;
    }

    $user_activity = GetActivityCount($cfg["user"]);

    if ($user_activity == 0) {
        $user_percent = 0;
    } else {
        $user_percent = number_format(($user_activity/$total_activity)*100);
    }

?>

    <table width="100%" border="0" cellpadding="3" cellspacing="0">
    <tr>

        <!-- left column -->
        <td width="50%" bgcolor="<?php echo $cfg["table_data_bg"] ?>" valign="top">
        <div align="center">
        <table border="0" cellpadding="0" cellspacing="0">
        <tr>
            <td align="right"><?php echo _JOINED ?>:&nbsp;</td>
            <td><strong><?php echo date(_DATETIMEFORMAT, $time_created) ?></strong></td>
        </tr>
        <tr>
            <td colspan="2" align="center">&nbsp;</td>
        </tr>
        <tr>
            <td align="right"><?php echo _UPLOADPARTICIPATION ?>:&nbsp;</td>
            <td>
                <table width="200" border="0" cellpadding="0" cellspacing="0">
                <tr>
                    <td background="themes/<?php echo $cfg["theme"] ?>/images/proglass.gif" width="<?php echo $user_percent*2 ?>"><img src="images/blank.gif" width="1" height="12" border="0"></td>
                    <td background="themes/<?php echo $cfg["theme"] ?>/images/noglass.gif" width="<?php echo (200 - ($user_percent*2)) ?>"><img src="images/blank.gif" width="1" height="12" border="0"></td>
                </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td align="right"><?php echo _UPLOADS ?>:&nbsp;</td>
            <td><strong><?php echo $user_activity ?></strong></td>
        </tr>
        <tr>
            <td align="right"><?php echo _PERCENTPARTICIPATION ?>:&nbsp;</td>
            <td><strong><?php echo $user_percent ?>%</strong></td>
        </tr>
        <tr>
            <td colspan="2" align="center"><div align="center" class="tiny">(<?php echo _PARTICIPATIONSTATEMENT. " ".$cfg['days_to_keep']." "._DAYS ?>)</div><br></td>
        </tr>
        <tr>
            <td align="right"><?php echo _TOTALPAGEVIEWS ?>:&nbsp;</td>
            <td><strong><?php echo $hits ?></strong></td>
        </tr>
        <tr>
            <td align="right"><?php echo _USERTYPE ?>:&nbsp;</td>
            <td><strong><?php echo $user_type ?></strong></td>
        </tr>
        <tr>
            <td colspan="2" align="center">
                <table>
                    <tr>
                        <td align="center">
                            <BR />[ <a href="?op=showCookies">Cookie Management</a> ]
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        </table>
        </div>
        </td>

        <!-- right column -->
        <td valign="top">
        <div align="center">
        <table cellpadding="5" cellspacing="0" border="0">
        <form name="theForm" action="profile.php?op=updateProfile" method="post" onsubmit="return validateProfile()">
        <tr>
            <td align="right"><?php echo _USER ?>:</td>
            <td>
            <input readonly="true" type="Text" value="<?php echo $cfg["user"] ?>" size="15">
            </td>
        </tr>
        <tr>
            <td align="right"><?php echo _NEWPASSWORD ?>:</td>
            <td>
            <input name="pass1" type="Password" value="" size="15">
            </td>
        </tr>
        <tr>
            <td align="right"><?php echo _CONFIRMPASSWORD ?>:</td>
            <td>
            <input name="pass2" type="Password" value="" size="15">
            </td>
        </tr>
        <tr>
            <td align="right"><?php echo _THEME ?>:</td>
            <td>
            <select name="theme">
<?php
    $arThemes = GetThemes();
    for($inx = 0; $inx < sizeof($arThemes); $inx++)
    {
        $selected = "";
        if ($cfg["theme"] == $arThemes[$inx])
        {
            $selected = "selected";
        }
        echo "<option value=\"".$arThemes[$inx]."\" ".$selected.">".$arThemes[$inx]."</option>";
    }
?>
            </select>
            </td>
        </tr>
                <tr>
            <td align="right"><?php echo _LANGUAGE ?>:</td>
            <td>
            <select name="language">
<?php
    $arLanguage = GetLanguages();
    for($inx = 0; $inx < sizeof($arLanguage); $inx++)
    {
        $selected = "";
        if ($cfg["language_file"] == $arLanguage[$inx])
        {
            $selected = "selected";
        }
        echo "<option value=\"".$arLanguage[$inx]."\" ".$selected.">".GetLanguageFromFile($arLanguage[$inx])."</option>";
    }
?>
            </select>
            </td>
        </tr>
        <tr>
            <td colspan="2">
            <input name="hideOffline" type="Checkbox" value="1" <?php echo $hideChecked ?>> <?php echo _HIDEOFFLINEUSERS ?><br>
            </td>
        </tr>
        <tr>
            <td align="center" colspan="2">
            <input type="Submit" value="<?php echo _UPDATE ?>">
            </td>
        </tr>
        </form>

        </table>

        </div>
        </td>
    </tr>

    <!-- user-settings -->
    <tr><td colspan="2" align="center" bgcolor="<?php echo($cfg["table_border_dk"]); ?>"><strong><?php echo($cfg["user"]); ?>'s Settings</strong></td></tr>
    <tr><td colspan="2"><br></td></tr>
    <tr><td colspan="2">

    <form name="settingsForm" action="profile.php?op=updateSettingsUser" method="post">
    <table border="0" cellpadding="3" cellspacing="0" width="100%">

        <tr><td colspan="2" align="center" bgcolor="<?php echo($cfg["table_header_bg"]); ?>"><strong>Index-Page</strong></td></tr>

        <tr>
            <td align="left" width="350" valign="top"><strong>Select index-page</strong><br>
            Select the index-Page.
            </td>
            <td valign="top">
                <?php printIndexPageSelectForm(); ?>
            </td>
        </tr>

        <tr>
            <td align="left" width="350" valign="top"><strong>index-page settings</strong><br>
            Select the columns in transfer-list on index-Page.<br>(only for b4rt-index-page)
            </td>
            <td valign="top">
                <?php printIndexPageSettingsForm(); ?>
            </td>
        </tr>

        <tr>
            <td align="left" width="350" valign="top"><strong>Width</strong><br>
            Specify the width of the index-page. (780):
            </td>
            <td valign="bottom">
                <input name="ui_dim_main_w" type="Text" maxlength="5" value="<?php echo($cfg["ui_dim_main_w"]); ?>" size="5">
            </td>
        </tr>
        <tr>
            <td align="left" width="350" valign="top"><strong>Display Links</strong><br>
            Display Links on the index-page. (true):
            </td>
            <td valign="bottom">
                <select name="ui_displaylinks">
                        <option value="1">true</option>
                        <option value="0" <?php
                        if (!$cfg["ui_displaylinks"])
                        {
                            echo "selected";
                        }
                        ?>>false</option>
                </select>
            </td>
        </tr>
        <tr>
            <td align="left" width="350" valign="top"><strong>Display Users</strong><br>
            Display Users on the index-page. (true):
            </td>
            <td valign="bottom">
                <select name="ui_displayusers">
                        <option value="1">true</option>
                        <option value="0" <?php
                        if (!$cfg["ui_displayusers"])
                        {
                            echo "selected";
                        }
                        ?>>false</option>
                </select>
            </td>
        </tr>
        <tr>
            <td align="left" width="350" valign="top"><strong>Select Drivespace-Bar</strong><br>
            Select Style of Drivespace-Bar on index-Page.
            </td>
            <td valign="top">
                <?php printDrivespacebarSelectForm(); ?>
            </td>
        </tr>
        <tr>
            <td align="left" width="350" valign="top"><strong>Show Server Stats</strong><br>
            Enable showing the server stats at the bottom:
            </td>
            <td valign="top">
                <select name="index_page_stats">
                        <option value="1">true</option>
                        <option value="0" <?php
                        if (!$cfg["index_page_stats"])
                        {
                            echo "selected";
                        }
                        ?>>false</option>
                </select>
            </td>
        </tr>

        <tr>
            <td align="left" width="350" valign="top"><strong>Show Server Load</strong><br>
            Enable showing the average server load over the last 15 minutes:
            </td>
            <td valign="top">
                <select name="show_server_load">
                        <option value="1">true</option>
                        <option value="0" <?php
                        if (!$cfg["show_server_load"])
                        {
                            echo "selected";
                        }
                        ?>>false</option>
                </select>
            </td>
        </tr>

        <tr>
            <td align="left" width="350" valign="top"><strong>Show Connections</strong><br>
            Enable showing the Sum of TCP-Connections:
            </td>
            <td valign="top">
                <select name="index_page_connections">
                        <option value="1">true</option>
                        <option value="0" <?php
                        if (!$cfg["index_page_connections"])
                        {
                            echo "selected";
                        }
                        ?>>false</option>
                </select>
            </td>
        </tr>

        <tr>
            <td align="left" width="350" valign="top"><strong>Use Refresh</strong><br>
            Use meta-refresh on index-page. (true):
            </td>
            <td valign="bottom">
                <select name="ui_indexrefresh">
                        <option value="1">true</option>
                        <option value="0" <?php
                        if (!$cfg["ui_indexrefresh"])
                        {
                            echo "selected";
                        }
                        ?>>false</option>
                </select>
            </td>
        </tr>
        <tr>
            <td align="left" width="350" valign="top"><strong>Page Refresh (in seconds)</strong><br>
            Number of seconds before the torrent list page refreshes:
            </td>
            <td valign="top">
                <input name="page_refresh" type="Text" maxlength="3" value="<?php echo($cfg["page_refresh"]); ?>" size="3">
            </td>
        </tr>

        <tr>
            <td align="left" width="350" valign="top"><strong>Select Sort-Order</strong><br>
            Select default Sort-Order of transfers on index-Page.
            </td>
            <td valign="top">
                <?php printSortOrderSettingsForm(); ?>
            </td>
        </tr>
        <tr>
            <td align="left" width="350" valign="top"><strong>Enable sorttable</strong><br>
            Enable Client-Side sorting of Transfer-Table:
            </td>
            <td valign="top">
                <select name="enable_sorttable">
                        <option value="1">true</option>
                        <option value="0" <?php
                        if (!$cfg["enable_sorttable"])
                        {
                            echo "selected";
                        }
                        ?>>false</option>
                </select>
            </td>
        </tr>

        <tr>
           <td align="left" width="350" valign="top"><strong>Enable Good looking statistics</strong><br>
           Enable/Disable "Good looking statistics" :
           </td>
           <td valign="top">
               <select name="enable_goodlookstats">
                       <option value="1">true</option>
                       <option value="0" <?php
                       if (!$cfg["enable_goodlookstats"])
                       {
                           echo "selected";
                       }
                       ?>>false</option>
               </select>
           </td>
        </tr>

        <tr>
           <td align="left" width="350" valign="top"><strong>Good looking statistics settings</strong><br>
           Configure Settings of "Good looking statistics" :
           </td>
           <td valign="top">
            <?php printGoodLookingStatsForm(); ?>
           </td>
        </tr>

        <tr>
           <td align="left" width="350" valign="top"><strong>Enable Big bold drivespace warning</strong><br>
           Enable/Disable "Big bold drivespace warning" :
           </td>
           <td valign="top">
               <select name="enable_bigboldwarning">
                       <option value="1">true</option>
                       <option value="0" <?php
                       if (!$cfg["enable_bigboldwarning"])
                       {
                           echo "selected";
                       }
                       ?>>false</option>
               </select>
           </td>
        </tr>

        <tr><td colspan="2" align="center" bgcolor="<?php echo($cfg["table_header_bg"]); ?>"><strong>Download-Details</strong></td></tr>
        <tr>
            <td align="left" width="350" valign="top"><strong>Width</strong><br>
            Specify the width of the details-popup. (450):
            </td>
            <td valign="bottom">
                <input name="ui_dim_details_w" type="Text" maxlength="5" value="<?php echo($cfg["ui_dim_details_w"]); ?>" size="5">
            </td>
        </tr>
        <tr>
            <td align="left" width="350" valign="top"><strong>Height</strong><br>
            Specify the height of the details-popup. (290):
            </td>
            <td valign="bottom">
                <input name="ui_dim_details_h" type="Text" maxlength="5" value="<?php echo($cfg["ui_dim_details_h"]); ?>" size="5">
            </td>
        </tr>

        <tr><td colspan="2" align="center" bgcolor="<?php echo($cfg["table_header_bg"]); ?>"><strong>Misc</strong></td></tr>

        <tr>
            <td align="left" width="350" valign="top"><strong>Default Torrent Search Engine</strong><br>
            Select the default search engine for torrent searches:
            </td>
            <td valign="top">
                <?php echo buildSearchEngineDDL($cfg["searchEngine"]); ?>
            </td>
        </tr>

        <!-- move hack settings -->
        <?php if ($cfg["enable_move"] != 0) { ?>
            <script src="move_extensionSettings.js" type="text/javascript"></script>
    		<tr>
            	<td align="left" width="350" valign="top"><strong>Move Settings</strong><br>
            	<u>Note :</u> You must specify absolute paths here. relative paths are not valid.<br>
            	<u>Note :</u> The created dirs will not be deleted after removing a entry from the List.
            	</td>
        		<td valign="top">
                 <?php printMoveSettingsForm(); ?>
    		    </td>
    		</tr>
        <?php } ?>

        <tr>
            <td align="left" width="350" valign="top"><strong>Display TorrentFlux Link</strong><br>
            Display TorrentFlux Link at bottom of pages. (true):
            </td>
            <td valign="bottom">
                <select name="ui_displayfluxlink">
                        <option value="1">true</option>
                        <option value="0" <?php
                        if (!$cfg["ui_displayfluxlink"])
                        {
                            echo "selected";
                        }
                        ?>>false</option>
                </select>
            </td>
        </tr>

        <tr><td colspan="2"><hr noshade></td></tr>
        <tr>
            <td align="center" colspan="2">
                <input type="Submit" value="Update Settings">
            </td>
        </tr>
    </table>
    </form>

    </td></tr>
    <!-- user-settings -->

    </table>

    <script language="JavaScript">
    function validateProfile()
    {
        var msg = ""
        if (theForm.pass1.value != "" || theForm.pass2.value != "")
        {
            if (theForm.pass1.value.length <= 5 || theForm.pass2.value.length <= 5)
            {
                msg = msg + "* <?php echo _PASSWORDLENGTH ?>\n";
                theForm.pass1.focus();
            }
            if (theForm.pass1.value != theForm.pass2.value)
            {
                msg = msg + "* <?php echo _PASSWORDNOTMATCH ?>\n";
                theForm.pass1.value = "";
                theForm.pass2.value = "";
                theForm.pass1.focus();
            }
        }

        if (msg != "")
        {
            alert("<?php echo _PLEASECHECKFOLLOWING ?>:\n\n" + msg);
            return false;
        }
        else
        {
            return true;
        }
    }
    </script>

<?php
    echo "</td></tr>";
    echo "</table></div><br><br>";

    DisplayFoot();
}


//******************************************************************************
// updateProfile -- update profile
//******************************************************************************
function updateProfile($pass1, $pass2, $hideOffline, $theme, $language)
{
    global $cfg;

    if ($pass1 != "")
    {
        $_SESSION['user'] = md5($cfg["pagetitle"]);
    }

    UpdateUserProfile($cfg["user"], $pass1, $hideOffline, $theme, $language);

    echo DisplayHead($cfg["user"]."'s "._PROFILE);

    echo "<div align=\"center\">";
    echo "<table border=1 bordercolor=\"".$cfg["table_admin_border"]."\" cellpadding=\"2\" cellspacing=\"0\" bgcolor=\"".$cfg["table_data_bg"]."\" width=\"760\">";
    echo "<tr><td colspan=6 background=\"themes/".$cfg["theme"]."/images/bar.gif\">";
    echo "<img src=\"images/properties.png\" width=18 height=13 border=0>&nbsp;&nbsp;<font class=\"title\">".$cfg["user"]."'s "._PROFILE."</font>";
    echo "</td></tr><tr><td align=\"center\">";
?>
    <br>
    <?php echo _PROFILEUPDATEDFOR." ".$cfg["user"] ?>
    <br><br>
<?php
    echo "</td></tr>";
    echo "</table></div><br><br>";

    DisplayFoot();
}


//******************************************************************************
// ShowCookies -- show cookies for user
//******************************************************************************
function ShowCookies()
{
    global $cfg, $db;
    echo DisplayHead($cfg["user"] . "'s "._PROFILE);

    $cid = @ $_GET["cid"]; // Cookie ID

    // Used for when editing a cookie
    $hostvalue = $datavalue = "";
    if( !empty( $cid ) ) {
        // Get cookie information from database
        $cookie = getCookie( $cid );
        $hostvalue = " value=\"" . $cookie['host'] . "\"";
        $datavalue = " value=\"" . $cookie['data'] . "\"";
    }

?>
<SCRIPT LANGUAGE="JavaScript">
    <!-- Begin
    function popUp(name_file)
    {
        window.open (name_file,'help','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=no,width=800,height=600')
    }
    // End -->
</script>
<div align="center">[<a href="?">Return to Profile</a>]</div>
<br />
<div align="center">
    <form action="?op=<?php echo ( !empty( $cid ) ) ? "modCookie" : "addCookie"; ?>"" method="post">
    <input type="hidden" name="cid" value="<?php echo $cid;?>" />
    <table border="1" bordercolor="<?php echo $cfg["table_admin_border"];?>" cellpadding="2" cellspacing="0" bgcolor="<?php echo $cfg["table_data_bg"];?>">
        <tr>
            <td colspan="3" bgcolor="<?php echo $cfg["table_header_bg"];?>" background="themes/<? echo $cfg["theme"] ?>/images/bar.gif">
                <img src="images/properties.png" width=18 height=13 border=0 align="absbottom">&nbsp;<font class="title">Cookie Management</font>
            </td>
        </tr>
        <tr>
            <td width="80" align="right">&nbsp;Host:</td>
            <td>
                <input type="Text" size="50" maxlength="255" name="host"<?php echo $hostvalue;?>><BR />
            </td>
            <td>
                www.host.com
            </td>
        </tr>
        <tr>
            <td width="80" align="right">&nbsp;Data:</td>
            <td>
                <input type="Text" size="50" maxlength="255" name="data"<?php echo $datavalue;?>><BR />
            </td>
            <td>
                uid=123456;pass=a1b2c3d4e5f6g7h8i9j1
            </td>
        </tr>
        <tr>
            <td>&nbsp;</td>
            <td colspan="2">
                <input type="Submit" value="<?php echo ( !empty( $cid ) ) ? _UPDATE : "Add"; ?>">
            </td>
        </tr>
<?php
    // We are editing a cookie, so have a link back to cookie list
    if( !empty( $cid ) )
    {
?>
        <tr>
            <td colspan="3">
                <center>[ <a href="?op=editCookies">back</a> ]</center>
            </td>
        </tr>
<?php
    }
    else
    {
?>
        <tr>
            <td colspan="3">
                <table border="1" bordercolor="<?php echo $cfg["table_admin_border"];?>" cellpadding="2" cellspacing="0" bgcolor="<?php echo $cfg["table_data_bg"];?>" width="100%">
                    <tr>
                        <td style="font-weight: bold; padding-left: 3px;" width="50">Action</td>
                        <td style="font-weight: bold; padding-left: 3px;">Host</td>
                        <td style="font-weight: bold; padding-left: 3px;">Data</td>
                    </tr>
<?php
        // Output the list of cookies in the database
        $sql = "SELECT c.cid, c.host, c.data FROM tf_cookies AS c, tf_users AS u WHERE u.uid=c.uid AND u.user_id='" . $cfg["user"] . "'";
        $dat = $db->GetAll( $sql );
        if( empty( $dat ) )
        {
?>
                <tr>
                    <td colspan="3">No cookie entries exist.</td>
                </tr>
<?php
        }
        else
        {
            foreach( $dat as $cookie )
            {
?>
                    <tr>
                        <td>
                            <a href="?op=deleteCookie&cid=<?php echo $cookie["cid"];?>"><img src="images/delete_on.gif" width=16 height=16 border=0 title="<?php echo _DELETE . " " . $cookie["host"]; ?>" align="absmiddle"></a>
                            <a href="?op=editCookies&cid=<?php echo $cookie["cid"];?>"><img src="images/properties.png" width=18 height=13 border=0 title="<?php echo _EDIT . " " . $cookie["host"]; ?>" align="absmiddle"></a>
                        </td>
                        <td><?php echo $cookie["host"];?></td>
                        <td><?php echo $cookie["data"];?></td>
                    </tr>
<?php
            }
        }
?>
                </table>
            </td>
        </tr>
<?php
    }
?>
        <tr>
            <td colspan="3">
                <br>
                <div align="center">
                <A HREF="javascript:popUp('cookiehelp.php')">How to get cookie information....</A>
                </div>
            </td>
        </tr>
        </table>
        </form>
    </div>
    <br />
    <br />
    <br />
<?php
    DisplayFoot();
}

//******************************************************************************
// updateSettingsUser -- update per user settings
//******************************************************************************
function updateSettingsUser() {
    global $cfg;
    $settings = processSettingsParams();
    saveUserSettings($cfg["uid"],$settings);
    AuditAction( $cfg["constants"]["admin"], "updated per user settings for ".$cfg["user"]);
    header( "location: profile.php" );
}

//******************************************************************************
// addCookie -- adding a Cookie Host Information
//******************************************************************************
function addCookie( $newCookie ) {
    if( !empty( $newCookie ) ) {
        global $cfg;
        AddCookieInfo( $newCookie );
        AuditAction( $cfg["constants"]["admin"], "New Cookie: " . $newCookie["host"] . " | " . $newCookie["data"] );
    }
    header( "location: profile.php?op=showCookies" );
}

//******************************************************************************
// deleteCookie -- delete a Cookie Host Information
//******************************************************************************
function deleteCookie($cid) {
    global $cfg;
    $cookie = getCookie( $cid );
    deleteCookieInfo( $cid );
    AuditAction( $cfg["constants"]["admin"], _DELETE . " Cookie: " . $cookie["host"] );
    header( "location: profile.php?op=showCookies" );
}

//******************************************************************************
// modCookie -- edit a Cookie Host Information
//******************************************************************************
function modCookie($cid,$newCookie) {
    global $cfg;
    modCookieInfo($cid,$newCookie);
    AuditAction($cfg["constants"]["admin"], "Modified Cookie: ".$newCookie["host"]." | ".$newCookie["data"]);
    header("location: profile.php?op=showCookies");
}

//****************************************************************************
//****************************************************************************
//****************************************************************************
//****************************************************************************
// TRAFFIC CONTROLER
$op = getRequestVar('op');

switch ($op) {
    default:
        showIndex();
        exit;
    break;

    // update per user settings
    case "updateSettingsUser":
        updateSettingsUser();
    break;

    case "updateProfile":
        $pass1 = getRequestVar('pass1');
        $pass2 = getRequestVar('pass2');
        $hideOffline = getRequestVar('hideOffline');
        $theme = getRequestVar('theme');
        $language = getRequestVar('language');
        updateProfile($pass1, $pass2, $hideOffline, $theme, $language);
    break;

    // Show main Cookie Management
    case "showCookies":
    case "editCookies":
        showCookies();
    break;

    // Add a new cookie to user
    case "addCookie":
        $newCookie["host"] = getRequestVar('host');
        $newCookie["data"] = getRequestVar('data');
        addCookie( $newCookie );
    break;

    // Modify an existing cookie from user
    case "modCookie":
        $newCookie["host"] = getRequestVar( 'host' );
        $newCookie["data"] = getRequestVar( 'data' );
        $cid = getRequestVar( 'cid' );
        modCookie( $cid, $newCookie );
    break;

    // Delete selected cookie from user
    case "deleteCookie":
        $cid = $_GET["cid"];
        deleteCookie( $cid );
    break;

}
//****************************************************************************
//****************************************************************************
//****************************************************************************
//****************************************************************************


?>