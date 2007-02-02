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

/*
    v 1.00 - Mar 31, 06 - Created
*/

class SearchEngine extends SearchEngineBase
{
    var $dateAdded = "";

    function SearchEngine($cfg)
    {
        $this->mainURL = "newnova.org";
        $this->altURL = "newnova.org";
        $this->mainTitle = "NewNova";
        $this->engineName = "NewNova";

        $this->author = "kboy";
        $this->version = "1.00";
        $this->updateURL = "http://www.torrentflux.com/forum/index.php?topic=1002.0.html";

        $this->Initialize($cfg);
    }

    //----------------------------------------------------------------
    // Function to Get Main Categories
    function populateMainCategories()
    {
        $this->mainCatalog["1"] = "Games";
        $this->mainCatalog["2"] = "Movies";
        $this->mainCatalog["3"] = "TV Shows";
        $this->mainCatalog["4"] = "Music";
        $this->mainCatalog["5"] = "Apps";
        $this->mainCatalog["6"] = "Miscellaneous";
        $this->mainCatalog["7"] = "Anime";
    }

    //----------------------------------------------------------------
    // Function to Get Sub Categories
    function getSubCategories($mainGenre)
    {
        $output = array();

        $mainGenreName = $this->GetMainCatName($mainGenre);

        if($mainGenre == "6")
        {
            $mainGenreName = "Misc";
        }

        $request = '/list_torrents/' . str_replace(" ","",strtolower($mainGenreName)) . '.html';

        if ($this->makeRequest($request))
        {
            $thing = $this->htmlPage;

            if (is_integer(strpos($thing,"seperator")))
            {
                $thing = substr($thing,strpos($thing,"seperator")+strlen("seperator"));
                $thing = substr($thing,strpos($thing,"<table"));
                $thing = substr($thing,0,strpos($thing,"</table>"));

                while (is_integer(strpos($thing,"href=\"JavaScript:showTbl2(")))
                {

                    $thing = substr($thing,strpos($thing,"href=\"JavaScript:showTbl2(")+strlen("href=\"JavaScript:showTbl2("));
                    $tmpStr = substr($thing,0,strpos($thing,')'));
                    $tmpArr = split(",",$tmpStr);

                    $subid = substr($tmpArr[0],strpos($tmpArr[0],'/')+1);
                    while(is_integer(strpos($subid,"/")))
                    {
                        $subid = substr($subid,strpos($subid,'/')+1);
                    }
                    $subid = str_replace(array("'"),"",$subid);
                    $subname = str_replace(array("'"),"",$tmpArr[1]);

                    if($subname != $mainGenreName)
                    {
                        $output[$subid] = $subname;
                    }
                }
            }
       }

        return $output;
    }

