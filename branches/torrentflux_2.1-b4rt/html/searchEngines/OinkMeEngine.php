<?php

/* $Id$ */

/*************************************************************
*  TorrentFlux PHP Torrent Manager
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

include_once("SearchEngineBase.php");

class SearchEngine extends SearchEngineBase
{

    function SearchEngine($cfg)
    {
        $this->mainURL = "oink.me.uk";
        $this->altURL = "oink.me.uk";
        $this->mainTitle = "OinkMe";
        $this->engineName = "OinkMe";

        $this->author = "JimmyTheGrunt, and Anglachel";
        $this->version = "1.11";
        $this->updateURL = "http://www.torrentflux.com/forum/index.php/topic,1096.0.html";
        //$this->updateURL = "http://www.torrentflux.com/forum/index.php";

        $this->Initialize($cfg);

    }


    //----------------------------------------------------------------
    // Function to Get Main Categories
    function populateMainCategories()
    {
        $this->mainCatalog["41"] = "Audio Books";
        $this->mainCatalog["44"] = "Comedy";
        $this->mainCatalog["33"] = "eBooks";
        $this->mainCatalog["81"] = "eLearning Books";
        $this->mainCatalog["82"] = "eLearning Videos";
        $this->mainCatalog["69"] = "Mac Apps";
        $this->mainCatalog["62"] = "Music Apps";
        $this->mainCatalog["35"] = "Phone";
        $this->mainCatalog["70"] = "Unix Apps";
        $this->mainCatalog["22"] = "Win Apps";
        $this->mainCatalog["47"] = "60s";
        $this->mainCatalog["48"] = "70s";
        $this->mainCatalog["49"] = "80s";
        $this->mainCatalog["50"] = "90s";
        $this->mainCatalog["39"] = "Alternative";
        $this->mainCatalog["56"] = "Ambient";
        $this->mainCatalog["73"] = "Asian";
        $this->mainCatalog["53"] = "Blues";
        $this->mainCatalog["78"] = "Breaks";
        $this->mainCatalog["43"] = "Classical";
        $this->mainCatalog["34"] = "Country";
        $this->mainCatalog["24"] = "Dance";
        $this->mainCatalog["72"] = "Discography";
        $this->mainCatalog["55"] = "Drum 'n' Bass";
        $this->mainCatalog["6"] = "Electronic";
        $this->mainCatalog["58"] = "Emo";
        $this->mainCatalog["63"] = "Experimental";
        $this->mainCatalog["59"] = "Folk";
        $this->mainCatalog["75"] = "Funk";
        $this->mainCatalog["60"] = "Garage";
        $this->mainCatalog["76"] = "Hardcore";
        $this->mainCatalog["30"] = "Hip-Hop/Rap";
        $this->mainCatalog["40"] = "House";
        $this->mainCatalog["51"] = "IDM";
        $this->mainCatalog["38"] = "Indie";
        $this->mainCatalog["57"] = "Industrial";
        $this->mainCatalog["77"] = "J-Music";
        $this->mainCatalog["37"] = "Jazz";
        $this->mainCatalog["65"] = "Kids";
        $this->mainCatalog["31"] = "Metal";
        $this->mainCatalog["25"] = "Misc";
        $this->mainCatalog["27"] = "OST";
        $this->mainCatalog["1"] = "Pop";
        $this->mainCatalog["46"] = "Pre 60s";
        $this->mainCatalog["80"] = "Psychedelic";
        $this->mainCatalog["42"] = "Punk";
        $this->mainCatalog["28"] = "R'n'B";
        $this->mainCatalog["32"] = "Reggae";
        $this->mainCatalog["23"] = "Rock";
        $this->mainCatalog["61"] = "Ska";
        $this->mainCatalog["45"] = "Soul";
        $this->mainCatalog["71"] = "Techno";
        $this->mainCatalog["66"] = "Trance";
        $this->mainCatalog["74"] = "Trip Hop";
        $this->mainCatalog["64"] = "UK Garage";
        $this->mainCatalog["54"] = "World/Ethnic";
    }

    //----------------------------------------------------------------
    // Function to get Latest..
    function getLatest()
    {

        $cat = getRequestVar('mainGenre');

        if (empty($cat)) $cat = getRequestVar('cat');

        $request = "/browse.php";

        if(!empty($cat))
        {
            if(strpos($request,"?"))
            {
                $request .= "&cat=".$cat;
            }
            else
            {
                $request .= "?cat=".$cat;
            }
        }

        if (!empty($this->pg))
        {
            if(strpos($request,"?"))
            {
                $request .= "&page=" . $this->pg;
            }
            else
            {
                $request .= "?page=" . $this->pg;
            }
        }

        if ($this->makeRequest($request,true))
        {
            if (strlen($this->htmlPage) > 0 )
            {
              return $this->parseResponse();
            }
            else
            {
              return 'Unable to Browse at this time.';
            }
        }
        else
        {
           return $this->msg;
        }
    }

    //----------------------------------------------------------------
    // Function to perform Search.
    function performSearch($searchTerm)
    {

        // create the request string.
        $searchTerm = str_replace(" ", "+", $searchTerm);
        $request = "/browse.php?search=".$searchTerm;

        if(!empty($cat))
        {
            $request .= "&cat=".$cat;
        }

        $incldead = getRequestVar('incldead');
        if (empty($incldead)) $incldead = "0";
        $request .= "&incldead=".$incldead;

        if (!empty($this->pg))
        {
            $request .= "&page=" . $this->pg;
        }


        if ($this->makeRequest($request,true))
        {
            return $this->parseResponse();
        }
        else
        {
            return $this->msg;
        }
    }

    //----------------------------------------------------------------
    // Override the base to show custom table header.
    // Function to setup the table header
    function tableHeader()
    {
        $output = "<table width=\"100%\" cellpadding=3 cellspacing=0 border=0>";

        $output .= "<br>\n";

        $output .= "<tr bgcolor=\"".$this->cfg["bgLight"]."\">";
        if ($needWait)
        {
            $output .= "  <td colspan=8 align=center>";
        }
        else
        {
            $output .= "  <td colspan=7 align=center>";
        }



        if (is_integer(strpos($this->htmlPage,"Welcome back, ")))
        {
          $userinfo = substr($this->htmlPage,strpos($this->htmlPage,"Welcome back, ")+strlen("Welcome back, "));
          $userinfo = substr($userinfo,strpos($userinfo,"<br />")+strlen("<br />"));
          $userinfo = substr($userinfo,0,strpos($userinfo,"<br />"));
          //$userinfo = substr($userinfo,strpos($userinfo,"<br>")+strlen("<br>"));
          //$userinfo = str_replace("<font class=\"font_10px\">","",$userinfo);
          //$userinfo = str_replace("</font>","",$userinfo);
          //$userinfo = str_replace("<br>","",$userinfo);
                //$output .= "<tr bgcolor=\"".$this->cfg["table_header_bg"]."\">";
          $output .= $userinfo;
        }
        $output .= "</td></tr>";

        $output .= "<tr bgcolor=\"".$this->cfg["table_header_bg"]."\">";
        $output .= "  <td>&nbsp;</td>";
        $output .= "  <td><strong>Torrent Name</strong> &nbsp;(";

        $tmpURI = str_replace(array("?hideSeedless=yes","&hideSeedless=yes","?hideSeedless=no","&hideSeedless=no"),"",$_SERVER["REQUEST_URI"]);

        // Check to see if Question mark is there.
        if (strpos($tmpURI,'?'))
        {
            $tmpURI .= "&";
        }
        else
        {
            $tmpURI .= "?";
        }

        if($this->hideSeedless == "yes")
        {
            $output .= "<a href=\"". $tmpURI . "hideSeedless=no\">Show Seedless</a>";
        }
        else
        {
            $output .= "<a href=\"". $tmpURI . "hideSeedless=yes\">Hide Seedless</a>";
        }

        $output .= ")</td>";
        $output .= "  <td><strong>Category</strong></td>";
        $output .= "  <td align=center><strong>&nbsp;&nbsp;Size</strong></td>";
        $output .= "  <td><strong>Seeds</strong></td>";
        $output .= "  <td><strong>Peers</strong></td>";
        $output .= "  <td><strong>Snatched</strong></td>";
        $output .= "</tr>\n";

        return $output;
    }

    //----------------------------------------------------------------
    // Function to parse the response.
    function parseResponse($latest = true)
    {


        $thing = $this->htmlPage;

        if(strpos($thing,"Not logged in!") > 0)
        {
            $tmpStr = substr($thing,strpos($thing,"takelogin"));
            $tmpStr = substr($tmpStr,strpos($tmpStr, ">")+1);
            $tmpStr2 = "<form method=\"post\" action=\"http://".$this->mainURL."/takelogin.php\">";
            $tmpStr = substr($tmpStr,0,strpos($tmpStr,"</form>")+strlen("</form>"));
            $output = $tmpStr2.str_replace("src=\"","src=\"http://".$this->mainURL."/",$tmpStr)."</table>";

        }
        else
        {

            $output = $this->tableHeader();

            if(strpos($thing,"Error:") > 0)
            {
                $tmpStr = substr($thing,strpos($thing,"Error:")+strlen("Error:"));
                $tmpStr = substr($tmpStr,0,strpos($tmpStr,"</p>"));
                $this->msg = strip_tags($tmpStr);
                return $output . "<center>".$this->msg."</center><br>";
            }

            // We got a response so display it.
            // Chop the front end off.
            $thing = substr($thing,strpos($thing,">Upped&nbsp;by<"));

            $thing = substr($thing,strpos($thing,"<tr>")+strlen("<tr>"));

            //$tmpList = substr($thing,0,strpos($thing,"</table>"));
            // ok so now we have the listing.
            $tmpListArr = split("</tr>",$thing);

            $bg = $this->cfg["bgLight"];
            //var_export($tmpListArr);
            foreach($tmpListArr as $key =>$value)
            {

                $buildLine = true;
                if (strpos($value,"id="))
                {
                    $ts = new OinkMe($value);

                    // Determine if we should build this output
                    /*if (is_int(array_search($ts->MainId,$this->catFilter)))
                    {
                        $buildLine = false;
                    }

                    if ($this->hideSeedless == "yes")
                    {
                        if($ts->Seeds == "N/A" || $ts->Seeds == "0")
                        {
                            $buildLine = false;
                        }
                    }*/


                    if (!empty($ts->torrentFile) && $buildLine) {

                        $output .= trim($ts->BuildOutput($bg,$this->searchURL()));

                        // ok switch colors.
                        if ($bg == $this->cfg["bgLight"])
                        {
                            $bg = $this->cfg["bgDark"];
                        }
                        else
                        {
                            $bg = $this->cfg["bgLight"];
                        }
                    }

                }
            }

            // set thing to end of this table.
            $thing = substr($thing,strpos($thing,"</table>"));

            $output .= "</table>";

            // is there paging at the bottom?
            if (strpos($thing, "page=") != false)
            {
                // Yes, then lets grab it and display it!  ;)

                $pages = substr($thing,strpos($thing,"<p"));
                $pages = substr($pages,strpos($pages,">"));
                $pages = substr($pages,0,strpos($pages,"</p>"));

                $pages = str_replace("&nbsp; ",'',$pages);

                $tmpPageArr = split("</a>",$pages);
                array_pop($tmpPageArr);

                $pagesout = '';
                foreach($tmpPageArr as $key => $value)
                {
                    $value .= "</a> &nbsp;";
                    $tmpVal = substr($value,strpos($value,"browse.php?"),strpos($value,">")-1);

                    $pgNum = substr($tmpVal,strpos($tmpVal,"page=")+strlen("page="));
                    $pagesout .= str_replace($tmpVal,"XXXURLXXX".$pgNum,$value);
                }

                $cat = getRequestVar('mainGenre');

                if(strpos($this->curRequest,"LATEST"))
                {
                    if (!empty($cat))
                    {
                        $pages = str_replace("XXXURLXXX",$this->searchURL()."&LATEST=1&cat=".$cat."&pg=",$pagesout);
                    }
                    else
                    {
                        $pages = str_replace("XXXURLXXX",$this->searchURL()."&LATEST=1&pg=",$pagesout);
                    }
                }
                else
                {
                    if(!empty($cat))
                    {
                        $pages = str_replace("XXXURLXXX",$this->searchURL()."&searchterm=".$_REQUEST["searchterm"]."&cat=".$cat."&pg=",$pagesout);

                    }
                    else
                    {
                        $pages = str_replace("XXXURLXXX",$this->searchURL()."&searchterm=".$_REQUEST["searchterm"]."&pg=",$pagesout);
                    }
                }
               // $pages = strip_tags($pages,"<a><b>");
                $output .= "<div align=center>".substr($pages,1)."</div>";
            }
        }
        return $output;
    }
}

