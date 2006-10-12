<?php

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
	v 1.05 - May 02. 06 - Added choice to organize display by size,date,seeds,peers or health.
	v 1.04 - Apr 26. 06 - Fixed filtering lists - Possible bug in admin.php (line 1997 - option value [NO FILTER] needs setting to -1 instead of "")
	v 1.02 - Apr 25. 06 - Added Category Headings & tidied search results table
	v 1.01 - Apr 23, 06 - First Release
    
*/

class SearchEngine extends SearchEngineBase
{

    function SearchEngine($cfg)
    {
        $this->mainURL = "www.seedler.org";
        $this->altURL = "www.seedler.org";
        $this->mainTitle = "Seedler";
        $this->engineName = "Seedler";

        $this->author = "moldavite";
        $this->version = "1.05";
        $this->updateURL = "http://www.torrentflux.com/forum/index.php/topic,1259.0.html";

        $this->Initialize($cfg);
    }

    function populateMainCategories()
    {
        $this->mainCatalog["0"] = "(all types)";
        $this->mainCatalog["155"] = "Adult";
        $this->mainCatalog["18"] = "Anime";
        $this->mainCatalog["17"] = "Books & Docs";
        $this->mainCatalog["8"] = "Games";
        $this->mainCatalog["2"] = "Movies";
		$this->mainCatalog["15"] = "Music";
        $this->mainCatalog["160"] = "Other";
        $this->mainCatalog["7"] = "Software";
		$this->mainCatalog["1"] = "TV";
    }