    //----------------------------------------------------------------
    // Function to get Latest..
    function getLatest()
    {
        //http://www.newnova.org/list_news.html
        $request = "/list_news.html";

        if (array_key_exists("mode",$_REQUEST))
        {
            if($_REQUEST["mode"] == "yesterday" )
            {
                $request = "/list_news_1.html";
            }
        }

        if (array_key_exists("subGenre",$_REQUEST))
        {
            $request = "/list_torrents/".$_REQUEST["subGenre"].".html";
        }

        if (array_key_exists("dteAdded",$_REQUEST))
        {
            $this->dateAdded = $_REQUEST["dteAdded"];
        }

        if ($this->makeRequest($request))
        {
          return $this->parseResponse();
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

        $request = "/search.foo?data[searchTerm]=".$searchTerm."&";

        if ($this->makeRequest($request))
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

        $output .= "<tr bgcolor=\"".$this->cfg["table_header_bg"]."\">";
        $output .= "<td colspan=7 align=center><strong>".$this->dateAdded."</strong></td>";
        $output .= "</tr>\n";

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
        $output .= "  <td align=center><strong>Kind</strong></td>";
        $output .= "</tr>\n";

        return $output;
    }

    //----------------------------------------------------------------
    // Function to parse the response.
    function parseResponse()
    {

        if (is_integer(strpos($this->htmlPage,"seperator")))
        {
            $dteAdded = substr($this->htmlPage,strpos($this->htmlPage,"seperator",20)+strlen("seperator"));
            $dteAdded = substr($dteAdded,strpos($dteAdded,">")+1);
            $this->dateAdded = substr($dteAdded,0,strpos($dteAdded,"<"));
        }
        else
        {
            if (!strlen($this->dateAdded) > 0)
            {
                $this->dateAdded = date(' j. F Y',time());
            }
        }

        $output = $this->tableHeader();

        $thing = $this->htmlPage;

        if (is_integer(strpos($thing,"Loading...")))
        {
            $thing = substr($thing,strpos($thing,"Loading..."));
            $thing = substr($thing,strpos($thing,"<script>")+strlen("<script>"));
            $tmpList = substr($thing,0,strpos($thing,"</script>"));
        }
        else
        {
            $tmpList = $thing;
        }

        // We got a response so display it.
        // Chop the front end off.
        if (is_integer(strpos($tmpList,"at2_nws_hdr")))
        {
            $output .= $this->nwsTableRows($tmpList);
        }
        elseif (is_integer(strpos($tmpList,"at2_header")))
        {
            $output .= $this->at2TableRows($tmpList);
        }
        else
        {
            if(is_integer(strpos($thing,"search returned no results")))
            {
                $output .= "<center>Your search returned no results. Please refine your search</center><br>";
            }
        }

        if (is_integer(strpos($thing,"seperator")))
        {
            $dteAdd = substr($thing,strpos($thing,"seperator",20)+strlen("seperator"));
            $dteAdd = substr($dteAdd,strpos($dteAdd,">")+1);
            $dteAdd = substr($dteAdd,0,strpos($dteAdd,"<"));
        }

        $output .= "</table>";

        $pages = '';

        if(array_key_exists("LATEST",$_REQUEST))
        {
            if (array_key_exists("mode",$_REQUEST))
            {
                if (! $_REQUEST["mode"] == "yesterday")
                {
                    $pages .= "<br><a href=\"".$this->searchURL()."&LATEST=1&mode=yesterday&dteAdded=".$dteAdd."\" title=\"Yesterday's Latest\">Yesterday's Latest</a>";
                }
            }
            else
            {
                $pages .= "<br><a href=\"".$this->searchURL()."&LATEST=1&mode=yesterday&dteAdded=".$dteAdd."\" title=\"Yesterday's Latest\">Yesterday's Latest</a>";
            }

            $output .= "<br><div align=center>".$pages."</div><br>";
        }

        return $output;
    }

    function at2TableRows($tmpList)
    {
        $output = '';
        $bg = $this->cfg["bgLight"];

        while (is_integer(strpos($tmpList,"at2_header")))
        {
            $tmpList = substr($tmpList,strpos($tmpList,"at2_header(")+strlen("at2_header('"));
            $curCat = substr($tmpList,0,strpos($tmpList,"'"));
            $tmpList = substr($tmpList,strpos($tmpList,"at2("));
            if (is_int(array_search($curCat,$this->catFilter)))
            {
                // Skip this category.
            }
            else
            {
                // We have something to-do.
                if (is_integer(strpos($tmpList,"at2_header(")))
                {
                    $tmpList2 = substr($tmpList,0,strpos($tmpList,"at2_header("));
                }
                elseif (is_integer(strpos($tmpList,"str.push")))
                {
                    $tmpList2 = substr($tmpList,0,strpos($tmpList,"str.push"));
                }

                //prepare line
                $tmpList2 = str_replace("at2(","",$tmpList2);

                // ok so now we have the listing.
                $tmpListArr = split(");",$tmpList2);

                $output .= $this->buildTableRows($tmpListArr, $bg);
            }

            // set tmpList to end of this category.
            if (is_integer(strpos($tmpList,"at2_header(")))
            {
                $tmpList = substr($tmpList,strpos($tmpList,"at2_header("));
            }
            elseif (is_integer(strpos($tmpList,"str.push")))
            {
                $tmpList =  substr($tmpList,strpos($tmpList,"str.push"));
            }

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
        return $output;
    }

    function nwsTableRows($tmpList)
    {
        $output = '';
        $bg = $this->cfg["bgLight"];

        while (is_integer(strpos($tmpList,"at2_nws_hdr")))
        {
            $tmpList = substr($tmpList,strpos($tmpList,"at2_nws_hdr(")+strlen("at2_nws_hdr('"));
            $curCat = substr($tmpList,0,strpos($tmpList,"'"));
            $tmpList = substr($tmpList,strpos($tmpList,"at2("));

            if (is_int(array_search($curCat,$this->catFilter)))
            {
                // Skip this category.
            }
            else
            {
                // We have something to-do.
                if (is_integer(strpos($tmpList,"at2_nws_hdr(")))
                {
                    $tmpList2 = substr($tmpList,0,strpos($tmpList,"at2_nws_hdr("));
                }
                elseif (is_integer(strpos($tmpList,"str.push")))
                {
                    $tmpList2 = substr($tmpList,0,strpos($tmpList,"str.push"));
                }

                //prepare line
                $tmpList2 = str_replace("at2(","",$tmpList2);

                // ok so now we have the listing.
                $tmpListArr = split(");",$tmpList2);

                $output .= $this->buildTableRows($tmpListArr, $bg);
            }

            // set tmpList to end of this category.
            if (is_integer(strpos($tmpList,"at2_nws_hdr(")))
            {
                $tmpList = substr($tmpList,strpos($tmpList,"at2_nws_hdr("));
            }
            elseif (is_integer(strpos($tmpList,"str.push")))
            {
                $tmpList =  substr($tmpList,strpos($tmpList,"str.push"));
            }

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
        return $output;
    }

    function buildTableRows($tmpListArr, $bg)
    {

        $output = "";

        foreach($tmpListArr as $key =>$value)
        {

            $buildLine = true;
            $ts = new tNova($value);

            // Determine if we should build this output
            if (is_int(array_search($ts->MainCategory,$this->catFilter)))
            {
                $buildLine = false;
            }

            if ($this->hideSeedless == "yes")
            {
                if($ts->Seeds == "N/A" || $ts->Seeds == "0")
                {
                    $buildLine = false;
                }
            }

            if (!empty($ts->torrentFile) && $buildLine) {

                $output .= trim($ts->BuildOutput($bg, $this->searchURL()));

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

        return $output;
    }
}


// This is a worker class that takes in a row in a table and parses it.
class tNova
{
    var $torrentName = "";
    var $torrentDisplayName = "";
    var $torrentFile = "";
    var $torrentSize = "";
    var $torrentStatus = "";
    var $MainId = "";
    var $MainCategory = "";
    var $SubCategory = "";

    var $fileCount = "";
    var $torrentAdded = "";
    var $Seeds = "";
    var $Peers = "";
    var $Data = "";

/*
   function at2 (
 0   torrent_id,
 1   torrent_additiondate_day,
 2   torrent_server_id,
 3   torrent_viewserver_id,
 4   torrent_view_link,
 5   torrent_link_desc,
 6   torrent_name,
 7   torrent_link,
 8   torrent_filesize_mb,
 9   torrent_lastnrseeds,
10   torrent_lastnrleeches,
11   torrent_quality,
12   torrent_submitter_id,
13   torrent_submitter_nickname,
14   torrent_submitter_status,
15   torrent_infolink,
16   torrent_type_id,
17   torrent_tracker_has_nostats,
18   torrent_tracker_has_unknownstats,
19   torrent_lastcheck_nr_invalid,
20   torrent_is_moderated,
21   torrent_is_deleted,
22   torrent_is_softdeleted,
23   torrent_has_rights,
24   torrent_tracker_registered
)
*/

    function tNova( $htmlLine , $dteAdded = "")
    {
        if (strlen($htmlLine) > 0)
        {

            $this->Data = $htmlLine;

            // Chunck up the row into columns.
            $tmpListArr = split(",",str_replace(array("'"),"",$htmlLine));

            if(count($tmpListArr) >= 24)
            {
                $this->torrentAdded = " | Added" . $dteAdded . " " . $tmpListArr[1];
                if(strlen($tmpListArr[13])>0)
                {
                    $this->torrentAdded .= " By ". $tmpListArr[13];
                }

                if( $tmpListArr[2])
                {
                    $this->torrentFile = "http://www.newnova.org/site/torrents/" . trim($tmpListArr[7]);
                }else{
                    $this->torrentFile = "http://www.newnova.org/get/" . trim($tmpListArr[7]);
                }

                $tmpArr = split(" - ",$tmpListArr[5]);
                if(count($tmpArr) == 2)
                {
                    $this->MainCategory = trim($tmpArr[0]);
                    $this->SubCategory = trim($tmpArr[1]);
                }
                else
                {
                    $this->MainCategory = trim($tmpListArr[5]);
                }

                $this->torrentName = $tmpListArr[6];
                $this->torrentSize = $tmpListArr[8];
                $this->Seeds = $tmpListArr[9];
                $this->Peers = $tmpListArr[10];
                $this->torrentStatus = $tmpListArr[11];

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
           }
        }
    }

    //----------------------------------------------------------------
    // Function to build output for the table.
    function BuildOutput($bg, $searchURL = '')
    {
		global $cfg;
        $output = "<tr>\n";
        $output .= "    <td width=16 bgcolor=\"".$bg."\"><a href=\"dispatcher.php?action=urlUpload&type=torrent&url=".$this->torrentFile."\"><img src=\"".getImagesPath()."download_owner.gif\" width=\"16\" height=\"16\" title=\"".$this->torrentName."\" border=0></a></td>\n";
		$output .= "    <td bgcolor=\"".$bg."\"><a href=\"dispatcher.php?action=urlUpload&type=torrent&url=".$this->torrentFile."\" title=\"".$this->torrentName."\">".$this->torrentDisplayName."</a></td>\n";

        if (strlen($this->SubCategory) > 1){
            $genre = $this->MainCategory . " - " . $this->SubCategory;
        }else{
            $genre = $this->MainCategory;
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