// This is a worker class that takes in a row in a table and parses it.
class OinkMe
{
    var $torrentName = "";
    var $torrentDisplayName = "";
    var $torrentFile = "";
    var $torrentSize = "";
    var $torrentStatus = "";
    var $MainId = "";
    var $MainCategory = "";
    var $fileCount = "";
    var $Seeds = "";
    var $Peers = "";

    var $needsWait = false;
    var $waitTime = "";

    var $Data = "";

    var $torrentRating = "";

    function OinkMe( $htmlLine )
    {
        if (strlen($htmlLine) > 0)
        {

            $this->Data = $htmlLine;


            // Cleanup any bugs in the HTML
            $htmlLine = eregi_replace("</td>\n</td>",'</td>',$htmlLine);

            // Chunck up the row into columns.
            $tmpListArr = split("<td ",$htmlLine);

            //if(count($tmpListArr) > 12)
            //{

                $tmpStr = substr($tmpListArr["1"],strpos($tmpListArr["1"],"alt=\"")+strlen("alt=\"")); // MainCategory
                $this->MainCategory = substr($tmpStr,0,strpos($tmpStr,"\""));

                $tmpStr = substr($tmpListArr["1"],strpos($tmpListArr["1"],"cat=")+strlen("cat=")); // Main Id
                $this->MainId = substr($tmpStr,0,strpos($tmpStr,"\""));

                //$this->torrentName = $this->cleanLine("<td ".$tmpListArr["2"]."</td>");  // TorrentName

                $this->torrentName = substr($tmpListArr["2"],strpos($tmpListArr["2"],"<b>")+strlen("<b>"),strpos($tmpListArr["2"],"</b>"));

                $tmpStr = substr($tmpListArr["2"],strpos($tmpListArr["2"],"href=\"downloadpk")+strlen("href=\""));
                $this->torrentFile = "http://oink.me.uk/".substr($tmpStr,0,strpos($tmpStr,"\""));

                //$tmpStr = substr($tmpListArr["2"],strpos($tmpListArr["2"],"id=")+strlen("id=")); // File Id
                //$tmpStr = substr($tmpStr,0,strpos($tmpStr,"&"));

                //$this->torrentFile = "http://oink.me.uk/downloadpk/".$tmpStr."/".str_replace(" ","_",$this->torrentName).".torrent";

                $this->fileCount = $this->cleanLine("<td ".$tmpListArr["3"]."</td>");  // File Count

                $this->torrentSize = $this->cleanLine("<td ".$tmpListArr["6"]."</td>");  // Size of File

                $this->torrentStatus = $this->cleanLine(str_replace("<br>"," ","<td ".$tmpListArr["4"]."</td>"));  // Snatched

                $this->Seeds = $this->cleanLine("<td ".$tmpListArr["8"]."</td>");  // Seeds
                $this->Peers = $this->cleanLine("<td ".$tmpListArr["9"]."</td>");  // Leech

                /*if ($this->Peers == '')
                {
                    $this->Peers = "N/A";
                    if (empty($this->Seeds)) $this->Seeds = "N/A";
                }
                if ($this->Seeds == '') $this->Seeds = "N/A";*/

                $this->torrentDisplayName = $this->torrentName;
                if(strlen($this->torrentDisplayName) > 50)
                {
                    $this->torrentDisplayName = substr($this->torrentDisplayName,0,50)."...";
                }

           //}
           /*elseif (count($tmpListArr) > 11)
           {
                $tmpStr = substr($tmpListArr["1"],strpos($tmpListArr["1"],"alt=\"")+strlen("alt=\"")); // MainCategory
                $this->MainCategory = substr($tmpStr,0,strpos($tmpStr,"\""));

                $tmpStr = substr($tmpListArr["1"],strpos($tmpListArr["1"],"cat=")+strlen("cat=")); // Main Id
                $this->MainId = substr($tmpStr,0,strpos($tmpStr,"\""));

                $this->torrentName = $this->cleanLine("<td ".$tmpListArr["2"]."</td>");  // TorrentName

                $tmpStr = substr($tmpListArr["2"],strpos($tmpListArr["2"],"id=")+strlen("id=")); // File Id
                $tmpStr = substr($tmpStr,0,strpos($tmpStr,"&"));

                $this->torrentFile = "http://oink.me.uk/downloadpk/".$tmpStr."/".str_replace(" ","_",$this->torrentName).".torrent";

                $this->fileCount = $this->cleanLine("<td ".$tmpListArr["4"]."</td>");  // File Count

                $this->torrentSize = $this->cleanLine("<td ".$tmpListArr["7"]."</td>");  // Size of File

                $this->torrentStatus = $this->cleanLine(str_replace("<br>"," ","<td ".$tmpListArr["8"]."</td>"));  // Snatched

                $this->Seeds = $this->cleanLine("<td ".$tmpListArr["9"]."</td>");  // Seeds
                $this->Peers = $this->cleanLine("<td ".$tmpListArr["10"]."</td>");  // Leech

                if ($this->Peers == '')
                {
                    $this->Peers = "N/A";
                    if (empty($this->Seeds)) $this->Seeds = "N/A";
                }
                if ($this->Seeds == '') $this->Seeds = "N/A";

                $this->torrentDisplayName = $this->torrentName;
                if(strlen($this->torrentDisplayName) > 50)
                {
                    $this->torrentDisplayName = substr($this->torrentDisplayName,0,50)."...";
                }

           }*/
        }

    }