    //----------------------------------------------------------------
    // Function to Get Sub Categories
    function getSubCategories($mainGenre)
    {
        $output = array();

        switch ($mainGenre)
        {
            case "155" :
				$output["155"] = "All";
			    $output["156"] = "Games";
                $output["16"] = "Movies";
                $output["157"] = "Pictures";
                $output["158"] = "Hentai";
                $output["159"] = "Other";
                break;
			case "17" :
				$output["17"] = "All";
				$output["51"] = "Audio Books";
                $output["52"] = "E-Books";
                $output["145"] = "Manuals";
                break;
            case "8" :
				$outptu["8"] = "All";
				$output["13"] = "Computer";
                $output["58"] = "Other Platforms";
                $output["53"] = "Seage Dreamcast";
                $output["14"] = "Console";
                $output["61"] = "PS2";
                $output["64"] = "Sega Saturn";
                $output["54"] = "Fixes / Patches";
                $output["62"] = "PSP";
                $output["65"] = "Video Demos";
                $output["55"] = "GameCube";
                $output["60"] = "PSX";
                $output["67"] = "Windows";
                $output["56"] = "Linux";
                $output["59"] = "Palm / PocketPC";
                $output["68"] = "Windows - Kids";
                $output["57"] = "Mac";
                $output["63"] = "ROMs / Retro";
                $output["69"] = "XBox";
                break;
            case "2" :
				$output["2"] = "All";
				$output["20"] = "Action";
				$output["21"] = "Adventure";
				$output["22"] = "Animation";
				$output["71"] = "Asian";
				$output["70"] = "Automotive/Cars";
				$output["23"] = "Comedy";
				$output["72"] = "Concerts";
				$output["24"] = "Crime";
				$output["73"] = "DVD / Extras";
				$output["25"] = "Documentary";
				$output["26"] = "Drama";
				$output["81"] = "Experimental";
				$output["27"] = "Family";
				$output["28"] = "Fantasy";
				$output["29"] = "Film-Noir";
				$output["74"] = "Gore flicks";
				$output["30"] = "Horror";
				$output["75"] = "Kids";
				$output["77"] = "Martial Arts";
				$output["32"] = "Musicals";
				$output["33"] = "Mystery";
				$output["78"] = "Other";
				$output["34"] = "Romance";
				$output["35"] = "Sci-Fi";
				$output["36"] = "Short";
				$output["80"] = "Sports related";
				$output["82"] = "Student";
				$output["39"] = "Thriller";
				$output["79"] = "Trailes / Samples";
				$output["76"] = "VCD";
				$output["37"] = "War";
				$output["38"] = "Western";
				$output["249"] = "iPod /Portables";
                break;
            case "15" :
				$output["15"] = "All";
				$output["91"] = "60s";
				$output["90"] = "70s";
				$output["89"] = "80s";
				$output["83"] = "Alternative";
				$output["102"] = "Amateur";
				$output["84"] = "Asian";
				$output["40"] = "Blues";
				$output["86"] = "Christian";
				$output["41"] = "Classical";
				$output["103"] = "Club / Trance";
				$output["42"] = "Country";
				$output["87"] = "Drum & Bass";
				$output["88"] = "Electronic";
				$output["43"] = "Folk";
				$output["101"] = "Game music";
				$output["100"] = "Hardcore";
				$output["97"] = "Hip Hop";
				$output["95"] = "Industrial";
				$output["44"] = "Jazz";
				$output["96"] = "Karaoke";
				$output["94"] = "Latin";
				$output["147"] = "Metal";
				$output["45"] = "Misc";
				$output["46"] = "Newage";
				$output["146"] = "Other";
				$output["148"] = "Pop";
				$output["149"] = "Punk";
				$output["98"] = "R&B / Soul";
				$output["150"] = "Rap";
				$output["47"] = "Reggae";
				$output["48"] = "Rock";
				$output["49"] = "Soundtracks";
				$output["92"] = "Spiritual";
				$output["99"] = "Video clips";
				$output["93"] = "World";
                break;
            case "160" :
				$output["160"] = "All";
				$output["161"] = "Comics";
                $output["19"] = "Misc";
				$output["162"] = "Pictures";
                break;
			case "7" :
				$output["7"] = "All";
				$output["105"] = "Cellphone Stuff";
                $output["11"] = "Linux";
				$output["10"] = "Mac";
				$output["12"] = "Other";
				$output["104"] = "Palm / PocketPC";
				$output["9"] = "Windows";
                break;
			case "1" :
				$output["1"] = "All";
				$output["106"] = "24";
				$output["107"] = "3rd Rock From the sun";
				$output["184"] = "7th Heaven";
				$output["180"] = "8 Simple Rules";
				$output["108"] = "Alias";
				$output["183"] = "Allo Allo";
				$output["166"] = "American Dad";
				$output["174"] = "American Idol";
				$output["202"] = "Andromeda";
				$output["238"] = "Arrested Development";
				$output["193"] = "Beauty and the Geek";
				$output["187"] = "Blakes 7";
				$output["199"] = "Blind Justice";
				$output["201"] = "Brimstone";
				$output["218"] = "Britney and Kevin Chaotic";
				$output["109"] = "Buffy";
				$output["112"] = "CSI";
				$output["181"] = "CSI Miami";
				$output["110"] = "Carnivale";
				$output["3"] = "Cartoons";
				$output["194"] = "Charlie Jade";
				$output["111"] = "Charmed";
				$output["168"] = "Deadwood";
				$output["164"] = "Desperate Housewives";
				$output["113"] = "Discovery channel";
				$output["198"] = "Doctor Who";
				$output["165"] = "Doctor Who 2005";
				$output["216"] = "Due South";
				$output["247"] = "ER";
				$output["191"] = "Entourage";
				$output["248"] = "Everybody Hates Chris";
				$output["241"] = "Extras";
				$output["163"] = "Family Guy";
				$output["114"] = "Friends";
				$output["219"] = "Futurama";
				$output["230"] = "Gilmore Girls";
				$output["207"] = "Greys Anatomy";
				$output["233"] = "Hogans Heroes";
				$output["178"] = "House";
				$output["115"] = "Huff";
				$output["116"] = "JAG";
				$output["240"] = "Jeremiah";
				$output["206"] = "Joan of Arcadia";
				$output["117"] = "Joey";
				$output["204"] = "Justice League Unlimited";
				$output["236"] = "Kevin Spencer";
				$output["245"] = "Kolchak the Night Stalker";
				$output["118"] = "Las Vegas";
				$output["119"] = "Law and order";
				$output["5"] = "Live";
				$output["120"] = "Lost";
				$output["121"] = "MacGyver";
				$output["176"] = "Malcolm in the Middle";
				$output["205"] = "Medium";
				$output["242"] = "Mind of Mencia";
				$output["228"] = "Monk";
				$output["235"] = "My Name Is Earl";
				$output["209"] = "My Restaurant Rules";
				$output["122"] = "Navy NCIS";
				$output["179"] = "Numb3rs";
				$output["182"] = "One Tree Hill";
				$output["123"] = "Other";
				$output["229"] = "Outer Limits";
				$output["232"] = "Over There";
				$output["186"] = "Penn and Teller Bullshit";
				$output["195"] = "Pimp My Ride";
				$output["223"] = "Prison Break";
				$output["200"] = "Red Dwarf";
				$output["226"] = "Rescue Me";
				$output["203"] = "Revelations";
				$output["197"] = "Robot Chicken";
				$output["222"] = "Rome";
				$output["221"] = "Sci-Fi";
				$output["124"] = "Scrubs";
				$output["210"] = "Seinfeld";
				$output["217"] = "Six Feet Under";
				$output["234"] = "Sliders";
				$output["127"] = "Smallville";
				$output["175"] = "South Park";
				$output["173"] = "Space 1999";
				$output["128"] = "Sports illustrated";
				$output["129"] = "Star-Trek Enterprise";
				$output["130"] = "Star-Trek TNG";
				$output["132"] = "StarGate SG1";
				$output["131"] = "Stargate Atlantis";
				$output["244"] = "Supernatural";
				$output["214"] = "Teen Titans";
				$output["167"] = "The 4400";
				$output["133"] = "The 70s show";
				$output["189"] = "The Apprentice";
				$output["237"] = "The Closer";
				$output["196"] = "The Comeback";
				$output["134"] = "The Daily Show";
				$output["211"] = "The Dead Zone";
				$output["188"] = "The Inside";
				$output["239"] = "The King of Queens";
				$output["172"] = "The L Word";
				$output["136"] = "The O.C.";
				$output["212"] = "The Osbournes";
				$output["170"] = "The Shield";
				$output["215"] = "The Simple Life";
				$output["220"] = "The Sopranos";
				$output["190"] = "The Ultimate Fighter";
				$output["138"] = "The West Wing";
				$output["135"] = "The lost world";
				$output["137"] = "The simpsons";
				$output["231"] = "Threshold";
				$output["192"] = "Top Gear";
				$output["169"] = "Trailer Park Boys";
				$output["225"] = "Tripping The Rift";
				$output["208"] = "Tru Calling";
				$output["213"] = "Twin Peaks";
				$output["246"] = "Two and a Half Men";
				$output["139"] = "UK";
				$output["142"] = "Unscripted";
				$output["143"] = "Veronica mars";
				$output["227"] = "Viva La Bam";
				$output["144"] = "WWE";
				$output["224"] = "Weeds";
				$output["243"] = "Will and Grace";
				$output["171"] = "Wonder Woman";
				$output["185"] = "World Poker Tour";
				$output["250"] = "iPod / Portables";
				$output["177"] = "summerland";
                break;
        }

        return $output;

    }
	
    //----------------------------------------------------------------
    // Function to Make the Request (overriding base)
    function makeRequest($request)
    {
        return parent::makeRequest($request, false);
    }

    //----------------------------------------------------------------
    // Function to get Latest..
    function getLatest()
    {
        
		
		if (array_key_exists("mainGenre",$_REQUEST))
        {
            $request = "/en/html/list/".$_REQUEST["mainGenre"];
		
        }
		elseif (array_key_exists("subGenre",$_REQUEST))
        {
			$request = "/en/html/list/".$_REQUEST["subGenre"];
		
        }
        else
        {
            $request = "/en/";
		
        }
		
		if (array_key_exists("sort",$_REQUEST))
		{
			$request .= "?tl_sortby=" .$_REQUEST["sort"];
		}
		
		if (!empty($this->pg))
        {
            if(strpos($request,"?"))
            {
				$request .= "&tl_offset=" . $this->pg;
				
				
            }
            else
            {
				
                $request .= "&tl_offset=" . $this->pg;
				
            }
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
        $request = "/en/html/search/".$searchTerm;

		if(strlen($searchTerm) > 0)
        {
            
			$searchTerm = str_replace(" ", "+", $searchTerm);
			
				
        }
		
		if (array_key_exists("sort",$_REQUEST))
		{
			$request .= "?tl_sortby=" .$_REQUEST["sort"];
		}
		
        if (!empty($this->pg))
        {
            $request .= "&tl_offset=" . $this->pg;
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
    // Override the base to show custom table header.
    // Function to setup the table header
    function tableHeader()
    {
        $output = "<table width=\"100%\" cellpadding=3 cellspacing=0 border=0>";
		$tmpStr = $this->htmlPage;
		
        $output .= "<br>\n";
		
		$output .= "<tr bgcolor=\"".$this->cfg["bgLight"]."\">";
        $output .= "  <td colspan=7 align=center>";
        
        $tmpStr = substr($tmpStr,strpos($tmpStr,"name=rating value=\"\">"));
		$tmpStr = substr($tmpStr,strpos($tmpStr,"align=\"left\">"));
        $tmpStr = substr($tmpStr,strpos($tmpStr,"<b>"));
		$tmpStr = substr($tmpStr,0,strpos($tmpStr,"</b>"));
		
		if (!empty($tmpStr))
		{
		$output .= "<font size=5px><b> Category : ".strip_tags($tmpStr)."</b></font>";
		}
		else
		{
		$output .= "<font size=5px><b> Category: Top Ten Lists</b></font>";
		}
        $output .= "</td>";

        $output .= "<tr bgcolor=\"".$this->cfg["table_header_bg"]."\">";
        $output .= "  <td>&nbsp;</td>";
        $output .= "  <td><strong>Torrent Name</strong> &nbsp;(";

        $tmpURI = str_replace(array("?hideSeedless=yes","&hideSeedless=yes","?hideSeedless=no","&hideSeedless=no"),"",$_SERVER["REQUEST_URI"]);

        //Check to see if Question mark is there.
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
		
        if ((array_key_exists("mainGenre",$_REQUEST)) || (array_key_exists("subGenre",$_REQUEST)) || (array_key_exists("searchterm",$_REQUEST)))
        {
            $output .= "  <td align=center><strong><a href=\"";
			if (array_key_exists("mainGenre",$_REQUEST))
			{
				$mainSort .= $this->SearchURL()."&mainGenre=" .$_REQUEST["mainGenre"];
			}
			elseif (array_key_exists("subGenre",$_REQUEST))
			{
				$mainSort .= $this->SearchURL()."&subGenre=" .$_REQUEST["subGenre"];
			}
			elseif  (array_key_exists("searchterm",$_REQUEST))
			{
				$mainSort .= $this->SearchURL()."&searchterm=".str_replace(" ", "+", $_REQUEST["searchterm"]);
			}
			$output .= $mainSort."&sort=SizeD\">Size</a></strong></td>";
			$output .= "  <td align=center><strong><a href=\"".$mainSort."&sort=AddedD\">Date Added</a></strong></td>";
			$output .= "  <td><strong><a href=\"".$mainSort."&sort=SeedersD\">Seeds</a></strong></td>";
			$output .= "  <td><strong><a href=\"".$mainSort."&sort=LeechersD\">Peers</a></strong></td>";
			$output .= "  <td><strong><a href=\"".$mainSort."&sort=HealthD\">Health</a></strong></td>";
		}
		else
		{
			$output .= "  <td align=center><strong>&nbsp;&nbsp;Size</strong></td>";
			$output .= "  <td align=center><strong>Date Added</strong></td>";
			$output .= "  <td><strong>Seeds</strong></td>";
			$output .= "  <td><strong>Peers</strong></td>";
			$output .= "  <td><strong>Health</strong></td>";
		}
        
		$output .= "</tr>\n";

        return $output;
    }

    //----------------------------------------------------------------
    // Function to parse the response.
    function parseResponse()
    {
	 
        $output = $this->tableHeader();
		
		$thing = $this->htmlPage;
		
		
        // We got a response so display it.
        // Chop the front end off.
        	
        while (is_integer(strpos($thing,"class=\"torrent_table\">")))
	
        {
            
			$thing = substr($thing,strpos($thing,"class=\"torrent_table\">")); 
			
			$thing = substr($thing,strpos($thing,"<td"));
           
			$tmplist = substr($thing,0,strpos($thing,"<td class=\"adbrite\""));
			
            // ok so now we have the listing.
            $tmpListArr = explode("<td colspan=\"3\">",$tmplist); //original
			foreach($tmpListArr as $key =>$value)
            {
			
						$tmpListArr2 = explode("</tr>",$value);
						$bg = $this->cfg["bgLight"];
						foreach($tmpListArr2 as $key1 =>$value1){
						
						$buildLine = true;
						
				if (strpos($value,"/en"))
			
                {
					
                    $ts = new sddr($value1);

                    // Determine if we should build this output
                     if (is_int(array_search($ts->CatId,$this->catFilter)))
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
            }
        }
		}

        $output .= "</table>";

        // is there paging at the bottom?
        if (strpos($thing, "&tl_offset=") != false)
		
        {
			// Yes, then lets grab it and display it!  ;)
            $thing = substr($thing,strpos($thing,"class=\"pager")+strlen("class=\"pager"));
            $thing = substr($thing,strpos($thing,">")+1);
            $pages = substr($thing,0,strpos($thing,"<style"));
			$pages = substr($thing,0,strpos($thing,"</td>"));
						
				if(strpos($this->curRequest,"LATEST"))
				{
                $pages = str_replace("/en/html/list/",$this->searchURL()."&LATEST=1&subGenre=",$pages);
				$pages = str_replace("/en/htm/search/",$this->searchURL()."&LATEST=1&subGenre=",$pages);
				}
				else
				{
					$pages = str_replace("/en/html/list/",$this->searchURL()."&subGenre=",$pages);
					$pages = str_replace("/en/html/search/",$this->searchURL()."&searchterm=",$pages);
				}	
           
            $pages = str_replace("?tl_sortby=","&sort=",$pages);
            $pages = str_replace("tl_offset=","pg=",$pages);
			$pages = str_replace("&&","&",$pages);
			$pages = str_replace("class=\"pager_link\"","",$pages);

            $output .= "<div align=center>".$pages."</div>";
        }
		
		

        return $output;
    }
}


// This is a worker class that takes in a row in a table and parses it.
class sddr
{
    var $torrentName = "";
    var $torrentDisplayName = "";
    var $torrentFile = "";
    var $torrentSize = "";
    var $torrentStatus = "";
    var $CatName = "";
    var $CatId = "";
    var $MainId = "";
    var $MainCategory = "";
    var $SubId = "";
    var $SubCategory = "";
    var $Seeds = "";
    var $Peers = "";
    var $Data = "";

    var $dateAdded = "";
    var $dwnldCount = "";

	
 function sddr( $htmlLine )
    {
        if (strlen($htmlLine) > 0)
        {
            $this->Data = $htmlLine;
			
            // Chunck up the row into columns.
			
			
            $tmpListArr = explode("</td>",$htmlLine);
			foreach($tmpListArr as $key =>$value){
				$value = str_replace("href=\"/en/html/info/","href=\"http://www.seedler.org/download.x?id=",$value);
				$value = str_replace("href=\"/en/html/list/","href=\"http://www.seedler.org/en/html/list/",$value);
				}
			
            
			if(count($tmpListArr) == 7) //get Category and Torrent Name
            {
               
                $tmpStr = "";
                $tmpStr = substr($tmpListArr["0"],strpos($tmpListArr["0"],"title=\"")+strlen("title=\"")); 
				$tmpStr = substr($tmpStr,strpos($tmpStr,"\">")+strlen("\">")); 
				$tmpStr = substr($tmpStr,0,strpos($tmpStr,"/"));
				
				$this->torrentName = $this->cleanLine($tmpStr);  // TorrentName
				
				
               
				$this->dateAdded = $this->cleanLine($tmpListArr["2"]); // Date Added
				
                
				$tmpStr = "";
				$tmpStr = str_replace("href=\"/en/html/info/","href=\"http://www.seedler.org/download.x?id=",$tmpListArr["0"]); // Download Link
                $tmpStr = substr($tmpStr,strpos($tmpStr,"href=\"")+strlen("href=\"")); 
                $this->torrentFile = substr($tmpStr,0,strpos($tmpStr,"\""));
			
                
				$this->torrentSize = $this->cleanLine($tmpListArr["1"]);  // Size of File
				
				
				$this->Seeds = $this->cleanLine($tmpListArr["3"]);  // Seeds
			
                
				$this->Peers = $this->cleanLine($tmpListArr["4"]);  // Peers
				
				$tmpStr = "";
                $tmpStr = substr($tmpListArr["5"],strpos($tmpListArr["5"],"title=\"")+strlen("title=\"")); //Seed Health
				$tmpStr = substr($tmpStr,0,strpos($tmpStr,"\""));
				$this->Health = $this->cleanLine($tmpStr);

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
		   if(count($tmpListArr) == 8) //get Category and Torrent Name for main page and search
            {
                
                $tmpStr = "";
                $tmpStr = substr($tmpListArr["1"],strpos($tmpListArr["1"],"title=\"")+strlen("title=\"")); 
				$tmpStr = substr($tmpStr,strpos($tmpStr,"\">")+strlen("\">")); 
				$tmpStr = substr($tmpStr,0,strpos($tmpStr,"/"));
				
				$this->torrentName = $this->cleanLine($tmpStr);  // TorrentName
				
				$tmpStr ="";
				$tmpStr = $tmpListArr["0"];
				$tmpStr = substr($tmpStr,strpos($tmpStr,"href=\"")+strlen("href=\"")); 
				$tmpStr = str_replace("/en/html/cat/","",$tmpStr);
                $this->CatId = substr($tmpStr,0,strpos($tmpStr,"\""));
               
				$this->dateAdded = $this->cleanLine($tmpListArr["3"]); // Date Added
				
                
				$tmpStr = "";
				$tmpListArr["1"] = str_replace("href=\"/en/html/info/","href=\"http://www.seedler.org/download.x?id=",$tmpListArr["1"]); // Download Link
                $tmpStr = substr($tmpListArr["1"],strpos($tmpListArr["1"],"href=\"")+strlen("href=\"")); 
                $this->torrentFile = substr($tmpStr,0,strpos($tmpStr,"\""));
				
                
				$this->torrentSize = $this->cleanLine($tmpListArr["2"]);  // Size of File
				
				
				$this->Seeds = $this->cleanLine($tmpListArr["4"]);  // Seeds
				
                
				$this->Peers = $this->cleanLine($tmpListArr["5"]);  // Peers
				
				$tmpStr = "";
                $tmpStr = substr($tmpListArr["6"],strpos($tmpListArr["6"],"title=\"")+strlen("title=\"")); 
				$tmpStr = substr($tmpStr,0,strpos($tmpStr,"\""));
				$this->Health = $this->cleanLine($tmpStr);
				

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

    function cleanLine($stringIn,$tags='')
    {
        if(empty($tags))
            return trim(str_replace(array("&nbsp;","&nbsp")," ",strip_tags($stringIn)));
        else
            return trim(str_replace(array("&nbsp;","&nbsp")," ",strip_tags($stringIn,$tags)));
    }

    //----------------------------------------------------------------
    // Function to build output for the table.
    function BuildOutput($bg, $searchURL)
    {
        $output = "<tr>\n";
        $output .= "    <td width=16 bgcolor=\"".$bg."\"><a href=\"index.php?url_upload=".$this->torrentFile."\"><img src=\"images/download_owner.gif\" width=\"16\" height=\"16\" title=\"".$this->torrentName."\" border=0></a></td>\n";
        $output .= "    <td bgcolor=\"".$bg."\"><a href=\"index.php?url_upload=".$this->torrentFile."\" title=\"".$this->torrentName."\">".$this->torrentDisplayName."</a></td>\n";

        $output .= "    <td bgcolor=\"".$bg."\" align=right>".$this->torrentSize."</td>\n";
		$output .= "    <td bgcolor=\"".$bg."\" align=right>".$this->dateAdded."</td>\n";   
        $output .= "    <td bgcolor=\"".$bg."\" align=center>".$this->Seeds."</td>\n";
        $output .= "    <td bgcolor=\"".$bg."\" align=center>".$this->Peers."</td>\n";
		$output .= "    <td bgcolor=\"".$bg."\" align=center>".$this->Health."</td>\n";
        $output .= "</tr>\n";
		

        return $output;

    }
}

?>