    function cleanLine($stringIn,$tags='')
    {
        if(empty($tags))
            return trim(str_replace(array("&nbsp;","&nbsp")," ",strip_tags($stringIn)));
        else
            return trim(str_replace(array("&nbsp;","&nbsp")," ",strip_tags($stringIn,$tags)));
    }

    //----------------------------------------------------------------
    // Function to build output for the table.
    function BuildOutput($bg, $searchURL = '')
    {
        $output = "<tr>\n";
        $output .= "    <td width=16 bgcolor=\"".$bg."\"><a href=\"index.php?url_upload=".$this->torrentFile."\"><img src=\"images/download_owner.gif\" width=\"16\" height=\"16\" title=\"".$this->torrentName." - ".$this->fileCount." "._FILE."\" border=0></a></td>\n";
        $output .= "    <td bgcolor=\"".$bg."\"><a href=\"index.php?url_upload=".$this->torrentFile."\" title=\"".$this->torrentName."\">".$this->torrentDisplayName."</a></td>\n";

        if (strlen($this->MainCategory) > 1){
            $genre = "<a href=\"".$searchURL."&mainGenre=".$this->MainId."\">".$this->MainCategory."</a>";
        }else{
            $genre = "";
        }

        $output .= "    <td bgcolor=\"".$bg."\">". $genre ."</td>\n";

        $output .= "    <td bgcolor=\"".$bg."\" align=right>".$this->torrentSize."</td>\n";
        $output .= "    <td bgcolor=\"".$bg."\" align=center>".$this->Seeds."</td>\n";
        $output .= "    <td bgcolor=\"".$bg."\" align=center>".$this->Peers."</td>\n";
        $output .= "    <td bgcolor=\"".$bg."\" align=center>".$this->torrentStatus."</td>\n";
        $output .= "</tr>\n";

        return $output;

    }
}

?>